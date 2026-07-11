<?php

namespace App\Modules\Server\Apache\Controllers;

use App\Models\Server;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ApacheController
{
    public function index(Request $request, Server $server): View
    {
        if ($server->user_id !== $request->user()->id) {
            abort(403);
        }

        return view('modules.server.apache.index', compact('server'));
    }

    public function install(Request $request, Server $server): JsonResponse
    {
        if ($server->user_id !== $request->user()->id) {
            abort(403);
        }

        return response()->json(['message' => 'Not implemented yet']);
    }

    public function deinstall(Request $request, Server $server): JsonResponse
    {
        if ($server->user_id !== $request->user()->id) {
            abort(403);
        }

        return response()->json(['message' => 'Not implemented yet']);
    }

    public function status(Request $request, Server $server): JsonResponse
    {
        if ($server->user_id !== $request->user()->id) {
            abort(403);
        }

        return response()->json(['message' => 'Not implemented yet']);
    }

    public function service(Request $request, Server $server, string $action): JsonResponse
    {
        if ($server->user_id !== $request->user()->id) {
            abort(403);
        }

        return response()->json(['message' => 'Not implemented yet']);
    }

    public function configtest(Request $request, Server $server): JsonResponse
    {
        if ($server->user_id !== $request->user()->id) {
            abort(403);
        }

        return response()->json(['message' => 'Not implemented yet']);
    }

    public function sites(Request $request, Server $server): JsonResponse
    {
        if ($server->user_id !== $request->user()->id) {
            abort(403);
        }

        return response()->json(['message' => 'Not implemented yet']);
    }

    public function enableSite(Request $request, Server $server, string $site): JsonResponse
    {
        if ($server->user_id !== $request->user()->id) {
            abort(403);
        }

        return response()->json(['message' => 'Not implemented yet']);
    }

    public function disableSite(Request $request, Server $server, string $site): JsonResponse
    {
        if ($server->user_id !== $request->user()->id) {
            abort(403);
        }

        return response()->json(['message' => 'Not implemented yet']);
    }

    public function deleteSite(Request $request, Server $server, string $site): JsonResponse
    {
        if ($server->user_id !== $request->user()->id) {
            abort(403);
        }

        return response()->json(['message' => 'Not implemented yet']);
    }

    public function createVhost(Request $request, Server $server): JsonResponse
    {
        if ($server->user_id !== $request->user()->id) {
            abort(403);
        }

        return response()->json(['message' => 'Not implemented yet']);
    }

    public function modules(Request $request, Server $server): JsonResponse
    {
        if ($server->user_id !== $request->user()->id) {
            abort(403);
        }

        return response()->json(['message' => 'Not implemented yet']);
    }

    public function enableModule(Request $request, Server $server, string $module): JsonResponse
    {
        if ($server->user_id !== $request->user()->id) {
            abort(403);
        }

        return response()->json(['message' => 'Not implemented yet']);
    }

    public function disableModule(Request $request, Server $server, string $module): JsonResponse
    {
        if ($server->user_id !== $request->user()->id) {
            abort(403);
        }

        return response()->json(['message' => 'Not implemented yet']);
    }

    public function installCertbot(Request $request, Server $server): JsonResponse
    {
        if ($server->user_id !== $request->user()->id) {
            abort(403);
        }

        return response()->json(['message' => 'Not implemented yet']);
    }

    public function obtainSsl(Request $request, Server $server): JsonResponse
    {
        if ($server->user_id !== $request->user()->id) {
            abort(403);
        }

        return response()->json(['message' => 'Not implemented yet']);
    }
}
