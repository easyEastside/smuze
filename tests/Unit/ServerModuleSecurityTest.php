<?php

use App\Models\Server;
use App\Modules\Server\Apache\Actions\ApacheAction;
use App\Modules\Server\Firewall\Actions\FirewallAction;
use App\Modules\Server\Github\Actions\GithubAction;
use App\Modules\Server\Mysql\Actions\MysqlAction;
use App\Services\ExecutionEngine\ExecutionResult;
use App\Services\ExecutionEngine\PushAgentEngine;
use Tests\TestCase;

uses(TestCase::class);

test('apache site config writes encoded content', function () {
    $server = new Server;
    $content = '<VirtualHost *:80>example</VirtualHost>';

    $engine = Mockery::mock(PushAgentEngine::class);
    $engine->shouldReceive('action')
        ->once()
        ->withArgs(function (Server $serverArgument, string $action, array $payload) use ($server, $content): bool {
            expect($serverArgument)->toBe($server);
            expect($action)->toBe('apache.save_site_config')
                ->and($payload)->toBe([
                    'site' => 'example.com.conf',
                    'content' => $content,
                ]);

            return true;
        })
        ->andReturn(new ExecutionResult(stdout: '', stderr: '', exitCode: 0, success: true));

    $result = (new ApacheAction($engine))->saveSiteConfig($server, 'example.com', $content);

    expect($result['success'])->toBeTrue();
});

test('apache module action rejects unsafe module names', function () {
    $engine = Mockery::mock(PushAgentEngine::class);
    $engine->shouldReceive('action')->never();

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

test('mysql create database delegates to validated agent action', function () {
    $server = new Server;

    $engine = Mockery::mock(PushAgentEngine::class);
    $engine->shouldReceive('action')
        ->once()
        ->withArgs(function (Server $serverArgument, string $action, array $payload) use ($server): bool {
            expect($serverArgument)->toBe($server);
            expect($action)->toBe('mysql.create_database')
                ->and($payload)->toBe(['db_name' => 'app_db']);

            return true;
        })
        ->andReturn(new ExecutionResult(stdout: '', stderr: '', exitCode: 0, success: true));

    $result = (new MysqlAction($engine))->createDatabase($server, 'app_db');

    expect($result['success'])->toBeTrue();
});

test('mysql create database rejects unsafe names before agent action', function () {
    $engine = Mockery::mock(PushAgentEngine::class);
    $engine->shouldReceive('action')->never();

    $result = (new MysqlAction($engine))->createDatabase(new Server, 'app db; drop');

    expect($result['success'])->toBeFalse();
});

test('mysql create user delegates to validated agent action', function () {
    $server = new Server;

    $engine = Mockery::mock(PushAgentEngine::class);
    $engine->shouldReceive('action')
        ->once()
        ->withArgs(function (Server $serverArgument, string $action, array $payload) use ($server): bool {
            expect($serverArgument)->toBe($server);
            expect($action)->toBe('mysql.create_user')
                ->and($payload)->toBe([
                    'username' => 'app_user',
                    'host' => 'localhost',
                    'password' => 'secret',
                ]);

            return true;
        })
        ->andReturn(new ExecutionResult(stdout: '', stderr: '', exitCode: 0, success: true));

    $result = (new MysqlAction($engine))->createUser($server, 'app_user', 'localhost', 'secret');

    expect($result['success'])->toBeTrue();
});

test('mysql create user rejects unsafe hosts before agent action', function () {
    $engine = Mockery::mock(PushAgentEngine::class);
    $engine->shouldReceive('action')->never();

    $result = (new MysqlAction($engine))->createUser(new Server, 'app_user', 'local..host', 'secret');

    expect($result['success'])->toBeFalse();
});

test('github deploy delegates to validated agent action', function () {
    $server = new Server;

    $engine = Mockery::mock(PushAgentEngine::class);
    $engine->shouldReceive('action')
        ->once()
        ->withArgs(function (Server $serverArgument, string $action, array $payload) use ($server): bool {
            expect($serverArgument)->toBe($server);
            expect($action)->toBe('github.deploy')
                ->and($payload)->toBe([
                    'repo_url' => 'https://github.com/owner/repo.git',
                    'host' => 'example.com',
                    'target_name' => 'repo',
                    'use_ssl' => false,
                    'email' => '',
                ]);

            return true;
        })
        ->andReturn(new ExecutionResult(stdout: 'Projekt geklont', stderr: '', exitCode: 0, success: true));

    $result = (new GithubAction($engine))->deploy($server, 'https://github.com/owner/repo.git', 'example.com', 'repo');

    expect($result['success'])->toBeTrue();
});

test('github deploy rejects unsafe repo before agent action', function () {
    $engine = Mockery::mock(PushAgentEngine::class);
    $engine->shouldReceive('action')->never();

    $result = (new GithubAction($engine))->deploy(new Server, 'git@github.com:owner/repo.git', 'example.com', 'repo');

    expect($result['success'])->toBeFalse();
});
