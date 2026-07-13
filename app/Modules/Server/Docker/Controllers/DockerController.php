<?php

namespace App\Modules\Server\Docker\Controllers;

use App\Models\Server;
use App\Modules\Server\Docker\Actions\DockerAction;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

class DockerController
{
    public function index(Request $request, Server $server): View
    {
        Gate::authorize('view', $server);

        return view('modules.server.docker.index', compact('server'));
    }

    public function status(Request $request, Server $server, DockerAction $action): JsonResponse
    {
        Gate::authorize('view', $server);

        return response()->json($action->status($server));
    }

    public function info(Request $request, Server $server, DockerAction $action): JsonResponse
    {
        Gate::authorize('view', $server);

        return response()->json($action->info($server));
    }

    public function install(Request $request, Server $server, DockerAction $action): JsonResponse
    {
        Gate::authorize('update', $server);

        return response()->json($action->install($server));
    }

    public function deinstall(Request $request, Server $server, DockerAction $action): JsonResponse
    {
        Gate::authorize('update', $server);

        return response()->json($action->deinstall($server));
    }

    public function service(Request $request, Server $server, string $action, DockerAction $dockerAction): JsonResponse
    {
        Gate::authorize('update', $server);

        $result = match ($action) {
            'start' => $dockerAction->start($server),
            'stop' => $dockerAction->stop($server),
            'restart' => $dockerAction->restart($server),
            default => abort(404),
        };

        return response()->json($result);
    }

    public function ps(Request $request, Server $server, DockerAction $action): JsonResponse
    {
        Gate::authorize('view', $server);

        $all = $request->boolean('all', true);

        return response()->json($action->ps($server, $all));
    }

    public function containerStart(Request $request, Server $server, string $container, DockerAction $action): JsonResponse
    {
        Gate::authorize('update', $server);

        return response()->json($action->containerStart($server, $container));
    }

    public function containerStop(Request $request, Server $server, string $container, DockerAction $action): JsonResponse
    {
        Gate::authorize('update', $server);

        return response()->json($action->containerStop($server, $container));
    }

    public function containerRestart(Request $request, Server $server, string $container, DockerAction $action): JsonResponse
    {
        Gate::authorize('update', $server);

        return response()->json($action->containerRestart($server, $container));
    }

    public function containerRemove(Request $request, Server $server, string $container, DockerAction $action): JsonResponse
    {
        Gate::authorize('update', $server);

        $force = $request->boolean('force', false);

        return response()->json($action->containerRemove($server, $container, $force));
    }

    public function containerLogs(Request $request, Server $server, string $container, DockerAction $action): JsonResponse
    {
        Gate::authorize('view', $server);

        $tail = (int) $request->integer('tail', 100);

        return response()->json($action->containerLogs($server, $container, $tail));
    }

    public function containerCreate(Request $request, Server $server, DockerAction $action): JsonResponse
    {
        Gate::authorize('update', $server);

        $data = $request->validate([
            'image' => ['required', 'string', 'max:500'],
            'name' => ['nullable', 'string', 'max:255'],
            'ports' => ['nullable', 'string', 'max:500'],
            'env' => ['nullable', 'array'],
            'env.*' => ['string', 'max:500'],
            'volume' => ['nullable', 'string', 'max:500'],
        ]);

        return response()->json($action->containerCreate(
            $server,
            $data['image'],
            $data['name'] ?? null,
            $data['ports'] ?? null,
            $data['env'] ?? [],
            $data['volume'] ?? null,
        ));
    }

    public function containerExec(Request $request, Server $server, string $container, DockerAction $action): JsonResponse
    {
        Gate::authorize('update', $server);

        $data = $request->validate([
            'command' => ['required', 'string', 'max:1000'],
        ]);

        return response()->json($action->containerExec($server, $container, $data['command']));
    }

    public function images(Request $request, Server $server, DockerAction $action): JsonResponse
    {
        Gate::authorize('view', $server);

        return response()->json($action->images($server));
    }

    public function imagePull(Request $request, Server $server, DockerAction $action): JsonResponse
    {
        Gate::authorize('update', $server);

        $data = $request->validate([
            'image' => ['required', 'string', 'max:500'],
        ]);

        return response()->json($action->imagePull($server, $data['image']));
    }

    public function imageRemove(Request $request, Server $server, string $image, DockerAction $action): JsonResponse
    {
        Gate::authorize('update', $server);

        $force = $request->boolean('force', false);

        return response()->json($action->imageRemove($server, $image, $force));
    }

    public function networks(Request $request, Server $server, DockerAction $action): JsonResponse
    {
        Gate::authorize('view', $server);

        return response()->json($action->networks($server));
    }

    public function composePs(Request $request, Server $server, DockerAction $action): JsonResponse
    {
        Gate::authorize('view', $server);

        $projectPath = $request->input('project_path');

        return response()->json($action->composePs($server, $projectPath));
    }

    public function composeUp(Request $request, Server $server, DockerAction $action): JsonResponse
    {
        Gate::authorize('update', $server);

        $projectPath = $request->input('project_path');

        return response()->json($action->composeUp($server, $projectPath));
    }

    public function composeDown(Request $request, Server $server, DockerAction $action): JsonResponse
    {
        Gate::authorize('update', $server);

        $projectPath = $request->input('project_path');

        return response()->json($action->composeDown($server, $projectPath));
    }

    public function systemPrune(Request $request, Server $server, DockerAction $action): JsonResponse
    {
        Gate::authorize('update', $server);

        $all = $request->boolean('all', false);

        return response()->json($action->systemPrune($server, $all));
    }

    public function stats(Request $request, Server $server, DockerAction $action): JsonResponse
    {
        Gate::authorize('view', $server);

        return response()->json($action->stats($server));
    }
}
