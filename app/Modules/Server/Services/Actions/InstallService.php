<?php

namespace App\Modules\Server\Services\Actions;

use App\Models\Server;
use App\Services\ExecutionEngine\PushAgentEngine;

class InstallService
{
    private const LABELS = [
        'php' => 'PHP',
        'apache' => 'Apache',
        'mysql' => 'MySQL',
        'node' => 'Node.js',
        'nvm' => 'nvm',
        'npm' => 'npm',
        'composer' => 'Composer',
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

        $result = $this->engine->action($server, 'services.install', ['service' => $service], $onOutput);

        $label = self::LABELS[$service] ?? $service;

        return [
            'success' => $result->success,
            'message' => $result->success
                ? "{$label} wurde installiert."
                : $result->stderr,
        ];
    }
}
