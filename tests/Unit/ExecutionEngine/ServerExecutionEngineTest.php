<?php

use App\Models\Server;
use App\Services\ConnectionResult;
use App\Services\ExecutionEngine\ExecutionResult;
use App\Services\ExecutionEngine\PushAgentEngine;
use App\Services\ExecutionEngine\ServerExecutionEngine;
use App\Services\ExecutionEngine\SshEngine;
use Tests\TestCase;

uses(TestCase::class);

test('server execution engine uses ssh for ssh driver', function () {
    $server = new Server(['execution_driver' => 'ssh']);
    $expected = new ExecutionResult(stdout: 'ssh', stderr: '', exitCode: 0, success: true);
    $ssh = Mockery::mock(SshEngine::class);
    $agent = Mockery::mock(PushAgentEngine::class);

    $ssh->shouldReceive('execute')->once()->andReturn($expected);
    $agent->shouldReceive('execute')->never();

    expect((new ServerExecutionEngine($ssh, $agent))->execute($server, 'echo OK'))->toBe($expected);
});

test('server execution engine uses agent for agent driver', function () {
    $server = new Server(['execution_driver' => 'agent']);
    $expected = new ExecutionResult(stdout: 'agent', stderr: '', exitCode: 0, success: true);
    $ssh = Mockery::mock(SshEngine::class);
    $agent = Mockery::mock(PushAgentEngine::class);

    $ssh->shouldReceive('execute')->never();
    $agent->shouldReceive('execute')->once()->andReturn($expected);

    expect((new ServerExecutionEngine($ssh, $agent))->execute($server, 'echo OK'))->toBe($expected);
});

test('server execution engine uses agent for connected auto driver', function () {
    $server = new Server([
        'execution_driver' => 'auto',
        'agent_enabled' => true,
        'agent_status' => 'connected',
        'agent_last_seen_at' => now(),
    ]);
    $ssh = Mockery::mock(SshEngine::class);
    $agent = Mockery::mock(PushAgentEngine::class);

    $ssh->shouldReceive('test')->never();
    $agent->shouldReceive('test')->once()->andReturn(new ConnectionResult(success: true));

    expect((new ServerExecutionEngine($ssh, $agent))->test($server)->success)->toBeTrue();
});

test('server execution engine falls back to ssh for stale auto driver', function () {
    $server = new Server([
        'execution_driver' => 'auto',
        'agent_enabled' => true,
        'agent_status' => 'connected',
        'agent_last_seen_at' => now()->subMinutes(5),
    ]);
    $ssh = Mockery::mock(SshEngine::class);
    $agent = Mockery::mock(PushAgentEngine::class);

    $ssh->shouldReceive('test')->once()->andReturn(new ConnectionResult(success: false));
    $agent->shouldReceive('test')->never();

    expect((new ServerExecutionEngine($ssh, $agent))->test($server)->success)->toBeFalse();
});

test('server execution engine falls back to ssh for disconnected auto driver', function () {
    $server = new Server([
        'execution_driver' => 'auto',
        'agent_enabled' => true,
        'agent_status' => 'disconnected',
    ]);
    $ssh = Mockery::mock(SshEngine::class);
    $agent = Mockery::mock(PushAgentEngine::class);

    $ssh->shouldReceive('test')->once()->andReturn(new ConnectionResult(success: false));
    $agent->shouldReceive('test')->never();

    expect((new ServerExecutionEngine($ssh, $agent))->test($server)->success)->toBeFalse();
});
