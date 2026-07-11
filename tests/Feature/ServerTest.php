<?php

use App\Models\Server;
use App\Models\User;
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
            'port' => 22,
            'username' => 'root',
            'auth_type' => 'key',
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
            'port' => $server->port,
            'username' => $server->username,
            'auth_type' => $server->auth_type,
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
            'port' => $server->port,
            'username' => $server->username,
            'auth_type' => $server->auth_type,
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
            'port' => 22,
            'username' => '',
            'auth_type' => '',
        ])
        ->assertInvalid(['name', 'host', 'username', 'auth_type']);
});

test('user can create server with use_sudo and key_path', function () {
    $this->actingAs($this->user)
        ->post(route('server.store'), [
            'name' => 'Sudo Server',
            'host' => '10.0.0.1',
            'port' => 2222,
            'username' => 'admin',
            'auth_type' => 'key',
            'key_path' => '/home/user/.ssh/id_ed25519',
            'use_sudo' => '1',
            'notes' => 'Test server',
        ])
        ->assertRedirect(route('server.index', absolute: false));

    $this->assertDatabaseHas('servers', [
        'user_id' => $this->user->id,
        'name' => 'Sudo Server',
        'host' => '10.0.0.1',
        'port' => 2222,
        'username' => 'admin',
        'auth_type' => 'key',
        'key_path' => '/home/user/.ssh/id_ed25519',
        'use_sudo' => 1,
        'notes' => 'Test server',
    ]);
});

test('user can create server with password auth', function () {
    $this->actingAs($this->user)
        ->post(route('server.store'), [
            'name' => 'Password Server',
            'host' => '192.168.1.100',
            'port' => 22,
            'username' => 'root',
            'auth_type' => 'password',
            'credentials' => 'secret-password',
            'use_sudo' => '0',
        ])
        ->assertRedirect(route('server.index', absolute: false));

    $this->assertDatabaseHas('servers', [
        'user_id' => $this->user->id,
        'name' => 'Password Server',
        'auth_type' => 'password',
        'use_sudo' => 0,
    ]);
});

test('guest cannot view server dashboard', function () {
    $server = Server::factory()->create();

    $this->get(route('server.dashboard', $server))->assertRedirect(route('login', absolute: false));
});

test('user can view their own server dashboard', function () {
    $server = Server::factory()->create(['user_id' => $this->user->id]);

    $this->actingAs($this->user)
        ->get(route('server.dashboard', $server))
        ->assertSuccessful()
        ->assertSee('Server Dashboard')
        ->assertSee($server->name)
        ->assertSee('System')
        ->assertSee('Dienste')
        ->assertSee('Firewall')
        ->assertSee('Apache')
        ->assertSee('MySQL')
        ->assertSee('GitHub')
        ->assertSee('Terminal');
});

test('user cannot view another users server dashboard', function () {
    $otherUser = User::factory()->create();
    $server = Server::factory()->create(['user_id' => $otherUser->id]);

    $this->actingAs($this->user)
        ->get(route('server.dashboard', $server))
        ->assertForbidden();
});

test('guest cannot refresh server dashboard', function () {
    $server = Server::factory()->create();

    $this->get(route('server.dashboard.refresh', $server))->assertRedirect(route('login', absolute: false));
});

test('user cannot refresh another users server dashboard', function () {
    $otherUser = User::factory()->create();
    $server = Server::factory()->create(['user_id' => $otherUser->id]);

    $this->actingAs($this->user)
        ->get(route('server.dashboard.refresh', $server))
        ->assertForbidden();
});

test('user can update server with sudo disabled', function () {
    $server = Server::factory()->create([
        'user_id' => $this->user->id,
        'use_sudo' => true,
    ]);

    $this->actingAs($this->user)
        ->put(route('server.update', $server), [
            'name' => $server->name,
            'host' => $server->host,
            'port' => $server->port,
            'username' => $server->username,
            'auth_type' => $server->auth_type,
            'use_sudo' => '0',
        ])
        ->assertRedirect(route('server.index', absolute: false));

    expect($server->refresh()->use_sudo)->toBeFalse();
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
        'port' => 1,
        'username' => 'test',
        'auth_type' => 'password',
        'credentials' => 'test',
    ]);

    $this->actingAs($this->user)
        ->post(route('server.services.install', ['server' => $server, 'service' => 'php']))
        ->assertJson(['success' => false]);
});

test('service deinstall returns error json', function () {
    $server = Server::factory()->create([
        'user_id' => $this->user->id,
        'host' => '127.0.0.1',
        'port' => 1,
        'username' => 'test',
        'auth_type' => 'password',
        'credentials' => 'test',
    ]);

    $this->actingAs($this->user)
        ->post(route('server.services.deinstall', ['server' => $server, 'service' => 'php']))
        ->assertJson(['success' => false]);
});

test('unknown service returns not found', function () {
    $server = Server::factory()->create([
        'user_id' => $this->user->id,
        'host' => '127.0.0.1',
        'auth_type' => 'password',
        'credentials' => 'test',
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
        'port' => 1,
        'username' => 'test',
        'auth_type' => 'password',
        'credentials' => 'test',
    ]);

    $this->actingAs($this->user)
        ->get(route('server.firewall.status', $server))
        ->assertJsonStructure(['success']);
});

test('firewall rules returns json with success field', function () {
    $server = Server::factory()->create([
        'user_id' => $this->user->id,
        'host' => '127.0.0.1',
        'port' => 1,
        'username' => 'test',
        'auth_type' => 'password',
        'credentials' => 'test',
    ]);

    $this->actingAs($this->user)
        ->get(route('server.firewall.rules', $server))
        ->assertJsonStructure(['success']);
});

test('firewall allow rejects invalid port', function () {
    $server = Server::factory()->create([
        'user_id' => $this->user->id,
        'host' => '127.0.0.1',
        'auth_type' => 'password',
        'credentials' => 'test',
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
        'auth_type' => 'password',
        'credentials' => 'test',
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
        'port' => 1,
        'username' => 'test',
        'auth_type' => 'password',
        'credentials' => 'test',
    ]);

    $this->actingAs($this->user)
        ->post(route('server.firewall.allow', $server), [
            'port' => 8080,
            'protocol' => 'tcp',
        ])
        ->assertJson(['success' => false]);
});

test('firewall deny returns json with success field', function () {
    $server = Server::factory()->create([
        'user_id' => $this->user->id,
        'host' => '127.0.0.1',
        'port' => 1,
        'username' => 'test',
        'auth_type' => 'password',
        'credentials' => 'test',
    ]);

    $this->actingAs($this->user)
        ->post(route('server.firewall.deny', $server), [
            'port' => 8080,
            'protocol' => 'tcp',
        ])
        ->assertJson(['success' => false]);
});

test('firewall enable returns json with success field', function () {
    $server = Server::factory()->create([
        'user_id' => $this->user->id,
        'host' => '127.0.0.1',
        'port' => 1,
        'username' => 'test',
        'auth_type' => 'password',
        'credentials' => 'test',
    ]);

    $this->actingAs($this->user)
        ->post(route('server.firewall.enable', $server))
        ->assertJson(['success' => false]);
});

test('firewall disable returns json with success field', function () {
    $server = Server::factory()->create([
        'user_id' => $this->user->id,
        'host' => '127.0.0.1',
        'port' => 1,
        'username' => 'test',
        'auth_type' => 'password',
        'credentials' => 'test',
    ]);

    $this->actingAs($this->user)
        ->post(route('server.firewall.disable', $server))
        ->assertJson(['success' => false]);
});

test('firewall destroy returns json with success field', function () {
    $server = Server::factory()->create([
        'user_id' => $this->user->id,
        'host' => '127.0.0.1',
        'port' => 1,
        'username' => 'test',
        'auth_type' => 'password',
        'credentials' => 'test',
    ]);

    $this->actingAs($this->user)
        ->delete(route('server.firewall.destroy', ['server' => $server, 'rule' => 1]))
        ->assertJson(['success' => false]);
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
        'port' => 1,
        'username' => 'test',
        'auth_type' => 'password',
        'credentials' => 'test',
    ]);

    $this->actingAs($this->user)
        ->get(route('server.apache.status', $server))
        ->assertJsonStructure(['success']);
});

test('apache install returns json with success field', function () {
    $server = Server::factory()->create([
        'user_id' => $this->user->id,
        'host' => '127.0.0.1',
        'port' => 1,
        'username' => 'test',
        'auth_type' => 'password',
        'credentials' => 'test',
    ]);

    $this->actingAs($this->user)
        ->post(route('server.apache.install', $server))
        ->assertJson(['success' => false]);
});

test('apache deinstall returns json with success field', function () {
    $server = Server::factory()->create([
        'user_id' => $this->user->id,
        'host' => '127.0.0.1',
        'port' => 1,
        'username' => 'test',
        'auth_type' => 'password',
        'credentials' => 'test',
    ]);

    $this->actingAs($this->user)
        ->post(route('server.apache.deinstall', $server))
        ->assertJson(['success' => false]);
});

test('apache service start returns json with success field', function () {
    $server = Server::factory()->create([
        'user_id' => $this->user->id,
        'host' => '127.0.0.1',
        'port' => 1,
        'username' => 'test',
        'auth_type' => 'password',
        'credentials' => 'test',
    ]);

    $this->actingAs($this->user)
        ->post(route('server.apache.service', ['server' => $server, 'action' => 'start']))
        ->assertJson(['success' => false]);
});

test('apache configtest returns json with success field', function () {
    $server = Server::factory()->create([
        'user_id' => $this->user->id,
        'host' => '127.0.0.1',
        'port' => 1,
        'username' => 'test',
        'auth_type' => 'password',
        'credentials' => 'test',
    ]);

    $this->actingAs($this->user)
        ->post(route('server.apache.configtest', $server))
        ->assertJsonStructure(['success']);
});

test('apache sites returns json with success field', function () {
    $server = Server::factory()->create([
        'user_id' => $this->user->id,
        'host' => '127.0.0.1',
        'port' => 1,
        'username' => 'test',
        'auth_type' => 'password',
        'credentials' => 'test',
    ]);

    $this->actingAs($this->user)
        ->get(route('server.apache.sites', $server))
        ->assertJsonStructure(['success']);
});

test('apache modules returns json with success field', function () {
    $server = Server::factory()->create([
        'user_id' => $this->user->id,
        'host' => '127.0.0.1',
        'port' => 1,
        'username' => 'test',
        'auth_type' => 'password',
        'credentials' => 'test',
    ]);

    $this->actingAs($this->user)
        ->get(route('server.apache.modules', $server))
        ->assertJsonStructure(['success']);
});

test('apache create vhost validates domain', function () {
    $server = Server::factory()->create([
        'user_id' => $this->user->id,
        'host' => '127.0.0.1',
        'auth_type' => 'password',
        'credentials' => 'test',
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
        'auth_type' => 'password',
        'credentials' => 'test',
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
        'port' => 1,
        'username' => 'test',
        'auth_type' => 'password',
        'credentials' => 'test',
    ]);

    $this->actingAs($this->user)
        ->post(route('server.apache.vhost', $server), [
            'domain' => 'example.com',
            'document_root' => '/var/www/test',
        ])
        ->assertJson(['success' => false]);
});

test('apache enable site returns json with success field', function () {
    $server = Server::factory()->create([
        'user_id' => $this->user->id,
        'host' => '127.0.0.1',
        'port' => 1,
        'username' => 'test',
        'auth_type' => 'password',
        'credentials' => 'test',
    ]);

    $this->actingAs($this->user)
        ->post(route('server.apache.sites.enable', ['server' => $server, 'site' => 'test.conf']))
        ->assertJson(['success' => false]);
});

test('apache enable module returns json with success field', function () {
    $server = Server::factory()->create([
        'user_id' => $this->user->id,
        'host' => '127.0.0.1',
        'port' => 1,
        'username' => 'test',
        'auth_type' => 'password',
        'credentials' => 'test',
    ]);

    $this->actingAs($this->user)
        ->post(route('server.apache.modules.enable', ['server' => $server, 'module' => 'rewrite']))
        ->assertJson(['success' => false]);
});

test('apache certbot install returns json with success field', function () {
    $server = Server::factory()->create([
        'user_id' => $this->user->id,
        'host' => '127.0.0.1',
        'port' => 1,
        'username' => 'test',
        'auth_type' => 'password',
        'credentials' => 'test',
    ]);

    $this->actingAs($this->user)
        ->post(route('server.apache.ssl.install-certbot', $server))
        ->assertJson(['success' => false]);
});

test('apache obtain ssl validates fields', function () {
    $server = Server::factory()->create([
        'user_id' => $this->user->id,
        'host' => '127.0.0.1',
        'auth_type' => 'password',
        'credentials' => 'test',
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
        'port' => 1,
        'username' => 'test',
        'auth_type' => 'password',
        'credentials' => 'test',
    ]);

    $this->actingAs($this->user)
        ->get(route('server.mysql.status', $server))
        ->assertJsonStructure(['success']);
});

test('mysql install returns json with success field', function () {
    $server = Server::factory()->create([
        'user_id' => $this->user->id,
        'host' => '127.0.0.1',
        'port' => 1,
        'username' => 'test',
        'auth_type' => 'password',
        'credentials' => 'test',
    ]);

    $this->actingAs($this->user)
        ->post(route('server.mysql.install', $server))
        ->assertJson(['success' => false]);
});

test('mysql deinstall returns json with success field', function () {
    $server = Server::factory()->create([
        'user_id' => $this->user->id,
        'host' => '127.0.0.1',
        'port' => 1,
        'username' => 'test',
        'auth_type' => 'password',
        'credentials' => 'test',
    ]);

    $this->actingAs($this->user)
        ->post(route('server.mysql.deinstall', $server))
        ->assertJson(['success' => false]);
});

test('mysql service start returns json with success field', function () {
    $server = Server::factory()->create([
        'user_id' => $this->user->id,
        'host' => '127.0.0.1',
        'port' => 1,
        'username' => 'test',
        'auth_type' => 'password',
        'credentials' => 'test',
    ]);

    $this->actingAs($this->user)
        ->post(route('server.mysql.service', ['server' => $server, 'action' => 'start']))
        ->assertJson(['success' => false]);
});

test('mysql databases returns json with success field', function () {
    $server = Server::factory()->create([
        'user_id' => $this->user->id,
        'host' => '127.0.0.1',
        'port' => 1,
        'username' => 'test',
        'auth_type' => 'password',
        'credentials' => 'test',
    ]);

    $this->actingAs($this->user)
        ->get(route('server.mysql.databases', $server))
        ->assertJsonStructure(['success']);
});

test('mysql users returns json with success field', function () {
    $server = Server::factory()->create([
        'user_id' => $this->user->id,
        'host' => '127.0.0.1',
        'port' => 1,
        'username' => 'test',
        'auth_type' => 'password',
        'credentials' => 'test',
    ]);

    $this->actingAs($this->user)
        ->get(route('server.mysql.users', $server))
        ->assertJsonStructure(['success']);
});

test('mysql create database validates name', function () {
    $server = Server::factory()->create([
        'user_id' => $this->user->id,
        'host' => '127.0.0.1',
        'auth_type' => 'password',
        'credentials' => 'test',
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
        'auth_type' => 'password',
        'credentials' => 'test',
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
        'port' => 1,
        'username' => 'test',
        'auth_type' => 'password',
        'credentials' => 'test',
    ]);

    $this->actingAs($this->user)
        ->post(route('server.mysql.databases.create', $server), [
            'db_name' => 'test_db',
        ])
        ->assertJson(['success' => false]);
});

test('mysql create user validates fields', function () {
    $server = Server::factory()->create([
        'user_id' => $this->user->id,
        'host' => '127.0.0.1',
        'auth_type' => 'password',
        'credentials' => 'test',
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
        'port' => 1,
        'username' => 'test',
        'auth_type' => 'password',
        'credentials' => 'test',
    ]);

    $this->actingAs($this->user)
        ->post(route('server.mysql.users.create', $server), [
            'username' => 'testuser',
            'host' => 'localhost',
            'password' => 'secret',
        ])
        ->assertJson(['success' => false]);
});

test('mysql set password validates field', function () {
    $server = Server::factory()->create([
        'user_id' => $this->user->id,
        'host' => '127.0.0.1',
        'auth_type' => 'password',
        'credentials' => 'test',
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
        'port' => 1,
        'username' => 'test',
        'auth_type' => 'password',
        'credentials' => 'test',
    ]);

    $this->actingAs($this->user)
        ->post(route('server.mysql.users.grant', ['server' => $server, 'username' => 'test', 'host' => 'localhost']))
        ->assertJson(['success' => false]);
});

test('mysql drop database returns json with success field', function () {
    $server = Server::factory()->create([
        'user_id' => $this->user->id,
        'host' => '127.0.0.1',
        'port' => 1,
        'username' => 'test',
        'auth_type' => 'password',
        'credentials' => 'test',
    ]);

    $this->actingAs($this->user)
        ->delete(route('server.mysql.databases.destroy', ['server' => $server, 'database' => 'test_db']))
        ->assertJson(['success' => false]);
});

test('mysql drop user returns json with success field', function () {
    $server = Server::factory()->create([
        'user_id' => $this->user->id,
        'host' => '127.0.0.1',
        'port' => 1,
        'username' => 'test',
        'auth_type' => 'password',
        'credentials' => 'test',
    ]);

    $this->actingAs($this->user)
        ->delete(route('server.mysql.users.destroy', ['server' => $server, 'username' => 'test', 'host' => 'localhost']))
        ->assertJson(['success' => false]);
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
        'auth_type' => 'password',
        'credentials' => 'test',
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
        'auth_type' => 'password',
        'credentials' => 'test',
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
        'auth_type' => 'password',
        'credentials' => 'test',
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
        'port' => 1,
        'username' => 'test',
        'auth_type' => 'password',
        'credentials' => 'test',
    ]);

    $this->actingAs($this->user)
        ->post(route('server.github.deploy', $server), [
            'repo_url' => 'https://github.com/owner/repo.git',
            'host' => 'example.com',
            'target_name' => 'myproject',
        ])
        ->assertJson(['success' => false]);
});

test('github deploy rejects non-https url', function () {
    $server = Server::factory()->create([
        'user_id' => $this->user->id,
        'host' => '127.0.0.1',
        'port' => 1,
        'username' => 'test',
        'auth_type' => 'password',
        'credentials' => 'test',
    ]);

    $this->actingAs($this->user)
        ->post(route('server.github.deploy', $server), [
            'repo_url' => 'git@github.com:owner/repo.git',
            'host' => 'example.com',
            'target_name' => 'myproject',
        ])
        ->assertJson(['success' => false]);
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
