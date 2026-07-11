<?php

namespace App\Modules\Server\Mysql\Controllers;

use App\Models\Server;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class MysqlController
{
    public function index(Request $request, Server $server): View
    {
        if ($server->user_id !== $request->user()->id) {
            abort(403);
        }

        return view('modules.server.mysql.index', compact('server'));
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

    public function databases(Request $request, Server $server): JsonResponse
    {
        if ($server->user_id !== $request->user()->id) {
            abort(403);
        }

        return response()->json(['message' => 'Not implemented yet']);
    }

    public function createDatabase(Request $request, Server $server): JsonResponse
    {
        if ($server->user_id !== $request->user()->id) {
            abort(403);
        }

        return response()->json(['message' => 'Not implemented yet']);
    }

    public function dropDatabase(Request $request, Server $server, string $database): JsonResponse
    {
        if ($server->user_id !== $request->user()->id) {
            abort(403);
        }

        return response()->json(['message' => 'Not implemented yet']);
    }

    public function tables(Request $request, Server $server, string $database): JsonResponse
    {
        if ($server->user_id !== $request->user()->id) {
            abort(403);
        }

        return response()->json(['message' => 'Not implemented yet']);
    }

    public function createTable(Request $request, Server $server, string $database): JsonResponse
    {
        if ($server->user_id !== $request->user()->id) {
            abort(403);
        }

        return response()->json(['message' => 'Not implemented yet']);
    }

    public function dropTable(Request $request, Server $server, string $database, string $table): JsonResponse
    {
        if ($server->user_id !== $request->user()->id) {
            abort(403);
        }

        return response()->json(['message' => 'Not implemented yet']);
    }

    public function users(Request $request, Server $server): JsonResponse
    {
        if ($server->user_id !== $request->user()->id) {
            abort(403);
        }

        return response()->json(['message' => 'Not implemented yet']);
    }

    public function createUser(Request $request, Server $server): JsonResponse
    {
        if ($server->user_id !== $request->user()->id) {
            abort(403);
        }

        return response()->json(['message' => 'Not implemented yet']);
    }

    public function dropUser(Request $request, Server $server, string $username, string $host): JsonResponse
    {
        if ($server->user_id !== $request->user()->id) {
            abort(403);
        }

        return response()->json(['message' => 'Not implemented yet']);
    }

    public function setPassword(Request $request, Server $server, string $username, string $host): JsonResponse
    {
        if ($server->user_id !== $request->user()->id) {
            abort(403);
        }

        return response()->json(['message' => 'Not implemented yet']);
    }

    public function grantAll(Request $request, Server $server, string $username, string $host): JsonResponse
    {
        if ($server->user_id !== $request->user()->id) {
            abort(403);
        }

        return response()->json(['message' => 'Not implemented yet']);
    }
}
