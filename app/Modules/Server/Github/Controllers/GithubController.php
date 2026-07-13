<?php

namespace App\Modules\Server\Github\Controllers;

use App\Models\Server;
use App\Modules\Server\Github\Actions\GithubAction;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

class GithubController
{
    public function index(Request $request, Server $server): View
    {
        Gate::authorize('view', $server);

        return view('modules.server.github.index', compact('server'));
    }

    public function deploy(Request $request, Server $server, GithubAction $action): JsonResponse
    {
        Gate::authorize('update', $server);

        $data = $request->validate([
            'repo_url' => ['required', 'string', 'max:500'],
            'target_name' => ['required', 'string', 'max:64'],
        ]);

        return response()->json($action->deploy(
            $server,
            $data['repo_url'],
            $data['target_name'],
        ));
    }
}
