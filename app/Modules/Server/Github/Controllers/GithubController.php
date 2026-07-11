<?php

namespace App\Modules\Server\Github\Controllers;

use App\Models\Server;
use App\Modules\Server\Github\Actions\GithubAction;
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

    public function deploy(Request $request, Server $server, GithubAction $action): JsonResponse
    {
        if ($server->user_id !== $request->user()->id) {
            abort(403);
        }

        $data = $request->validate([
            'repo_url' => ['required', 'string', 'max:500'],
            'host' => ['required', 'string', 'max:253'],
            'target_name' => ['required', 'string', 'max:64'],
            'use_ssl' => ['sometimes', 'boolean'],
            'email' => ['sometimes', 'string', 'email', 'max:255'],
        ]);

        return response()->json($action->deploy(
            $server,
            $data['repo_url'],
            $data['host'],
            $data['target_name'],
            (bool) ($data['use_ssl'] ?? false),
            $data['email'] ?? '',
        ));
    }
}
