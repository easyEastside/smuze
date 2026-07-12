<?php

namespace App\Modules\Server\Controllers;

use App\Models\Server;
use App\Modules\Server\Actions\CreateServer;
use App\Modules\Server\Actions\DeleteServer;
use App\Modules\Server\Actions\UpdateServer;
use App\Modules\Server\Requests\StoreServerRequest;
use App\Modules\Server\Requests\UpdateServerRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

class ServerController
{
    public function index(Request $request): View
    {
        $servers = $request->user()->servers()->orderByDesc('created_at')->get();

        return view('modules.server.index', compact('servers'));
    }

    public function create(): View
    {
        return view('modules.server.create');
    }

    public function store(StoreServerRequest $request, CreateServer $createServer): RedirectResponse
    {
        $createServer->handle($request);

        return to_route('server.index')
            ->with('flash', ['success' => 'Server created successfully.']);
    }

    public function edit(Server $server): View
    {
        Gate::authorize('update', $server);

        return view('modules.server.edit', compact('server'));
    }

    public function update(UpdateServerRequest $request, Server $server, UpdateServer $updateServer): RedirectResponse
    {
        Gate::authorize('update', $server);

        $updateServer->handle($request, $server);

        return to_route('server.index')
            ->with('flash', ['success' => 'Server updated successfully.']);
    }

    public function destroy(Request $request, Server $server, DeleteServer $deleteServer): RedirectResponse
    {
        Gate::authorize('delete', $server);

        $deleteServer->handle($server);

        return to_route('server.index')
            ->with('flash', ['success' => 'Server deleted successfully.']);
    }

    public function system(Server $server): View
    {
        Gate::authorize('view', $server);

        return view('modules.server.system', compact('server'));
    }
}
