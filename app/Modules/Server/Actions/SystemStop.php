<?php

namespace App\Modules\Server\Actions;

use App\Models\Server;
use App\Services\ExecutionEngine\ExecutionEngine;

class SystemStop
{
    public function __construct(
        private ExecutionEngine $engine,
    ) {}

    /** @return array<string, mixed> */
    public function handle(Server $server): array
    {
        $this->engine->execute($server, 'shutdown -h now', timeout: 15);

        return [
            'success' => true,
            'message' => 'Herunterfahr-Befehl gesendet.',
        ];
    }
}
