<?php

namespace App\Modules\Server\Mysql\Controllers;

use App\Models\Server;
use App\Modules\Server\Mysql\Actions\MysqlAction;
use App\Modules\Server\Mysql\Requests\CreateDatabaseRequest;
use App\Modules\Server\Mysql\Requests\CreateTableRequest;
use App\Modules\Server\Mysql\Requests\CreateUserRequest;
use App\Modules\Server\Mysql\Requests\SetPasswordRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

class MysqlController
{
    public function index(Request $request, Server $server): View
    {
        Gate::authorize('view', $server);

        return view('modules.server.mysql.index', compact('server'));
    }

    public function status(Request $request, Server $server, MysqlAction $action): JsonResponse
    {
        Gate::authorize('view', $server);

        return response()->json($action->status($server));
    }

    public function install(Request $request, Server $server, MysqlAction $action): JsonResponse
    {
        Gate::authorize('update', $server);

        $dbName = (string) $request->string('db_name', 'database');

        return response()->json($action->install($server, $dbName));
    }

    public function deinstall(Request $request, Server $server, MysqlAction $action): JsonResponse
    {
        Gate::authorize('update', $server);

        return response()->json($action->deinstall($server));
    }

    public function service(Request $request, Server $server, string $action, MysqlAction $mysqlAction): JsonResponse
    {
        Gate::authorize('update', $server);

        $result = match ($action) {
            'start' => $mysqlAction->start($server),
            'stop' => $mysqlAction->stop($server),
            'restart' => $mysqlAction->restart($server),
            default => abort(404),
        };

        return response()->json($result);
    }

    public function databases(Request $request, Server $server, MysqlAction $action): JsonResponse
    {
        Gate::authorize('view', $server);

        return response()->json($action->databases($server));
    }

    public function createDatabase(CreateDatabaseRequest $request, Server $server, MysqlAction $action): JsonResponse
    {
        Gate::authorize('update', $server);

        return response()->json($action->createDatabase($server, $request->input('db_name')));
    }

    public function dropDatabase(Request $request, Server $server, string $database, MysqlAction $action): JsonResponse
    {
        Gate::authorize('update', $server);

        return response()->json($action->dropDatabase($server, $database));
    }

    public function tables(Request $request, Server $server, string $database, MysqlAction $action): JsonResponse
    {
        Gate::authorize('view', $server);

        return response()->json($action->tables($server, $database));
    }

    public function createTable(CreateTableRequest $request, Server $server, string $database, MysqlAction $action): JsonResponse
    {
        Gate::authorize('update', $server);

        return response()->json($action->createTable($server, $database, $request->input('sql')));
    }

    public function dropTable(Request $request, Server $server, string $database, string $table, MysqlAction $action): JsonResponse
    {
        Gate::authorize('update', $server);

        return response()->json($action->dropTable($server, $database, $table));
    }

    public function users(Request $request, Server $server, MysqlAction $action): JsonResponse
    {
        Gate::authorize('view', $server);

        return response()->json($action->users($server));
    }

    public function createUser(CreateUserRequest $request, Server $server, MysqlAction $action): JsonResponse
    {
        Gate::authorize('update', $server);

        return response()->json($action->createUser($server, $request->input('username'), $request->input('host'), $request->input('password')));
    }

    public function dropUser(Request $request, Server $server, string $username, string $host, MysqlAction $action): JsonResponse
    {
        Gate::authorize('update', $server);

        return response()->json($action->dropUser($server, $username, $host));
    }

    public function setPassword(SetPasswordRequest $request, Server $server, string $username, string $host, MysqlAction $action): JsonResponse
    {
        Gate::authorize('update', $server);

        return response()->json($action->setPassword($server, $username, $host, $request->input('password')));
    }

    public function grantAll(Request $request, Server $server, string $username, string $host, MysqlAction $action): JsonResponse
    {
        Gate::authorize('update', $server);

        return response()->json($action->grantAll($server, $username, $host));
    }
}
