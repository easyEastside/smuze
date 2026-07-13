<?php

namespace App\Modules\Server\Firewall\Actions;

use App\Models\Server;
use App\Services\ExecutionEngine\PushAgentEngine;

class FirewallAction
{
    public function __construct(
        private PushAgentEngine $engine,
    ) {}

    public function status(Server $server): array
    {
        $result = $this->engine->action($server, 'firewall.status', []);

        if (! $result->success) {
            return ['success' => false, 'error' => $result->stderr];
        }

        $output = $result->stdout;

        if (str_contains($output, 'NOT_INSTALLED')) {
            return ['success' => true, 'installed' => false, 'active' => false, 'output' => ''];
        }

        $active = str_contains(mb_strtolower($output), 'status: active');

        return [
            'success' => true,
            'installed' => true,
            'active' => $active,
            'output' => $output,
        ];
    }

    public function rules(Server $server): array
    {
        $result = $this->engine->action($server, 'firewall.rules', []);

        if (! $result->success) {
            return ['success' => false, 'error' => $result->stderr];
        }

        $output = $result->stdout;

        if (str_contains($output, 'NOT_INSTALLED') || str_contains($output, 'Status: inactive')) {
            return ['success' => true, 'rules' => []];
        }

        $rules = [];
        foreach (explode("\n", $output) as $line) {
            $line = trim($line);

            if ($line === '' || ! str_starts_with($line, '[')) {
                continue;
            }

            $closingBracket = strpos($line, ']');
            if ($closingBracket === false) {
                continue;
            }

            $numStr = trim(substr($line, 1, $closingBracket - 1));
            $rest = trim(substr($line, $closingBracket + 1));

            if (! is_numeric($numStr)) {
                continue;
            }

            $parts = preg_split('/\s+/', $rest);
            $portProto = $parts[0] ?? '';

            if (($parts[1] ?? '') === '(v6)') {
                $action = $parts[2] ?? '';
                $direction = $parts[3] ?? '';
                $source = implode(' ', array_slice($parts, 4)) ?: 'Anywhere (v6)';
            } else {
                $action = $parts[1] ?? '';
                $direction = $parts[2] ?? '';
                $source = implode(' ', array_slice($parts, 3)) ?: 'Anywhere';
            }

            if (str_contains($portProto, '/')) {
                [$port, $proto] = explode('/', $portProto, 2);
            } else {
                $port = $portProto;
                $proto = '';
            }

            $rules[] = [
                'number' => (int) $numStr,
                'action' => $action,
                'port' => $port,
                'protocol' => mb_strtoupper($proto),
                'direction' => $direction,
                'source' => $source,
                'raw' => $rest,
            ];
        }

        return ['success' => true, 'rules' => $rules];
    }

    public function install(Server $server): array
    {
        $result = $this->engine->action($server, 'firewall.install', []);

        return [
            'success' => $result->success,
            'message' => $result->success ? 'UFW wurde installiert.' : $result->stderr,
        ];
    }

    public function allow(Server $server, string $port, string $protocol = 'tcp'): array
    {
        $validation = $this->validatePort($port);
        if (! $validation['valid']) {
            return ['success' => false, 'message' => $validation['error']];
        }

        if (! $this->validProtocol($protocol)) {
            return ['success' => false, 'message' => 'Protokoll muss tcp oder udp sein.'];
        }

        $result = $this->engine->action($server, 'firewall.allow', [
            'port' => $port,
            'protocol' => $protocol,
        ]);

        return [
            'success' => $result->success,
            'message' => $result->success ? "Port {$port} wurde freigegeben." : $result->stderr,
        ];
    }

    public function deny(Server $server, string $port, string $protocol = 'tcp'): array
    {
        $validation = $this->validatePort($port);
        if (! $validation['valid']) {
            return ['success' => false, 'message' => $validation['error']];
        }

        if (! $this->validProtocol($protocol)) {
            return ['success' => false, 'message' => 'Protokoll muss tcp oder udp sein.'];
        }

        $result = $this->engine->action($server, 'firewall.deny', [
            'port' => $port,
            'protocol' => $protocol,
        ]);

        return [
            'success' => $result->success,
            'message' => $result->success ? "Port {$port} wurde geblockt." : $result->stderr,
        ];
    }

    public function destroy(Server $server, int $ruleNumber): array
    {
        if ($ruleNumber < 1) {
            return ['success' => false, 'message' => 'Regelnummer muss positiv sein.'];
        }

        $result = $this->engine->action($server, 'firewall.delete', [
            'rule' => $ruleNumber,
        ]);

        return [
            'success' => $result->success,
            'message' => $result->success ? "Regel {$ruleNumber} wurde gelöscht." : $result->stderr,
        ];
    }

    public function enable(Server $server): array
    {
        $result = $this->engine->action($server, 'firewall.enable', []);

        return [
            'success' => $result->success,
            'message' => $result->success ? 'UFW wurde aktiviert.' : $result->stderr,
        ];
    }

    public function disable(Server $server): array
    {
        $result = $this->engine->action($server, 'firewall.disable', []);

        return [
            'success' => $result->success,
            'message' => $result->success ? 'UFW wurde deaktiviert.' : $result->stderr,
        ];
    }

    public function allowAll(Server $server): array
    {
        $result = $this->engine->action($server, 'firewall.allow_standard_ports', []);

        return [
            'success' => $result->success,
            'message' => $result->success ? 'Standard-Ports wurden freigegeben.' : $result->stderr,
        ];
    }

    /** @return array{valid: bool, error: string} */
    private function validatePort(string $port): array
    {
        $port = trim($port);

        if (! ctype_digit($port)) {
            return ['valid' => false, 'error' => 'Port muss eine Zahl zwischen 1 und 65535 sein.'];
        }

        $number = (int) $port;

        if ($number < 1 || $number > 65535) {
            return ['valid' => false, 'error' => 'Port muss eine Zahl zwischen 1 und 65535 sein.'];
        }

        return ['valid' => true, 'error' => ''];
    }

    private function validProtocol(string $protocol): bool
    {
        return in_array($protocol, ['tcp', 'udp', ''], true);
    }
}
