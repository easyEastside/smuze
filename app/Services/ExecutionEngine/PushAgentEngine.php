<?php

namespace App\Services\ExecutionEngine;

use App\Models\Server;
use App\Models\ServerAgentCommand;
use App\Services\ConnectionResult;
use Illuminate\Support\Facades\Http;

class PushAgentEngine
{
    /**
     * @param  array<string, mixed>  $payload
     */
    public function action(
        Server $server,
        string $action,
        array $payload = [],
        ?callable $onOutput = null,
    ): ExecutionResult {
        $startedAt = now();
        $started = microtime(true);

        try {
            $response = Http::timeout(3605)
                ->withToken($server->agent_token)
                ->acceptJson()
                ->post($this->agentUrl($server).'/actions', [
                    'action' => $action,
                    'payload' => $payload,
                ]);

            if (! $response->ok() && $response->status() !== 422) {
                $result = new ExecutionResult(
                    stdout: '',
                    stderr: 'Agent action failed: '.$response->status(),
                    exitCode: -1,
                    success: false,
                );

                $this->recordAction($server, $action, $payload, $result, $startedAt, $started);

                return $result;
            }

            $data = $response->json() ?: [];
            $stdout = (string) ($data['stdout'] ?? '');
            $stderr = (string) ($data['stderr'] ?? $data['error'] ?? '');

            if ($onOutput !== null && $stdout !== '') {
                $onOutput('stdout', $stdout);
            }

            if ($onOutput !== null && $stderr !== '') {
                $onOutput('stderr', $stderr);
            }

            $result = new ExecutionResult(
                stdout: $stdout,
                stderr: $stderr,
                exitCode: (int) ($data['exit_code'] ?? -1),
                success: (bool) ($data['success'] ?? false),
            );

            $this->recordAction($server, $action, $payload, $result, $startedAt, $started);

            return $result;
        } catch (\Throwable $e) {
            $result = new ExecutionResult(
                stdout: '',
                stderr: 'Agent action failed: '.$e->getMessage(),
                exitCode: -1,
                success: false,
            );

            $this->recordAction($server, $action, $payload, $result, $startedAt, $started);

            return $result;
        }
    }

    public function execute(
        Server $server,
        string $command,
        int $timeout = 30,
        bool $useSudo = true,
        ?callable $onOutput = null,
    ): ExecutionResult {
        $startedAt = now();
        $started = microtime(true);

        try {
            $url = $this->agentUrl($server).'/execute';

            $response = Http::timeout($timeout + 5)
                ->withToken($server->agent_token)
                ->acceptJson()
                ->withOptions(['stream' => true])
                ->post($url, [
                    'command' => $command,
                    'timeout' => $timeout,
                    'use_sudo' => $useSudo,
                ]);

            if ($response->failed()) {
                $result = new ExecutionResult(
                    stdout: '',
                    stderr: 'Agent communication failed: '.$response->status(),
                    exitCode: -1,
                    success: false,
                );

                $this->recordCommand($server, $command, $timeout, $useSudo, $result, $startedAt, $started);

                return $result;
            }
        } catch (\Throwable $e) {
            $result = new ExecutionResult(
                stdout: '',
                stderr: 'Agent communication failed: '.$e->getMessage(),
                exitCode: -1,
                success: false,
            );

            $this->recordCommand($server, $command, $timeout, $useSudo, $result, $startedAt, $started);

            return $result;
        }

        $body = $response->body();
        $lines = explode("\n", trim($body));

        $stdout = '';
        $stderr = '';
        $exitCode = -1;

        foreach ($lines as $line) {
            if (trim($line) === '') {
                continue;
            }

            $chunk = json_decode($line, true);

            if ($chunk === null) {
                continue;
            }

            if (isset($chunk['stream']) && isset($chunk['data'])) {
                if ($chunk['stream'] === 'stdout') {
                    $stdout .= $chunk['data'];

                    if ($onOutput !== null) {
                        $onOutput('stdout', $chunk['data']);
                    }
                } elseif ($chunk['stream'] === 'stderr') {
                    $stderr .= $chunk['data'];

                    if ($onOutput !== null) {
                        $onOutput('stderr', $chunk['data']);
                    }
                }
            }

            if (isset($chunk['done']) && $chunk['done'] === true) {
                $exitCode = $chunk['exit_code'] ?? -1;
                break;
            }

            if (isset($chunk['error'])) {
                $stderr .= $chunk['error'];
            }
        }

        $result = new ExecutionResult(
            stdout: $stdout,
            stderr: $stderr,
            exitCode: $exitCode,
            success: $exitCode === 0,
        );

        $this->recordCommand($server, $command, $timeout, $useSudo, $result, $startedAt, $started);

        return $result;
    }

    public function test(Server $server, int $timeout = 5): ConnectionResult
    {
        $start = microtime(true);

        try {
            $response = Http::timeout($timeout)
                ->withToken($server->agent_token)
                ->acceptJson()
                ->get($this->agentUrl($server).'/health');

            $latency = (microtime(true) - $start) * 1000;

            if ($response->successful()) {
                $data = $response->json();

                $server->forceFill([
                    'agent_enabled' => true,
                    'agent_version' => $data['version'] ?? $server->agent_version,
                    'agent_last_seen_at' => now(),
                    'agent_status' => 'connected',
                ])->save();

                return new ConnectionResult(success: true, latencyMs: round($latency, 1));
            }

            $server->forceFill([
                'agent_status' => 'disconnected',
            ])->save();

            return new ConnectionResult(
                success: false,
                latencyMs: round($latency, 1),
                errorMessage: 'Agent responded with status '.$response->status(),
            );
        } catch (\Throwable $e) {
            $server->forceFill([
                'agent_status' => 'disconnected',
            ])->save();

            return new ConnectionResult(
                success: false,
                latencyMs: round((microtime(true) - $start) * 1000, 1),
                errorMessage: $e->getMessage(),
            );
        }
    }

    public function triggerUpdate(Server $server): array
    {
        try {
            $response = Http::timeout(5)
                ->withToken($server->agent_token)
                ->acceptJson()
                ->post($this->agentUrl($server).'/update');

            if ($response->successful()) {
                return ['success' => true, 'message' => 'Agent-Update gestartet.'];
            }

            return ['success' => false, 'message' => 'Agent antwortete mit Status '.$response->status()];
        } catch (\Throwable $e) {
            return ['success' => false, 'message' => 'Update fehlgeschlagen: '.$e->getMessage()];
        }
    }

    private function agentUrl(Server $server): string
    {
        $port = $server->agent_port ?? config('agent.push_port', 9300);

        return "http://{$server->host}:{$port}";
    }

    private function recordCommand(
        Server $server,
        string $command,
        int $timeout,
        bool $useSudo,
        ExecutionResult $result,
        \DateTimeInterface $startedAt,
        float $started,
    ): void {
        ServerAgentCommand::create([
            'server_id' => $server->id,
            'user_id' => auth()->id(),
            'source' => 'agent',
            'command' => $this->truncate($command, 2000),
            'timeout' => $timeout,
            'use_sudo' => $useSudo,
            'exit_code' => $result->exitCode,
            'success' => $result->success,
            'duration_ms' => (int) round((microtime(true) - $started) * 1000),
            'stdout' => $this->truncate($result->stdout, 8000),
            'stderr' => $this->truncate($result->stderr, 8000),
            'started_at' => $startedAt,
            'finished_at' => now(),
        ]);
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    private function recordAction(
        Server $server,
        string $action,
        array $payload,
        ExecutionResult $result,
        \DateTimeInterface $startedAt,
        float $started,
    ): void {
        ServerAgentCommand::create([
            'server_id' => $server->id,
            'user_id' => auth()->id(),
            'source' => 'action',
            'action' => $action,
            'payload' => $payload,
            'command' => null,
            'timeout' => 0,
            'use_sudo' => true,
            'exit_code' => $result->exitCode,
            'success' => $result->success,
            'duration_ms' => (int) round((microtime(true) - $started) * 1000),
            'stdout' => $this->truncate($result->stdout, 8000),
            'stderr' => $this->truncate($result->stderr, 8000),
            'started_at' => $startedAt,
            'finished_at' => now(),
        ]);
    }

    private function truncate(string $value, int $limit): string
    {
        if (mb_strlen($value) <= $limit) {
            return $value;
        }

        return mb_substr($value, 0, $limit).'... [truncated]';
    }
}
