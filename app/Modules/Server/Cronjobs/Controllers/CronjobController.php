<?php

namespace App\Modules\Server\Cronjobs\Controllers;

use App\Models\Server;
use App\Models\ServerCronjob;
use App\Services\ExecutionEngine\ExecutionResult;
use App\Services\ExecutionEngine\PushAgentEngine;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class CronjobController
{
    public function __construct(
        private PushAgentEngine $agent,
    ) {}

    public function index(Server $server): View
    {
        Gate::authorize('view', $server);

        $cronjobs = $server->cronjobs()
            ->latest()
            ->get();

        return view('modules.server.cronjobs.index', compact('cronjobs', 'server'));
    }

    public function remote(Server $server): JsonResponse
    {
        Gate::authorize('view', $server);

        $result = $this->agent->action($server, 'cronjobs.list');
        $entries = [];

        if ($result->success && $result->stdout !== '') {
            $entries = json_decode($result->stdout, true) ?: [];
        }

        return response()->json([
            'success' => $result->success,
            'entries' => $entries,
            'error' => $result->success ? null : ($result->stderr ?: 'Cronjobs konnten nicht geladen werden.'),
        ], $result->success ? 200 : 422);
    }

    public function store(Request $request, Server $server): RedirectResponse
    {
        Gate::authorize('update', $server);

        $data = $this->validatedCronjob($request);

        $server->cronjobs()->create([
            ...$data,
            'user_id' => $request->user()->id,
        ]);

        $result = $this->syncServer($server);

        return to_route('server.cronjobs.index', $server)
            ->with('flash', [$result->success ? 'success' : 'error' => $result->success ? 'Cronjob gespeichert.' : $result->stderr]);
    }

    public function update(Request $request, Server $server, ServerCronjob $cronjob): RedirectResponse
    {
        Gate::authorize('update', $server);
        $this->authorizeCronjob($server, $cronjob);

        $cronjob->update($this->validatedCronjob($request));

        $result = $this->syncServer($server);

        return to_route('server.cronjobs.index', $server)
            ->with('flash', [$result->success ? 'success' : 'error' => $result->success ? 'Cronjob aktualisiert.' : $result->stderr]);
    }

    public function destroy(Server $server, ServerCronjob $cronjob): RedirectResponse
    {
        Gate::authorize('update', $server);
        $this->authorizeCronjob($server, $cronjob);

        $cronjob->delete();

        $result = $this->syncServer($server);

        return to_route('server.cronjobs.index', $server)
            ->with('flash', [$result->success ? 'success' : 'error' => $result->success ? 'Cronjob gelöscht.' : $result->stderr]);
    }

    public function toggle(Server $server, ServerCronjob $cronjob): RedirectResponse
    {
        Gate::authorize('update', $server);
        $this->authorizeCronjob($server, $cronjob);

        $cronjob->update(['enabled' => ! $cronjob->enabled]);

        $result = $this->syncServer($server);

        return to_route('server.cronjobs.index', $server)
            ->with('flash', [$result->success ? 'success' : 'error' => $result->success ? 'Cronjob umgeschaltet.' : $result->stderr]);
    }

    public function run(Server $server, ServerCronjob $cronjob): JsonResponse
    {
        Gate::authorize('update', $server);
        $this->authorizeCronjob($server, $cronjob);

        $result = $this->agent->action($server, 'cronjobs.run', [
            'id' => $cronjob->id,
            'command' => $cronjob->command,
            'working_directory' => $cronjob->working_directory,
            'run_as' => $cronjob->run_as,
        ]);

        $cronjob->update([
            'last_run_at' => now(),
            'last_exit_code' => $result->exitCode,
            'last_stdout' => $result->stdout,
            'last_stderr' => $result->stderr,
        ]);

        return response()->json([
            'success' => $result->success,
            'exit_code' => $result->exitCode,
            'stdout' => $result->stdout,
            'stderr' => $result->stderr,
            'error' => $result->success ? null : ($result->stderr ?: 'Cronjob-Ausführung fehlgeschlagen.'),
        ], $result->success ? 200 : 422);
    }

    public function sync(Server $server): RedirectResponse
    {
        Gate::authorize('update', $server);

        $result = $this->syncServer($server);

        return to_route('server.cronjobs.index', $server)
            ->with('flash', [$result->success ? 'success' : 'error' => $result->success ? 'Cronjobs synchronisiert.' : $result->stderr]);
    }

    /** @return array{name: string, schedule: string, command: string, working_directory: ?string, run_as: ?string, enabled: bool} */
    private function validatedCronjob(Request $request): array
    {
        /** @var array{name: string, schedule: string, command: string, working_directory?: ?string, run_as?: ?string, enabled?: bool} $data */
        $data = $request->validate([
            'name' => ['required', 'string', 'max:120', 'not_regex:/[\r\n]/'],
            'schedule' => ['required', 'string', 'max:120', 'not_regex:/[\r\n]/', function (string $attribute, mixed $value, callable $fail): void {
                if (! $this->isValidSchedule((string) $value)) {
                    $fail('Der Zeitplan muss ein gültiger Cron-Ausdruck mit 5 Feldern sein.');
                }
            }],
            'command' => ['required', 'string', 'max:1000', 'not_regex:/[\r\n]/'],
            'working_directory' => ['nullable', 'string', 'max:255', 'starts_with:/', 'not_regex:/[\r\n]/', 'not_regex:/\.\./'],
            'run_as' => ['nullable', 'string', 'max:64', 'regex:/^[A-Za-z_][A-Za-z0-9_-]*$/'],
            'enabled' => ['nullable', Rule::in(['1', '0', 1, 0, true, false])],
        ]);

        return [
            'name' => $data['name'],
            'schedule' => trim($data['schedule']),
            'command' => trim($data['command']),
            'working_directory' => $data['working_directory'] ?? null,
            'run_as' => $data['run_as'] ?? null,
            'enabled' => $request->boolean('enabled'),
        ];
    }

    private function isValidSchedule(string $schedule): bool
    {
        $fields = preg_split('/\s+/', trim($schedule));

        if (count($fields) !== 5) {
            return false;
        }

        foreach ($fields as $field) {
            if (! preg_match('/^[A-Za-z0-9*\/,#?\-]+$/', $field)) {
                return false;
            }
        }

        return true;
    }

    private function authorizeCronjob(Server $server, ServerCronjob $cronjob): void
    {
        abort_unless($cronjob->server_id === $server->id, 404);
    }

    private function syncServer(Server $server): ExecutionResult
    {
        $jobs = $server->cronjobs()
            ->where('enabled', true)
            ->orderBy('id')
            ->get()
            ->map(fn (ServerCronjob $cronjob): array => [
                'id' => $cronjob->id,
                'name' => $cronjob->name,
                'schedule' => $cronjob->schedule,
                'command' => $cronjob->command,
                'working_directory' => $cronjob->working_directory,
                'run_as' => $cronjob->run_as,
            ])
            ->all();

        return $this->agent->action($server, 'cronjobs.install', ['jobs' => $jobs]);
    }
}
