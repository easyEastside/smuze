<?php

namespace App\Services\ExecutionEngine;

use App\Models\Server;
use App\Services\ConnectionResult;
use App\Services\SshService;

class SshEngine implements ExecutionEngine
{
    public function __construct(
        private SshService $ssh,
    ) {}

    public function execute(
        Server $server,
        string $command,
        int $timeout = 30,
        bool $useSudo = true,
        ?callable $onOutput = null,
    ): ExecutionResult {
        $result = $this->ssh->execute($server, $command, $timeout, $useSudo, $onOutput);

        return new ExecutionResult(
            stdout: $result->stdout,
            stderr: $result->stderr,
            exitCode: $result->exitCode,
            success: $result->success,
        );
    }

    public function test(Server $server, int $timeout = 5): ConnectionResult
    {
        return $this->ssh->test($server, $timeout);
    }

    public function supports(string $feature): bool
    {
        return in_array($feature, ['terminal', 'live-output'], true);
    }
}
