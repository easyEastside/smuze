<?php

namespace App\Modules\Server\Services\Controllers;

use App\Models\Server;
use App\Modules\Server\Services\Actions\DeinstallService;
use App\Modules\Server\Services\Actions\InstallService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ServicesController
{
    public function index(Request $request, Server $server): View
    {
        if ($server->user_id !== $request->user()->id) {
            abort(403);
        }

        return view('modules.server.services.index', compact('server'));
    }

    public function install(Request $request, Server $server, string $service, InstallService $installService): JsonResponse
    {
        if ($server->user_id !== $request->user()->id) {
            abort(403);
        }

        return response()->json($installService->handle($server, $service));
    }

    public function deinstall(Request $request, Server $server, string $service, DeinstallService $deinstallService): JsonResponse
    {
        if ($server->user_id !== $request->user()->id) {
            abort(403);
        }

        return response()->json($deinstallService->handle($server, $service));
    }
}
