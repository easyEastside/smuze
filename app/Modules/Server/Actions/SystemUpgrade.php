<?php

namespace App\Modules\Server\Actions;

use App\Models\Server;
use App\Services\ExecutionEngine\ExecutionEngine;

class SystemUpgrade
{
    public function __construct(
        private ExecutionEngine $engine,
    ) {}

    /** @return array<string, mixed> */
    public function handle(Server $server): array
    {
        $result = $this->engine->execute($server, 'apt upgrade -y', timeout: 900);

        return [
            'success' => $result->success,
            'message' => $result->success ? 'Pakete aktualisiert.' : $result->stderr,
        ];
    }
}
