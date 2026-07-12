<?php

namespace App\Modules\Server\Agent\Controllers;

use App\Models\Server;
use App\Services\ExecutionEngine\PushAgentEngine;
use App\Services\SshService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Str;

class ServerAgentController
{
    public function __construct(
        private SshService $ssh,
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
            'agent_transport' => 'push',
            'agent_port' => config('agent.push_port', 9300),
            'execution_driver' => $server->execution_driver === 'ssh' ? 'auto' : $server->execution_driver,
        ])->save();

        $this->ssh->execute($server, 'systemctl restart smuze-agent', timeout: 10);

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
            'execution_driver' => 'ssh',
        ])->save();

        return response()->json(['success' => true]);
    }

    public function install(Request $request, Server $server): JsonResponse
    {
        Gate::authorize('update', $server);

        $token = 'smz_'.Str::random(64);
        $appUrl = $request->getSchemeAndHttpHost();
        $port = config('agent.push_port', 9300);

        $server->forceFill([
            'agent_enabled' => true,
            'agent_token' => $token,
            'agent_status' => 'disconnected',
            'agent_transport' => 'push',
            'agent_port' => $port,
            'execution_driver' => $server->execution_driver === 'ssh' ? 'auto' : $server->execution_driver,
        ])->save();

        $script = $this->buildRemoteInstallScript($appUrl, $server, $token, $port);
        $result = $this->ssh->execute($server, $script, timeout: 120, useSudo: true);

        if ($result->success) {
            for ($i = 0; $i < 3; $i++) {
                if ($i > 0) {
                    sleep(1);
                }

                $connectionResult = $this->agent->test($server);

                if ($connectionResult->success) {
                    $server->forceFill([
                        'agent_status' => 'connected',
                    ])->save();

                    return response()->json([
                        'success' => true,
                        'message' => 'Agent installiert und verbunden.',
                    ]);
                }
            }

            return response()->json([
                'success' => true,
                'message' => 'Agent installiert, aber Verbindung fehlgeschlagen: '.($connectionResult->errorMessage ?? 'Zeitüberschreitung'),
            ]);
        }

        return response()->json([
            'success' => false,
            'message' => 'Installation fehlgeschlagen: '.$result->stderr,
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
        return 'smuze-agent install'
            .' --app-url '.escapeshellarg($appUrl)
            .' --server-id '.escapeshellarg((string) $server->id)
            .' --token '.escapeshellarg($token)
            .' --port '.escapeshellarg((string) $server->agent_port)
            .' && systemctl daemon-reload && systemctl restart smuze-agent';
    }

    private function buildRemoteInstallScript(string $appUrl, Server $server, string $token, int $port): string
    {
        $downloadUrl = rtrim($appUrl, '/').'/agent/download';
        $binaryPath = '/usr/local/bin/smuze-agent';

        $tmpPath = $binaryPath.'.tmp';

        return implode(' && ', [
            // Install curl if missing, then download the binary
            '(command -v curl >/dev/null 2>&1 || (apt-get update -qq && apt-get install -y -qq curl))',
            'curl -fsSL '.escapeshellarg($downloadUrl).' -o '.$tmpPath,
            'mv '.$tmpPath.' '.$binaryPath,
            'chmod +x '.$binaryPath,
            $binaryPath.' install'
                .' --app-url '.escapeshellarg($appUrl)
                .' --server-id '.escapeshellarg((string) $server->id)
                .' --token '.escapeshellarg($token)
                .' --port '.escapeshellarg((string) $port),
            'systemctl daemon-reload',
            'systemctl restart smuze-agent',
        ]);
    }
}
