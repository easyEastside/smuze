<?php

namespace App\Services;

use App\Models\Server;
use RuntimeException;
use Spatie\Ssh\Ssh;

class SshService
{
    private const CONTROL_PATH = 'ssh-sockets';

    public function execute(Server $server, string $command, int $timeout = 30, bool $useSudo = true, ?callable $onOutput = null): SshResult
    {
        try {
            if ($useSudo && $server->use_sudo) {
                $command = $this->applySudo($command);
            }

            $ssh = $this->buildSsh($server, $this->commandTimeout($server, $timeout));

            if ($onOutput !== null) {
                $ssh->onOutput($onOutput);
            }

            $process = $ssh->execute($command);

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
            $command = sprintf(
                'ssh -O stop -p %d -o %s %s 2>/dev/null',
                $server->port,
                escapeshellarg('ControlPath='.$controlPath),
                escapeshellarg("{$server->username}@{$server->host}"),
            );

            @exec($command);
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
                controlPersist: $this->option($server, 'ssh_control_persist', 30).'m',
            )
            ->setTimeout($timeout)
            ->addExtraOption('-o ConnectTimeout='.$this->option($server, 'ssh_connect_timeout', 5))
            ->addExtraOption('-o ServerAliveInterval='.$this->option($server, 'ssh_server_alive_interval', 15))
            ->addExtraOption('-o ServerAliveCountMax='.$this->option($server, 'ssh_server_alive_count_max', 3))
            ->addExtraOption('-o ConnectionAttempts='.$this->option($server, 'ssh_connection_attempts', 2))
            ->disableStrictHostKeyChecking()
            ->enableQuietMode();

        if ($server->ssh_compression) {
            $ssh->addExtraOption('-o Compression=yes');
        }

        if ($server->auth_type === 'key') {
            $keyPath = $this->resolveKeyPath($server);

            if ($keyPath !== null) {
                $ssh->usePrivateKey($keyPath)
                    ->disablePasswordAuthentication()
                    ->addExtraOption('-o IdentitiesOnly=yes');
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

            $normalized = str_replace("\r\n", "\n", $content);

            if (! str_ends_with($normalized, "\n")) {
                $normalized .= "\n";
            }

            if (! file_exists($path) || file_get_contents($path) !== $normalized) {
                file_put_contents($path, $normalized);
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
        $dir = storage_path(self::CONTROL_PATH);

        if (! is_dir($dir)) {
            mkdir($dir, 0700, true);
        }

        $suffix = $server->id ?? md5($server->host.'-'.$server->port.'-'.$server->username);

        return "{$dir}/server_{$suffix}";
    }

    private function commandTimeout(Server $server, int $timeout): int
    {
        if ($timeout !== 30) {
            return $timeout;
        }

        return $this->option($server, 'ssh_command_timeout', 30);
    }

    private function option(Server $server, string $key, int $default): int
    {
        $value = $server->getAttribute($key);

        return is_numeric($value) && (int) $value > 0 ? (int) $value : $default;
    }

    private function applySudo(string $command): string
    {
        if (str_starts_with($command, 'sudo ')) {
            return $command;
        }

        return 'sudo DEBIAN_FRONTEND=noninteractive sh -lc '.escapeshellarg($command);
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

        if (str_contains($lower, 'connection timed out') || str_contains($lower, 'operation timed out')) {
            return 'SSH-Verbindung abgelaufen. Connect Timeout oder Netzwerkverbindung prüfen.';
        }

        if (str_contains($lower, 'connection refused')) {
            return 'SSH-Verbindung abgelehnt. SSH-Dienst, Port und Firewall prüfen.';
        }

        if (str_contains($lower, 'no route to host')) {
            return 'Server nicht erreichbar. Netzwerkroute oder Firewall prüfen.';
        }

        if (str_contains($lower, 'host key verification failed')) {
            return 'SSH-Host-Key konnte nicht verifiziert werden. Known-Hosts Eintrag prüfen.';
        }

        if (str_contains($lower, 'too many authentication failures')) {
            return 'Zu viele SSH-Authentifizierungsversuche. Hinterlegten Key und IdentitiesOnly prüfen.';
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
