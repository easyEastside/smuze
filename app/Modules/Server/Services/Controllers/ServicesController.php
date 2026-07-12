<?php

namespace App\Modules\Server\Services\Controllers;

use App\Models\Server;
use App\Modules\Server\Services\Actions\DeinstallService;
use App\Modules\Server\Services\Actions\InstallService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

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

    public function deinstall(Request $request, Server $server, string $service, DeinstallService $deinstallService): JsonResponse
    {
        Gate::authorize('update', $server);

        return response()->json($deinstallService->handle($server, $service));
    }
}
