<?php

namespace App\Modules\Server\Agent\Controllers;

use App\Models\Server;
use App\Services\ExecutionEngine\PushAgentEngine;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

class ServerAgentController
{
    public function __construct(
        private PushAgentEngine $agent,
    ) {}

    public function rotateToken(Request $request, Server $server): JsonResponse
    {
        Gate::authorize('update', $server);

        $token = 'smz_'.Str::random(64);

        $server->forceFill([
            'agent_enabled' => true,
            'agent_token' => $token,
            'agent_status' => 'disconnected',
            'agent_port' => $server->agent_port ?? config('agent.push_port', 9300),
        ])->save();

        return response()->json([
            'success' => true,
            'token' => $token,
            'server_id' => $server->id,
            'app_url' => $request->getSchemeAndHttpHost(),
            'port' => $server->agent_port,
            'install_command' => $this->installCommand($request->getSchemeAndHttpHost(), $server, $token),
        ]);
    }

    public function disable(Server $server): JsonResponse
    {
        Gate::authorize('update', $server);

        $server->forceFill([
            'agent_enabled' => false,
            'agent_token' => null,
            'agent_status' => 'disconnected',
        ])->save();

        return response()->json(['success' => true]);
    }

    public function install(Request $request, Server $server): JsonResponse
    {
        Gate::authorize('update', $server);

        $token = 'smz_'.Str::random(64);
        $appUrl = $request->getSchemeAndHttpHost();
        $port = $server->agent_port ?? config('agent.push_port', 9300);

        $server->forceFill([
            'agent_enabled' => true,
            'agent_token' => $token,
            'agent_status' => 'disconnected',
            'agent_port' => $port,
        ])->save();

        return response()->json([
            'success' => true,
            'token' => $token,
            'server_id' => $server->id,
            'app_url' => $appUrl,
            'port' => $port,
            'message' => 'Install-Kommando generiert. Führe es manuell auf dem Server aus.',
            'install_command' => $this->installCommand($appUrl, $server, $token),
        ]);
    }

    public function checkUpdate(Request $request, Server $server): JsonResponse
    {
        Gate::authorize('view', $server);

        $latestVersion = config('agent.latest_version', '0.1.0');
        $currentVersion = $server->agent_version ?? '0.0.0';
        $hasUpdate = version_compare($latestVersion, $currentVersion, '>');

        $response = [
            'has_update' => $hasUpdate,
            'current_version' => $currentVersion,
            'latest_version' => $latestVersion,
        ];

        if ($hasUpdate) {
            $versionPath = storage_path('app/agent/version.json');

            if (file_exists($versionPath)) {
                $versionData = json_decode(file_get_contents($versionPath), true);
                $response['checksum'] = $versionData['checksum'] ?? '';
            }
        }

        return response()->json($response);
    }

    public function updateAgent(Request $request, Server $server): JsonResponse
    {
        Gate::authorize('update', $server);

        if (! $server->agent_enabled) {
            return response()->json([
                'success' => false,
                'message' => 'Agent ist nicht aktiviert.',
            ]);
        }

        $result = $this->agent->triggerUpdate($server);

        return response()->json($result);
    }

    public function downloadBinary(): mixed
    {
        $path = storage_path('app/agent/smuze-agent');

        if (! file_exists($path)) {
            abort(404, 'Agent binary not built. Run go build in scripts/smuze-agent first.');
        }

        return response()->file($path, [
            'Content-Type' => 'application/octet-stream',
        ]);
    }

    private function installCommand(string $appUrl, Server $server, string $token): string
    {
        $downloadUrl = rtrim($appUrl, '/').'/agent/download';
        $binaryPath = '/usr/local/bin/smuze-agent';
        $tmpPath = $binaryPath.'.tmp';

        return '(command -v curl >/dev/null 2>&1 || (apt-get update -qq && apt-get install -y -qq curl))'
            .' && curl -fsSL '.escapeshellarg($downloadUrl).' -o '.escapeshellarg($tmpPath)
            .' && mv '.escapeshellarg($tmpPath).' '.escapeshellarg($binaryPath)
            .' && chmod +x '.escapeshellarg($binaryPath)
            .' && '.escapeshellarg($binaryPath).' install'
            .' --app-url '.escapeshellarg($appUrl)
            .' --server-id '.escapeshellarg((string) $server->id)
            .' --token '.escapeshellarg($token)
            .' --port '.escapeshellarg((string) $server->agent_port)
            .' && systemctl daemon-reload && systemctl restart smuze-agent';
    }

    public function proxyHealth(Server $server): JsonResponse
    {
        Gate::authorize('view', $server);

        try {
            $response = Http::timeout(5)
                ->withToken($server->agent_token)
                ->acceptJson()
                ->get($this->agentBaseUrl($server).'/health');

            return response()->json($response->json() ?: ['error' => 'invalid_response'], $response->status());
        } catch (ConnectionException) {
            return response()->json(['error' => 'Agent nicht erreichbar'], 503);
        }
    }

    public function proxyMetrics(Server $server): JsonResponse
    {
        Gate::authorize('view', $server);

        try {
            $response = Http::timeout(10)
                ->withToken($server->agent_token)
                ->acceptJson()
                ->get($this->agentBaseUrl($server).'/metrics');

            return response()->json($response->json() ?: ['error' => 'invalid_response'], $response->status());
        } catch (ConnectionException) {
            return response()->json(['error' => 'Agent nicht erreichbar'], 503);
        }
    }

    public function proxyExecute(Request $request, Server $server): JsonResponse
    {
        Gate::authorize('update', $server);

        $data = $request->validate([
            'command' => ['required', 'string', 'max:5000'],
            'timeout' => ['nullable', 'integer', 'min:1', 'max:3600'],
            'use_sudo' => ['nullable', 'boolean'],
        ]);

        try {
            $response = Http::timeout(($data['timeout'] ?? 30) + 5)
                ->withToken($server->agent_token)
                ->acceptJson()
                ->post($this->agentBaseUrl($server).'/execute', [
                    'command' => $data['command'],
                    'timeout' => $data['timeout'] ?? 30,
                    'use_sudo' => $data['use_sudo'] ?? true,
                ]);

            if ($response->successful()) {
                $lines = explode("\n", trim($response->body()));
                $result = ['success' => true, 'data' => []];

                foreach ($lines as $line) {
                    if (trim($line) === '') {
                        continue;
                    }

                    $chunk = json_decode($line, true);

                    if ($chunk === null) {
                        continue;
                    }

                    $result['data'][] = $chunk;

                    if (isset($chunk['done']) && $chunk['done'] === true) {
                        $result['exit_code'] = $chunk['exit_code'] ?? -1;
                        break;
                    }
                }

                return response()->json($result);
            }

            return response()->json(['success' => false, 'error' => 'Agent responded with '.$response->status()], $response->status());
        } catch (ConnectionException) {
            return response()->json(['success' => false, 'error' => 'Agent nicht erreichbar'], 503);
        }
    }

    private function agentBaseUrl(Server $server): string
    {
        $port = $server->agent_port ?? config('agent.push_port', 9300);

        return "http://{$server->host}:{$port}";
    }
}
