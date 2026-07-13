<?php

namespace App\Modules\Server\Logs\Controllers;

use App\Models\Server;
use App\Services\ExecutionEngine\PushAgentEngine;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

class LogController
{
    private const array KNOWN_PATHS = [
        'syslog' => '/var/log/syslog',
        'auth' => '/var/log/auth.log',
        'kern' => '/var/log/kern.log',
        'dmesg' => '/var/log/dmesg',
        'nginx_access' => '/var/log/nginx/access.log',
        'nginx_error' => '/var/log/nginx/error.log',
        'apache_access' => '/var/log/apache2/access.log',
        'apache_error' => '/var/log/apache2/error.log',
        'mysql_error' => '/var/log/mysql/error.log',
        'mysql_slow' => '/var/log/mysql/mysql-slow.log',
    ];

    public function __construct(
        private PushAgentEngine $agent,
    ) {}

    public function index(Server $server): View
    {
        Gate::authorize('view', $server);

        return view('modules.server.logs.index', compact('server'));
    }

    public function fetch(Request $request, Server $server): JsonResponse
    {
        Gate::authorize('view', $server);

        $data = $request->validate([
            'source' => ['required', 'string', 'max:500'],
            'lines' => ['nullable', 'integer', 'min:10', 'max:5000'],
            'filter' => ['nullable', 'string', 'max:200'],
        ]);

        $lines = $data['lines'] ?? 200;
        $cmd = $this->buildCommand($data['source'], $lines, $data['filter'] ?? '');

        if ($cmd === null) {
            return response()->json(['error' => 'Ungültige Log-Quelle.'], 422);
        }

        $result = $this->agent->execute($server, $cmd, 15, true);

        if (! $result->success) {
            return response()->json([
                'error' => $result->stderr ?: 'Log konnte nicht gelesen werden.',
            ], 422);
        }

        $output = explode("\n", rtrim($result->stdout));

        return response()->json([
            'lines' => $output,
            'total' => count($output),
        ]);
    }

    public function stream(Request $request, Server $server): JsonResponse
    {
        Gate::authorize('view', $server);

        $data = $request->validate([
            'source' => ['required', 'string', 'max:500'],
            'filter' => ['nullable', 'string', 'max:200'],
        ]);

        $cmd = $this->buildCommand($data['source'], 50, $data['filter'] ?? '');

        if ($cmd === null) {
            return response()->json(['error' => 'Ungültige Log-Quelle.'], 422);
        }

        $result = $this->agent->execute($server, $cmd, 60, true);

        if (! $result->success) {
            return response()->json([
                'error' => $result->stderr ?: 'Log konnte nicht gelesen werden.',
            ], 422);
        }

        $output = explode("\n", rtrim($result->stdout));

        return response()->json([
            'lines' => $output,
            'total' => count($output),
        ]);
    }

    private function buildCommand(string $source, int $lines, string $filter, bool $follow = false): ?string
    {
        if (str_starts_with($source, 'journalctl:')) {
            $unit = substr($source, 11);

            if (! preg_match('/^[A-Za-z0-9._-]+$/', $unit)) {
                return null;
            }

            $cmd = 'journalctl -u '.escapeshellarg($unit).' --no-pager --output=short';

            if ($follow) {
                $cmd .= ' -f -n 50';
            } elseif ($lines > 0) {
                $cmd .= ' -n '.$lines;
            } else {
                $cmd .= ' -n 200';
            }

            if (filled($filter)) {
                $cmd .= ' | grep -i --line-buffered '.escapeshellarg($filter);
            }

            return $cmd;
        }

        $path = self::KNOWN_PATHS[$source] ?? null;

        if ($path === null && preg_match('#^/var/log/[A-Za-z0-9/._-]+$#', $source) && ! str_contains($source, '..')) {
            $path = $source;
        }

        if ($path === null) {
            return null;
        }

        $cmd = 'tail';

        if ($follow) {
            $cmd .= ' -f -n 50';
        } elseif ($lines > 0) {
            $cmd .= ' -n '.$lines;
        } else {
            $cmd .= ' -n 200';
        }

        $cmd .= ' '.escapeshellarg($path);

        if (filled($filter)) {
            $cmd .= ' | grep -i --line-buffered '.escapeshellarg($filter);
        }

        return $cmd;
    }
}
