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
