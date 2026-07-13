<?php

namespace App\Modules\Server\Services\Actions;

use App\Models\Server;
use App\Services\ExecutionEngine\PushAgentEngine;

class DeinstallService
{
    private const LABELS = [
        'php' => 'PHP',
        'apache' => 'Apache',
        'nginx' => 'Nginx',
        'mysql' => 'MySQL',
        'node' => 'Node.js',
        'nvm' => 'nvm',
        'npm' => 'npm',
        'composer' => 'Composer',
        'python' => 'Python',
    ];

    public function __construct(
        private PushAgentEngine $engine,
    ) {}

    /** @return array<string, mixed> */
    public function handle(Server $server, string $service, ?callable $onOutput = null): array
    {
        if (! array_key_exists($service, self::LABELS)) {
            return ['success' => false, 'message' => "Unbekannter Service: {$service}"];
        }

        $result = $this->engine->action($server, 'services.deinstall', ['service' => $service], $onOutput);

        $label = self::LABELS[$service] ?? $service;

        return [
            'success' => $result->success,
            'message' => $result->success
                ? "{$label} wurde deinstalliert."
                : $result->stderr,
        ];
    }
}
