<?php

namespace App\Modules\Server\Terminal\Controllers;

use App\Models\Server;
use App\Modules\Server\Terminal\Actions\CreateTerminalSession;
use App\Services\SshService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\View\View;

class TerminalController
{
    public function index(Request $request, Server $server): View
    {
        if ($server->user_id !== $request->user()->id) {
            abort(403);
        }

        return view('modules.server.terminal.index', compact('server'));
    }

    public function store(Request $request, Server $server, CreateTerminalSession $createTerminalSession): JsonResponse
    {
        if ($server->user_id !== $request->user()->id) {
            abort(403);
        }

        return response()->json($createTerminalSession->handle(
            $server,
            $request->user()->id,
            $this->websocketUrl($request),
            'terminal',
        ));
    }

    public function metrics(Request $request, Server $server, CreateTerminalSession $createTerminalSession): JsonResponse
    {
        if ($server->user_id !== $request->user()->id) {
            abort(403);
        }

        return response()->json($createTerminalSession->handle(
            $server,
            $request->user()->id,
            $this->websocketUrl($request),
            'metrics',
        ));
    }

    public function resolve(Request $request, string $token, SshService $ssh): JsonResponse
    {
        $secret = (string) config('terminal.secret');
        $providedSecret = (string) $request->header('X-Terminal-Secret', '');

        if ($secret === '' || ! hash_equals($secret, $providedSecret)) {
            abort(403);
        }

        $session = Cache::get("terminal-session:{$token}");

        if (! is_array($session)) {
            abort(404);
        }

        $server = Server::query()->findOrFail($session['server_id']);

        if ((int) $server->user_id !== (int) $session['user_id']) {
            abort(403);
        }

        return response()->json([
            'session' => $session,
            'server' => $ssh->terminalConnection($server),
        ]);
    }

    private function websocketUrl(Request $request): string
    {
        $configuredUrl = config('terminal.websocket_url');

        if (is_string($configuredUrl) && $configuredUrl !== '') {
            return $configuredUrl;
        }

        $scheme = $request->isSecure() ? 'wss' : 'ws';
        $host = $request->getHost();
        $port = (int) config('terminal.websocket_port', 8081);

        return "{$scheme}://{$host}:{$port}";
    }
}
