<?php

namespace App\Console\Commands;

use App\Models\ServerBackup;
use App\Modules\Server\Backups\Actions\BackupAction;
use App\Services\ExecutionEngine\PushAgentEngine;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;

class RunScheduledBackups extends Command
{
    protected $signature = 'backups:run-scheduled';

    protected $description = 'Führt fällige geplante Backups aus';

    public function handle(PushAgentEngine $engine): int
    {
        $backups = ServerBackup::query()
            ->where('enabled', true)
            ->whereNotNull('schedule')
            ->get();

        $ran = 0;

        foreach ($backups as $backup) {
            $lockKey = 'backup:running:'.$backup->id;

            if (Cache::has($lockKey)) {
                continue;
            }

            if (! $this->isDue($backup)) {
                continue;
            }

            $server = $backup->server;

            if (! $server->agent_enabled || $server->agent_status !== 'connected') {
                continue;
            }

            $action = new BackupAction($engine);

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

            Cache::put($lockKey, true, 600);

            $result = $action->run(
                $server,
                $backup->type,
                $backup->targets,
                $backup->storage,
                $backup->s3_config,
                $backup->retention_days,
            );

            Cache::forget($lockKey);

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

            $ran++;
        }

        $this->info("{$ran} geplante Backups ausgeführt.");

        return Command::SUCCESS;
    }

    private function isDue(ServerBackup $backup): bool
    {
        $lastRun = $backup->last_run_at;
        $schedule = $backup->schedule;

        if ($lastRun === null) {
            return true;
        }

        [$minute, $hour, $dayOfMonth, $month, $dayOfWeek] = explode(' ', $schedule);
        $parts = compact('minute', 'hour', 'dayOfMonth', 'month', 'dayOfWeek');

        $check = $lastRun->copy()->addMinute();

        for ($i = 0; $i < 60; $i++) {
            if ($this->matchesCron($check, $parts)) {
                return $check->isPast();
            }

            $check->addMinute();
        }

        return false;
    }

    /** @param array<string, string> $parts */
    private function matchesCron(Carbon $date, array $parts): bool
    {
        foreach ($parts as $field => $pattern) {
            $value = match ($field) {
                'minute' => (int) $date->format('i'),
                'hour' => (int) $date->format('H'),
                'dayOfMonth' => (int) $date->format('d'),
                'month' => (int) $date->format('m'),
                'dayOfWeek' => (int) $date->format('w'),
            };

            if (! $this->fieldMatches($pattern, $value)) {
                return false;
            }
        }

        return true;
    }

    private function fieldMatches(string $pattern, int $value): bool
    {
        if ($pattern === '*') {
            return true;
        }

        if (str_contains($pattern, ',')) {
            foreach (explode(',', $pattern) as $part) {
                if ($this->fieldMatches(trim($part), $value)) {
                    return true;
                }
            }

            return false;
        }

        if (str_contains($pattern, '/')) {
            [$range, $step] = explode('/', $pattern, 2);
            $range = $range === '*' ? [0, 59] : explode('-', $range);

            $start = (int) ($range[0] ?? 0);
            $end = (int) ($range[1] ?? 59);
            $step = (int) $step;

            if ($value < $start || $value > $end) {
                return false;
            }

            return ($value - $start) % $step === 0;
        }

        if (str_contains($pattern, '-')) {
            [$start, $end] = explode('-', $pattern, 2);

            return $value >= (int) $start && $value <= (int) $end;
        }

        return (int) $pattern === $value;
    }
}
