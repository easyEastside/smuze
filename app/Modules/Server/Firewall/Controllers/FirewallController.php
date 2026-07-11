<?php

namespace App\Modules\Server\Firewall\Controllers;

use App\Models\Server;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class FirewallController
{
    public function index(Request $request, Server $server): View
    {
        if ($server->user_id !== $request->user()->id) {
            abort(403);
        }

        return view('modules.server.firewall.index', compact('server'));
    }

    public function status(Request $request, Server $server): JsonResponse
    {
        if ($server->user_id !== $request->user()->id) {
            abort(403);
        }

        return response()->json(['message' => 'Not implemented yet']);
    }

    public function rules(Request $request, Server $server): JsonResponse
    {
        if ($server->user_id !== $request->user()->id) {
            abort(403);
        }

        return response()->json(['message' => 'Not implemented yet']);
    }

    public function allow(Request $request, Server $server): JsonResponse
    {
        if ($server->user_id !== $request->user()->id) {
            abort(403);
        }

        return response()->json(['message' => 'Not implemented yet']);
    }

    public function deny(Request $request, Server $server): JsonResponse
    {
        if ($server->user_id !== $request->user()->id) {
            abort(403);
        }

        return response()->json(['message' => 'Not implemented yet']);
    }

    public function destroy(Request $request, Server $server, int $rule): JsonResponse
    {
        if ($server->user_id !== $request->user()->id) {
            abort(403);
        }

        return response()->json(['message' => 'Not implemented yet']);
    }

    public function enable(Request $request, Server $server): JsonResponse
    {
        if ($server->user_id !== $request->user()->id) {
            abort(403);
        }

        return response()->json(['message' => 'Not implemented yet']);
    }

    public function disable(Request $request, Server $server): JsonResponse
    {
        if ($server->user_id !== $request->user()->id) {
            abort(403);
        }

        return response()->json(['message' => 'Not implemented yet']);
    }
}
