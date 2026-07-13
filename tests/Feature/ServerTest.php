<?php

use App\Models\Server;
use App\Models\ServerAgentCommand;
use App\Models\ServerCronjob;
use App\Models\ServerMetric;
use App\Models\User;
use App\Services\ExecutionEngine\ExecutionResult;
use App\Services\ExecutionEngine\PushAgentEngine;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Http;
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
        ->assertSee('Web Server')
        ->assertSee('Bitte zuerst Server auswählen')
        ->assertSee('System')
        ->assertSee('Dienste')
        ->assertSee('Firewall')
        ->assertSee('Daten')
        ->assertSee('Unwichtig')
        ->assertSee('Bank')
        ->assertSee('Inventory');
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
        ->assertSee('Server hinzufügen')
        ->assertSee('agent_public_url', false);
});

test('user can view edit server form', function () {
    $server = Server::factory()->create(['user_id' => $this->user->id]);

    $this->actingAs($this->user)
        ->get(route('server.edit', $server))
        ->assertSuccessful()
        ->assertSee('Server bearbeiten')
        ->assertSee('agent_public_url', false);
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

test('user can create server with agent public url', function () {
    $this->actingAs($this->user)
        ->post(route('server.store'), [
            'name' => 'Public Agent Server',
            'host' => '10.0.0.1',
            'agent_port' => 9400,
            'agent_public_url' => 'https://agent.example.com/',
        ])
        ->assertRedirect(route('server.index', absolute: false));

    $this->assertDatabaseHas('servers', [
        'user_id' => $this->user->id,
        'name' => 'Public Agent Server',
        'agent_public_url' => 'https://agent.example.com',
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
        ->assertSee('System-Aktionen')
        ->assertSee(route('server.terminal', $server), false)
        ->assertSee(route('server.nginx.index', $server), false)
        ->assertSee(route('server.services.index', $server), false)
        ->assertSee(route('server.agent.metrics', $server), false)
        ->assertSee('setInterval(fetchMetrics, metricsRefreshMs)', false)
        ->assertSee('const metricsRefreshMs = 15000', false)
        ->assertSee('clearInterval(metricsRefreshTimer)', false)
        ->assertSee('Dienste')
        ->assertSee('Unwichtig');
});

test('guest cannot view server terminal', function () {
    $server = Server::factory()->create();

    $this->get(route('server.terminal', $server))->assertRedirect(route('login', absolute: false));
});

test('user can view their own server terminal', function () {
    $server = Server::factory()->create(['user_id' => $this->user->id]);

    $this->actingAs($this->user)
        ->get(route('server.terminal', $server))
        ->assertSuccessful()
        ->assertSee('Freies Terminal')
        ->assertSee($server->name)
        ->assertSee(route('server.agent.terminal-token', $server), false)
        ->assertSee('terminal-root', false)
        ->assertSee('Interaktive Shell')
        ->assertDontSee('body.getReader()', false)
        ->assertDontSee('terminal-command', false)
        ->assertDontSee('use_sudo', false);
});

test('user cannot view another users server terminal', function () {
    $otherUser = User::factory()->create();
    $server = Server::factory()->create(['user_id' => $otherUser->id]);

    $this->actingAs($this->user)
        ->get(route('server.terminal', $server))
        ->assertForbidden();
});

test('guest cannot view server monitoring', function () {
    $server = Server::factory()->create();

    $this->get(route('server.monitoring.index', $server))->assertRedirect(route('login', absolute: false));
});

test('user can view their own server monitoring', function () {
    $server = Server::factory()->create(['user_id' => $this->user->id]);

    $this->actingAs($this->user)
        ->get(route('server.monitoring.index', $server))
        ->assertSuccessful()
        ->assertSee('Prozesse &amp; Services', false)
        ->assertSee(route('server.monitoring.processes', $server), false)
        ->assertSee(route('server.monitoring.services', $server), false)
        ->assertSee(route('server.monitoring.processes.kill', $server), false)
        ->assertSee(route('server.monitoring.services.action', $server), false);
});

test('user cannot view another users server monitoring', function () {
    $otherUser = User::factory()->create();
    $server = Server::factory()->create(['user_id' => $otherUser->id]);

    $this->actingAs($this->user)
        ->get(route('server.monitoring.index', $server))
        ->assertForbidden();
});

test('server monitoring proxies process and service data', function () {
    $server = Server::factory()->withAgent()->create([
        'user_id' => $this->user->id,
        'host' => '127.0.0.1',
        'agent_port' => 9300,
    ]);

    Http::fake([
        'http://127.0.0.1:9300/actions' => Http::sequence()
            ->push([
                'success' => true,
                'action' => 'monitoring.processes',
                'exit_code' => 0,
                'stdout' => json_encode([['pid' => '1', 'ppid' => '0', 'user' => 'root', 'stat' => 'Ss', 'cpu' => '0.1', 'mem' => '0.2', 'command' => '/sbin/init']]),
                'stderr' => '',
                'duration_ms' => 10,
            ])
            ->push([
                'success' => true,
                'action' => 'monitoring.services',
                'exit_code' => 0,
                'stdout' => "nginx.service loaded active running nginx\n",
                'stderr' => '',
                'duration_ms' => 10,
            ]),
    ]);

    $this->actingAs($this->user)
        ->getJson(route('server.monitoring.processes', $server))
        ->assertSuccessful()
        ->assertJsonPath('action', 'monitoring.processes')
        ->assertJsonPath('stdout', json_encode([['pid' => '1', 'ppid' => '0', 'user' => 'root', 'stat' => 'Ss', 'cpu' => '0.1', 'mem' => '0.2', 'command' => '/sbin/init']]));

    $this->actingAs($this->user)
        ->getJson(route('server.monitoring.services', $server))
        ->assertSuccessful()
        ->assertJsonPath('action', 'monitoring.services')
        ->assertJsonPath('stdout', "nginx.service loaded active running nginx\n");
});

test('server monitoring can control service and kill process', function () {
    $server = Server::factory()->withAgent()->create([
        'user_id' => $this->user->id,
        'host' => '127.0.0.1',
        'agent_port' => 9300,
    ]);

    Http::fake([
        'http://127.0.0.1:9300/actions' => Http::sequence()
            ->push([
                'success' => true,
                'action' => 'monitoring.service_restart',
                'exit_code' => 0,
                'stdout' => '',
                'stderr' => '',
                'duration_ms' => 10,
            ])
            ->push([
                'success' => true,
                'action' => 'monitoring.process_kill',
                'exit_code' => 0,
                'stdout' => '',
                'stderr' => '',
                'duration_ms' => 10,
            ]),
    ]);

    $this->actingAs($this->user)
        ->postJson(route('server.monitoring.services.action', $server), [
            'service' => 'nginx.service',
            'action' => 'restart',
        ])
        ->assertSuccessful()
        ->assertJsonPath('action', 'monitoring.service_restart');

    $this->actingAs($this->user)
        ->postJson(route('server.monitoring.processes.kill', $server), [
            'pid' => 123,
        ])
        ->assertSuccessful()
        ->assertJsonPath('action', 'monitoring.process_kill');
});

test('server monitoring validates mutation payloads', function () {
    $server = Server::factory()->withAgent()->create([
        'user_id' => $this->user->id,
    ]);

    $this->actingAs($this->user)
        ->postJson(route('server.monitoring.services.action', $server), [
            'service' => 'nginx;reboot.service',
            'action' => 'restart',
        ])
        ->assertInvalid(['service']);

    $this->actingAs($this->user)
        ->postJson(route('server.monitoring.processes.kill', $server), [
            'pid' => 1,
        ])
        ->assertInvalid(['pid']);
});

test('guest cannot view server cronjobs', function () {
    $server = Server::factory()->create();

    $this->get(route('server.cronjobs.index', $server))->assertRedirect(route('login', absolute: false));
});

test('user can view their own server cronjobs', function () {
    $server = Server::factory()->create(['user_id' => $this->user->id]);
    ServerCronjob::factory()->create([
        'server_id' => $server->id,
        'user_id' => $this->user->id,
        'name' => 'Laravel Scheduler',
    ]);

    $this->actingAs($this->user)
        ->get(route('server.cronjobs.index', $server))
        ->assertSuccessful()
        ->assertSee('Zeitgesteuerte Aufgaben')
        ->assertSee('Laravel Scheduler')
        ->assertSee(route('server.cronjobs.remote', $server), false)
        ->assertSee('data-run-button', false)
        ->assertSee('data-confirm="Cronjob wirklich löschen?"', false)
        ->assertSee('addEventListener(\'click\'', false)
        ->assertSee('replaceChildren', false)
        ->assertDontSee('onclick="runCronjob', false)
        ->assertDontSee('onsubmit="return confirm', false)
        ->assertDontSee('target.innerHTML', false)
        ->assertDontSee('result.innerHTML', false);
});

test('user cannot view another users server cronjobs', function () {
    $otherUser = User::factory()->create();
    $server = Server::factory()->create(['user_id' => $otherUser->id]);

    $this->actingAs($this->user)
        ->get(route('server.cronjobs.index', $server))
        ->assertForbidden();
});

test('server cronjobs can load remote crontab entries including foreign jobs', function () {
    $server = Server::factory()->withAgent()->create([
        'user_id' => $this->user->id,
        'host' => '127.0.0.1',
        'agent_port' => 9300,
    ]);

    Http::fake([
        'http://127.0.0.1:9300/actions' => Http::response([
            'success' => true,
            'action' => 'cronjobs.list',
            'exit_code' => 0,
            'stdout' => json_encode([
                ['managed' => false, 'schedule' => '* * * * *', 'command' => 'echo foreign', 'line' => '* * * * * echo foreign'],
                ['managed' => true, 'schedule' => '0 * * * *', 'command' => 'php artisan schedule:run', 'line' => '0 * * * * php artisan schedule:run'],
            ]),
            'stderr' => '',
            'duration_ms' => 10,
        ]),
    ]);

    $this->actingAs($this->user)
        ->getJson(route('server.cronjobs.remote', $server))
        ->assertSuccessful()
        ->assertJsonPath('entries.0.managed', false)
        ->assertJsonPath('entries.0.command', 'echo foreign')
        ->assertJsonPath('entries.1.managed', true);
});

test('server cronjobs can be created and synced to agent', function () {
    $server = Server::factory()->withAgent()->create([
        'user_id' => $this->user->id,
        'host' => '127.0.0.1',
        'agent_port' => 9300,
    ]);

    Http::fake([
        'http://127.0.0.1:9300/actions' => Http::response([
            'success' => true,
            'action' => 'cronjobs.install',
            'exit_code' => 0,
            'stdout' => '',
            'stderr' => '',
            'duration_ms' => 10,
        ]),
    ]);

    $this->actingAs($this->user)
        ->post(route('server.cronjobs.store', $server), [
            'name' => 'Scheduler',
            'schedule' => '* * * * *',
            'command' => 'php artisan schedule:run',
            'working_directory' => '/var/www/html',
            'enabled' => '1',
        ])
        ->assertRedirect(route('server.cronjobs.index', $server, absolute: false));

    $this->assertDatabaseHas('server_cronjobs', [
        'server_id' => $server->id,
        'user_id' => $this->user->id,
        'name' => 'Scheduler',
        'schedule' => '* * * * *',
        'command' => 'php artisan schedule:run',
    ]);

    Http::assertSent(fn ($request): bool => $request['action'] === 'cronjobs.install'
        && $request['payload']['jobs'][0]['command'] === 'php artisan schedule:run');
});

test('server cronjobs can be updated toggled and deleted with agent sync', function () {
    $server = Server::factory()->withAgent()->create([
        'user_id' => $this->user->id,
        'host' => '127.0.0.1',
        'agent_port' => 9300,
    ]);
    $cronjob = ServerCronjob::factory()->create([
        'server_id' => $server->id,
        'user_id' => $this->user->id,
        'name' => 'Old Scheduler',
        'enabled' => true,
    ]);

    Http::fake([
        'http://127.0.0.1:9300/actions' => Http::response([
            'success' => true,
            'action' => 'cronjobs.install',
            'exit_code' => 0,
            'stdout' => '',
            'stderr' => '',
            'duration_ms' => 10,
        ]),
    ]);

    $this->actingAs($this->user)
        ->patch(route('server.cronjobs.update', [$server, $cronjob]), [
            'name' => 'Updated Scheduler',
            'schedule' => '*/5 * * * *',
            'command' => 'php artisan schedule:run',
            'working_directory' => '/var/www/html',
            'run_as' => 'www-data',
            'enabled' => '1',
        ])
        ->assertRedirect(route('server.cronjobs.index', $server, absolute: false));

    expect($cronjob->refresh())
        ->name->toBe('Updated Scheduler')
        ->schedule->toBe('*/5 * * * *')
        ->run_as->toBe('www-data');

    $this->actingAs($this->user)
        ->post(route('server.cronjobs.toggle', [$server, $cronjob]))
        ->assertRedirect(route('server.cronjobs.index', $server, absolute: false));

    expect($cronjob->refresh())->enabled->toBeFalse();

    $this->actingAs($this->user)
        ->delete(route('server.cronjobs.destroy', [$server, $cronjob]))
        ->assertRedirect(route('server.cronjobs.index', $server, absolute: false));

    $this->assertDatabaseMissing('server_cronjobs', [
        'id' => $cronjob->id,
    ]);

    Http::assertSentCount(3);
});

test('server cronjobs validate unsafe payloads', function () {
    $server = Server::factory()->withAgent()->create(['user_id' => $this->user->id]);

    $this->actingAs($this->user)
        ->post(route('server.cronjobs.store', $server), [
            'name' => "Bad\nName",
            'schedule' => '* * * * *',
            'command' => 'php artisan schedule:run',
        ])
        ->assertInvalid(['name']);

    $this->actingAs($this->user)
        ->post(route('server.cronjobs.store', $server), [
            'name' => 'Bad Path',
            'schedule' => '* * * * *',
            'command' => 'php artisan schedule:run',
            'working_directory' => '../app',
        ])
        ->assertInvalid(['working_directory']);
});

test('server cronjob run stores latest result', function () {
    $server = Server::factory()->withAgent()->create([
        'user_id' => $this->user->id,
        'host' => '127.0.0.1',
        'agent_port' => 9300,
    ]);
    $cronjob = ServerCronjob::factory()->create([
        'server_id' => $server->id,
        'user_id' => $this->user->id,
        'command' => 'php artisan schedule:run',
    ]);

    Http::fake([
        'http://127.0.0.1:9300/actions' => Http::response([
            'success' => true,
            'action' => 'cronjobs.run',
            'exit_code' => 0,
            'stdout' => 'ok',
            'stderr' => '',
            'duration_ms' => 10,
        ]),
    ]);

    $this->actingAs($this->user)
        ->postJson(route('server.cronjobs.run', [$server, $cronjob]))
        ->assertSuccessful()
        ->assertJsonPath('stdout', 'ok');

    expect($cronjob->refresh())
        ->last_exit_code->toBe(0)
        ->last_stdout->toBe('ok');
});

test('guest cannot view server files', function () {
    $server = Server::factory()->create();

    $this->get(route('server.files.index', $server))->assertRedirect(route('login', absolute: false));
});

test('user can view their own server files', function () {
    $server = Server::factory()->create(['user_id' => $this->user->id]);

    $this->actingAs($this->user)
        ->get(route('server.files.index', $server))
        ->assertSuccessful()
        ->assertSee('Server-Dateien')
        ->assertSee('path-input', false)
        ->assertSee('data-directory-shortcut="/tmp"', false)
        ->assertSee('files-create-directory-button', false)
        ->assertSee('files-create-file-button', false)
        ->assertSee('addEventListener(\'click\'', false)
        ->assertSee('replaceChildren', false)
        ->assertSee(route('server.files.list', $server), false)
        ->assertSee(route('server.files.upload', $server), false)
        ->assertSee(route('server.files.chmod', $server), false)
        ->assertDontSee('onclick="loadDirectory', false)
        ->assertDontSee('onclick="promptCreateDirectory', false)
        ->assertDontSee('onclick="promptCreateFile', false)
        ->assertDontSee('onclick="downloadSelectedFile', false)
        ->assertDontSee('onclick="saveSelectedFile', false)
        ->assertDontSee('innerHTML', false);
});

test('user cannot view another users server files', function () {
    $otherUser = User::factory()->create();
    $server = Server::factory()->create(['user_id' => $otherUser->id]);

    $this->actingAs($this->user)
        ->get(route('server.files.index', $server))
        ->assertForbidden();
});

test('server files can list read write and delete through agent', function () {
    $server = Server::factory()->withAgent()->create([
        'user_id' => $this->user->id,
        'host' => '127.0.0.1',
        'agent_port' => 9300,
    ]);

    Http::fake([
        'http://127.0.0.1:9300/actions' => Http::sequence()
            ->push([
                'success' => true,
                'action' => 'files.list',
                'exit_code' => 0,
                'stdout' => json_encode(['path' => '/var/www', 'entries' => [['name' => 'index.php', 'path' => '/var/www/index.php', 'type' => 'file']]]),
                'stderr' => '',
                'duration_ms' => 10,
            ])
            ->push([
                'success' => true,
                'action' => 'files.read',
                'exit_code' => 0,
                'stdout' => json_encode(['path' => '/var/www/index.php', 'content' => 'hello', 'size' => 5]),
                'stderr' => '',
                'duration_ms' => 10,
            ])
            ->push([
                'success' => true,
                'action' => 'files.write',
                'exit_code' => 0,
                'stdout' => json_encode(['path' => '/var/www/index.php', 'size' => 7]),
                'stderr' => '',
                'duration_ms' => 10,
            ])
            ->push([
                'success' => true,
                'action' => 'files.delete',
                'exit_code' => 0,
                'stdout' => json_encode(['path' => '/var/www/index.php']),
                'stderr' => '',
                'duration_ms' => 10,
            ]),
    ]);

    $this->actingAs($this->user)
        ->getJson(route('server.files.list', $server).'?path=/var/www')
        ->assertSuccessful()
        ->assertJsonPath('data.path', '/var/www')
        ->assertJsonPath('data.entries.0.name', 'index.php');

    $this->actingAs($this->user)
        ->getJson(route('server.files.read', $server).'?path=/var/www/index.php')
        ->assertSuccessful()
        ->assertJsonPath('data.content', 'hello');

    $this->actingAs($this->user)
        ->postJson(route('server.files.write', $server), ['path' => '/var/www/index.php', 'content' => 'updated'])
        ->assertSuccessful()
        ->assertJsonPath('data.size', 7);

    $this->actingAs($this->user)
        ->deleteJson(route('server.files.delete', $server), ['path' => '/var/www/index.php'])
        ->assertSuccessful()
        ->assertJsonPath('data.path', '/var/www/index.php');
});

test('server files can create directories files and rename through agent', function () {
    $server = Server::factory()->withAgent()->create([
        'user_id' => $this->user->id,
        'host' => '127.0.0.1',
        'agent_port' => 9300,
    ]);

    Http::fake([
        'http://127.0.0.1:9300/actions' => Http::sequence()
            ->push([
                'success' => true,
                'action' => 'files.mkdir',
                'exit_code' => 0,
                'stdout' => json_encode(['path' => '/tmp/smuze-files']),
                'stderr' => '',
                'duration_ms' => 10,
            ])
            ->push([
                'success' => true,
                'action' => 'files.touch',
                'exit_code' => 0,
                'stdout' => json_encode(['path' => '/tmp/smuze-files/a.txt']),
                'stderr' => '',
                'duration_ms' => 10,
            ])
            ->push([
                'success' => true,
                'action' => 'files.rename',
                'exit_code' => 0,
                'stdout' => json_encode(['path' => '/tmp/smuze-files/b.txt']),
                'stderr' => '',
                'duration_ms' => 10,
            ]),
    ]);

    $this->actingAs($this->user)
        ->postJson(route('server.files.directories.store', $server), ['path' => '/tmp/smuze-files'])
        ->assertSuccessful()
        ->assertJsonPath('data.path', '/tmp/smuze-files');

    $this->actingAs($this->user)
        ->postJson(route('server.files.files.store', $server), ['path' => '/tmp/smuze-files/a.txt'])
        ->assertSuccessful()
        ->assertJsonPath('data.path', '/tmp/smuze-files/a.txt');

    $this->actingAs($this->user)
        ->postJson(route('server.files.rename', $server), [
            'path' => '/tmp/smuze-files/a.txt',
            'new_path' => '/tmp/smuze-files/b.txt',
        ])
        ->assertSuccessful()
        ->assertJsonPath('data.path', '/tmp/smuze-files/b.txt');
});

test('server files can upload and download through agent', function () {
    $server = Server::factory()->withAgent()->create([
        'user_id' => $this->user->id,
        'host' => '127.0.0.1',
        'agent_port' => 9300,
    ]);

    Http::fake([
        'http://127.0.0.1:9300/actions' => Http::sequence()
            ->push([
                'success' => true,
                'action' => 'files.upload',
                'exit_code' => 0,
                'stdout' => json_encode(['path' => '/var/www/test.txt', 'size' => 7]),
                'stderr' => '',
                'duration_ms' => 10,
            ])
            ->push([
                'success' => true,
                'action' => 'files.download',
                'exit_code' => 0,
                'stdout' => json_encode(['path' => '/var/www/test.txt', 'content_base64' => base64_encode('content')]),
                'stderr' => '',
                'duration_ms' => 10,
            ]),
    ]);

    $this->actingAs($this->user)
        ->post(route('server.files.upload', $server), [
            'directory' => '/var/www',
            'file' => UploadedFile::fake()->createWithContent('test.txt', 'content'),
        ])
        ->assertSuccessful()
        ->assertJsonPath('data.size', 7);

    $this->actingAs($this->user)
        ->get(route('server.files.download', $server).'?path=/var/www/test.txt')
        ->assertSuccessful()
        ->assertHeader('Content-Disposition', 'attachment; filename="test.txt"')
        ->assertContent('content');
});

test('server files reject unsafe upload file names', function () {
    $server = Server::factory()->withAgent()->create(['user_id' => $this->user->id]);

    Http::fake();

    $this->actingAs($this->user)
        ->post(route('server.files.upload', $server), [
            'directory' => '/var/www',
            'file' => UploadedFile::fake()->createWithContent('bad..txt', 'content'),
        ])
        ->assertStatus(422);

    Http::assertNothingSent();
});

test('server files can chmod through agent', function () {
    $server = Server::factory()->withAgent()->create([
        'user_id' => $this->user->id,
        'host' => '127.0.0.1',
        'agent_port' => 9300,
    ]);

    Http::fake([
        'http://127.0.0.1:9300/actions' => Http::response([
            'success' => true,
            'action' => 'files.chmod',
            'exit_code' => 0,
            'stdout' => json_encode(['path' => '/var/www/index.php', 'mode' => '0o755']),
            'stderr' => '',
            'duration_ms' => 10,
        ]),
    ]);

    $this->actingAs($this->user)
        ->postJson(route('server.files.chmod', $server), [
            'path' => '/var/www/index.php',
            'mode' => '0755',
        ])
        ->assertSuccessful()
        ->assertJsonPath('data.mode', '0o755');
});

test('server files validate unsafe paths', function () {
    $server = Server::factory()->withAgent()->create(['user_id' => $this->user->id]);

    $this->actingAs($this->user)
        ->getJson(route('server.files.read', $server).'?path=../etc/passwd')
        ->assertInvalid(['path']);

    $this->actingAs($this->user)
        ->postJson(route('server.files.write', $server), ['path' => '/var/www/../bad', 'content' => 'x'])
        ->assertInvalid(['path']);

    $this->actingAs($this->user)
        ->postJson(route('server.files.chmod', $server), ['path' => '/var/www/index.php', 'mode' => '999'])
        ->assertInvalid(['mode']);
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

test('user can update server agent public url', function () {
    $server = Server::factory()->create([
        'user_id' => $this->user->id,
    ]);

    $this->actingAs($this->user)
        ->put(route('server.update', $server), [
            'name' => $server->name,
            'host' => $server->host,
            'agent_port' => $server->agent_port,
            'agent_public_url' => 'https://agent.example.com/',
        ])
        ->assertRedirect(route('server.index', absolute: false));

    expect($server->refresh()->agent_public_url)->toBe('https://agent.example.com');
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
        ->assertSee($server->name)
        ->assertSee(route('server.agent.metrics', $server), false)
        ->assertSee('serviceRow(svc, installed, version)', false)
        ->assertSee('addEventListener(\'click\'', false)
        ->assertSee('dataset.phpVersion', false)
        ->assertSee('"8.5"', false)
        ->assertSee('"8.2"', false)
        ->assertDontSee('onclick="serviceAction', false)
        ->assertDontSee('${svc.label}', false)
        ->assertDontSee('${installed ? version', false)
        ->assertDontSee('sessionStorage', false)
        ->assertDontSee("http://{$server->host}:{$server->agent_port}/metrics", false);
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

    $engine->shouldReceive('action')
        ->once()
        ->withArgs(function (Server $serverArgument, string $action, array $payload, callable $onOutput) use ($server): bool {
            expect($serverArgument->is($server))->toBeTrue();
            expect($action)->toBe('services.install')
                ->and($payload)->toBe(['service' => 'php', 'version' => '8.4']);

            $onOutput('stdout', "Paketlisten werden gelesen...\n");

            return true;
        })
        ->andReturn(new ExecutionResult(
            stdout: 'Paketlisten werden gelesen...',
            stderr: '',
            exitCode: 0,
            success: true,
        ));

    $this->app->instance(PushAgentEngine::class, $engine);

    $response = $this->actingAs($this->user)
        ->post(route('server.services.install.stream', ['server' => $server, 'service' => 'php']), ['version' => '8.4'])
        ->assertSuccessful();

    $content = $response->streamedContent();

    expect($content)
        ->toContain('Starte Ausf\u00fchrung')
        ->toContain('Paketlisten werden gelesen')
        ->toContain('PHP wurde installiert.');
});

test('service install stream accepts nginx', function () {
    $server = Server::factory()->create(['user_id' => $this->user->id]);
    $engine = Mockery::mock(PushAgentEngine::class);

    $engine->shouldReceive('action')
        ->once()
        ->withArgs(function (Server $serverArgument, string $action, array $payload): bool {
            expect($serverArgument->exists)->toBeTrue();
            expect($action)->toBe('services.install')
                ->and($payload)->toBe(['service' => 'nginx']);

            return true;
        })
        ->andReturn(new ExecutionResult(stdout: '', stderr: '', exitCode: 0, success: true));

    $this->app->instance(PushAgentEngine::class, $engine);

    $response = $this->actingAs($this->user)
        ->post(route('server.services.install.stream', ['server' => $server, 'service' => 'nginx']))
        ->assertSuccessful();

    expect($response->streamedContent())->toContain('Nginx wurde installiert.');
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

// Docker
test('guest cannot view docker page', function () {
    $server = Server::factory()->create();

    $this->get(route('server.docker.index', $server))->assertRedirect(route('login', absolute: false));
});

test('user can view their own docker page', function () {
    $server = Server::factory()->create(['user_id' => $this->user->id]);

    $this->actingAs($this->user)
        ->get(route('server.docker.index', $server))
        ->assertSuccessful()
        ->assertSee('Docker-Container-Verwaltung')
        ->assertSee($server->name)
        ->assertSee('encodeURIComponent(value)', false)
        ->assertSee('function containerRow(container)', false)
        ->assertSee('function imageRow(image)', false)
        ->assertSee('function networkRow(network)', false)
        ->assertSee('function composeRow(service)', false)
        ->assertSee('addEventListener(\'click\'', false)
        ->assertDontSee('onclick="containerAction', false)
        ->assertDontSee('onclick="imageRemove', false)
        ->assertDontSee('${c.CONTAINER_ID', false)
        ->assertDontSee('${img.IMAGE_ID', false)
        ->assertDontSee('${net.NAME', false)
        ->assertDontSee('${svc.NAME', false);
});

test('user cannot view another users docker page', function () {
    $otherUser = User::factory()->create();
    $server = Server::factory()->create(['user_id' => $otherUser->id]);

    $this->actingAs($this->user)
        ->get(route('server.docker.index', $server))
        ->assertForbidden();
});

test('docker status and list endpoints return json with success field', function (string $routeName) {
    $server = Server::factory()->create(['user_id' => $this->user->id, 'host' => '127.0.0.1']);

    $this->actingAs($this->user)
        ->get(route($routeName, $server))
        ->assertJsonStructure(['success']);
})->with([
    'server.docker.status',
    'server.docker.info',
    'server.docker.ps',
    'server.docker.stats',
    'server.docker.images',
    'server.docker.networks',
    'server.docker.compose.ps',
]);

test('docker install deinstall service and prune endpoints return json with success field', function (string $method, string $routeName, array $parameters = []) {
    $server = Server::factory()->create(['user_id' => $this->user->id, 'host' => '127.0.0.1']);

    $this->actingAs($this->user)
        ->{$method}(route($routeName, ['server' => $server, ...$parameters]))
        ->assertJsonStructure(['success']);
})->with([
    ['post', 'server.docker.install'],
    ['post', 'server.docker.deinstall'],
    ['post', 'server.docker.service', ['action' => 'start']],
    ['post', 'server.docker.system-prune'],
]);

test('docker container and image actions validate request shape', function () {
    $server = Server::factory()->create(['user_id' => $this->user->id]);

    $this->actingAs($this->user)
        ->post(route('server.docker.containers.create', $server), [])
        ->assertInvalid(['image']);

    $this->actingAs($this->user)
        ->post(route('server.docker.images.pull', $server), [])
        ->assertInvalid(['image']);

    $this->actingAs($this->user)
        ->post(route('server.docker.containers.exec', ['server' => $server, 'container' => 'test']), [])
        ->assertInvalid(['command']);
});

test('user cannot update docker on another users server', function () {
    $otherUser = User::factory()->create();
    $server = Server::factory()->create(['user_id' => $otherUser->id]);

    $this->actingAs($this->user)
        ->post(route('server.docker.install', $server))
        ->assertForbidden();
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
        ->assertSee($server->name)
        ->assertSee('data-action-url', false)
        ->assertSee('data-port-action="allow"', false)
        ->assertSee('data-port-action="deny"', false)
        ->assertSee('data-preset-port="22"', false)
        ->assertSee('addEventListener(\'click\'', false)
        ->assertSee('replaceChildren', false)
        ->assertDontSee('onclick="refreshFirewall', false)
        ->assertDontSee('onclick="firewallAction', false)
        ->assertDontSee('onclick="firewallPortAction', false)
        ->assertDontSee('onclick="presetAllow', false)
        ->assertDontSee('onclick="allowAllPorts', false)
        ->assertDontSee('innerHTML', false);
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

test('firewall allow rejects invalid protocol', function () {
    $server = Server::factory()->create([
        'user_id' => $this->user->id,
        'host' => '127.0.0.1',
    ]);

    $this->actingAs($this->user)
        ->post(route('server.firewall.allow', $server), [
            'port' => 80,
            'protocol' => 'icmp',
        ])
        ->assertInvalid(['protocol']);
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

test('firewall allow standard ports returns json with success field', function () {
    $server = Server::factory()->create([
        'user_id' => $this->user->id,
        'host' => '127.0.0.1',
    ]);

    $this->actingAs($this->user)
        ->post(route('server.firewall.allow-standard-ports', $server))
        ->assertJsonStructure(['success']);
});

test('firewall install returns json with success field', function () {
    $server = Server::factory()->create([
        'user_id' => $this->user->id,
        'host' => '127.0.0.1',
    ]);

    $this->actingAs($this->user)
        ->post(route('server.firewall.install', $server))
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

test('user cannot allow standard ports on another users firewall', function () {
    $otherUser = User::factory()->create();
    $server = Server::factory()->create(['user_id' => $otherUser->id]);

    $this->actingAs($this->user)
        ->post(route('server.firewall.allow-standard-ports', $server))
        ->assertForbidden();
});

test('user cannot install firewall on another users server', function () {
    $otherUser = User::factory()->create();
    $server = Server::factory()->create(['user_id' => $otherUser->id]);

    $this->actingAs($this->user)
        ->post(route('server.firewall.install', $server))
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
        ->assertSee($server->name)
        ->assertSee('encodeURIComponent(value)', false)
        ->assertSee('apacheSiteRow(site)', false)
        ->assertSee('apacheModuleRow(mod)', false)
        ->assertSee('addEventListener(\'click\'', false)
        ->assertDontSee('onclick="apacheSiteAction', false)
        ->assertDontSee('onclick="apacheModuleAction', false)
        ->assertDontSee('${site.name}', false)
        ->assertDontSee('${mod.name}', false);
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
        ->assertJsonStructure(['success', 'modules', 'message']);
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

// Nginx
test('guest cannot view nginx page', function () {
    $server = Server::factory()->create();

    $this->get(route('server.nginx.index', $server))->assertRedirect(route('login', absolute: false));
});

test('user can view their own nginx page', function () {
    $server = Server::factory()->create(['user_id' => $this->user->id]);

    $this->actingAs($this->user)
        ->get(route('server.nginx.index', $server))
        ->assertSuccessful()
        ->assertSee('Nginx-Webserver-Verwaltung')
        ->assertSee($server->name)
        ->assertSee('encodeURIComponent(value)', false)
        ->assertSee('function siteRow(site)', false)
        ->assertSee('cell.textContent = value ||', false)
        ->assertSee('addEventListener', false)
        ->assertDontSee('onclick="nginxSiteAction', false)
        ->assertDontSee('onclick="nginxDeleteSite', false);
});

test('user cannot view another users nginx page', function () {
    $otherUser = User::factory()->create();
    $server = Server::factory()->create(['user_id' => $otherUser->id]);

    $this->actingAs($this->user)
        ->get(route('server.nginx.index', $server))
        ->assertForbidden();
});

test('nginx status returns json with success field', function () {
    $server = Server::factory()->create(['user_id' => $this->user->id, 'host' => '127.0.0.1']);

    $this->actingAs($this->user)
        ->get(route('server.nginx.status', $server))
        ->assertJsonStructure(['success']);
});

test('nginx install returns json with success field', function () {
    $server = Server::factory()->create(['user_id' => $this->user->id, 'host' => '127.0.0.1']);

    $this->actingAs($this->user)
        ->post(route('server.nginx.install', $server))
        ->assertJsonStructure(['success']);
});

test('nginx deinstall returns json with success field', function () {
    $server = Server::factory()->create(['user_id' => $this->user->id, 'host' => '127.0.0.1']);

    $this->actingAs($this->user)
        ->post(route('server.nginx.deinstall', $server))
        ->assertJsonStructure(['success']);
});

test('nginx service start returns json with success field', function () {
    $server = Server::factory()->create(['user_id' => $this->user->id, 'host' => '127.0.0.1']);

    $this->actingAs($this->user)
        ->post(route('server.nginx.service', ['server' => $server, 'action' => 'start']))
        ->assertJsonStructure(['success']);
});

test('nginx configtest returns json with success field', function () {
    $server = Server::factory()->create(['user_id' => $this->user->id, 'host' => '127.0.0.1']);

    $this->actingAs($this->user)
        ->post(route('server.nginx.configtest', $server))
        ->assertJsonStructure(['success']);
});

test('nginx sites returns json with success field', function () {
    $server = Server::factory()->create(['user_id' => $this->user->id, 'host' => '127.0.0.1']);

    $this->actingAs($this->user)
        ->get(route('server.nginx.sites', $server))
        ->assertJsonStructure(['success']);
});

test('nginx create vhost validates domain', function () {
    $server = Server::factory()->create(['user_id' => $this->user->id, 'host' => '127.0.0.1']);

    $this->actingAs($this->user)
        ->post(route('server.nginx.vhost', $server), [
            'domain' => 'invalid domain!',
            'document_root' => '/var/www/test',
        ])
        ->assertInvalid(['domain']);
});

test('nginx create vhost validates document root', function () {
    $server = Server::factory()->create(['user_id' => $this->user->id, 'host' => '127.0.0.1']);

    $this->actingAs($this->user)
        ->post(route('server.nginx.vhost', $server), [
            'domain' => 'example.com',
            'document_root' => 'relative/path',
        ])
        ->assertInvalid(['document_root']);
});

test('nginx create vhost returns json with success field', function () {
    $server = Server::factory()->create(['user_id' => $this->user->id, 'host' => '127.0.0.1']);

    $this->actingAs($this->user)
        ->post(route('server.nginx.vhost', $server), [
            'domain' => 'example.com',
            'document_root' => '/var/www/test/public',
        ])
        ->assertJsonStructure(['success']);
});

test('nginx enable site returns json with success field', function () {
    $server = Server::factory()->create(['user_id' => $this->user->id, 'host' => '127.0.0.1']);

    $this->actingAs($this->user)
        ->post(route('server.nginx.sites.enable', ['server' => $server, 'site' => 'test.conf']))
        ->assertJsonStructure(['success']);
});

test('nginx certbot install returns json with success field', function () {
    $server = Server::factory()->create(['user_id' => $this->user->id, 'host' => '127.0.0.1']);

    $this->actingAs($this->user)
        ->post(route('server.nginx.ssl.install-certbot', $server))
        ->assertJsonStructure(['success']);
});

test('nginx obtain ssl validates fields', function () {
    $server = Server::factory()->create(['user_id' => $this->user->id, 'host' => '127.0.0.1']);

    $this->actingAs($this->user)
        ->post(route('server.nginx.ssl.obtain', $server), [
            'domain' => '',
            'email' => '',
        ])
        ->assertInvalid(['domain', 'email']);
});

test('user cannot start nginx on another users server', function () {
    $otherUser = User::factory()->create();
    $server = Server::factory()->create(['user_id' => $otherUser->id]);

    $this->actingAs($this->user)
        ->post(route('server.nginx.service', ['server' => $server, 'action' => 'start']))
        ->assertForbidden();
});

test('user cannot vhost nginx on another users server', function () {
    $otherUser = User::factory()->create();
    $server = Server::factory()->create(['user_id' => $otherUser->id]);

    $this->actingAs($this->user)
        ->post(route('server.nginx.vhost', $server), [
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
        ->assertSee($server->name)
        ->assertSee('encodeURIComponent(value)', false)
        ->assertSee('function databaseRow(db)', false)
        ->assertSee('function userRow(user)', false)
        ->assertSee('textContent = db', false)
        ->assertSee('addEventListener(\'click\'', false)
        ->assertDontSee('onclick="showTables', false)
        ->assertDontSee('onclick="dropDatabase', false)
        ->assertDontSee('onclick="userAction', false)
        ->assertDontSee('${user.username}', false)
        ->assertDontSee('${db}', false);
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
        ->assertSee($server->name)
        ->assertSee('Apache oder Nginx')
        ->assertDontSee('Apache installieren')
        ->assertDontSee('SSL mit Let');
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
            'target_name' => 'myproject',
        ])
        ->assertInvalid(['repo_url']);
});

test('github deploy does not require host', function () {
    $server = Server::factory()->create([
        'user_id' => $this->user->id,
        'host' => '127.0.0.1',
    ]);

    $this->actingAs($this->user)
        ->post(route('server.github.deploy', $server), [
            'repo_url' => 'https://github.com/owner/repo.git',
            'target_name' => 'myproject',
        ])
        ->assertJsonStructure(['success']);
});

test('github deploy validates target_name', function () {
    $server = Server::factory()->create([
        'user_id' => $this->user->id,
        'host' => '127.0.0.1',
    ]);

    $this->actingAs($this->user)
        ->post(route('server.github.deploy', $server), [
            'repo_url' => 'https://github.com/owner/repo.git',
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
            'target_name' => 'myproject',
        ])
        ->assertForbidden();
});

test('metrics history returns empty for new server', function () {
    $server = Server::factory()->create(['user_id' => $this->user->id]);

    $this->actingAs($this->user)
        ->getJson(route('server.agent.metrics.history', $server))
        ->assertSuccessful()
        ->assertJsonCount(0, 'labels')
        ->assertJsonCount(0, 'cpu');
});

test('metrics history returns stored data', function () {
    $server = Server::factory()->create(['user_id' => $this->user->id]);

    ServerMetric::create([
        'server_id' => $server->id,
        'cpu_percent' => 45,
        'ram_percent' => 60,
        'disk_percent' => 72,
        'created_at' => now()->subMinutes(5),
    ]);
    ServerMetric::create([
        'server_id' => $server->id,
        'cpu_percent' => 50,
        'ram_percent' => 62,
        'disk_percent' => 72,
        'created_at' => now()->subMinutes(2),
    ]);

    $this->actingAs($this->user)
        ->getJson(route('server.agent.metrics.history', $server))
        ->assertSuccessful()
        ->assertJsonCount(2, 'labels')
        ->assertJsonCount(2, 'cpu')
        ->assertJsonCount(2, 'ram')
        ->assertJsonCount(2, 'disk')
        ->assertJsonPath('cpu.0', 45)
        ->assertJsonPath('cpu.1', 50);
});

test('metrics history respects 7d range', function () {
    $server = Server::factory()->create(['user_id' => $this->user->id]);

    ServerMetric::create([
        'server_id' => $server->id,
        'cpu_percent' => 10,
        'created_at' => now()->subDays(5),
    ]);
    ServerMetric::create([
        'server_id' => $server->id,
        'cpu_percent' => 20,
        'created_at' => now()->subDays(10),
    ]);

    $this->actingAs($this->user)
        ->getJson(route('server.agent.metrics.history', $server).'?range=7d')
        ->assertSuccessful()
        ->assertJsonCount(1, 'cpu');
});

test('guest cannot view metrics history', function () {
    $server = Server::factory()->create();

    $this->get(route('server.agent.metrics.history', $server))->assertRedirect(route('login', absolute: false));
});

test('user cannot view another users metrics history', function () {
    $otherUser = User::factory()->create();
    $server = Server::factory()->create(['user_id' => $otherUser->id]);

    $this->actingAs($this->user)
        ->getJson(route('server.agent.metrics.history', $server))
        ->assertForbidden();
});

test('proxyMetrics stores metrics on success', function () {
    $server = Server::factory()->withAgent()->create([
        'user_id' => $this->user->id,
        'host' => '127.0.0.1',
        'agent_port' => 9300,
    ]);

    Http::fake([
        'http://127.0.0.1:9300/metrics' => Http::response([
            'cpu_percent' => 30,
            'ram_percent' => 55,
            'ram_used_mb' => 2048,
            'ram_total_mb' => 8192,
            'disk_percent' => 60,
            'disk_used_mb' => 50000,
            'disk_total_mb' => 100000,
            'load' => '1.5',
            'hostname' => 'web-01',
        ]),
        'http://127.0.0.1:9300/health' => Http::response([
            'status' => 'ok',
            'version' => '0.1.0',
        ]),
    ]);

    $this->actingAs($this->user)
        ->getJson(route('server.agent.metrics', $server))
        ->assertSuccessful();

    $this->assertDatabaseHas('server_metrics', [
        'server_id' => $server->id,
        'cpu_percent' => 30,
        'ram_percent' => 55,
    ]);
});

test('user can view logs page', function () {
    $server = Server::factory()->create(['user_id' => $this->user->id]);

    $this->actingAs($this->user)
        ->get(route('server.logs.index', $server))
        ->assertSuccessful()
        ->assertSee('Log-Dateien')
        ->assertSee('syslog')
        ->assertSee('Nginx Access')
        ->assertSee('MySQL Error');
});

test('user can fetch log content', function () {
    $server = Server::factory()->create(['user_id' => $this->user->id]);

    $engine = Mockery::mock(PushAgentEngine::class);
    $engine->shouldReceive('execute')
        ->once()
        ->with(Mockery::type(Server::class), Mockery::on(fn ($cmd) => str_contains($cmd, 'syslog')), 15, true)
        ->andReturn(new ExecutionResult(
            stdout: "line1\nline2\nline3\n",
            stderr: '',
            exitCode: 0,
            success: true,
        ));

    $this->instance(PushAgentEngine::class, $engine);

    $this->actingAs($this->user)
        ->postJson(route('server.logs.fetch', $server), [
            'source' => 'syslog',
            'lines' => 50,
        ])
        ->assertSuccessful()
        ->assertJsonPath('total', 3)
        ->assertJsonCount(3, 'lines');
});

test('user cannot fetch logs on another users server', function () {
    $otherUser = User::factory()->create();
    $server = Server::factory()->create(['user_id' => $otherUser->id]);

    $this->actingAs($this->user)
        ->postJson(route('server.logs.fetch', $server), [
            'source' => 'syslog',
        ])
        ->assertForbidden();
});

test('guest cannot view logs', function () {
    $server = Server::factory()->create();

    $this->get(route('server.logs.index', $server))->assertRedirect(route('login', absolute: false));
});
