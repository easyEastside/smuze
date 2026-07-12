<?php

namespace App\Modules\Server\Agent\Controllers;

use App\Models\Server;
use App\Models\ServerCommand;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class AgentController
{
    public function heartbeat(Request $request): JsonResponse
    {
        $server = $this->authenticate($request);
        $data = $request->validate([
            'version' => ['nullable', 'string', 'max:20'],
        ]);

        $server->forceFill([
            'agent_enabled' => true,
            'agent_version' => $data['version'] ?? $server->agent_version,
            'agent_last_seen_at' => now(),
            'agent_status' => 'connected',
        ])->save();

        $latestVersion = config('agent.latest_version', '0.1.0');
        $currentVersion = $data['version'] ?? '0.0.0';

        $response = ['success' => true];

        if (version_compare($latestVersion, $currentVersion, '>')) {
            $response['update'] = [
                'latest_version' => $latestVersion,
                'download_url' => url('/agent/download'),
            ];

            $versionPath = storage_path('app/agent/version.json');

            if (file_exists($versionPath)) {
                $versionData = json_decode(file_get_contents($versionPath), true);

                if (isset($versionData['checksum']) && $versionData['checksum'] !== '') {
                    $response['update']['checksum'] = $versionData['checksum'];
                }
            }
        }

        return response()->json($response);
    }

    public function updateCheck(Request $request): JsonResponse
    {
        $server = $this->authenticate($request);

        $latestVersion = config('agent.latest_version', '0.1.0');
        $currentVersion = $server->agent_version ?? '0.0.0';

        $update = [
            'latest_version' => $latestVersion,
            'download_url' => url('/agent/download'),
            'has_update' => version_compare($latestVersion, $currentVersion, '>'),
        ];

        $versionPath = storage_path('app/agent/version.json');

        if (file_exists($versionPath)) {
            $versionData = json_decode(file_get_contents($versionPath), true);

            if (isset($versionData['checksum']) && $versionData['checksum'] !== '') {
                $update['checksum'] = $versionData['checksum'];
            }
        }

        return response()->json([
            'success' => true,
            'update' => $update,
        ]);
    }

    public function metrics(Request $request): JsonResponse
    {
        $server = $this->authenticate($request);
        $data = $request->validate([
            'metrics' => ['required', 'array'],
            'metrics.hostname' => ['nullable', 'string', 'max:255'],
            'metrics.os' => ['nullable', 'string', 'max:255'],
            'metrics.uptime' => ['nullable', 'string', 'max:255'],
            'metrics.load' => ['nullable', 'string', 'max:50'],
            'metrics.cpu_percent' => ['nullable', 'integer', 'min:0', 'max:100'],
            'metrics.ram_total_mb' => ['nullable', 'integer', 'min:0'],
            'metrics.ram_used_mb' => ['nullable', 'integer', 'min:0'],
            'metrics.ram_percent' => ['nullable', 'integer', 'min:0', 'max:100'],
            'metrics.disk_total_mb' => ['nullable', 'integer', 'min:0'],
            'metrics.disk_used_mb' => ['nullable', 'integer', 'min:0'],
            'metrics.disk_percent' => ['nullable', 'integer', 'min:0', 'max:100'],
            'collected_at' => ['nullable', 'date'],
        ]);

        $server->forceFill([
            'agent_last_seen_at' => now(),
            'agent_status' => 'connected',
            'agent_metrics' => $data['metrics'],
            'agent_metrics_collected_at' => isset($data['collected_at']) ? $data['collected_at'] : now(),
        ])->save();

        return response()->json(['success' => true]);
    }

    public function pendingCommands(Request $request): JsonResponse
    {
        $server = $this->authenticate($request);
        $limit = min(max((int) $request->integer('limit', 1), 1), 10);

        $commands = DB::transaction(function () use ($server, $limit) {
            $commands = ServerCommand::query()
                ->whereBelongsTo($server)
                ->where('status', ServerCommand::StatusQueued)
                ->oldest()
                ->limit($limit)
                ->lockForUpdate()
                ->get();

            foreach ($commands as $command) {
                $command->forceFill([
                    'status' => ServerCommand::StatusRunning,
                    'started_at' => now(),
                ])->save();
            }

            return $commands;
        });

        return response()->json([
            'commands' => $commands->map(fn (ServerCommand $command): array => $this->commandPayload($command))->values(),
        ]);
    }

    public function commandOutput(Request $request, ServerCommand $serverCommand): JsonResponse
    {
        $server = $this->authenticate($request);
        $this->ensureCommandBelongsToServer($serverCommand, $server);

        $data = $request->validate([
            'stream' => ['required', Rule::in(['stdout', 'stderr'])],
            'data' => ['required', 'string'],
        ]);

        $column = $data['stream'];
        $serverCommand->forceFill([
            $column => ($serverCommand->{$column} ?? '').$data['data'],
            'status' => ServerCommand::StatusRunning,
            'started_at' => $serverCommand->started_at ?? now(),
        ])->save();

        return response()->json(['success' => true]);
    }

    public function completeCommand(Request $request, ServerCommand $serverCommand): JsonResponse
    {
        $server = $this->authenticate($request);
        $this->ensureCommandBelongsToServer($serverCommand, $server);

        $data = $request->validate([
            'status' => ['required', Rule::in([
                ServerCommand::StatusCompleted,
                ServerCommand::StatusFailed,
                ServerCommand::StatusTimeout,
            ])],
            'exit_code' => ['nullable', 'integer'],
            'stdout' => ['nullable', 'string'],
            'stderr' => ['nullable', 'string'],
        ]);

        $serverCommand->forceFill([
            'status' => $data['status'],
            'exit_code' => $data['exit_code'] ?? null,
            'stdout' => $data['stdout'] ?? $serverCommand->stdout,
            'stderr' => $data['stderr'] ?? $serverCommand->stderr,
            'completed_at' => $data['status'] === ServerCommand::StatusCompleted ? now() : null,
            'failed_at' => $data['status'] !== ServerCommand::StatusCompleted ? now() : null,
        ])->save();

        return response()->json(['success' => true]);
    }

    private function authenticate(Request $request): Server
    {
        $serverId = (int) $request->header('X-Smuze-Server-Id');
        $token = (string) $request->bearerToken();

        if ($serverId <= 0 || $token === '') {
            abort(403);
        }

        $server = Server::query()->findOrFail($serverId);
        $storedToken = (string) $server->agent_token;

        if (! $server->agent_enabled || $storedToken === '' || ! hash_equals($storedToken, $token)) {
            abort(403);
        }

        return $server;
    }

    private function ensureCommandBelongsToServer(ServerCommand $serverCommand, Server $server): void
    {
        if ((int) $serverCommand->server_id !== (int) $server->id) {
            abort(404);
        }
    }

    /** @return array<string, mixed> */
    private function commandPayload(ServerCommand $command): array
    {
        return [
            'id' => $command->id,
            'uuid' => $command->uuid,
            'command' => $command->command,
            'use_sudo' => $command->use_sudo,
            'timeout' => $command->timeout,
        ];
    }
}
