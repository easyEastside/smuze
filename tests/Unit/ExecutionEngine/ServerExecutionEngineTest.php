<?php

use App\Models\Server;
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
