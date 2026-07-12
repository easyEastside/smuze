<?php

namespace App\Modules\Server\Controllers;

use App\Models\Server;
use App\Modules\Server\Actions\CreateServer;
use App\Modules\Server\Actions\DeleteServer;
use App\Modules\Server\Actions\RefreshSystem;
use App\Modules\Server\Actions\SystemRestart;
use App\Modules\Server\Actions\SystemStop;
use App\Modules\Server\Actions\SystemUpdate;
use App\Modules\Server\Actions\SystemUpgrade;
use App\Modules\Server\Actions\TestConnection;
use App\Modules\Server\Actions\UpdateServer;
use App\Modules\Server\Requests\StoreServerRequest;
use App\Modules\Server\Requests\UpdateServerRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

class ServerController
{
    public function index(Request $request): View
    {
        $servers = $request->user()->servers()->orderByDesc('created_at')->get()->map(function (Server $server) {
            $connection = @fsockopen($server->host, $server->port, $errno, $errstr, 2);

            $server->is_reachable = $connection !== false;

            if ($connection !== false) {
                fclose($connection);
            }

            return $server;
        });

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

    public function systemRefresh(Server $server, RefreshSystem $refreshSystem): JsonResponse
    {
        Gate::authorize('view', $server);

        return response()->json($refreshSystem->handle($server));
    }

    public function systemTestConnection(Server $server, TestConnection $testConnection): JsonResponse
    {
        Gate::authorize('view', $server);

        return response()->json($testConnection->handle($server));
    }

    public function updatePackages(Server $server, SystemUpdate $systemUpdate): JsonResponse
    {
        Gate::authorize('update', $server);

        return response()->json($systemUpdate->handle($server));
    }

    public function upgradePackages(Server $server, SystemUpgrade $systemUpgrade): JsonResponse
    {
        Gate::authorize('update', $server);

        return response()->json($systemUpgrade->handle($server));
    }

    public function restartServer(Server $server, SystemRestart $systemRestart): JsonResponse
    {
        Gate::authorize('update', $server);

        return response()->json($systemRestart->handle($server));
    }

    public function stopServer(Server $server, SystemStop $systemStop): JsonResponse
    {
        Gate::authorize('update', $server);

        return response()->json($systemStop->handle($server));
    }
}
