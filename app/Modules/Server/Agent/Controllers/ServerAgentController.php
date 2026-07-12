<?php

namespace App\Modules\Server\Agent\Controllers;

use App\Models\Server;
use App\Services\SshService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Str;

class ServerAgentController
{
    public function __construct(
        private SshService $ssh,
    ) {}

    public function rotateToken(Request $request, Server $server): JsonResponse
    {
        Gate::authorize('update', $server);

        $token = 'smz_'.Str::random(64);

        $server->forceFill([
            'agent_enabled' => true,
            'agent_token' => $token,
            'agent_status' => 'disconnected',
            'agent_transport' => 'polling',
            'execution_driver' => $server->execution_driver === 'ssh' ? 'auto' : $server->execution_driver,
        ])->save();

        $this->ssh->execute($server, 'systemctl restart smuze-agent', timeout: 10);

        return response()->json([
            'success' => true,
            'token' => $token,
            'server_id' => $server->id,
            'app_url' => $request->getSchemeAndHttpHost(),
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

        $server->forceFill([
            'agent_enabled' => true,
            'agent_token' => $token,
            'agent_status' => 'disconnected',
            'agent_transport' => 'polling',
            'execution_driver' => $server->execution_driver === 'ssh' ? 'auto' : $server->execution_driver,
        ])->save();

        $script = $this->buildRemoteInstallScript($appUrl, $server, $token);
        $result = $this->ssh->execute($server, $script, timeout: 120, useSudo: true);

        return response()->json([
            'success' => $result->success,
            'message' => $result->success
                ? 'Agent installiert und gestartet.'
                : 'Installation fehlgeschlagen: '.$result->stderr,
        ]);
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
            .' && systemctl daemon-reload && systemctl restart smuze-agent';
    }

    private function buildRemoteInstallScript(string $appUrl, Server $server, string $token): string
    {
        $downloadUrl = rtrim($appUrl, '/').'/agent/download';
        $binaryPath = '/usr/local/bin/smuze-agent';

        $tmpPath = $binaryPath.'.tmp';

        return implode(' && ', [
            'curl -fsSL '.escapeshellarg($downloadUrl).' -o '.$tmpPath,
            'mv '.$tmpPath.' '.$binaryPath,
            'chmod +x '.$binaryPath,
            $binaryPath.' install'
                .' --app-url '.escapeshellarg($appUrl)
                .' --server-id '.escapeshellarg((string) $server->id)
                .' --token '.escapeshellarg($token),
            'systemctl daemon-reload',
            'systemctl restart smuze-agent',
        ]);
    }
}
