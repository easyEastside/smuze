<?php

namespace App\Modules\Server\Services\Actions;

use App\Models\Server;
use App\Services\ExecutionEngine\PushAgentEngine;

class InstallService
{
    public const PHP_VERSIONS = ['8.5', '8.4', '8.3', '8.2'];

    private const LABELS = [
        'php' => 'PHP',
        'apache' => 'Apache',
        'nginx' => 'Nginx',
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
    public function handle(Server $server, string $service, ?callable $onOutput = null, ?string $phpVersion = null): array
    {
        if (! array_key_exists($service, self::LABELS)) {
            return ['success' => false, 'message' => "Unbekannter Service: {$service}"];
        }

        $payload = ['service' => $service];

        if ($service === 'php') {
            $payload['version'] = $phpVersion ?: self::PHP_VERSIONS[0];
        }

        $result = $this->engine->action($server, 'services.install', $payload, $onOutput);

        $label = self::LABELS[$service] ?? $service;

        return [
            'success' => $result->success,
            'message' => $result->success
                ? "{$label} wurde installiert."
                : $result->stderr,
        ];
    }
}
