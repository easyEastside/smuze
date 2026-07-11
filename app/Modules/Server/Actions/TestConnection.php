<?php

namespace App\Modules\Server\Actions;

use App\Models\Server;
use App\Services\ServerMonitoringService;

class TestConnection
{
    public function __construct(
        private ServerMonitoringService $monitoring,
    ) {}

    /** @return array<string, mixed> */
    public function handle(Server $server): array
    {
        $result = $this->monitoring->testConnection($server);

        return [
            'success' => $result->success,
            'latency_ms' => $result->latencyMs,
            'error' => $result->errorMessage,
        ];
    }
}
