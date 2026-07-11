<?php

namespace App\Services;

use App\Models\Server;
use RuntimeException;
use Spatie\Ssh\Ssh;

class SshService
{
    private const CONTROL_PATH = 'ssh-sockets';

    public function execute(Server $server, string $command, int $timeout = 30, bool $useSudo = true): SshResult
    {
        try {
            if ($useSudo && $server->use_sudo) {
                $command = $this->applySudo($command);
            }

            $process = $this->buildSsh($server, $timeout)->execute($command);

            $stdout = trim($process->getOutput() ?: '');
            $stderr = trim($process->getErrorOutput() ?: '');
            $exitCode = $process->getExitCode();

            $result = new SshResult(
                stdout: $stdout,
                stderr: $stderr,
                exitCode: $exitCode ?? -1,
                success: $exitCode === 0,
            );

            if (! $result->success) {
                $errorMessage = $this->classifyError($result->stdout ?: $result->stderr, $command);
                $result = new SshResult(
                    stdout: $result->stdout,
                    stderr: $errorMessage,
                    exitCode: $result->exitCode,
                    success: false,
                );
            }

            return $result;
        } catch (\Exception $e) {
            return new SshResult(
                stdout: '',
                stderr: $e->getMessage(),
                exitCode: -1,
                success: false,
            );
        }
    }

    public function test(Server $server, int $timeout = 5): ConnectionResult
    {
        $start = hrtime(true);

        try {
            $process = $this->buildSsh($server, $timeout)->execute('echo OK');
            $elapsed = (hrtime(true) - $start) / 1e6;
            $output = trim($process->getOutput() ?: '');

            if ($output === 'OK') {
                return new ConnectionResult(success: true, latencyMs: round($elapsed, 1));
            }

            return new ConnectionResult(success: false, latencyMs: round($elapsed, 1), errorMessage: 'Unerwartete Antwort vom Server');
        } catch (\Exception $e) {
            $elapsed = (hrtime(true) - $start) / 1e6;

            return new ConnectionResult(success: false, latencyMs: round($elapsed, 1), errorMessage: $e->getMessage());
        }
    }

    public function disconnect(Server $server): void
    {
        $controlPath = $this->controlPath($server);

        if (file_exists($controlPath)) {
            @exec("ssh -O stop -o ControlPath={$controlPath} {$server->username}@{$server->host} 2>/dev/null");
        }
    }

    public function disconnectAll(): void
    {
        $dir = storage_path(self::CONTROL_PATH);

        if (is_dir($dir)) {
            foreach (glob("{$dir}/server_*") as $socket) {
                @unlink($socket);
            }
        }
    }

    /** @return array{host: string, port: int, username: string, auth_type: string, password: ?string, key_path: ?string, key_content: ?string, control_path: string} */
    public function terminalConnection(Server $server): array
    {
        return [
            'host' => $server->host,
            'port' => $server->port,
            'username' => $server->username,
            'auth_type' => $server->auth_type,
            'password' => $server->auth_type === 'password' ? $server->credentials : null,
            'key_path' => $server->key_path,
            'key_content' => $server->auth_type === 'key' ? $server->key_content : null,
            'control_path' => $this->controlPath($server),
        ];
    }

    private function buildSsh(Server $server, int $timeout): Ssh
    {
        $ssh = Ssh::create($server->username, $server->host, $server->port)
            ->useMultiplexing(
                controlPath: $this->controlPath($server),
                controlPersist: '10m',
            )
            ->setTimeout($timeout)
            ->disableStrictHostKeyChecking()
            ->enableQuietMode();

        if ($server->auth_type === 'key') {
            $keyPath = $this->resolveKeyPath($server);

            if ($keyPath !== null) {
                $ssh->usePrivateKey($keyPath);
            } else {
                throw new RuntimeException('SSH-Key nicht gefunden. Bitte hinterlege einen gültigen SSH-Key.');
            }
        } else {
            $ssh->usePassword($server->credentials);
        }

        return $ssh;
    }

    private function resolveKeyPath(Server $server): ?string
    {
        $content = $server->key_content ?? $server->credentials;

        if ($content && $server->auth_type === 'key') {
            $path = storage_path(self::CONTROL_PATH.'/keys/'.$server->id.'_key');

            if (! is_dir(dirname($path))) {
                mkdir(dirname($path), 0700, true);
            }

            if (! file_exists($path)) {
                file_put_contents($path, $content);
                chmod($path, 0600);
            }

            return $path;
        }

        if ($server->key_path && file_exists($server->key_path)) {
            return $server->key_path;
        }

        return null;
    }

    private function controlPath(Server $server): string
    {
        $suffix = $server->id ?? md5($server->host.'-'.$server->port.'-'.$server->username);

        return storage_path(self::CONTROL_PATH."/server_{$suffix}");
    }

    private function applySudo(string $command): string
    {
        if (str_starts_with($command, 'sudo ')) {
            return $command;
        }

        if (str_starts_with($command, 'sh -c ') || str_starts_with($command, 'bash -c ')) {
            $inner = substr($command, 6);
            $inner = trim($inner, "'\" \t\n\r\0\x0B");

            return 'sudo DEBIAN_FRONTEND=noninteractive sh -c '.escapeshellarg($inner);
        }

        return "sudo DEBIAN_FRONTEND=noninteractive {$command}";
    }

    private function classifyError(string $output, string $command): string
    {
        $lower = mb_strtolower($output);

        if (str_contains($lower, 'permission denied') && ! str_contains($lower, 'publickey')) {
            return 'Zugriff verweigert (Permission denied). Möglicherweise ist ein sudo-Passwort erforderlich.';
        }

        if (str_contains($lower, 'could not get lock') || str_contains($lower, 'dpkg lock') || str_contains($lower, 'unable to lock')) {
            return 'Ein anderer Prozess verwendet apt/dpkg. Bitte warten oder den Prozess manuell beenden.';
        }

        if (str_contains($lower, 'package not found') || str_contains($lower, 'has no installation candidate')) {
            return 'Das Paket konnte nicht gefunden werden. Eventuell muss apt update ausgeführt werden.';
        }

        if (str_contains($lower, 'command not found') || str_contains($lower, 'not found')) {
            return 'Befehl nicht gefunden. Das entsprechende Paket ist nicht installiert.';
        }

        if (str_contains($lower, 'syntax error') || str_contains($lower, 'configtest')) {
            return 'Apache-Konfigurationsfehler: '.mb_substr($output, 0, 200);
        }

        if (str_contains($lower, 'access denied') || str_contains($lower, 'access denied for user')) {
            return 'MySQL-Zugriff verweigert. Benutzeranmeldedaten prüfen.';
        }

        if (str_contains($lower, "target '") && str_contains($lower, 'already exists')) {
            return 'Das Verzeichnis existiert bereits. Git Clone abgebrochen.';
        }

        if (str_contains($lower, 'repository not found')) {
            return 'Git-Repository nicht gefunden. URL prüfen.';
        }

        if (str_contains($lower, 'could not resolve host')) {
            return 'DNS-Auflösung auf dem Server fehlgeschlagen. Die angegebene Adresse konnte nicht aufgelöst werden.';
        }

        if (str_contains($lower, 'sudo:') && (str_contains($lower, 'password') || str_contains($lower, 'required'))) {
            return 'sudo-Passwort erforderlich. Bitte Server-Einstellungen prüfen.';
        }

        if (str_contains($lower, 'sshpass') && str_contains($lower, 'not found')) {
            return 'sshpass ist nicht auf dem Server installiert. Für Passwort-Authentifizierung sshpass installieren oder SSH-Key verwenden.';
        }

        return $output;
    }
}
