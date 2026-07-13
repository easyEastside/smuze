<?php

namespace App\Modules\Server\Deployments\Controllers;

use App\Models\Server;
use App\Models\ServerDeployment;
use App\Modules\Server\Deployments\Actions\DeploymentAction;
use App\Modules\Server\Deployments\Requests\StoreDeploymentRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

class DeploymentController
{
    public function index(Server $server): View
    {
        Gate::authorize('view', $server);

        $deployments = $server->deployments()
            ->with(['runs' => fn ($query) => $query->latest()->limit(5)])
            ->latest()
            ->get();

        return view('modules.server.deployments.index', compact('deployments', 'server'));
    }

    public function store(StoreDeploymentRequest $request, Server $server): RedirectResponse
    {
        Gate::authorize('update', $server);

        $data = $request->validated();

        $server->deployments()->create($this->deploymentAttributes($request, $data));

        return to_route('server.deployments.index', $server)
            ->with('flash', ['success' => 'Deployment gespeichert.']);
    }

    public function update(StoreDeploymentRequest $request, Server $server, ServerDeployment $deployment): RedirectResponse
    {
        Gate::authorize('update', $server);
        abort_unless($deployment->server_id === $server->id, 404);

        $deployment->update($this->deploymentAttributes($request, $request->validated()));

        return to_route('server.deployments.index', $server)
            ->with('flash', ['success' => 'Deployment aktualisiert.']);
    }

    public function destroy(Server $server, ServerDeployment $deployment): RedirectResponse
    {
        Gate::authorize('update', $server);
        abort_unless($deployment->server_id === $server->id, 404);

        $deployment->delete();

        return to_route('server.deployments.index', $server)
            ->with('flash', ['success' => 'Deployment gelöscht.']);
    }

    public function run(Request $request, Server $server, ServerDeployment $deployment, DeploymentAction $action): JsonResponse
    {
        Gate::authorize('update', $server);
        abort_unless($deployment->server_id === $server->id, 404);

        $startedAt = now();

        $deployment->update([
            'last_status' => 'running',
            'last_run_at' => $startedAt,
        ]);

        $run = $deployment->runs()->create([
            'server_id' => $server->id,
            'user_id' => $request->user()->id,
            'status' => 'running',
            'started_at' => $startedAt,
        ]);

        $result = $action->deploy($server, $this->payload($deployment));

        $run->update([
            'status' => $result['success'] ? 'success' : 'failed',
            'exit_code' => $result['exit_code'],
            'output' => $result['output'],
            'error_output' => $result['error_output'],
            'duration_ms' => $startedAt->diffInMilliseconds(now()),
            'finished_at' => now(),
        ]);

        $deployment->update([
            'last_status' => $result['success'] ? 'success' : 'failed',
        ]);

        return response()->json([
            'success' => $result['success'],
            'message' => $result['message'],
            'run' => $run->fresh(),
        ], $result['success'] ? 200 : 422);
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    private function deploymentAttributes(Request $request, array $data): array
    {
        return [
            'user_id' => $request->user()->id,
            'name' => trim($data['name']),
            'repo_url' => trim($data['repo_url']),
            'target_path' => rtrim(trim($data['target_path']), '/'),
            'domain' => filled($data['domain'] ?? null) ? trim($data['domain']) : null,
            'webserver' => $data['webserver'],
            'php_version' => $data['php_version'],
            'install_node' => $request->boolean('install_node'),
            'run_build' => $request->boolean('run_build'),
            'run_migrations' => $request->boolean('run_migrations'),
            'write_env' => $request->boolean('write_env', true),
            'env' => $this->parseEnv((string) ($data['env'] ?? '')),
        ];
    }

    /** @return array<string, string> */
    private function parseEnv(string $env): array
    {
        $values = [];

        foreach (preg_split('/\r\n|\r|\n/', $env) ?: [] as $line) {
            $line = trim($line);
            if ($line === '' || str_starts_with($line, '#')) {
                continue;
            }

            [$key, $value] = explode('=', $line, 2);
            $values[$key] = $value;
        }

        return $values;
    }

    /** @return array<string, mixed> */
    private function payload(ServerDeployment $deployment): array
    {
        return [
            'repo_url' => $deployment->repo_url,
            'target_path' => $deployment->target_path,
            'domain' => $deployment->domain,
            'webserver' => $deployment->webserver,
            'php_version' => $deployment->php_version,
            'install_node' => $deployment->install_node,
            'run_build' => $deployment->run_build,
            'run_migrations' => $deployment->run_migrations,
            'write_env' => $deployment->write_env,
            'env' => $deployment->env ?? [],
        ];
    }
}
