<?php

use App\Models\Server;
use App\Models\User;
use App\Services\ConnectionResult;
use App\Services\ExecutionEngine\PushAgentEngine;
use App\Services\SshResult;
use App\Services\SshService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;

uses(RefreshDatabase::class);

test('user can rotate server agent token', function () {
    $user = User::factory()->create();
    $server = Server::factory()->create([
        'user_id' => $user->id,
        'agent_enabled' => false,
        'execution_driver' => 'ssh',
    ]);

    $response = $this->actingAs($user)
        ->postJson(route('server.agent.token', $server))
        ->assertSuccessful()
        ->assertJson([
            'success' => true,
            'server_id' => $server->id,
        ]);

    $token = $response->json('token');
    $installCommand = $response->json('install_command');
    $server->refresh();
    $rawToken = DB::table('servers')->whereKey($server->id)->value('agent_token');

    expect($token)->toStartWith('smz_')
        ->and($server->agent_enabled)->toBeTrue()
        ->and($server->agent_token)->toBe($token)
        ->and($installCommand)->toContain('smuze-agent install')
        ->and($installCommand)->toContain('--server-id')
        ->and($installCommand)->toContain((string) $server->id)
        ->and($installCommand)->toContain($token)
        ->and($rawToken)->not->toBe($token)
        ->and($server->agent_status)->toBe('disconnected')
        ->and($server->execution_driver)->toBe('auto');
});

test('user can disable server agent', function () {
    $user = User::factory()->create();
    $server = Server::factory()->withAgent()->create([
        'user_id' => $user->id,
        'execution_driver' => 'agent',
    ]);

    $this->actingAs($user)
        ->deleteJson(route('server.agent.disable', $server))
        ->assertSuccessful()
        ->assertJson(['success' => true]);

    $server->refresh();

    expect($server->agent_enabled)->toBeFalse()
        ->and($server->agent_token)->toBeNull()
        ->and($server->agent_status)->toBe('disconnected')
        ->and($server->execution_driver)->toBe('ssh');
});

test('user cannot manage another users server agent', function () {
    $user = User::factory()->create();
    $server = Server::factory()->create();

    $this->actingAs($user)
        ->postJson(route('server.agent.token', $server))
        ->assertForbidden();
});

test('user can install agent via ssh bootstrap', function () {
    $user = User::factory()->create();
    $server = Server::factory()->create([
        'user_id' => $user->id,
        'agent_enabled' => false,
        'execution_driver' => 'ssh',
    ]);

    $ssh = Mockery::mock(SshService::class);
    $ssh->shouldReceive('execute')
        ->once()
        ->andReturn(new SshResult(stdout: 'OK', stderr: '', exitCode: 0, success: true));
    $this->app->instance(SshService::class, $ssh);

    $agent = Mockery::mock(PushAgentEngine::class);
    $agent->shouldReceive('test')
        ->once()
        ->andReturn(new ConnectionResult(success: true, latencyMs: 1.0));
    $this->app->instance(PushAgentEngine::class, $agent);

    $this->actingAs($user)
        ->postJson(route('server.agent.install', $server))
        ->assertSuccessful()
        ->assertJson([
            'success' => true,
            'message' => 'Agent installiert und verbunden.',
        ]);

    $server->refresh();

    expect($server->agent_enabled)->toBeTrue()
        ->and($server->agent_token)->toStartWith('smz_')
        ->and($server->agent_status)->toBe('connected')
        ->and($server->execution_driver)->toBe('auto');
});

test('agent download endpoint serves binary', function () {
    $this->get('/agent/download')
        ->assertSuccessful()
        ->assertHeader('Content-Type', 'application/octet-stream');
});

test('user cannot install agent on another users server', function () {
    $user = User::factory()->create();
    $server = Server::factory()->create(['agent_enabled' => false]);

    $this->actingAs($user)
        ->postJson(route('server.agent.install', $server))
        ->assertForbidden();
});

test('agent check-update returns no update when versions match', function () {
    $user = User::factory()->create();
    $server = Server::factory()->withAgent()->create([
        'user_id' => $user->id,
        'agent_version' => '0.1.0',
    ]);

    config()->set('agent.latest_version', '0.1.0');

    $this->actingAs($user)
        ->getJson(route('server.agent.check-update', $server))
        ->assertSuccessful()
        ->assertJson([
            'has_update' => false,
            'current_version' => '0.1.0',
            'latest_version' => '0.1.0',
        ]);
});

test('agent check-update returns update when newer version available', function () {
    $saved = saveVersionFile();
    writeVersionFile('0.2.0', 'testchecksum123');
    config()->set('agent.latest_version', '0.2.0');

    $user = User::factory()->create();
    $server = Server::factory()->withAgent()->create([
        'user_id' => $user->id,
        'agent_version' => '0.1.0',
    ]);

    $this->actingAs($user)
        ->getJson(route('server.agent.check-update', $server))
        ->assertSuccessful()
        ->assertJson([
            'has_update' => true,
            'current_version' => '0.1.0',
            'latest_version' => '0.2.0',
            'checksum' => 'testchecksum123',
        ]);

    restoreVersionFile($saved);
});

test('agent update endpoint triggers update via push', function () {
    $user = User::factory()->create();
    $server = Server::factory()->withAgent()->create([
        'user_id' => $user->id,
    ]);

    $agent = Mockery::mock(PushAgentEngine::class);
    $agent->shouldReceive('triggerUpdate')
        ->once()
        ->with(Mockery::on(fn ($s) => $s->is($server)))
        ->andReturn(['success' => true, 'message' => 'Agent-Update gestartet.']);
    $this->app->instance(PushAgentEngine::class, $agent);

    $this->actingAs($user)
        ->postJson(route('server.agent.update', $server))
        ->assertSuccessful()
        ->assertJson([
            'success' => true,
            'message' => 'Agent-Update gestartet.',
        ]);
});

test('agent update fails when agent is not enabled', function () {
    $user = User::factory()->create();
    $server = Server::factory()->create([
        'user_id' => $user->id,
        'agent_enabled' => false,
    ]);

    $this->actingAs($user)
        ->postJson(route('server.agent.update', $server))
        ->assertSuccessful()
        ->assertJson([
            'success' => false,
            'message' => 'Agent ist nicht aktiviert.',
        ]);
});
