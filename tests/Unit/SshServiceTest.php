<?php

use App\Models\Server;
use App\Services\ConnectionResult;
use App\Services\SshResult;
use App\Services\SshService;
use Tests\TestCase;

uses(TestCase::class)->beforeEach(function () {
    $this->server = new Server([
        'host' => '192.0.2.1',
        'port' => 22,
        'username' => 'test',
        'auth_type' => 'password',
        'credentials' => 'password',
    ]);
});

test('ssh service returns error result on connection failure', function () {
    $service = new SshService;
    $result = $service->test($this->server);

    expect($result)->toBeInstanceOf(ConnectionResult::class);
    expect($result->success)->toBeFalse();
    expect($result->errorMessage)->toBeString();
});

test('ssh service returns error result on execute failure', function () {
    $service = new SshService;
    $result = $service->execute($this->server, 'echo test', timeout: 2);

    expect($result)->toBeInstanceOf(SshResult::class);
    expect($result->success)->toBeFalse();
});

test('ssh service handles key auth gracefully with missing key', function () {
    $keyServer = new Server([
        'host' => '192.0.2.1',
        'port' => 22,
        'username' => 'test',
        'auth_type' => 'key',
        'key_content' => null,
        'key_path' => '/nonexistent/key.pem',
    ]);

    $service = new SshService;
    $result = $service->execute($keyServer, 'echo test', timeout: 2);

    expect($result->success)->toBeFalse();
    expect($result->stderr)->toContain('SSH-Key nicht gefunden');
});

test('ssh service disconnects gracefully', function () {
    $service = new SshService;

    $service->disconnect($this->server);
    $service->disconnectAll();

    expect(true)->toBeTrue();
});

test('ssh service wraps complex commands in sudo shell', function () {
    $service = new SshService;
    $method = new ReflectionMethod($service, 'applySudo');

    $command = 'EXPECTED_CHECKSUM="$(curl -sS https://composer.github.io/installer.sig)" && if [ "$EXPECTED_CHECKSUM" != "$ACTUAL_CHECKSUM" ]; then exit 1; fi && mv composer.phar /usr/local/bin/composer';

    $wrapped = $method->invoke($service, $command);

    expect($wrapped)
        ->toStartWith('sudo DEBIAN_FRONTEND=noninteractive sh -lc ')
        ->toContain('EXPECTED_CHECKSUM')
        ->toContain('mv composer.phar /usr/local/bin/composer')
        ->not->toContain('sudo DEBIAN_FRONTEND=noninteractive EXPECTED_CHECKSUM');
});
