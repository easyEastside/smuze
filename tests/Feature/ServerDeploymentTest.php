<?php

use App\Models\Server;
use App\Models\ServerDeployment;
use App\Models\User;
use App\Services\ExecutionEngine\ExecutionResult;
use App\Services\ExecutionEngine\PushAgentEngine;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    $this->user = User::factory()->create();
});

test('guest cannot view deployments page', function () {
    $server = Server::factory()->create();

    $this->get(route('server.deployments.index', $server))
        ->assertRedirect(route('login', absolute: false));
});

test('user can view their own deployments page', function () {
    $server = Server::factory()->create(['user_id' => $this->user->id]);
    ServerDeployment::factory()->create([
        'server_id' => $server->id,
        'user_id' => $this->user->id,
        'name' => 'Production App',
    ]);

    $this->actingAs($this->user)
        ->get(route('server.deployments.index', $server))
        ->assertSuccessful()
        ->assertSee('Laravel Deployment')
        ->assertSee('Production App')
        ->assertSee('data-run-deployment', false)
        ->assertSee('addEventListener(\'click\'', false)
        ->assertSee('Fehler kopieren')
        ->assertSee('Fehler senden')
        ->assertDontSee('onclick="runDeployment', false)
        ->assertDontSee('innerHTML', false);
});

test('user cannot view other users deployments page', function () {
    $otherUser = User::factory()->create();
    $server = Server::factory()->create(['user_id' => $otherUser->id]);

    $this->actingAs($this->user)
        ->get(route('server.deployments.index', $server))
        ->assertForbidden();
});

test('user can create a deployment', function () {
    $server = Server::factory()->create(['user_id' => $this->user->id]);

    $this->actingAs($this->user)
        ->post(route('server.deployments.store', $server), [
            'name' => 'Production App',
            'repo_url' => 'https://github.com/laravel/laravel.git',
            'target_path' => '/var/www/production-app',
            'domain' => 'example.com',
            'webserver' => 'apache',
            'php_version' => '8.5',
            'write_env' => '1',
            'install_node' => '1',
            'run_build' => '1',
            'run_migrations' => '1',
            'env' => "APP_ENV=production\nAPP_DEBUG=false",
        ])
        ->assertRedirect(route('server.deployments.index', $server));

    $deployment = ServerDeployment::first();

    expect($deployment)->not->toBeNull()
        ->and($deployment->server_id)->toBe($server->id)
        ->and($deployment->env)->toBe([
            'APP_ENV' => 'production',
            'APP_DEBUG' => 'false',
        ])
        ->and($deployment->run_migrations)->toBeTrue();
});

test('deployment validation rejects unsafe input', function (array $payload, string $field) {
    $server = Server::factory()->create(['user_id' => $this->user->id]);

    $this->actingAs($this->user)
        ->from(route('server.deployments.index', $server))
        ->post(route('server.deployments.store', $server), array_merge([
            'name' => 'Production App',
            'repo_url' => 'https://github.com/laravel/laravel.git',
            'target_path' => '/var/www/production-app',
            'domain' => 'example.com',
            'webserver' => 'apache',
            'php_version' => '8.5',
            'write_env' => '1',
            'env' => 'APP_ENV=production',
        ], $payload))
        ->assertRedirect(route('server.deployments.index', $server))
        ->assertSessionHasErrors($field);
})->with([
    'non github url' => [['repo_url' => 'https://example.com/acme/app.git'], 'repo_url'],
    'unsafe path' => [['target_path' => '/tmp/app'], 'target_path'],
    'reserved path' => [['target_path' => '/var/www/html'], 'target_path'],
    'missing domain for vhost' => [['domain' => '', 'webserver' => 'nginx'], 'domain'],
    'invalid env' => [['env' => 'bad-key=value'], 'env'],
]);

test('user can run a deployment', function () {
    $server = Server::factory()->create(['user_id' => $this->user->id]);
    $deployment = ServerDeployment::factory()->create([
        'server_id' => $server->id,
        'user_id' => $this->user->id,
        'repo_url' => 'https://github.com/laravel/laravel.git',
        'target_path' => '/var/www/production-app',
        'domain' => 'example.com',
        'webserver' => 'apache',
        'env' => ['APP_ENV' => 'production'],
    ]);

    $engine = Mockery::mock(PushAgentEngine::class);
    $engine->shouldReceive('action')
        ->once()
        ->withArgs(function (Server $serverArgument, string $action, array $payload) use ($server): bool {
            return $serverArgument->is($server)
                && $action === 'laravel.deploy'
                && $payload['repo_url'] === 'https://github.com/laravel/laravel.git'
                && $payload['target_path'] === '/var/www/production-app'
                && $payload['env'] === ['APP_ENV' => 'production'];
        })
        ->andReturn(new ExecutionResult(
            stdout: 'Laravel deployment abgeschlossen: /var/www/production-app',
            stderr: '',
            exitCode: 0,
            success: true,
        ));

    $this->app->instance(PushAgentEngine::class, $engine);

    $this->actingAs($this->user)
        ->postJson(route('server.deployments.run', [$server, $deployment]))
        ->assertSuccessful()
        ->assertJson(['success' => true]);

    expect($deployment->fresh()->last_status)->toBe('success')
        ->and($deployment->runs()->first()->status)->toBe('success');
});

test('deployment run fails gracefully', function () {
    $server = Server::factory()->create(['user_id' => $this->user->id]);
    $deployment = ServerDeployment::factory()->create([
        'server_id' => $server->id,
        'user_id' => $this->user->id,
    ]);

    $engine = Mockery::mock(PushAgentEngine::class);
    $engine->shouldReceive('action')
        ->once()
        ->andReturn(new ExecutionResult(
            stdout: '',
            stderr: 'composer install failed',
            exitCode: 1,
            success: false,
        ));

    $this->app->instance(PushAgentEngine::class, $engine);

    $this->actingAs($this->user)
        ->postJson(route('server.deployments.run', [$server, $deployment]))
        ->assertUnprocessable()
        ->assertJson(['success' => false]);

    expect($deployment->fresh()->last_status)->toBe('failed')
        ->and($deployment->runs()->first()->status)->toBe('failed');
});
