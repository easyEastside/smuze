<?php

namespace App\Modules\Server\Backups\Actions;

use App\Models\Server;
use App\Services\ExecutionEngine\PushAgentEngine;

class BackupAction
{
    public function __construct(
        private PushAgentEngine $engine,
    ) {}

    public function run(Server $server, string $type, array $targets, string $storage, ?array $s3Config, int $retentionDays): array
    {
        $result = $this->engine->action($server, 'backup.run', [
            'type' => $type,
            'targets' => $targets,
            'storage' => $storage,
            's3_config' => $s3Config,
            'retention_days' => $retentionDays,
        ]);

        if (! $result->success) {
            return [
                'success' => false,
                'message' => $result->stderr ?: 'Backup-Ausführung fehlgeschlagen.',
            ];
        }

        $data = json_decode($result->stdout, true);

        return [
            'success' => true,
            'filename' => $data['filename'] ?? null,
            'size_bytes' => $data['size_bytes'] ?? null,
            'storage_path' => $data['storage_path'] ?? null,
            'message' => 'Backup wurde erstellt.',
        ];
    }

    public function list(Server $server, ?string $type = null, ?int $backupId = null): array
    {
        $result = $this->engine->action($server, 'backup.list', array_filter([
            'type' => $type,
            'backup_id' => $backupId,
        ]));

        if (! $result->success) {
            return [
                'success' => false,
                'files' => [],
                'message' => $result->stderr ?: 'Backup-Liste konnte nicht geladen werden.',
            ];
        }

        $data = json_decode($result->stdout, true);

        return [
            'success' => true,
            'files' => $data['files'] ?? [],
        ];
    }

    public function delete(Server $server, string $filename): array
    {
        $result = $this->engine->action($server, 'backup.delete', [
            'filename' => $filename,
        ]);

        return [
            'success' => $result->success,
            'message' => $result->success ? 'Backup gelöscht.' : ($result->stderr ?: 'Backup konnte nicht gelöscht werden.'),
        ];
    }

    public function restore(Server $server, string $filename, string $type, ?array $targets): array
    {
        $result = $this->engine->action($server, 'backup.restore', array_filter([
            'filename' => $filename,
            'type' => $type,
            'targets' => $targets,
        ]));

        return [
            'success' => $result->success,
            'message' => $result->success ? 'Backup wiederhergestellt.' : ($result->stderr ?: 'Wiederherstellung fehlgeschlagen.'),
            'output' => $result->stdout,
        ];
    }

    public function prune(Server $server, int $backupId, int $retentionDays): array
    {
        $result = $this->engine->action($server, 'backup.prune', [
            'backup_id' => $backupId,
            'retention_days' => $retentionDays,
        ]);

        if (! $result->success) {
            return [
                'success' => false,
                'deleted' => [],
                'message' => $result->stderr ?: 'Aufräumen fehlgeschlagen.',
            ];
        }

        $data = json_decode($result->stdout, true);

        return [
            'success' => true,
            'deleted' => $data['deleted'] ?? [],
            'message' => $data['message'] ?? 'Alte Backups bereinigt.',
        ];
    }
}
