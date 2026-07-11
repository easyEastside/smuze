<?php

namespace App\Modules\Admin\Server\Controllers;

use App\Models\Server;
use App\Models\User;
use App\Modules\Admin\Server\Requests\AdminServerRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class AdminServerController
{
    public function index(): View
    {
        $servers = Server::orderByDesc('created_at')->paginate(15);

        return view('modules.admin.server.index', compact('servers'));
    }

    public function create(): View
    {
        $users = User::orderBy('name')->get(['id', 'name']);

        return view('modules.admin.server.create', compact('users'));
    }

    public function store(AdminServerRequest $request): RedirectResponse
    {
        $data = $request->validated();
        $data['use_sudo'] = $request->boolean('use_sudo');
        $server = Server::create($data);

        return to_route('admin.servers.index')
            ->with('flash', ['success' => "Server {$server->name} created successfully."]);
    }

    public function edit(Server $server): View
    {
        $users = User::orderBy('name')->get(['id', 'name']);

        return view('modules.admin.server.edit', compact('server', 'users'));
    }

    public function update(AdminServerRequest $request, Server $server): RedirectResponse
    {
        $data = $request->validated();
        $data['use_sudo'] = $request->boolean('use_sudo');
        $server->update($data);

        return to_route('admin.servers.index')
            ->with('flash', ['success' => "Server {$server->name} updated successfully."]);
    }

    public function destroy(Server $server): RedirectResponse
    {
        $name = $server->name;

        $server->delete();

        return to_route('admin.servers.index')
            ->with('flash', ['success' => "Server {$name} deleted successfully."]);
    }
}
