<?php

use App\Models\Server;
use App\Models\ServerCommand;
use App\Services\ExecutionEngine\AgentEngine;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('agent engine queues command and returns timeout when agent does not complete it', function () {
    $server = Server::factory()->withAgent()->create();

    $result = (new AgentEngine)->execute($server, 'apt update', timeout: 0, useSudo: true);
    $command = ServerCommand::query()->sole();

    expect($result->success)->toBeFalse()
        ->and($result->exitCode)->toBe(-1)
        ->and($result->stderr)->toContain('Agent command timed out')
        ->and($command->server_id)->toBe($server->id)
        ->and($command->user_id)->toBe($server->user_id)
        ->and($command->command)->toBe('apt update')
        ->and($command->use_sudo)->toBeTrue()
        ->and($command->status)->toBe(ServerCommand::StatusTimeout);
});

test('agent engine connection test succeeds for fresh connected agent', function () {
    $server = Server::factory()->withAgent()->create();

    $result = (new AgentEngine)->test($server);

    expect($result->success)->toBeTrue()
        ->and($result->errorMessage)->toBeNull();
});

test('agent engine connection test fails for stale agent', function () {
    $server = Server::factory()->withAgent()->create([
        'agent_last_seen_at' => now()->subMinutes(2),
    ]);

    $result = (new AgentEngine)->test($server);

    expect($result->success)->toBeFalse()
        ->and($result->errorMessage)->toBe('Agent ist nicht verbunden.');
});
