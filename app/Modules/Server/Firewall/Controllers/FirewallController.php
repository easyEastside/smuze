<?php

namespace App\Modules\Server\Firewall\Controllers;

use App\Models\Server;
use App\Modules\Server\Firewall\Actions\FirewallAction;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

class FirewallController
{
    public function index(Request $request, Server $server): View
    {
        Gate::authorize('view', $server);

        return view('modules.server.firewall.index', compact('server'));
    }

    public function status(Request $request, Server $server, FirewallAction $action): JsonResponse
    {
        Gate::authorize('view', $server);

        return response()->json($action->status($server));
    }

    public function rules(Request $request, Server $server, FirewallAction $action): JsonResponse
    {
        Gate::authorize('view', $server);

        return response()->json($action->rules($server));
    }

    public function allow(Request $request, Server $server, FirewallAction $action): JsonResponse
    {
        Gate::authorize('update', $server);

        $data = $request->validate([
            'port' => ['required', 'integer', 'min:1', 'max:65535'],
            'protocol' => ['sometimes', 'string', 'in:tcp,udp,'],
        ]);

        return response()->json($action->allow($server, (string) $data['port'], $data['protocol'] ?? 'tcp'));
    }

    public function deny(Request $request, Server $server, FirewallAction $action): JsonResponse
    {
        Gate::authorize('update', $server);

        $data = $request->validate([
            'port' => ['required', 'integer', 'min:1', 'max:65535'],
            'protocol' => ['sometimes', 'string', 'in:tcp,udp,'],
        ]);

        return response()->json($action->deny($server, (string) $data['port'], $data['protocol'] ?? 'tcp'));
    }

    public function allowStandardPorts(Request $request, Server $server, FirewallAction $action): JsonResponse
    {
        Gate::authorize('update', $server);

        return response()->json($action->allowAll($server));
    }

    public function destroy(Request $request, Server $server, int $rule, FirewallAction $action): JsonResponse
    {
        Gate::authorize('update', $server);

        abort_unless($rule >= 1, 404);

        return response()->json($action->destroy($server, $rule));
    }

    public function enable(Request $request, Server $server, FirewallAction $action): JsonResponse
    {
        Gate::authorize('update', $server);

        return response()->json($action->enable($server));
    }

    public function disable(Request $request, Server $server, FirewallAction $action): JsonResponse
    {
        Gate::authorize('update', $server);

        return response()->json($action->disable($server));
    }

    public function install(Request $request, Server $server, FirewallAction $action): JsonResponse
    {
        Gate::authorize('update', $server);

        return response()->json($action->install($server));
    }
}
