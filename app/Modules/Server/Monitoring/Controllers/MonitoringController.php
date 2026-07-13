<?php

namespace App\Modules\Server\Monitoring\Controllers;

use App\Models\Server;
use App\Services\ExecutionEngine\PushAgentEngine;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class MonitoringController
{
    public function __construct(
        private PushAgentEngine $agent,
    ) {}

    public function index(Server $server): View
    {
        Gate::authorize('view', $server);

        return view('modules.server.monitoring.index', compact('server'));
    }

    public function processes(Server $server): JsonResponse
    {
        Gate::authorize('view', $server);

        return $this->actionResponse($server, 'monitoring.processes');
    }

    public function services(Server $server): JsonResponse
    {
        Gate::authorize('view', $server);

        return $this->actionResponse($server, 'monitoring.services');
    }

    public function serviceAction(Request $request, Server $server): JsonResponse
    {
        Gate::authorize('update', $server);

        $data = $request->validate([
            'service' => ['required', 'string', 'max:255', 'regex:/^[A-Za-z0-9][A-Za-z0-9._-]*\.service$/'],
            'action' => ['required', Rule::in(['start', 'stop', 'restart'])],
        ]);

        return $this->actionResponse($server, 'monitoring.service_'.$data['action'], [
            'service' => $data['service'],
        ]);
    }

    public function killProcess(Request $request, Server $server): JsonResponse
    {
        Gate::authorize('update', $server);

        $data = $request->validate([
            'pid' => ['required', 'integer', 'min:2'],
        ]);

        return $this->actionResponse($server, 'monitoring.process_kill', [
            'pid' => $data['pid'],
        ]);
    }

    /** @param array<string, mixed> $payload */
    private function actionResponse(Server $server, string $action, array $payload = []): JsonResponse
    {
        $result = $this->agent->action($server, $action, $payload);

        return response()->json([
            'success' => $result->success,
            'action' => $action,
            'exit_code' => $result->exitCode,
            'stdout' => $result->stdout,
            'stderr' => $result->stderr,
            'error' => $result->success ? null : ($result->stderr ?: 'Agent-Action fehlgeschlagen.'),
        ], $result->success ? 200 : 422);
    }
}
