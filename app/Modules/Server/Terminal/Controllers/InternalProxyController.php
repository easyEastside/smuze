<?php

namespace App\Modules\Server\Terminal\Controllers;

use App\Models\Server;
use App\Modules\Server\Actions\RefreshSystem;
use App\Modules\Server\Apache\Actions\ApacheAction;
use App\Modules\Server\Firewall\Actions\FirewallAction;
use App\Modules\Server\Mysql\Actions\MysqlAction;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class InternalProxyController
{
    public function __invoke(Request $request, Server $server, string $module, string $action): JsonResponse
    {
        $secret = (string) config('terminal.secret');
        $providedSecret = (string) $request->header('X-Terminal-Secret', '');

        if ($secret === '' || ! hash_equals($secret, $providedSecret)) {
            abort(403);
        }

        Gate::authorize('view', $server);

        $result = match ("{$module}.{$action}") {
            'system.refresh' => app(RefreshSystem::class)->handle($server),
            'apache.status' => app(ApacheAction::class)->status($server),
            'apache.sites' => app(ApacheAction::class)->sites($server),
            'apache.modules' => app(ApacheAction::class)->modules($server),
            'mysql.status' => app(MysqlAction::class)->status($server),
            'mysql.databases' => app(MysqlAction::class)->databases($server),
            'mysql.users' => app(MysqlAction::class)->users($server),
            'firewall.status' => app(FirewallAction::class)->status($server),
            'firewall.rules' => app(FirewallAction::class)->rules($server),
            default => throw new NotFoundHttpException("Unknown proxy route: {$module}.{$action}"),
        };

        return response()->json($result);
    }
}
