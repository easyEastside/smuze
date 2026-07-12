<?php

use App\Models\Server;
use App\Modules\Server\Apache\Actions\ApacheAction;
use App\Modules\Server\Firewall\Actions\FirewallAction;
use App\Services\ExecutionEngine\ExecutionResult;
use App\Services\ExecutionEngine\PushAgentEngine;
use Tests\TestCase;

uses(TestCase::class);

test('apache site config writes encoded content', function () {
    $server = new Server;
    $content = '<VirtualHost *:80>example</VirtualHost>';
    $encoded = base64_encode($content);

    $engine = Mockery::mock(PushAgentEngine::class);
    $engine->shouldReceive('execute')
        ->once()
        ->withArgs(function (Server $serverArgument, string $command, int $timeout, bool $useSudo) use ($server, $encoded): bool {
            expect($serverArgument)->toBe($server);
            expect($command)
                ->toContain(escapeshellarg($encoded))
                ->toContain('base64 -d')
                ->toContain(escapeshellarg('/etc/apache2/sites-available/example.com.conf'));

            return $timeout === 30 && $useSudo === true;
        })
        ->andReturn(new ExecutionResult(stdout: '', stderr: '', exitCode: 0, success: true));

    $result = (new ApacheAction($engine))->saveSiteConfig($server, 'example.com', $content);

    expect($result['success'])->toBeTrue();
});

test('apache module action rejects unsafe module names', function () {
    $engine = Mockery::mock(PushAgentEngine::class);
    $engine->shouldReceive('execute')->never();

    $result = (new ApacheAction($engine))->enableModule(new Server, 'rewrite; reboot');

    expect($result)
        ->toMatchArray([
            'success' => false,
            'message' => 'Ungültiger Modulname.',
        ]);
});

test('firewall allow delegates to validated agent action', function () {
    $server = new Server;

    $engine = Mockery::mock(PushAgentEngine::class);
    $engine->shouldReceive('action')
        ->once()
        ->withArgs(function (Server $serverArgument, string $action, array $payload) use ($server): bool {
            expect($serverArgument)->toBe($server);
            expect($action)->toBe('firewall.allow')
                ->and($payload)->toBe([
                    'port' => '80',
                    'protocol' => 'tcp',
                ]);

            return true;
        })
        ->andReturn(new ExecutionResult(stdout: '', stderr: '', exitCode: 0, success: true));

    $result = (new FirewallAction($engine))->allow($server, '80', 'tcp');

    expect($result['success'])->toBeTrue();
});

test('firewall allow rejects unsafe protocols', function () {
    $engine = Mockery::mock(PushAgentEngine::class);
    $engine->shouldReceive('action')->never();

    $result = (new FirewallAction($engine))->allow(new Server, '80', 'tcp; reboot');

    expect($result)
        ->toMatchArray([
            'success' => false,
            'message' => 'Protokoll muss tcp oder udp sein.',
        ]);
});

test('firewall allow all delegates to agent action', function () {
    $server = new Server;

    $engine = Mockery::mock(PushAgentEngine::class);
    $engine->shouldReceive('action')
        ->once()
        ->withArgs(function (Server $serverArgument, string $action, array $payload) use ($server): bool {
            expect($serverArgument)->toBe($server);
            expect($action)->toBe('firewall.allow_standard_ports')
                ->and($payload)->toBe([]);

            return true;
        })
        ->andReturn(new ExecutionResult(stdout: '', stderr: '', exitCode: 0, success: true));

    $result = (new FirewallAction($engine))->allowAll($server);

    expect($result['success'])->toBeTrue();
});
