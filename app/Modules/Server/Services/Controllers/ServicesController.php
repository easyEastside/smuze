<?php

namespace App\Modules\Server\Services\Controllers;

use App\Models\Server;
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

    public function install(Request $request, Server $server, string $service): JsonResponse
    {
        if ($server->user_id !== $request->user()->id) {
            abort(403);
        }

        return response()->json(['message' => 'Not implemented yet']);
    }

    public function deinstall(Request $request, Server $server, string $service): JsonResponse
    {
        if ($server->user_id !== $request->user()->id) {
            abort(403);
        }

        return response()->json(['message' => 'Not implemented yet']);
    }
}
