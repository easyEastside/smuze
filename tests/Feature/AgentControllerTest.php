<?php

use App\Models\Server;
use App\Models\ServerCommand;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

function agentHeaders(Server $server, string $token = 'agent-secret'): array
{
    return [
        'Authorization' => "Bearer {$token}",
        'X-Smuze-Server-Id' => (string) $server->id,
    ];
}

test('agent endpoints reject missing token', function () {
    $this->postJson('/api/agent/heartbeat')->assertForbidden();
});

test('agent heartbeat updates server status', function () {
    $server = Server::factory()->create([
        'agent_enabled' => true,
        'agent_token' => 'agent-secret',
        'agent_status' => 'disconnected',
    ]);

    $this->postJson('/api/agent/heartbeat', ['version' => '0.1.0'], agentHeaders($server))
        ->assertSuccessful()
        ->assertJson(['success' => true]);

    $server->refresh();

    expect($server->agent_status)->toBe('connected')
        ->and($server->agent_version)->toBe('0.1.0')
        ->and($server->agent_last_seen_at)->not->toBeNull();
});

test('agent heartbeat returns update info when newer version available', function () {
    $saved = saveVersionFile();
    writeVersionFile('9.9.9', 'abcdef1234567890');
    config()->set('agent.latest_version', '9.9.9');

    $server = Server::factory()->create([
        'agent_enabled' => true,
        'agent_token' => 'agent-secret',
        'agent_version' => '0.1.0',
    ]);

    $this->postJson('/api/agent/heartbeat', ['version' => '0.1.0'], agentHeaders($server))
        ->assertSuccessful()
        ->assertJson([
            'success' => true,
            'update' => [
                'latest_version' => '9.9.9',
                'checksum' => 'abcdef1234567890',
            ],
        ]);

    restoreVersionFile($saved);
});

test('agent heartbeat does not include update when agent is current', function () {
    config()->set('agent.latest_version', '0.1.0');

    $server = Server::factory()->create([
        'agent_enabled' => true,
        'agent_token' => 'agent-secret',
        'agent_version' => '0.1.0',
    ]);

    $response = $this->postJson('/api/agent/heartbeat', ['version' => '0.1.0'], agentHeaders($server))
        ->assertSuccessful()
        ->assertJson(['success' => true]);

    expect($response->json())->not->toHaveKey('update');
});

test('agent update-check endpoint returns version info', function () {
    $saved = saveVersionFile();
    writeVersionFile('0.2.0', 'sha256checksum');
    config()->set('agent.latest_version', '0.2.0');

    $server = Server::factory()->create([
        'agent_enabled' => true,
        'agent_token' => 'agent-secret',
        'agent_version' => '0.1.0',
    ]);

    $this->getJson('/api/agent/update-check', agentHeaders($server))
        ->assertSuccessful()
        ->assertJson([
            'success' => true,
            'update' => [
                'latest_version' => '0.2.0',
                'has_update' => true,
                'checksum' => 'sha256checksum',
            ],
        ]);

    restoreVersionFile($saved);
});

test('agent update-check shows no update when versions match', function () {
    config()->set('agent.latest_version', '0.1.0');

    $server = Server::factory()->create([
        'agent_enabled' => true,
        'agent_token' => 'agent-secret',
        'agent_version' => '0.1.0',
    ]);

    $this->getJson('/api/agent/update-check', agentHeaders($server))
        ->assertSuccessful()
        ->assertJsonPath('success', true)
        ->assertJsonPath('update.has_update', false)
        ->assertJsonPath('update.latest_version', '0.1.0');
});

test('agent metrics endpoint stores latest metrics', function () {
    $server = Server::factory()->create([
        'agent_enabled' => true,
        'agent_token' => 'agent-secret',
    ]);

    $this->postJson('/api/agent/metrics', [
        'metrics' => [
            'hostname' => 'web-01',
            'cpu_percent' => 24,
            'ram_total_mb' => 4096,
            'ram_used_mb' => 1024,
            'ram_percent' => 25,
            'disk_percent' => 40,
        ],
    ], agentHeaders($server))
        ->assertSuccessful()
        ->assertJson(['success' => true]);

    $server->refresh();

    expect($server->agent_status)->toBe('connected')
        ->and($server->agent_metrics)->toMatchArray([
            'hostname' => 'web-01',
            'cpu_percent' => 24,
            'ram_percent' => 25,
            'disk_percent' => 40,
        ])
        ->and($server->agent_metrics_collected_at)->not->toBeNull();
});

test('agent only receives queued commands for its server', function () {
    $server = Server::factory()->create([
        'agent_enabled' => true,
        'agent_token' => 'agent-secret',
    ]);
    $otherServer = Server::factory()->create([
        'agent_enabled' => true,
        'agent_token' => 'other-secret',
    ]);

    $ownCommand = ServerCommand::factory()->create([
        'server_id' => $server->id,
        'user_id' => $server->user_id,
        'command' => 'apt update',
    ]);
    ServerCommand::factory()->create([
        'server_id' => $otherServer->id,
        'user_id' => $otherServer->user_id,
        'command' => 'reboot',
    ]);

    $this->getJson('/api/agent/commands/pending', agentHeaders($server))
        ->assertSuccessful()
        ->assertJsonCount(1, 'commands')
        ->assertJsonPath('commands.0.uuid', $ownCommand->uuid)
        ->assertJsonPath('commands.0.command', 'apt update');

    expect($ownCommand->refresh()->status)->toBe(ServerCommand::StatusRunning)
        ->and($ownCommand->started_at)->not->toBeNull();
});

test('agent command output is appended to the selected stream', function () {
    $server = Server::factory()->create([
        'agent_enabled' => true,
        'agent_token' => 'agent-secret',
    ]);
    $command = ServerCommand::factory()->running()->create([
        'server_id' => $server->id,
        'user_id' => $server->user_id,
        'stdout' => 'first ',
    ]);

    $this->postJson("/api/agent/commands/{$command->id}/output", [
        'stream' => 'stdout',
        'data' => 'second',
    ], agentHeaders($server))->assertSuccessful();

    expect($command->refresh()->stdout)->toBe('first second');
});

test('agent completes a command', function () {
    $server = Server::factory()->create([
        'agent_enabled' => true,
        'agent_token' => 'agent-secret',
    ]);
    $command = ServerCommand::factory()->running()->create([
        'server_id' => $server->id,
        'user_id' => $server->user_id,
    ]);

    $this->postJson("/api/agent/commands/{$command->id}/complete", [
        'status' => ServerCommand::StatusCompleted,
        'exit_code' => 0,
        'stdout' => 'done',
    ], agentHeaders($server))->assertSuccessful();

    $command->refresh();

    expect($command->status)->toBe(ServerCommand::StatusCompleted)
        ->and($command->exit_code)->toBe(0)
        ->and($command->stdout)->toBe('done')
        ->and($command->completed_at)->not->toBeNull()
        ->and($command->failed_at)->toBeNull();
});

test('agent cannot update another server command', function () {
    $server = Server::factory()->create([
        'agent_enabled' => true,
        'agent_token' => 'agent-secret',
    ]);
    $otherCommand = ServerCommand::factory()->create();

    $this->postJson("/api/agent/commands/{$otherCommand->id}/complete", [
        'status' => ServerCommand::StatusCompleted,
        'exit_code' => 0,
    ], agentHeaders($server))->assertNotFound();
});
