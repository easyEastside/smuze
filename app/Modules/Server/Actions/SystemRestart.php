<?php

namespace App\Modules\Server\Actions;

use App\Models\Server;
use App\Services\ExecutionEngine\ExecutionEngine;

class SystemRestart
{
    public function __construct(
        private ExecutionEngine $engine,
    ) {}

    /** @return array<string, mixed> */
    public function handle(Server $server): array
    {
        $this->engine->execute($server, 'reboot', timeout: 15);

        return [
            'success' => true,
            'message' => 'Neustart-Befehl gesendet.',
        ];
    }
}
