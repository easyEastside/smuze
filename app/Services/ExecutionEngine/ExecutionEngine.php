<?php

namespace App\Services\ExecutionEngine;

use App\Models\Server;
use App\Services\ConnectionResult;

interface ExecutionEngine
{
    public function execute(
        Server $server,
        string $command,
        int $timeout = 30,
        bool $useSudo = true,
        ?callable $onOutput = null,
    ): ExecutionResult;

    public function test(Server $server, int $timeout = 5): ConnectionResult;

    public function supports(string $feature): bool;
}
