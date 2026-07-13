<?php

use App\Models\Server;
use App\Models\ServerBackup;
use App\Models\ServerBackupArchive;
use App\Models\User;
use App\Modules\Server\Backups\Actions\BackupAction;
use App\Services\ExecutionEngine\ExecutionResult;
use App\Services\ExecutionEngine\PushAgentEngine;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    $this->user = User::factory()->create();
});

test('guest cannot view backups page', function () {
    $server = Server::factory()->create();

    $this->get(route('server.backups.index', $server))
        ->assertRedirect(route('login', absolute: false));
});

test('user can view their own backups page', function () {
    $server = Server::factory()->create(['user_id' => $this->user->id]);
    ServerBackup::factory()->create([
        'server_id' => $server->id,
        'user_id' => $this->user->id,
        'name' => 'Daily MySQL',
    ]);

    $this->actingAs($this->user)
        ->get(route('server.backups.index', $server))
        ->assertSuccessful()
        ->assertSee('Backup-Verwaltung')
        ->assertSee('Daily MySQL')
        ->assertSee('Neue Backup-Konfiguration')
        ->assertSee('data-run-backup', false)
        ->assertSee('addEventListener(\'click\'', false)
        ->assertDontSee('onclick="runBackup', false)
        ->assertDontSee('onclick="restoreArchive', false)
        ->assertDontSee('onclick="deleteArchive', false)
        ->assertDontSee('onsubmit="return confirm', false)
        ->assertDontSee('innerHTML', false);
});

test('user cannot see other users backups page', function () {
    $otherUser = User::factory()->create();
    $server = Server::factory()->create(['user_id' => $otherUser->id]);

    $this->actingAs($this->user)
        ->get(route('server.backups.index', $server))
        ->assertForbidden();
});

test('user can create a backup configuration', function () {
    $server = Server::factory()->create(['user_id' => $this->user->id]);

    $this->actingAs($this->user)
        ->post(route('server.backups.store', $server), [
            'name' => 'Daily MySQL',
            'type' => 'mysql',
            'targets' => "database\nblog",
            'storage' => 'local',
            'retention_days' => 7,
            'enabled' => '1',
        ])
        ->assertRedirect(route('server.backups.index', $server));

    $this->assertDatabaseHas('server_backups', [
        'server_id' => $server->id,
        'name' => 'Daily MySQL',
        'type' => 'mysql',
        'storage' => 'local',
        'retention_days' => 7,
        'enabled' => true,
    ]);
});

test('user can create a backup configuration with s3', function () {
    $server = Server::factory()->create(['user_id' => $this->user->id]);

    $this->actingAs($this->user)
        ->post(route('server.backups.store', $server), [
            'name' => 'S3 Backup',
            'type' => 'files',
            'targets' => '/var/www',
            'storage' => 's3',
            's3_config' => [
                'bucket' => 'my-bucket',
                'region' => 'eu-central-1',
                'access_key_id' => 'AKID123',
                'secret_access_key' => 'secret123',
            ],
            'retention_days' => 30,
            'enabled' => '1',
        ])
        ->assertRedirect(route('server.backups.index', $server));

    $this->assertDatabaseHas('server_backups', [
        'server_id' => $server->id,
        'name' => 'S3 Backup',
        'storage' => 's3',
    ]);
});

test('user can update a backup configuration', function () {
    $server = Server::factory()->create(['user_id' => $this->user->id]);
    $backup = ServerBackup::factory()->create([
        'server_id' => $server->id,
        'user_id' => $this->user->id,
        'name' => 'Old Name',
    ]);

    $this->actingAs($this->user)
        ->patch(route('server.backups.update', [$server, $backup]), [
            'name' => 'New Name',
            'type' => 'mysql',
            'targets' => 'database',
            'storage' => 'local',
            'retention_days' => 14,
            'enabled' => '1',
        ])
        ->assertRedirect(route('server.backups.index', $server));

    expect($backup->fresh()->name)->toBe('New Name');
    expect($backup->fresh()->retention_days)->toBe(14);
});

test('user can delete a backup configuration', function () {
    $server = Server::factory()->create(['user_id' => $this->user->id]);
    $backup = ServerBackup::factory()->create([
        'server_id' => $server->id,
        'user_id' => $this->user->id,
    ]);

    $this->actingAs($this->user)
        ->delete(route('server.backups.destroy', [$server, $backup]))
        ->assertRedirect(route('server.backups.index', $server));

    $this->assertDatabaseMissing('server_backups', ['id' => $backup->id]);
});

test('user can toggle backup enabled state', function () {
    $server = Server::factory()->create(['user_id' => $this->user->id]);
    $backup = ServerBackup::factory()->create([
        'server_id' => $server->id,
        'user_id' => $this->user->id,
        'enabled' => true,
    ]);

    $this->actingAs($this->user)
        ->post(route('server.backups.toggle', [$server, $backup]))
        ->assertRedirect(route('server.backups.index', $server));

    expect($backup->fresh()->enabled)->toBeFalse();
});

test('user can run a backup', function () {
    $server = Server::factory()->create(['user_id' => $this->user->id]);
    $backup = ServerBackup::factory()->create([
        'server_id' => $server->id,
        'user_id' => $this->user->id,
        'type' => 'mysql',
        'targets' => ['database'],
        'storage' => 'local',
    ]);

    $engine = Mockery::mock(PushAgentEngine::class);
    $engine->shouldReceive('action')
        ->once()
        ->withArgs(function (Server $s, string $action, array $payload) use ($server, $backup): bool {
            return $s->is($server)
                && $action === 'backup.run'
                && $payload['backup_id'] === $backup->id;
        })
        ->andReturn(new ExecutionResult(
            stdout: json_encode(['filename' => 'backup-2026-07-13.tar.gz', 'size_bytes' => 1048576, 'storage_path' => '/var/backups/backup-2026-07-13.tar.gz']),
            stderr: '',
            exitCode: 0,
            success: true,
        ));

    $engine->shouldReceive('action')
        ->once()
        ->withArgs(function (Server $s, string $action) use ($server): bool {
            return $s->is($server) && $action === 'backup.prune';
        })
        ->andReturn(new ExecutionResult(
            stdout: json_encode(['deleted' => [], 'message' => 'Bereinigt.']),
            stderr: '',
            exitCode: 0,
            success: true,
        ));

    $this->app->instance(PushAgentEngine::class, $engine);

    $this->actingAs($this->user)
        ->postJson(route('server.backups.run', [$server, $backup]))
        ->assertSuccessful()
        ->assertJson(['success' => true]);

    expect($backup->fresh()->last_status)->toBe('success');
    expect($backup->fresh()->last_run_at)->not->toBeNull();
});

test('backup run fails gracefully', function () {
    $server = Server::factory()->create(['user_id' => $this->user->id]);
    $backup = ServerBackup::factory()->create([
        'server_id' => $server->id,
        'user_id' => $this->user->id,
    ]);

    $engine = Mockery::mock(PushAgentEngine::class);
    $engine->shouldReceive('action')
        ->once()
        ->andReturn(new ExecutionResult(
            stdout: '',
            stderr: 'mysqldump: Connection refused',
            exitCode: 1,
            success: false,
        ));

    $this->app->instance(PushAgentEngine::class, $engine);

    $this->actingAs($this->user)
        ->postJson(route('server.backups.run', [$server, $backup]))
        ->assertStatus(422)
        ->assertJson(['success' => false]);

    expect($backup->fresh()->last_status)->toBe('failed');
});

test('guest cannot run backup', function () {
    $server = Server::factory()->create();
    $backup = ServerBackup::factory()->create(['server_id' => $server->id]);

    $this->postJson(route('server.backups.run', [$server, $backup]))
        ->assertRedirect(route('login', absolute: false));
});

test('user cannot run backup on other users server', function () {
    $otherUser = User::factory()->create();
    $server = Server::factory()->create(['user_id' => $otherUser->id]);
    $backup = ServerBackup::factory()->create([
        'server_id' => $server->id,
        'user_id' => $otherUser->id,
    ]);

    $this->actingAs($this->user)
        ->postJson(route('server.backups.run', [$server, $backup]))
        ->assertForbidden();
});

test('user can view archives', function () {
    $server = Server::factory()->create(['user_id' => $this->user->id]);
    $backup = ServerBackup::factory()->create([
        'server_id' => $server->id,
        'user_id' => $this->user->id,
    ]);
    ServerBackupArchive::factory()->count(3)->create([
        'server_backup_id' => $backup->id,
    ]);

    $engine = Mockery::mock(PushAgentEngine::class);
    $engine->shouldReceive('action')
        ->once()
        ->andReturn(new ExecutionResult(
            stdout: json_encode(['files' => []]),
            stderr: '',
            exitCode: 0,
            success: true,
        ));

    $this->app->instance(PushAgentEngine::class, $engine);

    $this->actingAs($this->user)
        ->getJson(route('server.backups.archives', [$server, $backup]))
        ->assertSuccessful()
        ->assertJson(['success' => true])
        ->assertJsonCount(3, 'archives');
});

test('validation requires fields for backup creation', function () {
    $server = Server::factory()->create(['user_id' => $this->user->id]);

    $this->actingAs($this->user)
        ->from(route('server.backups.index', $server))
        ->post(route('server.backups.store', $server), [])
        ->assertRedirect(route('server.backups.index', $server))
        ->assertSessionHasErrors(['name', 'type', 'targets', 'retention_days']);
});

test('backup configuration shows on backups page', function () {
    $server = Server::factory()->create(['user_id' => $this->user->id]);
    ServerBackup::factory()->create([
        'server_id' => $server->id,
        'user_id' => $this->user->id,
        'name' => 'Files Backup',
        'type' => 'files',
        'targets' => ['/var/www'],
    ]);

    $this->actingAs($this->user)
        ->get(route('server.backups.index', $server))
        ->assertSuccessful()
        ->assertSee('Files Backup')
        ->assertSee('files');
});

test('user can restore an archive', function () {
    $server = Server::factory()->create(['user_id' => $this->user->id]);
    $backup = ServerBackup::factory()->create([
        'server_id' => $server->id,
        'user_id' => $this->user->id,
    ]);
    $archive = ServerBackupArchive::factory()->create([
        'server_backup_id' => $backup->id,
        'filename' => 'backup-test.tar.gz',
    ]);

    $engine = Mockery::mock(PushAgentEngine::class);
    $engine->shouldReceive('action')
        ->once()
        ->withArgs(fn (Server $s, string $action, array $payload): bool => $s->is($server)
            && $action === 'backup.restore'
            && $payload['backup_id'] === $backup->id)
        ->andReturn(new ExecutionResult(
            stdout: 'Wiederherstellung abgeschlossen.',
            stderr: '',
            exitCode: 0,
            success: true,
        ));

    $this->app->instance(PushAgentEngine::class, $engine);

    $this->actingAs($this->user)
        ->postJson(route('server.backups.archives.restore', [$server, $archive]))
        ->assertSuccessful()
        ->assertJson(['success' => true]);
});

test('user can delete an archive', function () {
    $server = Server::factory()->create(['user_id' => $this->user->id]);
    $backup = ServerBackup::factory()->create([
        'server_id' => $server->id,
        'user_id' => $this->user->id,
    ]);
    $archive = ServerBackupArchive::factory()->create([
        'server_backup_id' => $backup->id,
        'filename' => 'backup-old.tar.gz',
    ]);

    $engine = Mockery::mock(PushAgentEngine::class);
    $engine->shouldReceive('action')
        ->once()
        ->withArgs(fn (Server $s, string $action, array $payload): bool => $s->is($server)
            && $action === 'backup.delete'
            && $payload['backup_id'] === $backup->id)
        ->andReturn(new ExecutionResult(
            stdout: '',
            stderr: '',
            exitCode: 0,
            success: true,
        ));

    $this->app->instance(PushAgentEngine::class, $engine);

    $this->actingAs($this->user)
        ->deleteJson(route('server.backups.archives.destroy', [$server, $archive]))
        ->assertSuccessful()
        ->assertJson(['success' => true]);

    $this->assertDatabaseMissing('server_backup_archives', ['id' => $archive->id]);
});

test('backup action rejects unsafe archive filenames before agent action', function () {
    $server = Server::factory()->create(['user_id' => $this->user->id]);

    $engine = Mockery::mock(PushAgentEngine::class);
    $engine->shouldReceive('action')->never();

    $action = new BackupAction($engine);

    expect($action->delete($server, 1, '../backup.tar.gz'))
        ->toMatchArray([
            'success' => false,
            'message' => 'Backup-Dateiname ist ungültig.',
        ]);

    expect($action->restore($server, 1, '../backup.tar.gz', 'files', ['/tmp']))
        ->toMatchArray([
            'success' => false,
            'message' => 'Backup-Dateiname ist ungültig.',
        ]);
});

test('archive db row is kept when remote delete fails', function () {
    $server = Server::factory()->create(['user_id' => $this->user->id]);
    $backup = ServerBackup::factory()->create([
        'server_id' => $server->id,
        'user_id' => $this->user->id,
    ]);
    $archive = ServerBackupArchive::factory()->create([
        'server_backup_id' => $backup->id,
        'filename' => 'backup-old.tar.gz',
    ]);

    $engine = Mockery::mock(PushAgentEngine::class);
    $engine->shouldReceive('action')
        ->once()
        ->andReturn(new ExecutionResult(stdout: '', stderr: 'missing archive', exitCode: 1, success: false));

    $this->app->instance(PushAgentEngine::class, $engine);

    $this->actingAs($this->user)
        ->deleteJson(route('server.backups.archives.destroy', [$server, $archive]))
        ->assertStatus(422)
        ->assertJson(['success' => false]);

    $this->assertDatabaseHas('server_backup_archives', ['id' => $archive->id]);
});

test('nav bar shows backups link when server is selected', function () {
    $server = Server::factory()->create(['user_id' => $this->user->id]);

    $this->actingAs($this->user)
        ->get(route('server.backups.index', $server))
        ->assertSuccessful()
        ->assertSee('Backups');
});
