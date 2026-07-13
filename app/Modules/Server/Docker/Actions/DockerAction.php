<?php

namespace App\Modules\Server\Docker\Actions;

use App\Models\Server;
use App\Services\ExecutionEngine\PushAgentEngine;

class DockerAction
{
    public function __construct(
        private PushAgentEngine $engine,
    ) {}

    public function status(Server $server): array
    {
        $result = $this->engine->action($server, 'docker.status', []);

        if (! $result->success) {
            return ['success' => false, 'error' => $result->stderr];
        }

        $data = [];
        foreach (explode("\n", $result->stdout) as $line) {
            if (str_contains($line, '=')) {
                [$key, $value] = explode('=', $line, 2);
                $data[trim($key)] = trim($value ?? '');
            }
        }

        $installed = isset($data['INSTALLED'])
            ? $data['INSTALLED'] === 'yes'
            : ($data['ACTIVE'] ?? '') !== 'unknown';

        return [
            'success' => true,
            'installed' => $installed,
            'active' => ($data['ACTIVE'] ?? '') === 'active',
            'version' => $data['VERSION'] ?? null,
            'compose_version' => $data['COMPOSE_VERSION'] ?? null,
        ];
    }

    public function install(Server $server): array
    {
        $result = $this->engine->action($server, 'docker.install', []);

        return [
            'success' => $result->success,
            'message' => $result->success ? 'Docker wurde installiert.' : $result->stderr,
        ];
    }

    public function deinstall(Server $server): array
    {
        $result = $this->engine->action($server, 'docker.deinstall', []);

        return [
            'success' => $result->success,
            'message' => $result->success ? 'Docker wurde deinstalliert.' : $result->stderr,
        ];
    }

    public function start(Server $server): array
    {
        return $this->serviceAction($server, 'start');
    }

    public function stop(Server $server): array
    {
        return $this->serviceAction($server, 'stop');
    }

    public function restart(Server $server): array
    {
        return $this->serviceAction($server, 'restart');
    }

    private function serviceAction(Server $server, string $action): array
    {
        $result = $this->engine->action($server, "docker.{$action}", []);

        $labels = ['start' => 'gestartet', 'stop' => 'gestoppt', 'restart' => 'neugestartet'];

        return [
            'success' => $result->success,
            'message' => $result->success ? "Docker wurde {$labels[$action]}." : $result->stderr,
        ];
    }

    public function info(Server $server): array
    {
        $result = $this->engine->action($server, 'docker.info', []);

        if (! $result->success) {
            return ['success' => false, 'error' => $result->stderr];
        }

        $data = [];
        foreach (explode("\n", $result->stdout) as $line) {
            if (str_contains($line, '=')) {
                [$key, $value] = explode('=', $line, 2);
                $data[trim($key)] = trim($value ?? '');
            }
        }

        return [
            'success' => true,
            'containers_total' => (int) ($data['CONTAINERS_TOTAL'] ?? 0),
            'containers_running' => (int) ($data['CONTAINERS_RUNNING'] ?? 0),
            'containers_paused' => (int) ($data['CONTAINERS_PAUSED'] ?? 0),
            'containers_stopped' => (int) ($data['CONTAINERS_STOPPED'] ?? 0),
            'images_total' => (int) ($data['IMAGES_TOTAL'] ?? 0),
            'server_version' => $data['SERVER_VERSION'] ?? null,
            'storage_driver' => $data['STORAGE_DRIVER'] ?? null,
            'logging_driver' => $data['LOGGING_DRIVER'] ?? null,
            'cgroup_driver' => $data['CGROUP_DRIVER'] ?? null,
            'os_type' => $data['OS_TYPE'] ?? null,
            'kernel_version' => $data['KERNEL_VERSION'] ?? null,
            'cpu_count' => (int) ($data['CPU_COUNT'] ?? 0),
            'memory_total' => $data['MEMORY_TOTAL'] ?? null,
        ];
    }

    public function ps(Server $server, bool $all = true): array
    {
        $result = $this->engine->action($server, 'docker.ps', ['all' => $all]);

        if (! $result->success) {
            return ['success' => false, 'containers' => []];
        }

        $containers = $this->parseTabularOutput($result->stdout);

        return ['success' => true, 'containers' => $containers];
    }

    public function containerStart(Server $server, string $container): array
    {
        $result = $this->engine->action($server, 'docker.container_start', ['container' => $container]);

        return [
            'success' => $result->success,
            'message' => $result->success ? "Container {$container} wurde gestartet." : $result->stderr,
        ];
    }

    public function containerStop(Server $server, string $container): array
    {
        $result = $this->engine->action($server, 'docker.container_stop', ['container' => $container]);

        return [
            'success' => $result->success,
            'message' => $result->success ? "Container {$container} wurde gestoppt." : $result->stderr,
        ];
    }

    public function containerRestart(Server $server, string $container): array
    {
        $result = $this->engine->action($server, 'docker.container_restart', ['container' => $container]);

        return [
            'success' => $result->success,
            'message' => $result->success ? "Container {$container} wurde neugestartet." : $result->stderr,
        ];
    }

    public function containerRemove(Server $server, string $container, bool $force = false): array
    {
        $result = $this->engine->action($server, 'docker.container_remove', [
            'container' => $container,
            'force' => $force,
        ]);

        return [
            'success' => $result->success,
            'message' => $result->success ? "Container {$container} wurde entfernt." : $result->stderr,
        ];
    }

    public function containerLogs(Server $server, string $container, int $tail = 100): array
    {
        $result = $this->engine->action($server, 'docker.container_logs', [
            'container' => $container,
            'tail' => $tail,
        ]);

        return [
            'success' => $result->success,
            'output' => $result->success ? $result->stdout : $result->stderr,
        ];
    }

    public function containerExec(Server $server, string $container, string $command): array
    {
        if (trim($command) === '') {
            return ['success' => false, 'output' => 'Befehl darf nicht leer sein.'];
        }

        $result = $this->engine->action($server, 'docker.container_exec', [
            'container' => $container,
            'command' => $command,
        ]);

        return [
            'success' => $result->success,
            'output' => $result->success ? $result->stdout : $result->stderr,
        ];
    }

    public function containerCreate(Server $server, string $image, ?string $name = null, ?string $ports = null, array $env = [], ?string $volume = null): array
    {
        if (trim($image) === '') {
            return ['success' => false, 'message' => 'Image-Name darf nicht leer sein.'];
        }

        $result = $this->engine->action($server, 'docker.container_create', [
            'image' => $image,
            'name' => $name,
            'ports' => $ports,
            'env' => $env,
            'volume' => $volume,
        ]);

        return [
            'success' => $result->success,
            'message' => $result->success ? "Container aus Image {$image} wurde erstellt." : $result->stderr,
        ];
    }

    public function containerInspect(Server $server, string $container): array
    {
        $result = $this->engine->action($server, 'docker.container_inspect', ['container' => $container]);

        if (! $result->success) {
            return ['success' => false, 'error' => $result->stderr];
        }

        return [
            'success' => true,
            'inspect' => $result->stdout,
        ];
    }

    public function images(Server $server): array
    {
        $result = $this->engine->action($server, 'docker.images', []);

        if (! $result->success) {
            return ['success' => false, 'images' => []];
        }

        $images = $this->parseTabularOutput($result->stdout);

        return ['success' => true, 'images' => $images];
    }

    public function imagePull(Server $server, string $image): array
    {
        if (trim($image) === '') {
            return ['success' => false, 'message' => 'Image-Name darf nicht leer sein.'];
        }

        $result = $this->engine->action($server, 'docker.image_pull', ['image' => $image]);

        return [
            'success' => $result->success,
            'message' => $result->success ? "Image {$image} wurde gepullt." : $result->stderr,
        ];
    }

    public function imageRemove(Server $server, string $image, bool $force = false): array
    {
        $result = $this->engine->action($server, 'docker.image_remove', [
            'image' => $image,
            'force' => $force,
        ]);

        return [
            'success' => $result->success,
            'message' => $result->success ? "Image {$image} wurde entfernt." : $result->stderr,
        ];
    }

    public function networks(Server $server): array
    {
        $result = $this->engine->action($server, 'docker.networks', []);

        if (! $result->success) {
            return ['success' => false, 'networks' => []];
        }

        $networks = $this->parseTabularOutput($result->stdout);

        return ['success' => true, 'networks' => $networks];
    }

    public function composePs(Server $server, ?string $projectPath = null): array
    {
        $result = $this->engine->action($server, 'docker.compose_ps', [
            'project_path' => $projectPath,
        ]);

        if (! $result->success) {
            return ['success' => false, 'compose_services' => []];
        }

        $services = $this->parseTabularOutput($result->stdout);

        return ['success' => true, 'compose_services' => $services];
    }

    public function composeUp(Server $server, ?string $projectPath = null, bool $detach = true): array
    {
        $result = $this->engine->action($server, 'docker.compose_up', [
            'project_path' => $projectPath,
            'detach' => $detach,
        ]);

        return [
            'success' => $result->success,
            'message' => $result->success ? 'Docker Compose wurde gestartet.' : $result->stderr,
            'output' => $result->stdout,
        ];
    }

    public function composeDown(Server $server, ?string $projectPath = null): array
    {
        $result = $this->engine->action($server, 'docker.compose_down', [
            'project_path' => $projectPath,
        ]);

        return [
            'success' => $result->success,
            'message' => $result->success ? 'Docker Compose wurde gestoppt.' : $result->stderr,
            'output' => $result->stdout,
        ];
    }

    public function systemPrune(Server $server, bool $all = false): array
    {
        $result = $this->engine->action($server, 'docker.system_prune', ['all' => $all]);

        return [
            'success' => $result->success,
            'message' => $result->success ? 'Docker-Systembereinigung durchgeführt.' : $result->stderr,
            'output' => $result->stdout,
        ];
    }

    public function stats(Server $server): array
    {
        $result = $this->engine->action($server, 'docker.stats', []);

        if (! $result->success) {
            return ['success' => false, 'stats' => []];
        }

        $stats = $this->parseTabularOutput($result->stdout);

        return ['success' => true, 'stats' => $stats];
    }

    private function parseTabularOutput(string $output): array
    {
        $lines = explode("\n", trim($output));
        if (count($lines) < 2) {
            return [];
        }

        $headers = explode("\t", $lines[0]);
        $headers = array_map('trim', $headers);

        $rows = [];
        for ($i = 1; $i < count($lines); $i++) {
            $line = trim($lines[$i]);
            if ($line === '') {
                continue;
            }

            $columns = explode("\t", $line);
            $row = [];
            foreach ($headers as $index => $header) {
                $row[$header] = $columns[$index] ?? '';
            }
            $rows[] = $row;
        }

        return $rows;
    }
}
