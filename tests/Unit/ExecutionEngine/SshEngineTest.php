<?php

use App\Models\Server;
use App\Services\ConnectionResult;
use App\Services\ExecutionEngine\ExecutionResult;
use App\Services\ExecutionEngine\SshEngine;
use App\Services\SshResult;
use App\Services\SshService;
use Tests\TestCase;

uses(TestCase::class);

test('ssh engine delegates command execution to ssh service', function () {
    $server = new Server;
    $ssh = Mockery::mock(SshService::class);
    $output = function (): void {};

    $ssh->shouldReceive('execute')
        ->once()
        ->with($server, 'echo OK', 10, false, $output)
        ->andReturn(new SshResult(stdout: 'OK', stderr: '', exitCode: 0, success: true));

    $result = (new SshEngine($ssh))->execute($server, 'echo OK', timeout: 10, useSudo: false, onOutput: $output);

    expect($result)->toBeInstanceOf(ExecutionResult::class)
        ->and($result->stdout)->toBe('OK')
        ->and($result->stderr)->toBe('')
        ->and($result->exitCode)->toBe(0)
        ->and($result->success)->toBeTrue();
});

test('ssh engine delegates connection tests to ssh service', function () {
    $server = new Server;
    $ssh = Mockery::mock(SshService::class);
    $connectionResult = new ConnectionResult(success: true, latencyMs: 12.3);

    $ssh->shouldReceive('test')
        ->once()
        ->with($server, 7)
        ->andReturn($connectionResult);

    expect((new SshEngine($ssh))->test($server, timeout: 7))->toBe($connectionResult);
});

test('ssh engine exposes supported features', function () {
    $engine = new SshEngine(Mockery::mock(SshService::class));

    expect($engine->supports('terminal'))->toBeTrue()
        ->and($engine->supports('live-output'))->toBeTrue()
        ->and($engine->supports('agent'))->toBeFalse();
});
