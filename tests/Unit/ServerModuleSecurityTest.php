<?php

use App\Models\Server;
use App\Modules\Server\Apache\Actions\ApacheAction;
use App\Modules\Server\Firewall\Actions\FirewallAction;
use App\Services\SshResult;
use App\Services\SshService;
use Tests\TestCase;

uses(TestCase::class);

test('apache site config writes encoded content', function () {
    $server = new Server;
    $content = '<VirtualHost *:80>example</VirtualHost>';
    $encoded = base64_encode($content);

    $ssh = Mockery::mock(SshService::class);
    $ssh->shouldReceive('execute')
        ->once()
        ->withArgs(function (Server $serverArgument, string $command, int $timeout, bool $useSudo) use ($server, $encoded): bool {
            expect($serverArgument)->toBe($server);
            expect($command)
                ->toContain(escapeshellarg($encoded))
                ->toContain('base64 -d')
                ->toContain(escapeshellarg('/etc/apache2/sites-available/example.com.conf'));

            return $timeout === 30 && $useSudo === true;
        })
        ->andReturn(new SshResult(stdout: '', stderr: '', exitCode: 0, success: true));

    $result = (new ApacheAction($ssh))->saveSiteConfig($server, 'example.com', $content);

    expect($result['success'])->toBeTrue();
});

test('apache module action rejects unsafe module names', function () {
    $ssh = Mockery::mock(SshService::class);
    $ssh->shouldReceive('execute')->never();

    $result = (new ApacheAction($ssh))->enableModule(new Server, 'rewrite; reboot');

    expect($result)
        ->toMatchArray([
            'success' => false,
            'message' => 'Ungültiger Modulname.',
        ]);
});

test('firewall allow escapes port specification', function () {
    $server = new Server;

    $ssh = Mockery::mock(SshService::class);
    $ssh->shouldReceive('execute')
        ->once()
        ->withArgs(function (Server $serverArgument, string $command, int $timeout, bool $useSudo) use ($server): bool {
            expect($serverArgument)->toBe($server);
            expect($command)->toBe('ufw allow '.escapeshellarg('80/tcp'));

            return $timeout === 15 && $useSudo === true;
        })
        ->andReturn(new SshResult(stdout: '', stderr: '', exitCode: 0, success: true));

    $result = (new FirewallAction($ssh))->allow($server, '80', 'tcp');

    expect($result['success'])->toBeTrue();
});

test('firewall allow rejects unsafe protocols', function () {
    $ssh = Mockery::mock(SshService::class);
    $ssh->shouldReceive('execute')->never();

    $result = (new FirewallAction($ssh))->allow(new Server, '80', 'tcp; reboot');

    expect($result)
        ->toMatchArray([
            'success' => false,
            'message' => 'Protokoll muss tcp oder udp sein.',
        ]);
});
