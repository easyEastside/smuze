<?php

use App\Models\Server;
use App\Models\User;
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
