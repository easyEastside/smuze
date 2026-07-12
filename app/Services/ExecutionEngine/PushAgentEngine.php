<?php

namespace App\Services\ExecutionEngine;

use App\Models\Server;
use App\Services\ConnectionResult;
use Illuminate\Support\Facades\Http;

class PushAgentEngine
{
    public function execute(
        Server $server,
        string $command,
        int $timeout = 30,
        bool $useSudo = true,
        ?callable $onOutput = null,
    ): ExecutionResult {
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
            return new ExecutionResult(
                stdout: '',
                stderr: 'Agent communication failed: '.$response->status(),
                exitCode: -1,
                success: false,
            );
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

        return new ExecutionResult(
            stdout: $stdout,
            stderr: $stderr,
            exitCode: $exitCode,
            success: $exitCode === 0,
        );
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
}
