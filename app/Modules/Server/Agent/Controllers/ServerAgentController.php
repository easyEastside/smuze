<?php

namespace App\Modules\Server\Agent\Controllers;

use App\Models\Server;
use App\Models\ServerAgentCommand;
use App\Services\ExecutionEngine\PushAgentEngine;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Symfony\Component\HttpFoundation\StreamedResponse;

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

        $release = $this->agentRelease();
        $latestVersion = $release['version'] ?? config('agent.latest_version', '0.1.0');
        $this->refreshAgentHealth($server);
        $server->refresh();

        $currentVersion = $server->agent_version ?? '0.0.0';
        $hasUpdate = version_compare($latestVersion, $currentVersion, '>');

        $response = [
            'has_update' => $hasUpdate,
            'current_version' => $currentVersion,
            'latest_version' => $latestVersion,
            'download_url' => $request->getSchemeAndHttpHost().'/agent/download',
        ];

        if (($release['checksum'] ?? '') !== '') {
            $response['checksum'] = $release['checksum'];
        }

        return response()->json($response);
    }

    public function version(Request $request): JsonResponse
    {
        $release = $this->agentRelease();
        $latestVersion = $release['version'] ?? config('agent.latest_version', '0.1.0');

        return response()->json([
            'latest_version' => $latestVersion,
            'download_url' => $request->getSchemeAndHttpHost().'/agent/download',
            'checksum' => $release['checksum'] ?? '',
        ]);
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

    public function terminalToken(Request $request, Server $server): JsonResponse
    {
        Gate::authorize('update', $server);

        if (! $server->agent_enabled || blank($server->agent_token)) {
            return response()->json([
                'success' => false,
                'message' => 'Agent ist nicht aktiviert.',
            ], 422);
        }

        $ttl = (int) config('agent.terminal_token_ttl', 60);
        $expiresAt = now()->addSeconds($ttl);
        $token = $this->terminalTokenFor($server, $expiresAt->getTimestamp());

        return response()->json([
            'success' => true,
            'url' => $this->terminalWebsocketUrl($request, $server, $token),
            'expires_at' => $expiresAt->toIso8601String(),
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

    /** @return array{version?: string, checksum?: string} */
    private function agentRelease(): array
    {
        $versionPath = storage_path('app/agent/version.json');

        if (! file_exists($versionPath)) {
            return [];
        }

        $release = json_decode(file_get_contents($versionPath), true);

        return is_array($release) ? $release : [];
    }

    private function installCommand(string $appUrl, Server $server, string $token): string
    {
        $downloadUrl = rtrim($appUrl, '/').'/agent/download';
        $binaryPath = '/usr/local/bin/smuze-agent';
        $tmpPath = $binaryPath.'.tmp';

        return 'SUDO=""; if [ "$(id -u)" -ne 0 ]; then SUDO="sudo"; fi'
            .' && (command -v curl >/dev/null 2>&1 || ($SUDO apt-get update -qq && $SUDO apt-get install -y -qq curl))'
            .' && $SUDO curl -fsSL '.escapeshellarg($downloadUrl).' -o '.escapeshellarg($tmpPath)
            .' && $SUDO mv '.escapeshellarg($tmpPath).' '.escapeshellarg($binaryPath)
            .' && $SUDO chmod +x '.escapeshellarg($binaryPath)
            .' && $SUDO '.escapeshellarg($binaryPath).' install'
            .' --app-url '.escapeshellarg($appUrl)
            .' --server-id '.escapeshellarg((string) $server->id)
            .' --token '.escapeshellarg($token)
            .' --port '.escapeshellarg((string) $server->agent_port)
            .' && $SUDO systemctl daemon-reload && $SUDO systemctl enable --now smuze-agent';
    }

    public function proxyHealth(Server $server): JsonResponse
    {
        Gate::authorize('view', $server);

        try {
            $response = Http::timeout(5)
                ->withToken($server->agent_token)
                ->acceptJson()
                ->get($this->agentBaseUrl($server).'/health');

            $data = $response->json() ?: ['error' => 'invalid_response'];

            if ($response->successful()) {
                $this->recordAgentHealth($server, $data);
            }

            return response()->json($data, $response->status());
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
            'input' => ['nullable', 'string', 'max:16000'],
        ]);

        $startedAt = now();
        $started = microtime(true);

        try {
            $response = Http::timeout(($data['timeout'] ?? 30) + 5)
                ->withToken($server->agent_token)
                ->acceptJson()
                ->post($this->agentBaseUrl($server).'/execute', [
                    'command' => $data['command'],
                    'timeout' => $data['timeout'] ?? 30,
                    'use_sudo' => $data['use_sudo'] ?? true,
                    'input' => $data['input'] ?? '',
                ]);

            if ($response->successful()) {
                $lines = explode("\n", trim($response->body()));
                $result = ['success' => true, 'data' => []];
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

                    $result['data'][] = $chunk;

                    if (($chunk['stream'] ?? null) === 'stdout') {
                        $stdout .= $chunk['data'] ?? '';
                    } elseif (($chunk['stream'] ?? null) === 'stderr') {
                        $stderr .= $chunk['data'] ?? '';
                    }

                    if (isset($chunk['done']) && $chunk['done'] === true) {
                        $exitCode = $chunk['exit_code'] ?? -1;
                        $result['exit_code'] = $exitCode;
                        break;
                    }
                }

                $this->recordProxyCommand(
                    $server,
                    $request,
                    $data['command'],
                    $data['timeout'] ?? 30,
                    $data['use_sudo'] ?? true,
                    $exitCode,
                    $exitCode === 0,
                    $stdout,
                    $stderr,
                    $startedAt,
                    $started,
                );

                return response()->json($result);
            }

            $this->recordProxyCommand(
                $server,
                $request,
                $data['command'],
                $data['timeout'] ?? 30,
                $data['use_sudo'] ?? true,
                -1,
                false,
                '',
                'Agent responded with '.$response->status(),
                $startedAt,
                $started,
            );

            return response()->json(['success' => false, 'error' => 'Agent responded with '.$response->status()], $response->status());
        } catch (ConnectionException) {
            $this->recordProxyCommand(
                $server,
                $request,
                $data['command'],
                $data['timeout'] ?? 30,
                $data['use_sudo'] ?? true,
                -1,
                false,
                '',
                'Agent nicht erreichbar',
                $startedAt,
                $started,
            );

            return response()->json(['success' => false, 'error' => 'Agent nicht erreichbar'], 503);
        }
    }

    public function proxyExecuteStream(Request $request, Server $server): StreamedResponse
    {
        Gate::authorize('update', $server);

        $data = $request->validate([
            'command' => ['required', 'string', 'max:5000'],
            'timeout' => ['nullable', 'integer', 'min:1', 'max:3600'],
            'use_sudo' => ['nullable', 'boolean'],
            'input' => ['nullable', 'string', 'max:16000'],
        ]);

        $timeout = $data['timeout'] ?? 30;
        $useSudo = $data['use_sudo'] ?? true;
        $startedAt = now();
        $started = microtime(true);

        return response()->stream(function () use ($request, $server, $data, $timeout, $useSudo, $startedAt, $started): void {
            $stdout = '';
            $stderr = '';
            $exitCode = -1;
            $success = false;

            try {
                $response = Http::timeout($timeout + 5)
                    ->withOptions(['stream' => true])
                    ->withToken($server->agent_token)
                    ->acceptJson()
                    ->post($this->agentBaseUrl($server).'/execute', [
                        'command' => $data['command'],
                        'timeout' => $timeout,
                        'use_sudo' => $useSudo,
                        'input' => $data['input'] ?? '',
                    ]);

                if (! $response->successful()) {
                    $stderr = 'Agent responded with '.$response->status();
                    $this->sendNdjson(['error' => $stderr, 'done' => true, 'exit_code' => -1]);

                    return;
                }

                $body = $response->toPsrResponse()->getBody();
                $buffer = '';

                while (! $body->eof()) {
                    $buffer .= $body->read(4096);
                    $lines = explode("\n", $buffer);
                    $buffer = array_pop($lines) ?? '';

                    foreach ($lines as $line) {
                        $this->handleExecuteStreamLine($line, $stdout, $stderr, $exitCode, $success);
                    }
                }

                if (trim($buffer) !== '') {
                    $this->handleExecuteStreamLine($buffer, $stdout, $stderr, $exitCode, $success);
                }
            } catch (ConnectionException) {
                $stderr = 'Agent nicht erreichbar';
                $this->sendNdjson(['error' => $stderr, 'done' => true, 'exit_code' => -1]);
            } finally {
                $this->recordProxyCommand(
                    $server,
                    $request,
                    $data['command'],
                    $timeout,
                    $useSudo,
                    $exitCode,
                    $success,
                    $stdout,
                    $stderr,
                    $startedAt,
                    $started,
                );
            }
        }, 200, [
            'Content-Type' => 'application/x-ndjson',
            'Cache-Control' => 'no-cache, no-transform',
            'X-Accel-Buffering' => 'no',
        ]);
    }

    public function proxyAction(Request $request, Server $server): JsonResponse
    {
        Gate::authorize('update', $server);

        $data = $request->validate([
            'action' => ['required', 'string', Rule::in([
                'system.apt_update',
                'system.apt_upgrade',
                'system.reboot',
                'system.shutdown',
            ])],
            'payload' => ['nullable', 'array'],
        ]);

        $result = $this->agent->action($server, $data['action'], $data['payload'] ?? []);

        return response()->json([
            'success' => $result->success,
            'action' => $data['action'],
            'exit_code' => $result->exitCode,
            'stdout' => $result->stdout,
            'stderr' => $result->stderr,
            'error' => $result->success ? null : ($result->stderr ?: 'Agent-Action fehlgeschlagen.'),
        ], $result->success ? 200 : 422);
    }

    private function agentBaseUrl(Server $server): string
    {
        $port = $server->agent_port ?? config('agent.push_port', 9300);

        return "http://{$server->host}:{$port}";
    }

    private function terminalTokenFor(Server $server, int $expiresAt): string
    {
        $payload = $this->base64UrlEncode(json_encode([
            'server_id' => $server->id,
            'exp' => $expiresAt,
            'purpose' => 'terminal',
        ], JSON_THROW_ON_ERROR));

        $signature = hash_hmac('sha256', $payload, $server->agent_token, true);

        return $payload.'.'.$this->base64UrlEncode($signature);
    }

    private function terminalWebsocketUrl(Request $request, Server $server, string $token): string
    {
        if (filled($server->agent_public_url)) {
            $publicUrl = parse_url($server->agent_public_url);
            $scheme = ($publicUrl['scheme'] ?? 'http') === 'https' ? 'wss' : 'ws';
            $host = $publicUrl['host'] ?? $server->host;
            $port = isset($publicUrl['port']) ? ':'.$publicUrl['port'] : '';
            $path = rtrim($publicUrl['path'] ?? '', '/');

            return "{$scheme}://{$host}{$port}{$path}/terminal?token={$token}";
        }

        $scheme = $request->isSecure() ? 'wss' : 'ws';
        $port = $server->agent_port ?? config('agent.push_port', 9300);

        return "{$scheme}://{$server->host}:{$port}/terminal?token={$token}";
    }

    private function base64UrlEncode(string $value): string
    {
        return rtrim(strtr(base64_encode($value), '+/', '-_'), '=');
    }

    private function refreshAgentHealth(Server $server): void
    {
        if (! $server->agent_enabled || blank($server->agent_token)) {
            return;
        }

        try {
            $response = Http::timeout(2)
                ->withToken($server->agent_token)
                ->acceptJson()
                ->get($this->agentBaseUrl($server).'/health');

            if ($response->successful()) {
                $this->recordAgentHealth($server, $response->json() ?: []);
            }
        } catch (ConnectionException) {
            // The update check should still return the stored version if the agent is restarting.
        }
    }

    /** @param array<string, mixed> $data */
    private function recordAgentHealth(Server $server, array $data): void
    {
        $server->forceFill([
            'agent_enabled' => true,
            'agent_version' => $data['version'] ?? $server->agent_version,
            'agent_last_seen_at' => now(),
            'agent_status' => 'connected',
        ])->save();
    }

    private function handleExecuteStreamLine(string $line, string &$stdout, string &$stderr, int &$exitCode, bool &$success): void
    {
        if (trim($line) === '') {
            return;
        }

        $chunk = json_decode($line, true);

        if (! is_array($chunk)) {
            return;
        }

        if (($chunk['stream'] ?? null) === 'stdout') {
            $stdout .= $chunk['data'] ?? '';
        } elseif (($chunk['stream'] ?? null) === 'stderr') {
            $stderr .= $chunk['data'] ?? '';
        }

        if (($chunk['done'] ?? false) === true) {
            $exitCode = $chunk['exit_code'] ?? -1;
            $success = $exitCode === 0 && blank($chunk['error'] ?? '');
        }

        $this->sendNdjson($chunk);
    }

    /** @param array<string, mixed> $payload */
    private function sendNdjson(array $payload): void
    {
        echo json_encode($payload, JSON_THROW_ON_ERROR | JSON_INVALID_UTF8_SUBSTITUTE)."\n";

        if (ob_get_level() > 0) {
            ob_flush();
        }

        flush();
    }

    private function recordProxyCommand(
        Server $server,
        Request $request,
        string $command,
        int $timeout,
        bool $useSudo,
        int $exitCode,
        bool $success,
        string $stdout,
        string $stderr,
        \DateTimeInterface $startedAt,
        float $started,
    ): void {
        ServerAgentCommand::create([
            'server_id' => $server->id,
            'user_id' => $request->user()?->id,
            'source' => 'proxy',
            'command' => $this->truncate($command, 2000),
            'timeout' => $timeout,
            'use_sudo' => $useSudo,
            'exit_code' => $exitCode,
            'success' => $success,
            'duration_ms' => (int) round((microtime(true) - $started) * 1000),
            'stdout' => $this->truncate($stdout, 8000),
            'stderr' => $this->truncate($stderr, 8000),
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
