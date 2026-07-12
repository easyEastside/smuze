<?php

namespace App\Modules\Server\Services\Controllers;

use App\Models\Server;
use App\Modules\Server\Services\Actions\DeinstallService;
use App\Modules\Server\Services\Actions\InstallService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ServicesController
{
    public function index(Request $request, Server $server): View
    {
        Gate::authorize('view', $server);

        return view('modules.server.services.index', compact('server'));
    }

    public function install(Request $request, Server $server, string $service, InstallService $installService): JsonResponse
    {
        Gate::authorize('update', $server);

        return response()->json($installService->handle($server, $service));
    }

    public function installStream(Request $request, Server $server, string $service, InstallService $installService): StreamedResponse
    {
        Gate::authorize('update', $server);

        return $this->streamServiceAction(fn (callable $emit): array => $installService->handle($server, $service, $emit));
    }

    public function deinstall(Request $request, Server $server, string $service, DeinstallService $deinstallService): JsonResponse
    {
        Gate::authorize('update', $server);

        return response()->json($deinstallService->handle($server, $service));
    }

    public function deinstallStream(Request $request, Server $server, string $service, DeinstallService $deinstallService): StreamedResponse
    {
        Gate::authorize('update', $server);

        return $this->streamServiceAction(fn (callable $emit): array => $deinstallService->handle($server, $service, $emit));
    }

    private function streamServiceAction(callable $action): StreamedResponse
    {
        return response()->stream(function () use ($action): void {
            $this->sendStreamEvent('status', 'Starte Ausführung...');

            $result = $action(function (string $type, string $output): void {
                $this->sendStreamEvent($type === 'stderr' ? 'stderr' : 'stdout', $output);
            });

            $this->sendStreamEvent('finished', $result);
        }, 200, [
            'Content-Type' => 'application/x-ndjson',
            'Cache-Control' => 'no-cache, no-transform',
            'X-Accel-Buffering' => 'no',
        ]);
    }

    private function sendStreamEvent(string $type, mixed $data): void
    {
        echo json_encode(['type' => $type, 'data' => $data], JSON_THROW_ON_ERROR | JSON_INVALID_UTF8_SUBSTITUTE)."\n";

        if (ob_get_level() > 0) {
            ob_flush();
        }

        flush();
    }
}
