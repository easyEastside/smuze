<?php

namespace App\Modules\Server\Nginx\Controllers;

use App\Models\Server;
use App\Modules\Server\Nginx\Actions\NginxAction;
use App\Modules\Server\Nginx\Requests\CreateVhostRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

class NginxController
{
    public function index(Request $request, Server $server): View
    {
        Gate::authorize('view', $server);

        return view('modules.server.nginx.index', compact('server'));
    }

    public function install(Request $request, Server $server, NginxAction $action): JsonResponse
    {
        Gate::authorize('update', $server);

        return response()->json($action->install($server));
    }

    public function deinstall(Request $request, Server $server, NginxAction $action): JsonResponse
    {
        Gate::authorize('update', $server);

        return response()->json($action->deinstall($server));
    }

    public function status(Request $request, Server $server, NginxAction $action): JsonResponse
    {
        Gate::authorize('view', $server);

        return response()->json($action->status($server));
    }

    public function service(Request $request, Server $server, string $action, NginxAction $nginxAction): JsonResponse
    {
        Gate::authorize('update', $server);

        $result = match ($action) {
            'start' => $nginxAction->start($server),
            'stop' => $nginxAction->stop($server),
            'restart' => $nginxAction->restart($server),
            'reload' => $nginxAction->reload($server),
            default => abort(404),
        };

        return response()->json($result);
    }

    public function configtest(Request $request, Server $server, NginxAction $action): JsonResponse
    {
        Gate::authorize('update', $server);

        return response()->json($action->configtest($server));
    }

    public function sites(Request $request, Server $server, NginxAction $action): JsonResponse
    {
        Gate::authorize('view', $server);

        return response()->json($action->sites($server));
    }

    public function enableSite(Request $request, Server $server, string $site, NginxAction $action): JsonResponse
    {
        Gate::authorize('update', $server);

        return response()->json($action->enableSite($server, $site));
    }

    public function disableSite(Request $request, Server $server, string $site, NginxAction $action): JsonResponse
    {
        Gate::authorize('update', $server);

        return response()->json($action->disableSite($server, $site));
    }

    public function deleteSite(Request $request, Server $server, string $site, NginxAction $action): JsonResponse
    {
        Gate::authorize('update', $server);

        $deleteProject = $request->boolean('delete_project');
        $documentRoot = (string) $request->string('document_root', '');

        return response()->json($action->deleteSite($server, $site, $deleteProject, $documentRoot));
    }

    public function createVhost(CreateVhostRequest $request, Server $server, NginxAction $action): JsonResponse
    {
        Gate::authorize('update', $server);

        return response()->json($action->createVhost(
            $server,
            $request->input('domain'),
            $request->input('document_root'),
            $request->input('server_alias', ''),
            (bool) $request->input('use_ssl', false),
            $request->input('email', ''),
        ));
    }

    public function installCertbot(Request $request, Server $server, NginxAction $action): JsonResponse
    {
        Gate::authorize('update', $server);

        return response()->json($action->installCertbot($server));
    }

    public function obtainSsl(Request $request, Server $server, NginxAction $action): JsonResponse
    {
        Gate::authorize('update', $server);

        $data = $request->validate([
            'domain' => ['required', 'string', 'max:253'],
            'email' => ['required', 'string', 'email'],
        ]);

        return response()->json($action->obtainSsl($server, $data['domain'], $data['email']));
    }
}
