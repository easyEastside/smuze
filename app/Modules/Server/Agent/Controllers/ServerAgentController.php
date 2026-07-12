<?php

namespace App\Modules\Server\Agent\Controllers;

use App\Models\Server;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Str;

class ServerAgentController
{
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

    private function installCommand(string $appUrl, Server $server, string $token): string
    {
        return 'smuze-agent install'
            .' --app-url '.escapeshellarg($appUrl)
            .' --server-id '.escapeshellarg((string) $server->id)
            .' --token '.escapeshellarg($token)
            .' && systemctl daemon-reload && systemctl enable --now smuze-agent';
    }
}
