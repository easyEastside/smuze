<?php

use App\Models\Server;
use App\Modules\Server\Deployments\Actions\DeploymentAction;
use App\Services\ExecutionEngine\ExecutionResult;
use App\Services\ExecutionEngine\PushAgentEngine;
use Tests\TestCase;

uses(TestCase::class);

test('deployment action delegates payload to agent', function () {
    $server = new Server;
    $payload = [
        'repo_url' => 'https://github.com/laravel/laravel.git',
        'target_path' => '/var/www/app',
    ];

    $engine = Mockery::mock(PushAgentEngine::class);
    $engine->shouldReceive('action')
        ->once()
        ->withArgs(function (Server $serverArgument, string $action, array $payloadArgument) use ($server, $payload): bool {
            expect($serverArgument)->toBe($server);
            expect($action)->toBe('laravel.deploy')
                ->and($payloadArgument)->toBe($payload);

            return true;
        })
        ->andReturn(new ExecutionResult(stdout: 'ok', stderr: '', exitCode: 0, success: true));

    $result = (new DeploymentAction($engine))->deploy($server, $payload);

    expect($result)->toMatchArray([
        'success' => true,
        'message' => 'Deployment wurde ausgeführt.',
        'output' => 'ok',
    ]);
});

test('deployment action returns stderr on failure', function () {
    $engine = Mockery::mock(PushAgentEngine::class);
    $engine->shouldReceive('action')
        ->once()
        ->andReturn(new ExecutionResult(stdout: '', stderr: 'failed', exitCode: 1, success: false));

    $result = (new DeploymentAction($engine))->deploy(new Server, []);

    expect($result)->toMatchArray([
        'success' => false,
        'message' => 'failed',
        'error_output' => 'failed',
    ]);
});

test('deployment action includes stdout on failure', function () {
    $engine = Mockery::mock(PushAgentEngine::class);
    $engine->shouldReceive('action')
        ->once()
        ->andReturn(new ExecutionResult(stdout: "==> npm run build\nVite failed", stderr: '', exitCode: 1, success: false));

    $result = (new DeploymentAction($engine))->deploy(new Server, []);

    expect($result)->toMatchArray([
        'success' => false,
        'message' => "==> npm run build\nVite failed",
        'error_output' => "==> npm run build\nVite failed",
    ]);
});
