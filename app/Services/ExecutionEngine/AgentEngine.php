<?php

namespace App\Services\ExecutionEngine;

use App\Models\Server;
use App\Models\ServerCommand;
use App\Services\ConnectionResult;
use Illuminate\Support\Sleep;
use Illuminate\Support\Str;

class AgentEngine implements ExecutionEngine
{
    private const PollIntervalMilliseconds = 200;

    public function execute(
        Server $server,
        string $command,
        int $timeout = 30,
        bool $useSudo = true,
        ?callable $onOutput = null,
    ): ExecutionResult {
        $serverCommand = ServerCommand::query()->create([
            'server_id' => $server->id,
            'user_id' => $server->user_id,
            'uuid' => (string) Str::uuid(),
            'command' => $command,
            'use_sudo' => $useSudo,
            'timeout' => $timeout,
            'status' => ServerCommand::StatusQueued,
        ]);

        $deadline = microtime(true) + $timeout;

        while (microtime(true) < $deadline) {
            $serverCommand->refresh();

            if ($serverCommand->status === ServerCommand::StatusCompleted) {
                return $this->resultFromCommand($serverCommand, true);
            }

            if (in_array($serverCommand->status, [ServerCommand::StatusFailed, ServerCommand::StatusTimeout, ServerCommand::StatusCancelled], true)) {
                return $this->resultFromCommand($serverCommand, false);
            }

            Sleep::for(self::PollIntervalMilliseconds)->milliseconds();
        }

        $serverCommand->forceFill([
            'status' => ServerCommand::StatusTimeout,
            'stderr' => trim(($serverCommand->stderr ?? '')."\nAgent command timed out."),
            'exit_code' => -1,
            'failed_at' => now(),
        ])->save();

        return $this->resultFromCommand($serverCommand, false);
    }

    public function test(Server $server, int $timeout = 5): ConnectionResult
    {
        if ($this->isConnected($server)) {
            return new ConnectionResult(success: true);
        }

        return new ConnectionResult(
            success: false,
            errorMessage: 'Agent ist nicht verbunden.',
        );
    }

    public function supports(string $feature): bool
    {
        return in_array($feature, ['agent', 'polling'], true);
    }

    private function isConnected(Server $server): bool
    {
        return $server->agent_enabled
            && $server->agent_status === 'connected'
            && $server->agent_last_seen_at !== null
            && $server->agent_last_seen_at->greaterThanOrEqualTo(now()->subMinute());
    }

    private function resultFromCommand(ServerCommand $command, bool $success): ExecutionResult
    {
        return new ExecutionResult(
            stdout: $command->stdout ?? '',
            stderr: $command->stderr ?? '',
            exitCode: $command->exit_code ?? -1,
            success: $success,
        );
    }
}
