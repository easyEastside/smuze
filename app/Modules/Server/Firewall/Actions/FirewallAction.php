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
        $result = $this->engine->execute($server, 'ufw status verbose 2>/dev/null || echo \'NOT_INSTALLED\'', timeout: 15, useSudo: true);

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
        $result = $this->engine->execute($server, 'ufw status numbered 2>/dev/null || echo \'NOT_INSTALLED\'', timeout: 15, useSudo: true);

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
            $action = $parts[0] ?? '';
            $portProto = $parts[1] ?? '';

            if (str_contains($portProto, '/')) {
                [$port, $proto] = explode('/', $portProto, 2);
            } else {
                $port = $portProto;
                $proto = '';
            }

            $direction = $parts[2] ?? '';
            $source = $parts[count($parts) - 1] ?? 'Anywhere';

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
        $result = $this->engine->execute($server, 'DEBIAN_FRONTEND=noninteractive apt install ufw -y', timeout: 120, useSudo: true);

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

        $spec = $protocol ? "{$port}/{$protocol}" : $port;
        $result = $this->engine->execute($server, 'ufw allow '.escapeshellarg($spec), timeout: 15, useSudo: true);

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

        $spec = $protocol ? "{$port}/{$protocol}" : $port;
        $result = $this->engine->execute($server, 'ufw deny '.escapeshellarg($spec), timeout: 15, useSudo: true);

        return [
            'success' => $result->success,
            'message' => $result->success ? "Port {$port} wurde geblockt." : $result->stderr,
        ];
    }

    public function destroy(Server $server, int $ruleNumber): array
    {
        $result = $this->engine->execute($server, "ufw --force delete {$ruleNumber}", timeout: 15, useSudo: true);

        return [
            'success' => $result->success,
            'message' => $result->success ? "Regel {$ruleNumber} wurde gelöscht." : $result->stderr,
        ];
    }

    public function enable(Server $server): array
    {
        $result = $this->engine->execute($server, 'ufw --force enable', timeout: 30, useSudo: true);

        return [
            'success' => $result->success,
            'message' => $result->success ? 'UFW wurde aktiviert.' : $result->stderr,
        ];
    }

    public function disable(Server $server): array
    {
        $result = $this->engine->execute($server, 'ufw --force disable', timeout: 15, useSudo: true);

        return [
            'success' => $result->success,
            'message' => $result->success ? 'UFW wurde deaktiviert.' : $result->stderr,
        ];
    }

    public function allowAll(Server $server): array
    {
        $ports = ['22/tcp', '80/tcp', '443/tcp', '3306/tcp', '5432/tcp', '8080/tcp', '3000/tcp', '5000/tcp'];
        $commands = implode(' && ', array_map(fn (string $p): string => "ufw allow {$p}", $ports));

        $result = $this->engine->execute($server, $commands, timeout: 60, useSudo: true);

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
