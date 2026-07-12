<?php

namespace App\Services\ExecutionEngine;

use App\Models\Server;
use App\Services\ConnectionResult;

class ServerExecutionEngine implements ExecutionEngine
{
    public function __construct(
        private SshEngine $ssh,
        private AgentEngine $agent,
    ) {}

    public function execute(
        Server $server,
        string $command,
        int $timeout = 30,
        bool $useSudo = true,
        ?callable $onOutput = null,
    ): ExecutionResult {
        return $this->engineFor($server)->execute($server, $command, $timeout, $useSudo, $onOutput);
    }

    public function test(Server $server, int $timeout = 5): ConnectionResult
    {
        return $this->engineFor($server)->test($server, $timeout);
    }

    public function supports(string $feature): bool
    {
        return $this->ssh->supports($feature) || $this->agent->supports($feature);
    }

    private function engineFor(Server $server): ExecutionEngine
    {
        if ($server->execution_driver === 'agent') {
            return $this->agent;
        }

        if ($server->execution_driver === 'auto' && $this->agentConnected($server)) {
            return $this->agent;
        }

        return $this->ssh;
    }

    private function agentConnected(Server $server): bool
    {
        if (! $server->agent_enabled || $server->agent_status !== 'connected') {
            return false;
        }

        if ($server->agent_last_seen_at === null) {
            return false;
        }

        return $server->agent_last_seen_at->greaterThanOrEqualTo(now()->subMinute());
    }
}
