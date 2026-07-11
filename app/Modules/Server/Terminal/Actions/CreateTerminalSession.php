<?php

namespace App\Modules\Server\Terminal\Actions;

use App\Models\Server;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;

class CreateTerminalSession
{
    /** @return array{token: string, websocket_url: string, expires_at: string} */
    public function handle(Server $server, int $userId, string $websocketUrl): array
    {
        $token = Str::random(64);
        $ttl = (int) config('terminal.token_ttl_seconds', 60);
        $expiresAt = now()->addSeconds($ttl);

        Cache::put("terminal-session:{$token}", [
            'server_id' => $server->id,
            'user_id' => $userId,
            'expires_at' => $expiresAt->toISOString(),
        ], $expiresAt);

        return [
            'token' => $token,
            'websocket_url' => $websocketUrl,
            'expires_at' => $expiresAt->toISOString(),
        ];
    }
}
