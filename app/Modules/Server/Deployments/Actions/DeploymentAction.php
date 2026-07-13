<?php

namespace App\Modules\Server\Deployments\Actions;

use App\Models\Server;
use App\Services\ExecutionEngine\PushAgentEngine;

class DeploymentAction
{
    public function __construct(
        private PushAgentEngine $engine,
    ) {}

    /**
     * @param  array<string, mixed>  $payload
     * @return array{success: bool, message: string, output: string, error_output: string, exit_code: int|null, duration_ms: int|null}
     */
    public function deploy(Server $server, array $payload): array
    {
        $result = $this->engine->action($server, 'laravel.deploy', $payload);
        $failureOutput = trim($result->stderr."\n".$result->stdout);

        return [
            'success' => $result->success,
            'message' => $result->success ? 'Deployment wurde ausgeführt.' : ($failureOutput ?: 'Deployment fehlgeschlagen.'),
            'output' => $result->stdout,
            'error_output' => $result->success ? $result->stderr : $failureOutput,
            'exit_code' => $result->exitCode,
            'duration_ms' => null,
        ];
    }
}
