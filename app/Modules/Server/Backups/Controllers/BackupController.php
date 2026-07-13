<?php

namespace App\Modules\Server\Backups\Controllers;

use App\Models\Server;
use App\Models\ServerBackup;
use App\Models\ServerBackupArchive;
use App\Modules\Server\Backups\Actions\BackupAction;
use App\Modules\Server\Backups\Requests\StoreBackupRequest;
use App\Modules\Server\Backups\Requests\UpdateBackupRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

class BackupController
{
    public function index(Server $server): View
    {
        Gate::authorize('view', $server);

        $backups = $server->backups()
            ->with('archives')
            ->latest()
            ->get();

        return view('modules.server.backups.index', compact('backups', 'server'));
    }

    public function store(StoreBackupRequest $request, Server $server): RedirectResponse
    {
        Gate::authorize('update', $server);

        $data = $request->validated();

        $server->backups()->create([
            'name' => $data['name'],
            'type' => $data['type'],
            'targets' => $data['targets'],
            'storage' => $data['storage'],
            's3_config' => $data['storage'] === 's3' ? [
                'bucket' => $data['s3_config']['bucket'],
                'region' => $data['s3_config']['region'],
                'endpoint' => $data['s3_config']['endpoint'] ?? null,
                'access_key_id' => $data['s3_config']['access_key_id'],
                'secret_access_key' => $data['s3_config']['secret_access_key'],
            ] : null,
            'schedule' => $data['schedule'] ?? null,
            'enabled' => $request->boolean('enabled'),
            'retention_days' => (int) $data['retention_days'],
            'user_id' => $request->user()->id,
        ]);

        return to_route('server.backups.index', $server)
            ->with('flash', ['success' => 'Backup-Konfiguration gespeichert.']);
    }

    public function update(UpdateBackupRequest $request, Server $server, ServerBackup $backup): RedirectResponse
    {
        Gate::authorize('update', $server);
        abort_unless($backup->server_id === $server->id, 404);

        $data = $request->validated();

        $s3Config = $backup->s3_config;

        $backup->update([
            'name' => $data['name'],
            'type' => $data['type'],
            'targets' => $data['targets'],
            'storage' => $data['storage'],
            's3_config' => $data['storage'] === 's3' ? [
                'bucket' => $data['s3_config']['bucket'],
                'region' => $data['s3_config']['region'],
                'endpoint' => $data['s3_config']['endpoint'] ?? null,
                'access_key_id' => $data['s3_config']['access_key_id'],
                'secret_access_key' => $data['s3_config']['secret_access_key'] ?? ($s3Config['secret_access_key'] ?? ''),
            ] : null,
            'schedule' => $data['schedule'] ?? null,
            'enabled' => $request->boolean('enabled'),
            'retention_days' => (int) $data['retention_days'],
        ]);

        return to_route('server.backups.index', $server)
            ->with('flash', ['success' => 'Backup-Konfiguration aktualisiert.']);
    }

    public function destroy(Server $server, ServerBackup $backup): RedirectResponse
    {
        Gate::authorize('update', $server);
        abort_unless($backup->server_id === $server->id, 404);

        $backup->delete();

        return to_route('server.backups.index', $server)
            ->with('flash', ['success' => 'Backup-Konfiguration gelöscht.']);
    }

    public function run(Request $request, Server $server, ServerBackup $backup, BackupAction $action): JsonResponse
    {
        Gate::authorize('update', $server);
        abort_unless($backup->server_id === $server->id, 404);

        $backup->update([
            'last_status' => 'running',
            'last_run_at' => now(),
        ]);

        $archive = $backup->archives()->create([
            'type' => $backup->type,
            'filename' => '',
            'storage' => $backup->storage,
            'status' => 'running',
        ]);

        $result = $action->run(
            $server,
            $backup->type,
            $backup->targets,
            $backup->storage,
            $backup->s3_config,
            $backup->retention_days,
        );

        $archive->update([
            'filename' => $result['filename'] ?? ('backup-'.now()->format('Y-m-d-H-i-s').'.tar.gz'),
            'size_bytes' => $result['size_bytes'] ?? null,
            'storage_path' => $result['storage_path'] ?? null,
            'status' => $result['success'] ? 'success' : 'failed',
            'exit_code' => $result['success'] ? 0 : 1,
            'output' => $result['message'],
            'error_output' => $result['success'] ? null : ($result['message'] ?? null),
            'completed_at' => now(),
        ]);

        $backup->update([
            'last_status' => $result['success'] ? 'success' : 'failed',
        ]);

        if ($result['success']) {
            $action->prune($server, $backup->id, $backup->retention_days);
        }

        return response()->json([
            'success' => $result['success'],
            'message' => $result['message'],
            'archive' => $archive->fresh(),
        ], $result['success'] ? 200 : 422);
    }

    public function toggle(Request $request, Server $server, ServerBackup $backup): RedirectResponse
    {
        Gate::authorize('update', $server);
        abort_unless($backup->server_id === $server->id, 404);

        $backup->update(['enabled' => ! $backup->enabled]);

        return to_route('server.backups.index', $server)
            ->with('flash', ['success' => $backup->enabled ? 'Backup aktiviert.' : 'Backup deaktiviert.']);
    }

    public function archives(Request $request, Server $server, ServerBackup $backup, BackupAction $action): JsonResponse
    {
        Gate::authorize('view', $server);
        abort_unless($backup->server_id === $server->id, 404);

        $archives = $backup->archives()
            ->latest()
            ->get();

        $remoteResult = $action->list($server, $backup->type, $backup->id);
        $remoteFiles = $remoteResult['success'] ? ($remoteResult['files'] ?? []) : [];

        return response()->json([
            'success' => true,
            'archives' => $archives,
            'remote_files' => $remoteFiles,
        ]);
    }

    public function restore(Request $request, Server $server, ServerBackupArchive $archive, BackupAction $action): JsonResponse
    {
        Gate::authorize('update', $server);
        abort_unless($archive->backup->server_id === $server->id, 404);

        $result = $action->restore(
            $server,
            $archive->filename,
            $archive->type,
            $archive->backup->targets,
        );

        return response()->json([
            'success' => $result['success'],
            'message' => $result['message'],
            'output' => $result['output'] ?? null,
        ], $result['success'] ? 200 : 422);
    }

    public function destroyArchive(Request $request, Server $server, ServerBackupArchive $archive, BackupAction $action): JsonResponse
    {
        Gate::authorize('update', $server);
        abort_unless($archive->backup->server_id === $server->id, 404);

        $action->delete($server, $archive->filename);

        $archive->delete();

        return response()->json([
            'success' => true,
            'message' => 'Archiv gelöscht.',
        ]);
    }

    public function download(Request $request, Server $server, ServerBackupArchive $archive): JsonResponse
    {
        Gate::authorize('view', $server);
        abort_unless($archive->backup->server_id === $server->id, 404);

        if ($archive->storage === 'local') {
            return response()->json([
                'success' => false,
                'message' => 'Download von lokalen Backups wird über den Server-Pfad bereitgestellt.',
                'path' => $archive->storage_path,
            ]);
        }

        return response()->json([
            'success' => true,
            'message' => 'Backup ist im S3-Bucket gespeichert.',
            'bucket' => $archive->backup->s3_config['bucket'] ?? null,
            'key' => $archive->storage_path,
        ]);
    }
}
