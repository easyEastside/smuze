<?php

namespace App\Services\ExecutionEngine;

use App\Models\Server;
use App\Services\ConnectionResult;

class ServerExecutionEngine implements ExecutionEngine
{
    public function __construct(
        private SshEngine $ssh,
        private PushAgentEngine $agent,
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
        return $server->execution_driver === 'agent'
            ? $this->agent
            : $this->ssh;
    }
}
