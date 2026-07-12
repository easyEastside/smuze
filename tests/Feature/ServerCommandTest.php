<?php

use App\Models\Server;
use App\Models\ServerCommand;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;

uses(RefreshDatabase::class);

test('server stores agent metadata with casts', function () {
    $server = Server::factory()->withAgent()->create([
        'agent_token' => 'plain-agent-token',
    ]);

    $rawToken = DB::table('servers')->whereKey($server->id)->value('agent_token');

    expect($server->agent_enabled)->toBeTrue()
        ->and($server->agent_token)->toBe('plain-agent-token')
        ->and($rawToken)->not->toBe('plain-agent-token')
        ->and($server->agent_last_seen_at)->not->toBeNull()
        ->and($server->agent_status)->toBe('connected')
        ->and($server->execution_driver)->toBe('agent');
});

test('server commands belong to server and user', function () {
    $user = User::factory()->create();
    $server = Server::factory()->create(['user_id' => $user->id]);

    $command = ServerCommand::factory()->create([
        'server_id' => $server->id,
        'user_id' => $user->id,
        'command' => 'apt update',
    ]);

    expect($command->server->is($server))->toBeTrue()
        ->and($command->user->is($user))->toBeTrue()
        ->and($server->commands()->first()->is($command))->toBeTrue();
});

test('server command factory states set lifecycle fields', function () {
    $running = ServerCommand::factory()->running()->create();
    $completed = ServerCommand::factory()->completed()->create();
    $failed = ServerCommand::factory()->failed()->create();

    expect($running->status)->toBe(ServerCommand::StatusRunning)
        ->and($running->started_at)->not->toBeNull()
        ->and($completed->status)->toBe(ServerCommand::StatusCompleted)
        ->and($completed->exit_code)->toBe(0)
        ->and($completed->completed_at)->not->toBeNull()
        ->and($failed->status)->toBe(ServerCommand::StatusFailed)
        ->and($failed->exit_code)->toBe(1)
        ->and($failed->failed_at)->not->toBeNull();
});
