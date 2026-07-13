<?php

use App\Models\Server;
use App\Modules\Server\Apache\Actions\ApacheAction;
use App\Modules\Server\Firewall\Actions\FirewallAction;
use App\Modules\Server\Github\Actions\GithubAction;
use App\Modules\Server\Mysql\Actions\MysqlAction;
use App\Modules\Server\Nginx\Actions\NginxAction;
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

test('apache modules parses agent output', function () {
    $server = new Server;
    $stdout = "rewrite\tenabled\nssl\tdisabled\nauthz_core\tenabled\n";

    $engine = Mockery::mock(PushAgentEngine::class);
    $engine->shouldReceive('action')
        ->once()
        ->withArgs(fn (Server $s, string $action, array $payload): bool => $action === 'apache.modules')
        ->andReturn(new ExecutionResult(stdout: $stdout, stderr: '', exitCode: 0, success: true));

    $result = (new ApacheAction($engine))->modules($server);

    expect($result['success'])->toBeTrue()
        ->and($result['modules'])->toHaveCount(3)
        ->and($result['modules'][0])->toMatchArray(['name' => 'rewrite', 'enabled' => 'enabled'])
        ->and($result['modules'][1])->toMatchArray(['name' => 'ssl', 'enabled' => 'disabled'])
        ->and($result['modules'][2])->toMatchArray(['name' => 'authz_core', 'enabled' => 'enabled']);
});

test('apache modules returns error message on failure', function () {
    $server = new Server;

    $engine = Mockery::mock(PushAgentEngine::class);
    $engine->shouldReceive('action')
        ->once()
        ->withArgs(fn (Server $s, string $action, array $payload): bool => $action === 'apache.modules')
        ->andReturn(new ExecutionResult(stdout: '', stderr: 'Connection refused', exitCode: 1, success: false));

    $result = (new ApacheAction($engine))->modules($server);

    expect($result['success'])->toBeFalse()
        ->and($result['message'])->toBe('Connection refused')
        ->and($result['modules'])->toBe([]);
});

test('apache obtain ssl rejects invalid input before agent action', function (string $domain, string $email, string $message) {
    $engine = Mockery::mock(PushAgentEngine::class);
    $engine->shouldReceive('action')->never();

    $result = (new ApacheAction($engine))->obtainSsl(new Server, $domain, $email);

    expect($result)->toMatchArray([
        'success' => false,
        'message' => $message,
    ]);
})->with([
    ['bad domain', 'admin@example.com', 'Domain darf nur gültige DNS-Zeichen enthalten, z. B. example.com.'],
    ['example.com', 'not-an-email', 'Bitte eine gültige E-Mail-Adresse angeben.'],
]);

test('apache obtain ssl delegates validated payload to agent', function () {
    $server = new Server;

    $engine = Mockery::mock(PushAgentEngine::class);
    $engine->shouldReceive('action')
        ->once()
        ->withArgs(function (Server $serverArgument, string $action, array $payload) use ($server): bool {
            expect($serverArgument)->toBe($server);
            expect($action)->toBe('apache.obtain_ssl')
                ->and($payload)->toBe([
                    'domain' => 'example.com',
                    'email' => 'admin@example.com',
                ]);

            return true;
        })
        ->andReturn(new ExecutionResult(stdout: '', stderr: '', exitCode: 0, success: true));

    $result = (new ApacheAction($engine))->obtainSsl($server, ' example.com ', ' admin@example.com ');

    expect($result)->toMatchArray([
        'success' => true,
        'message' => 'SSL-Zertifikat für example.com wurde ausgestellt.',
    ]);
});

test('nginx site config writes encoded content', function () {
    $server = new Server;
    $content = 'server { listen 80; }';

    $engine = Mockery::mock(PushAgentEngine::class);
    $engine->shouldReceive('action')
        ->once()
        ->withArgs(function (Server $serverArgument, string $action, array $payload) use ($server, $content): bool {
            expect($serverArgument)->toBe($server);
            expect($action)->toBe('nginx.save_site_config')
                ->and($payload)->toBe([
                    'site' => 'example.com.conf',
                    'content' => $content,
                ]);

            return true;
        })
        ->andReturn(new ExecutionResult(stdout: '', stderr: '', exitCode: 0, success: true));

    $result = (new NginxAction($engine))->saveSiteConfig($server, 'example.com', $content);

    expect($result['success'])->toBeTrue();
});

test('nginx site action rejects unsafe site names', function () {
    $engine = Mockery::mock(PushAgentEngine::class);
    $engine->shouldReceive('action')->never();

    $result = (new NginxAction($engine))->enableSite(new Server, 'default; reboot');

    expect($result)
        ->toMatchArray([
            'success' => false,
            'message' => 'Ungültiger Site-Name.',
        ]);
});

test('nginx sites parses tab separated agent output safely', function () {
    $server = new Server;
    $stdout = "default.conf\tyes\texample.com\t/var/www/html\nquote\"site.conf\tno\t<script>alert(1)</script>\t/var/www/app/public\ninvalid\tline\n";

    $engine = Mockery::mock(PushAgentEngine::class);
    $engine->shouldReceive('action')
        ->once()
        ->withArgs(fn (Server $s, string $action, array $payload): bool => $action === 'nginx.sites' && $payload === [])
        ->andReturn(new ExecutionResult(stdout: $stdout, stderr: '', exitCode: 0, success: true));

    $result = (new NginxAction($engine))->sites($server);

    expect($result['success'])->toBeTrue()
        ->and($result['sites'])->toHaveCount(2)
        ->and($result['sites'][0])->toMatchArray([
            'name' => 'default.conf',
            'enabled' => 'yes',
            'server_name' => 'example.com',
            'document_root' => '/var/www/html',
        ])
        ->and($result['sites'][1])->toMatchArray([
            'name' => 'quote"site.conf',
            'enabled' => 'no',
            'server_name' => '<script>alert(1)</script>',
            'document_root' => '/var/www/app/public',
        ]);
});

test('nginx install deinstall and service actions delegate to agent', function (string $method, string $agentAction, string $message) {
    $server = new Server;

    $engine = Mockery::mock(PushAgentEngine::class);
    $engine->shouldReceive('action')
        ->once()
        ->withArgs(fn (Server $s, string $action, array $payload): bool => $s === $server && $action === $agentAction && $payload === [])
        ->andReturn(new ExecutionResult(stdout: '', stderr: '', exitCode: 0, success: true));

    $result = (new NginxAction($engine))->{$method}($server);

    expect($result)->toMatchArray([
        'success' => true,
        'message' => $message,
    ]);
})->with([
    ['install', 'nginx.install', 'Nginx wurde installiert.'],
    ['deinstall', 'nginx.deinstall', 'Nginx wurde deinstalliert.'],
    ['start', 'nginx.start', 'Nginx wurde gestartet.'],
    ['stop', 'nginx.stop', 'Nginx wurde gestoppt.'],
    ['restart', 'nginx.restart', 'Nginx wurde neugestartet.'],
    ['reload', 'nginx.reload', 'Nginx wurde neugeladen.'],
]);

test('nginx create vhost delegates sanitized config payload', function () {
    $server = new Server;

    $engine = Mockery::mock(PushAgentEngine::class);
    $engine->shouldReceive('action')
        ->once()
        ->withArgs(function (Server $serverArgument, string $action, array $payload) use ($server): bool {
            expect($serverArgument)->toBe($server);
            expect($action)->toBe('nginx.create_vhost')
                ->and($payload['domain'])->toBe('example.com')
                ->and($payload['document_root'])->toBe('/var/www/example/public')
                ->and($payload['config'])->toContain('server_name example.com www.example.com;')
                ->and($payload['config'])->toContain('root /var/www/example/public;')
                ->and($payload['config'])->toContain('fastcgi_pass unix:/run/php/php8.5-fpm.sock;');

            return true;
        })
        ->andReturn(new ExecutionResult(stdout: '', stderr: '', exitCode: 0, success: true));

    $result = (new NginxAction($engine))->createVhost($server, ' example.com ', ' /var/www/example/public ', ' www.example.com ');

    expect($result)->toMatchArray([
        'success' => true,
        'message' => 'VHost für example.com wurde erstellt.',
    ]);
});

test('nginx create vhost obtains ssl only after vhost creation succeeds', function () {
    $server = new Server;

    $engine = Mockery::mock(PushAgentEngine::class);
    $engine->shouldReceive('action')
        ->once()
        ->ordered()
        ->withArgs(fn (Server $s, string $action, array $payload): bool => $s === $server && $action === 'nginx.create_vhost' && $payload['domain'] === 'secure.example.com')
        ->andReturn(new ExecutionResult(stdout: '', stderr: '', exitCode: 0, success: true));
    $engine->shouldReceive('action')
        ->once()
        ->ordered()
        ->withArgs(fn (Server $s, string $action, array $payload): bool => $s === $server && $action === 'nginx.obtain_ssl' && $payload === [
            'domain' => 'secure.example.com',
            'email' => 'admin@example.com',
        ])
        ->andReturn(new ExecutionResult(stdout: '', stderr: '', exitCode: 0, success: true));

    $result = (new NginxAction($engine))->createVhost($server, 'secure.example.com', '/var/www/secure/public', useSsl: true, email: 'admin@example.com');

    expect($result)->toMatchArray([
        'success' => true,
        'message' => 'SSL-Zertifikat für secure.example.com wurde ausgestellt.',
    ]);
});

test('nginx create vhost rejects invalid aliases before agent action', function () {
    $engine = Mockery::mock(PushAgentEngine::class);
    $engine->shouldReceive('action')->never();

    $result = (new NginxAction($engine))->createVhost(new Server, 'example.com', '/var/www/example/public', 'valid.example.com bad_alias!');

    expect($result)->toMatchArray([
        'success' => false,
        'message' => 'ServerAlias ist ungültig: bad_alias!',
    ]);
});

test('nginx obtain ssl rejects invalid input before agent action', function (string $domain, string $email, string $message) {
    $engine = Mockery::mock(PushAgentEngine::class);
    $engine->shouldReceive('action')->never();

    $result = (new NginxAction($engine))->obtainSsl(new Server, $domain, $email);

    expect($result)->toMatchArray([
        'success' => false,
        'message' => $message,
    ]);
})->with([
    ['bad domain', 'admin@example.com', 'Domain darf nur gültige DNS-Zeichen enthalten, z. B. example.com.'],
    ['example.com', 'not-an-email', 'Bitte eine gültige E-Mail-Adresse angeben.'],
]);

test('nginx obtain ssl delegates validated payload to agent', function () {
    $server = new Server;

    $engine = Mockery::mock(PushAgentEngine::class);
    $engine->shouldReceive('action')
        ->once()
        ->withArgs(function (Server $serverArgument, string $action, array $payload) use ($server): bool {
            expect($serverArgument)->toBe($server);
            expect($action)->toBe('nginx.obtain_ssl')
                ->and($payload)->toBe([
                    'domain' => 'example.com',
                    'email' => 'admin@example.com',
                ]);

            return true;
        })
        ->andReturn(new ExecutionResult(stdout: '', stderr: '', exitCode: 0, success: true));

    $result = (new NginxAction($engine))->obtainSsl($server, ' example.com ', ' admin@example.com ');

    expect($result)->toMatchArray([
        'success' => true,
        'message' => 'SSL-Zertifikat für example.com wurde ausgestellt.',
    ]);
});

test('nginx delete site rejects unsafe project deletion paths before agent action', function () {
    $engine = Mockery::mock(PushAgentEngine::class);
    $engine->shouldReceive('action')->never();

    $result = (new NginxAction($engine))->deleteSite(new Server, 'example.com', true, '/etc/nginx/sites-available/example.conf');

    expect($result)->toMatchArray([
        'success' => false,
        'message' => 'Projektordner kann nur unter /var/www automatisch gelöscht werden.',
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

test('apache status uses installed field when present', function () {
    $server = new Server;

    $engine = Mockery::mock(PushAgentEngine::class);
    $engine->shouldReceive('action')
        ->once()
        ->withArgs(fn (Server $s, string $action, array $payload): bool => $action === 'apache.status')
        ->andReturn(new ExecutionResult(stdout: "ACTIVE=inactive\nINSTALLED=no", stderr: '', exitCode: 0, success: true));

    $result = (new ApacheAction($engine))->status($server);

    expect($result['success'])->toBeTrue()
        ->and($result['installed'])->toBeFalse();
});

test('apache status falls back to active when installed field is missing', function () {
    $server = new Server;

    $engine = Mockery::mock(PushAgentEngine::class);
    $engine->shouldReceive('action')
        ->once()
        ->withArgs(fn (Server $s, string $action, array $payload): bool => $action === 'apache.status')
        ->andReturn(new ExecutionResult(stdout: 'ACTIVE=inactive', stderr: '', exitCode: 0, success: true));

    $result = (new ApacheAction($engine))->status($server);

    expect($result['success'])->toBeTrue()
        ->and($result['installed'])->toBeTrue();
});

test('nginx status uses installed field when present', function () {
    $server = new Server;

    $engine = Mockery::mock(PushAgentEngine::class);
    $engine->shouldReceive('action')
        ->once()
        ->withArgs(fn (Server $s, string $action, array $payload): bool => $action === 'nginx.status')
        ->andReturn(new ExecutionResult(stdout: "ACTIVE=inactive\nINSTALLED=no", stderr: '', exitCode: 0, success: true));

    $result = (new NginxAction($engine))->status($server);

    expect($result['success'])->toBeTrue()
        ->and($result['installed'])->toBeFalse();
});

test('nginx status falls back to active when installed field is missing', function () {
    $server = new Server;

    $engine = Mockery::mock(PushAgentEngine::class);
    $engine->shouldReceive('action')
        ->once()
        ->withArgs(fn (Server $s, string $action, array $payload): bool => $action === 'nginx.status')
        ->andReturn(new ExecutionResult(stdout: 'ACTIVE=inactive', stderr: '', exitCode: 0, success: true));

    $result = (new NginxAction($engine))->status($server);

    expect($result['success'])->toBeTrue()
        ->and($result['installed'])->toBeTrue();
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
                    'target_name' => 'repo',
                ]);

            return true;
        })
        ->andReturn(new ExecutionResult(stdout: 'Projekt geklont', stderr: '', exitCode: 0, success: true));

    $result = (new GithubAction($engine))->deploy($server, 'https://github.com/owner/repo.git', 'repo');

    expect($result['success'])->toBeTrue();
});

test('github deploy rejects unsafe repo before agent action', function () {
    $engine = Mockery::mock(PushAgentEngine::class);
    $engine->shouldReceive('action')->never();

    $result = (new GithubAction($engine))->deploy(new Server, 'git@github.com:owner/repo.git', 'repo');

    expect($result['success'])->toBeFalse();
});
