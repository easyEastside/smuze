<?php

use App\Models\Server;
use App\Models\ServerAgentCommand;
use App\Models\User;
use App\Services\ExecutionEngine\ExecutionResult;
use App\Services\ExecutionEngine\PushAgentEngine;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    $this->user = User::factory()->create();
});

test('guest cannot view servers', function () {
    $this->get(route('server.index'))->assertRedirect(route('login', absolute: false));
});

test('authenticated user can view their own servers', function () {
    Server::factory()->create(['user_id' => $this->user->id, 'name' => 'Web Server']);

    $this->actingAs($this->user)
        ->get(route('server.index'))
        ->assertSuccessful()
        ->assertSee('Meine Server')
        ->assertSee('Web Server');
});

test('user cannot see other users servers', function () {
    $otherUser = User::factory()->create();
    Server::factory()->create(['user_id' => $otherUser->id, 'name' => 'Other Server']);

    $this->actingAs($this->user)
        ->get(route('server.index'))
        ->assertSuccessful()
        ->assertDontSee('Other Server');
});

test('servers page shows empty state', function () {
    $this->actingAs($this->user)
        ->get(route('server.index'))
        ->assertSuccessful()
        ->assertSee('Du hast noch keine Server.');
});

test('user can create a server', function () {
    $this->actingAs($this->user)
        ->post(route('server.store'), [
            'name' => 'My Server',
            'host' => '192.168.1.1',
            'agent_port' => 9300,
        ])
        ->assertRedirect(route('server.index', absolute: false));

    $this->assertDatabaseHas('servers', [
        'user_id' => $this->user->id,
        'name' => 'My Server',
        'host' => '192.168.1.1',
    ]);
});

test('user can view create server form', function () {
    $this->actingAs($this->user)
        ->get(route('server.create'))
        ->assertSuccessful()
        ->assertSee('Server hinzufügen');
});

test('user can view edit server form', function () {
    $server = Server::factory()->create(['user_id' => $this->user->id]);

    $this->actingAs($this->user)
        ->get(route('server.edit', $server))
        ->assertSuccessful()
        ->assertSee('Server bearbeiten');
});

test('user cannot edit another users server', function () {
    $otherUser = User::factory()->create();
    $server = Server::factory()->create(['user_id' => $otherUser->id]);

    $this->actingAs($this->user)
        ->get(route('server.edit', $server))
        ->assertForbidden();
});

test('user can update their server', function () {
    $server = Server::factory()->create(['user_id' => $this->user->id, 'name' => 'Old Name']);

    $this->actingAs($this->user)
        ->put(route('server.update', $server), [
            'name' => 'New Name',
            'host' => $server->host,
            'agent_port' => $server->agent_port,
        ])
        ->assertRedirect(route('server.index', absolute: false));

    expect($server->refresh()->name)->toBe('New Name');
});

test('user cannot update another users server', function () {
    $otherUser = User::factory()->create();
    $server = Server::factory()->create(['user_id' => $otherUser->id, 'name' => 'Other Server']);

    $this->actingAs($this->user)
        ->put(route('server.update', $server), [
            'name' => 'Hacked Name',
            'host' => $server->host,
            'agent_port' => $server->agent_port,
        ])
        ->assertForbidden();
});

test('user can delete their server', function () {
    $server = Server::factory()->create(['user_id' => $this->user->id]);

    $this->actingAs($this->user)
        ->delete(route('server.destroy', $server))
        ->assertRedirect(route('server.index', absolute: false));

    $this->assertDatabaseMissing('servers', ['id' => $server->id]);
});

test('user cannot delete another users server', function () {
    $otherUser = User::factory()->create();
    $server = Server::factory()->create(['user_id' => $otherUser->id]);

    $this->actingAs($this->user)
        ->delete(route('server.destroy', $server))
        ->assertForbidden();
});

test('server validation requires name and host', function () {
    $this->actingAs($this->user)
        ->post(route('server.store'), [
            'name' => '',
            'host' => '',
        ])
        ->assertInvalid(['name', 'host']);
});

test('user can create server with custom agent port', function () {
    $this->actingAs($this->user)
        ->post(route('server.store'), [
            'name' => 'Agent Server',
            'host' => '10.0.0.1',
            'agent_port' => 9400,
            'notes' => 'Test server',
        ])
        ->assertRedirect(route('server.index', absolute: false));

    $this->assertDatabaseHas('servers', [
        'user_id' => $this->user->id,
        'name' => 'Agent Server',
        'host' => '10.0.0.1',
        'agent_port' => 9400,
        'notes' => 'Test server',
    ]);
});

test('server creation defaults the agent port', function () {
    $this->actingAs($this->user)
        ->post(route('server.store'), [
            'name' => 'Default Agent Server',
            'host' => '192.168.1.100',
        ])
        ->assertRedirect(route('server.index', absolute: false));

    $this->assertDatabaseHas('servers', [
        'user_id' => $this->user->id,
        'name' => 'Default Agent Server',
        'agent_port' => config('agent.push_port', 9300),
    ]);
});

test('guest cannot view server system', function () {
    $server = Server::factory()->create();

    $this->get(route('server.system', $server))->assertRedirect(route('login', absolute: false));
});

test('user can view their own server system', function () {
    $server = Server::factory()->create(['user_id' => $this->user->id]);

    $this->actingAs($this->user)
        ->get(route('server.system', $server))
        ->assertSuccessful()
        ->assertSee('Server System')
        ->assertSee($server->name)
        ->assertSee('System')
        ->assertSee('Systeminformationen')
        ->assertSee('System-Aktionen');
});

test('user can view server agent audit commands', function () {
    $server = Server::factory()->create(['user_id' => $this->user->id]);

    ServerAgentCommand::query()->create([
        'server_id' => $server->id,
        'user_id' => $this->user->id,
        'source' => 'agent',
        'command' => 'apt update',
        'timeout' => 30,
        'use_sudo' => true,
        'exit_code' => 0,
        'success' => true,
        'duration_ms' => 125,
        'stdout' => 'ok',
        'stderr' => '',
        'started_at' => now(),
        'finished_at' => now(),
    ]);

    $this->actingAs($this->user)
        ->get(route('server.system', $server))
        ->assertSuccessful()
        ->assertSee('Agent-Audit')
        ->assertSee('apt update')
        ->assertSee('OK')
        ->assertSee('125 ms');
});

test('user cannot view another users server system', function () {
    $otherUser = User::factory()->create();
    $server = Server::factory()->create(['user_id' => $otherUser->id]);

    $this->actingAs($this->user)
        ->get(route('server.system', $server))
        ->assertForbidden();
});

test('user can update server agent port', function () {
    $server = Server::factory()->create([
        'user_id' => $this->user->id,
    ]);

    $this->actingAs($this->user)
        ->put(route('server.update', $server), [
            'name' => $server->name,
            'host' => $server->host,
            'agent_port' => 9400,
        ])
        ->assertRedirect(route('server.index', absolute: false));

    expect($server->refresh()->agent_port)->toBe(9400);
});

test('admin can view all servers in admin panel', function () {
    $role = Role::create(['name' => 'admin']);
    $permission = Permission::create(['name' => 'access-admin']);
    $role->givePermissionTo($permission);

    $admin = User::factory()->create()->assignRole('admin');

    Server::factory()->create(['user_id' => $this->user->id, 'name' => 'User Server']);

    $this->actingAs($admin)
        ->get(route('admin.servers.index'))
        ->assertSuccessful()
        ->assertSee('User Server');
});

test('admin can delete a server', function () {
    $role = Role::create(['name' => 'admin']);
    $permission = Permission::create(['name' => 'access-admin']);
    $role->givePermissionTo($permission);

    $admin = User::factory()->create()->assignRole('admin');

    $server = Server::factory()->create(['user_id' => $this->user->id]);

    $this->actingAs($admin)
        ->delete(route('admin.servers.destroy', $server))
        ->assertRedirect(route('admin.servers.index', absolute: false));

    $this->assertDatabaseMissing('servers', ['id' => $server->id]);
});

test('user without admin permission cannot access admin servers', function () {
    $this->actingAs($this->user)
        ->get(route('admin.servers.index'))
        ->assertForbidden();
});

test('guest cannot view services page', function () {
    $server = Server::factory()->create();

    $this->get(route('server.services.index', $server))->assertRedirect(route('login', absolute: false));
});

test('user can view their own services page', function () {
    $server = Server::factory()->create(['user_id' => $this->user->id]);

    $this->actingAs($this->user)
        ->get(route('server.services.index', $server))
        ->assertSuccessful()
        ->assertSee('Dienstverwaltung')
        ->assertSee($server->name);
});

test('user cannot view another users services page', function () {
    $otherUser = User::factory()->create();
    $server = Server::factory()->create(['user_id' => $otherUser->id]);

    $this->actingAs($this->user)
        ->get(route('server.services.index', $server))
        ->assertForbidden();
});

test('user cannot install service on another users server', function () {
    $otherUser = User::factory()->create();
    $server = Server::factory()->create(['user_id' => $otherUser->id]);

    $this->actingAs($this->user)
        ->post(route('server.services.install', ['server' => $server, 'service' => 'php']))
        ->assertForbidden();
});

test('user cannot deinstall service on another users server', function () {
    $otherUser = User::factory()->create();
    $server = Server::factory()->create(['user_id' => $otherUser->id]);

    $this->actingAs($this->user)
        ->post(route('server.services.deinstall', ['server' => $server, 'service' => 'php']))
        ->assertForbidden();
});

test('service install returns error json', function () {
    $server = Server::factory()->create([
        'user_id' => $this->user->id,
        'host' => '127.0.0.1',
    ]);

    $this->actingAs($this->user)
        ->post(route('server.services.install', ['server' => $server, 'service' => 'php']))
        ->assertJsonStructure(['success']);
});

test('service deinstall returns error json', function () {
    $server = Server::factory()->create([
        'user_id' => $this->user->id,
        'host' => '127.0.0.1',
    ]);

    $this->actingAs($this->user)
        ->post(route('server.services.deinstall', ['server' => $server, 'service' => 'php']))
        ->assertJsonStructure(['success']);
});

test('service install stream returns live output and final status', function () {
    $server = Server::factory()->create(['user_id' => $this->user->id]);
    $engine = Mockery::mock(PushAgentEngine::class);

    $engine->shouldReceive('execute')
        ->once()
        ->withArgs(function (Server $serverArgument, string $command, int $timeout, bool $useSudo, callable $onOutput) use ($server): bool {
            expect($serverArgument->is($server))->toBeTrue();
            expect($command)->toContain('apt install php');

            $onOutput('stdout', "Paketlisten werden gelesen...\n");

            return $timeout === 300 && $useSudo === true;
        })
        ->andReturn(new ExecutionResult(
            stdout: 'Paketlisten werden gelesen...',
            stderr: '',
            exitCode: 0,
            success: true,
        ));

    $this->app->instance(PushAgentEngine::class, $engine);

    $response = $this->actingAs($this->user)
        ->post(route('server.services.install.stream', ['server' => $server, 'service' => 'php']))
        ->assertSuccessful();

    $content = $response->streamedContent();

    expect($content)
        ->toContain('Starte Ausf\u00fchrung')
        ->toContain('Paketlisten werden gelesen')
        ->toContain('PHP wurde installiert.');
});

test('user cannot deinstall service stream on another users server', function () {
    $otherUser = User::factory()->create();
    $server = Server::factory()->create(['user_id' => $otherUser->id]);

    $this->actingAs($this->user)
        ->post(route('server.services.deinstall.stream', ['server' => $server, 'service' => 'php']))
        ->assertForbidden();
});

test('unknown service returns not found', function () {
    $server = Server::factory()->create([
        'user_id' => $this->user->id,
        'host' => '127.0.0.1',
    ]);

    $this->actingAs($this->user)
        ->post(route('server.services.install', ['server' => $server, 'service' => 'unknown']))
        ->assertNotFound();
});

test('guest cannot view firewall page', function () {
    $server = Server::factory()->create();

    $this->get(route('server.firewall.index', $server))->assertRedirect(route('login', absolute: false));
});

test('user can view their own firewall page', function () {
    $server = Server::factory()->create(['user_id' => $this->user->id]);

    $this->actingAs($this->user)
        ->get(route('server.firewall.index', $server))
        ->assertSuccessful()
        ->assertSee('UFW-Firewall-Verwaltung')
        ->assertSee($server->name);
});

test('user cannot view another users firewall page', function () {
    $otherUser = User::factory()->create();
    $server = Server::factory()->create(['user_id' => $otherUser->id]);

    $this->actingAs($this->user)
        ->get(route('server.firewall.index', $server))
        ->assertForbidden();
});

test('guest cannot access firewall status', function () {
    $server = Server::factory()->create();

    $this->get(route('server.firewall.status', $server))->assertRedirect(route('login', absolute: false));
});

test('guest cannot access firewall rules', function () {
    $server = Server::factory()->create();

    $this->get(route('server.firewall.rules', $server))->assertRedirect(route('login', absolute: false));
});

test('firewall status returns json with success field', function () {
    $server = Server::factory()->create([
        'user_id' => $this->user->id,
        'host' => '127.0.0.1',
    ]);

    $this->actingAs($this->user)
        ->get(route('server.firewall.status', $server))
        ->assertJsonStructure(['success']);
});

test('firewall rules returns json with success field', function () {
    $server = Server::factory()->create([
        'user_id' => $this->user->id,
        'host' => '127.0.0.1',
    ]);

    $this->actingAs($this->user)
        ->get(route('server.firewall.rules', $server))
        ->assertJsonStructure(['success']);
});

test('firewall allow rejects invalid port', function () {
    $server = Server::factory()->create([
        'user_id' => $this->user->id,
        'host' => '127.0.0.1',
    ]);

    $this->actingAs($this->user)
        ->post(route('server.firewall.allow', $server), [
            'port' => 0,
            'protocol' => 'tcp',
        ])
        ->assertInvalid(['port']);
});

test('firewall allow rejects overflow port', function () {
    $server = Server::factory()->create([
        'user_id' => $this->user->id,
        'host' => '127.0.0.1',
    ]);

    $this->actingAs($this->user)
        ->post(route('server.firewall.allow', $server), [
            'port' => 65536,
            'protocol' => 'tcp',
        ])
        ->assertInvalid(['port']);
});

test('firewall allow returns json with success field', function () {
    $server = Server::factory()->create([
        'user_id' => $this->user->id,
        'host' => '127.0.0.1',
    ]);

    $this->actingAs($this->user)
        ->post(route('server.firewall.allow', $server), [
            'port' => 8080,
            'protocol' => 'tcp',
        ])
        ->assertJsonStructure(['success']);
});

test('firewall deny returns json with success field', function () {
    $server = Server::factory()->create([
        'user_id' => $this->user->id,
        'host' => '127.0.0.1',
    ]);

    $this->actingAs($this->user)
        ->post(route('server.firewall.deny', $server), [
            'port' => 8080,
            'protocol' => 'tcp',
        ])
        ->assertJsonStructure(['success']);
});

test('firewall enable returns json with success field', function () {
    $server = Server::factory()->create([
        'user_id' => $this->user->id,
        'host' => '127.0.0.1',
    ]);

    $this->actingAs($this->user)
        ->post(route('server.firewall.enable', $server))
        ->assertJsonStructure(['success']);
});

test('firewall disable returns json with success field', function () {
    $server = Server::factory()->create([
        'user_id' => $this->user->id,
        'host' => '127.0.0.1',
    ]);

    $this->actingAs($this->user)
        ->post(route('server.firewall.disable', $server))
        ->assertJsonStructure(['success']);
});

test('firewall destroy returns json with success field', function () {
    $server = Server::factory()->create([
        'user_id' => $this->user->id,
        'host' => '127.0.0.1',
    ]);

    $this->actingAs($this->user)
        ->delete(route('server.firewall.destroy', ['server' => $server, 'rule' => 1]))
        ->assertJsonStructure(['success']);
});

test('user cannot access another users firewall status', function () {
    $otherUser = User::factory()->create();
    $server = Server::factory()->create(['user_id' => $otherUser->id]);

    $this->actingAs($this->user)
        ->get(route('server.firewall.status', $server))
        ->assertForbidden();
});

test('user cannot access another users firewall rules', function () {
    $otherUser = User::factory()->create();
    $server = Server::factory()->create(['user_id' => $otherUser->id]);

    $this->actingAs($this->user)
        ->get(route('server.firewall.rules', $server))
        ->assertForbidden();
});

test('user cannot allow on another users firewall', function () {
    $otherUser = User::factory()->create();
    $server = Server::factory()->create(['user_id' => $otherUser->id]);

    $this->actingAs($this->user)
        ->post(route('server.firewall.allow', $server), [
            'port' => 80,
            'protocol' => 'tcp',
        ])
        ->assertForbidden();
});

test('user cannot deny on another users firewall', function () {
    $otherUser = User::factory()->create();
    $server = Server::factory()->create(['user_id' => $otherUser->id]);

    $this->actingAs($this->user)
        ->post(route('server.firewall.deny', $server), [
            'port' => 80,
            'protocol' => 'tcp',
        ])
        ->assertForbidden();
});

test('user cannot enable another users firewall', function () {
    $otherUser = User::factory()->create();
    $server = Server::factory()->create(['user_id' => $otherUser->id]);

    $this->actingAs($this->user)
        ->post(route('server.firewall.enable', $server))
        ->assertForbidden();
});

test('user cannot disable another users firewall', function () {
    $otherUser = User::factory()->create();
    $server = Server::factory()->create(['user_id' => $otherUser->id]);

    $this->actingAs($this->user)
        ->post(route('server.firewall.disable', $server))
        ->assertForbidden();
});

test('user cannot destroy rule on another users firewall', function () {
    $otherUser = User::factory()->create();
    $server = Server::factory()->create(['user_id' => $otherUser->id]);

    $this->actingAs($this->user)
        ->delete(route('server.firewall.destroy', ['server' => $server, 'rule' => 1]))
        ->assertForbidden();
});

// Apache
test('guest cannot view apache page', function () {
    $server = Server::factory()->create();

    $this->get(route('server.apache.index', $server))->assertRedirect(route('login', absolute: false));
});

test('user can view their own apache page', function () {
    $server = Server::factory()->create(['user_id' => $this->user->id]);

    $this->actingAs($this->user)
        ->get(route('server.apache.index', $server))
        ->assertSuccessful()
        ->assertSee('Apache-Webserver-Verwaltung')
        ->assertSee($server->name);
});

test('user cannot view another users apache page', function () {
    $otherUser = User::factory()->create();
    $server = Server::factory()->create(['user_id' => $otherUser->id]);

    $this->actingAs($this->user)
        ->get(route('server.apache.index', $server))
        ->assertForbidden();
});

test('apache status returns json with success field', function () {
    $server = Server::factory()->create([
        'user_id' => $this->user->id,
        'host' => '127.0.0.1',
    ]);

    $this->actingAs($this->user)
        ->get(route('server.apache.status', $server))
        ->assertJsonStructure(['success']);
});

test('apache install returns json with success field', function () {
    $server = Server::factory()->create([
        'user_id' => $this->user->id,
        'host' => '127.0.0.1',
    ]);

    $this->actingAs($this->user)
        ->post(route('server.apache.install', $server))
        ->assertJsonStructure(['success']);
});

test('apache deinstall returns json with success field', function () {
    $server = Server::factory()->create([
        'user_id' => $this->user->id,
        'host' => '127.0.0.1',
    ]);

    $this->actingAs($this->user)
        ->post(route('server.apache.deinstall', $server))
        ->assertJsonStructure(['success']);
});

test('apache service start returns json with success field', function () {
    $server = Server::factory()->create([
        'user_id' => $this->user->id,
        'host' => '127.0.0.1',
    ]);

    $this->actingAs($this->user)
        ->post(route('server.apache.service', ['server' => $server, 'action' => 'start']))
        ->assertJsonStructure(['success']);
});

test('apache configtest returns json with success field', function () {
    $server = Server::factory()->create([
        'user_id' => $this->user->id,
        'host' => '127.0.0.1',
    ]);

    $this->actingAs($this->user)
        ->post(route('server.apache.configtest', $server))
        ->assertJsonStructure(['success']);
});

test('apache sites returns json with success field', function () {
    $server = Server::factory()->create([
        'user_id' => $this->user->id,
        'host' => '127.0.0.1',
    ]);

    $this->actingAs($this->user)
        ->get(route('server.apache.sites', $server))
        ->assertJsonStructure(['success']);
});

test('apache modules returns json with success field', function () {
    $server = Server::factory()->create([
        'user_id' => $this->user->id,
        'host' => '127.0.0.1',
    ]);

    $this->actingAs($this->user)
        ->get(route('server.apache.modules', $server))
        ->assertJsonStructure(['success']);
});

test('apache create vhost validates domain', function () {
    $server = Server::factory()->create([
        'user_id' => $this->user->id,
        'host' => '127.0.0.1',
    ]);

    $this->actingAs($this->user)
        ->post(route('server.apache.vhost', $server), [
            'domain' => 'invalid domain!',
            'document_root' => '/var/www/test',
        ])
        ->assertInvalid(['domain']);
});

test('apache create vhost validates document_root', function () {
    $server = Server::factory()->create([
        'user_id' => $this->user->id,
        'host' => '127.0.0.1',
    ]);

    $this->actingAs($this->user)
        ->post(route('server.apache.vhost', $server), [
            'domain' => 'example.com',
            'document_root' => 'relative/path',
        ])
        ->assertInvalid(['document_root']);
});

test('apache create vhost returns json with success field', function () {
    $server = Server::factory()->create([
        'user_id' => $this->user->id,
        'host' => '127.0.0.1',
    ]);

    $this->actingAs($this->user)
        ->post(route('server.apache.vhost', $server), [
            'domain' => 'example.com',
            'document_root' => '/var/www/test',
        ])
        ->assertJsonStructure(['success']);
});

test('apache enable site returns json with success field', function () {
    $server = Server::factory()->create([
        'user_id' => $this->user->id,
        'host' => '127.0.0.1',
    ]);

    $this->actingAs($this->user)
        ->post(route('server.apache.sites.enable', ['server' => $server, 'site' => 'test.conf']))
        ->assertJsonStructure(['success']);
});

test('apache enable module returns json with success field', function () {
    $server = Server::factory()->create([
        'user_id' => $this->user->id,
        'host' => '127.0.0.1',
    ]);

    $this->actingAs($this->user)
        ->post(route('server.apache.modules.enable', ['server' => $server, 'module' => 'rewrite']))
        ->assertJsonStructure(['success']);
});

test('apache certbot install returns json with success field', function () {
    $server = Server::factory()->create([
        'user_id' => $this->user->id,
        'host' => '127.0.0.1',
    ]);

    $this->actingAs($this->user)
        ->post(route('server.apache.ssl.install-certbot', $server))
        ->assertJsonStructure(['success']);
});

test('apache obtain ssl validates fields', function () {
    $server = Server::factory()->create([
        'user_id' => $this->user->id,
        'host' => '127.0.0.1',
    ]);

    $this->actingAs($this->user)
        ->post(route('server.apache.ssl.obtain', $server), [
            'domain' => '',
            'email' => '',
        ])
        ->assertInvalid(['domain', 'email']);
});

// Permission tests
test('user cannot access another users apache status', function () {
    $otherUser = User::factory()->create();
    $server = Server::factory()->create(['user_id' => $otherUser->id]);

    $this->actingAs($this->user)
        ->get(route('server.apache.status', $server))
        ->assertForbidden();
});

test('user cannot install apache on another users server', function () {
    $otherUser = User::factory()->create();
    $server = Server::factory()->create(['user_id' => $otherUser->id]);

    $this->actingAs($this->user)
        ->post(route('server.apache.install', $server))
        ->assertForbidden();
});

test('user cannot start apache on another users server', function () {
    $otherUser = User::factory()->create();
    $server = Server::factory()->create(['user_id' => $otherUser->id]);

    $this->actingAs($this->user)
        ->post(route('server.apache.service', ['server' => $server, 'action' => 'start']))
        ->assertForbidden();
});

test('user cannot configtest another users apache', function () {
    $otherUser = User::factory()->create();
    $server = Server::factory()->create(['user_id' => $otherUser->id]);

    $this->actingAs($this->user)
        ->post(route('server.apache.configtest', $server))
        ->assertForbidden();
});

test('user cannot view another users apache sites', function () {
    $otherUser = User::factory()->create();
    $server = Server::factory()->create(['user_id' => $otherUser->id]);

    $this->actingAs($this->user)
        ->get(route('server.apache.sites', $server))
        ->assertForbidden();
});

test('user cannot vhost on another users server', function () {
    $otherUser = User::factory()->create();
    $server = Server::factory()->create(['user_id' => $otherUser->id]);

    $this->actingAs($this->user)
        ->post(route('server.apache.vhost', $server), [
            'domain' => 'example.com',
            'document_root' => '/var/www/test',
        ])
        ->assertForbidden();
});

// MySQL
test('guest cannot view mysql page', function () {
    $server = Server::factory()->create();

    $this->get(route('server.mysql.index', $server))->assertRedirect(route('login', absolute: false));
});

test('user can view their own mysql page', function () {
    $server = Server::factory()->create(['user_id' => $this->user->id]);

    $this->actingAs($this->user)
        ->get(route('server.mysql.index', $server))
        ->assertSuccessful()
        ->assertSee('MySQL-Datenbank-Verwaltung')
        ->assertSee($server->name);
});

test('user cannot view another users mysql page', function () {
    $otherUser = User::factory()->create();
    $server = Server::factory()->create(['user_id' => $otherUser->id]);

    $this->actingAs($this->user)
        ->get(route('server.mysql.index', $server))
        ->assertForbidden();
});

test('mysql status returns json with success', function () {
    $server = Server::factory()->create([
        'user_id' => $this->user->id,
        'host' => '127.0.0.1',
    ]);

    $this->actingAs($this->user)
        ->get(route('server.mysql.status', $server))
        ->assertJsonStructure(['success']);
});

test('mysql install returns json with success field', function () {
    $server = Server::factory()->create([
        'user_id' => $this->user->id,
        'host' => '127.0.0.1',
    ]);

    $this->actingAs($this->user)
        ->post(route('server.mysql.install', $server))
        ->assertJsonStructure(['success']);
});

test('mysql deinstall returns json with success field', function () {
    $server = Server::factory()->create([
        'user_id' => $this->user->id,
        'host' => '127.0.0.1',
    ]);

    $this->actingAs($this->user)
        ->post(route('server.mysql.deinstall', $server))
        ->assertJsonStructure(['success']);
});

test('mysql service start returns json with success field', function () {
    $server = Server::factory()->create([
        'user_id' => $this->user->id,
        'host' => '127.0.0.1',
    ]);

    $this->actingAs($this->user)
        ->post(route('server.mysql.service', ['server' => $server, 'action' => 'start']))
        ->assertJsonStructure(['success']);
});

test('mysql databases returns json with success field', function () {
    $server = Server::factory()->create([
        'user_id' => $this->user->id,
        'host' => '127.0.0.1',
    ]);

    $this->actingAs($this->user)
        ->get(route('server.mysql.databases', $server))
        ->assertJsonStructure(['success']);
});

test('mysql users returns json with success field', function () {
    $server = Server::factory()->create([
        'user_id' => $this->user->id,
        'host' => '127.0.0.1',
    ]);

    $this->actingAs($this->user)
        ->get(route('server.mysql.users', $server))
        ->assertJsonStructure(['success']);
});

test('mysql create database validates name', function () {
    $server = Server::factory()->create([
        'user_id' => $this->user->id,
        'host' => '127.0.0.1',
    ]);

    $this->actingAs($this->user)
        ->post(route('server.mysql.databases.create', $server), [
            'db_name' => '',
        ])
        ->assertInvalid(['db_name']);
});

test('mysql create database validates invalid name', function () {
    $server = Server::factory()->create([
        'user_id' => $this->user->id,
        'host' => '127.0.0.1',
    ]);

    $this->actingAs($this->user)
        ->post(route('server.mysql.databases.create', $server), [
            'db_name' => 'invalid name with spaces!',
        ])
        ->assertInvalid(['db_name']);
});

test('mysql create database returns json with success field', function () {
    $server = Server::factory()->create([
        'user_id' => $this->user->id,
        'host' => '127.0.0.1',
    ]);

    $this->actingAs($this->user)
        ->post(route('server.mysql.databases.create', $server), [
            'db_name' => 'test_db',
        ])
        ->assertJsonStructure(['success']);
});

test('mysql create user validates fields', function () {
    $server = Server::factory()->create([
        'user_id' => $this->user->id,
        'host' => '127.0.0.1',
    ]);

    $this->actingAs($this->user)
        ->post(route('server.mysql.users.create', $server), [
            'username' => '',
            'host' => '',
            'password' => '',
        ])
        ->assertInvalid(['username', 'host', 'password']);
});

test('mysql create user returns json with success field', function () {
    $server = Server::factory()->create([
        'user_id' => $this->user->id,
        'host' => '127.0.0.1',
    ]);

    $this->actingAs($this->user)
        ->post(route('server.mysql.users.create', $server), [
            'username' => 'testuser',
            'host' => 'localhost',
            'password' => 'secret',
        ])
        ->assertJsonStructure(['success']);
});

test('mysql set password validates field', function () {
    $server = Server::factory()->create([
        'user_id' => $this->user->id,
        'host' => '127.0.0.1',
    ]);

    $this->actingAs($this->user)
        ->post(route('server.mysql.users.password', ['server' => $server, 'username' => 'test', 'host' => 'localhost']), [
            'password' => '',
        ])
        ->assertInvalid(['password']);
});

test('mysql grant all returns json with success field', function () {
    $server = Server::factory()->create([
        'user_id' => $this->user->id,
        'host' => '127.0.0.1',
    ]);

    $this->actingAs($this->user)
        ->post(route('server.mysql.users.grant', ['server' => $server, 'username' => 'test', 'host' => 'localhost']))
        ->assertJsonStructure(['success']);
});

test('mysql drop database returns json with success field', function () {
    $server = Server::factory()->create([
        'user_id' => $this->user->id,
        'host' => '127.0.0.1',
    ]);

    $this->actingAs($this->user)
        ->delete(route('server.mysql.databases.destroy', ['server' => $server, 'database' => 'test_db']))
        ->assertJsonStructure(['success']);
});

test('mysql drop user returns json with success field', function () {
    $server = Server::factory()->create([
        'user_id' => $this->user->id,
        'host' => '127.0.0.1',
    ]);

    $this->actingAs($this->user)
        ->delete(route('server.mysql.users.destroy', ['server' => $server, 'username' => 'test', 'host' => 'localhost']))
        ->assertJsonStructure(['success']);
});

// Permission tests
test('user cannot view another users mysql status', function () {
    $otherUser = User::factory()->create();
    $server = Server::factory()->create(['user_id' => $otherUser->id]);

    $this->actingAs($this->user)
        ->get(route('server.mysql.status', $server))
        ->assertForbidden();
});

test('user cannot install mysql on another users server', function () {
    $otherUser = User::factory()->create();
    $server = Server::factory()->create(['user_id' => $otherUser->id]);

    $this->actingAs($this->user)
        ->post(route('server.mysql.install', $server))
        ->assertForbidden();
});

test('user cannot start mysql on another users server', function () {
    $otherUser = User::factory()->create();
    $server = Server::factory()->create(['user_id' => $otherUser->id]);

    $this->actingAs($this->user)
        ->post(route('server.mysql.service', ['server' => $server, 'action' => 'start']))
        ->assertForbidden();
});

test('user cannot view another users mysql databases', function () {
    $otherUser = User::factory()->create();
    $server = Server::factory()->create(['user_id' => $otherUser->id]);

    $this->actingAs($this->user)
        ->get(route('server.mysql.databases', $server))
        ->assertForbidden();
});

test('user cannot create database on another users server', function () {
    $otherUser = User::factory()->create();
    $server = Server::factory()->create(['user_id' => $otherUser->id]);

    $this->actingAs($this->user)
        ->post(route('server.mysql.databases.create', $server), ['db_name' => 'test'])
        ->assertForbidden();
});

// GitHub
test('guest cannot view github page', function () {
    $server = Server::factory()->create();

    $this->get(route('server.github.index', $server))->assertRedirect(route('login', absolute: false));
});

test('user can view their own github page', function () {
    $server = Server::factory()->create(['user_id' => $this->user->id]);

    $this->actingAs($this->user)
        ->get(route('server.github.index', $server))
        ->assertSuccessful()
        ->assertSee('GitHub-Deployment')
        ->assertSee($server->name);
});

test('user cannot view another users github page', function () {
    $otherUser = User::factory()->create();
    $server = Server::factory()->create(['user_id' => $otherUser->id]);

    $this->actingAs($this->user)
        ->get(route('server.github.index', $server))
        ->assertForbidden();
});

test('github deploy validates repo_url', function () {
    $server = Server::factory()->create([
        'user_id' => $this->user->id,
        'host' => '127.0.0.1',
    ]);

    $this->actingAs($this->user)
        ->post(route('server.github.deploy', $server), [
            'repo_url' => '',
            'host' => 'example.com',
            'target_name' => 'myproject',
        ])
        ->assertInvalid(['repo_url']);
});

test('github deploy validates host', function () {
    $server = Server::factory()->create([
        'user_id' => $this->user->id,
        'host' => '127.0.0.1',
    ]);

    $this->actingAs($this->user)
        ->post(route('server.github.deploy', $server), [
            'repo_url' => 'https://github.com/owner/repo.git',
            'host' => '',
            'target_name' => 'myproject',
        ])
        ->assertInvalid(['host']);
});

test('github deploy validates target_name', function () {
    $server = Server::factory()->create([
        'user_id' => $this->user->id,
        'host' => '127.0.0.1',
    ]);

    $this->actingAs($this->user)
        ->post(route('server.github.deploy', $server), [
            'repo_url' => 'https://github.com/owner/repo.git',
            'host' => 'example.com',
            'target_name' => '',
        ])
        ->assertInvalid(['target_name']);
});

test('github deploy returns json with success field', function () {
    $server = Server::factory()->create([
        'user_id' => $this->user->id,
        'host' => '127.0.0.1',
    ]);

    $this->actingAs($this->user)
        ->post(route('server.github.deploy', $server), [
            'repo_url' => 'https://github.com/owner/repo.git',
            'host' => 'example.com',
            'target_name' => 'myproject',
        ])
        ->assertJsonStructure(['success']);
});

test('github deploy rejects non-https url', function () {
    $server = Server::factory()->create([
        'user_id' => $this->user->id,
        'host' => '127.0.0.1',
    ]);

    $this->actingAs($this->user)
        ->post(route('server.github.deploy', $server), [
            'repo_url' => 'git@github.com:owner/repo.git',
            'host' => 'example.com',
            'target_name' => 'myproject',
        ])
        ->assertJsonStructure(['success']);
});

test('user cannot deploy on another users server', function () {
    $otherUser = User::factory()->create();
    $server = Server::factory()->create(['user_id' => $otherUser->id]);

    $this->actingAs($this->user)
        ->post(route('server.github.deploy', $server), [
            'repo_url' => 'https://github.com/owner/repo.git',
            'host' => 'example.com',
            'target_name' => 'myproject',
        ])
        ->assertForbidden();
});
