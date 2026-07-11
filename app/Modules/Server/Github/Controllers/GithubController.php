<?php

namespace App\Modules\Server\Github\Controllers;

use App\Models\Server;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class GithubController
{
    public function index(Request $request, Server $server): View
    {
        if ($server->user_id !== $request->user()->id) {
            abort(403);
        }

        return view('modules.server.github.index', compact('server'));
    }

    public function deploy(Request $request, Server $server): JsonResponse
    {
        if ($server->user_id !== $request->user()->id) {
            abort(403);
        }

        return response()->json(['message' => 'Not implemented yet']);
    }
}
