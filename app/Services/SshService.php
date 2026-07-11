<?php

namespace App\Services;

use App\Models\Server;
use phpseclib3\Crypt\Common\PrivateKey;
use phpseclib3\Crypt\PublicKeyLoader;
use phpseclib3\Exception\UnableToConnectException;
use phpseclib3\Net\SSH2;
use RuntimeException;

class SshService
{
    private array $connections = [];

    public function connect(Server $server, int $timeout = 10): SSH2
    {
        $serverId = $server->id;

        if (isset($this->connections[$serverId]) && $this->connections[$serverId]->isConnected()) {
            return $this->connections[$serverId];
        }

        try {
            $ssh = new SSH2($server->host, $server->port, $timeout);
            $ssh->setTimeout($timeout);

            $key = null;
            $password = null;

            if ($server->auth_type === 'key') {
                if ($server->key_content) {
                    $key = PublicKeyLoader::load($server->key_content);
                } elseif ($server->key_path && file_exists($server->key_path)) {
                    $key = PublicKeyLoader::load(file_get_contents($server->key_path));
                } elseif ($server->credentials) {
                    $key = PublicKeyLoader::load($server->credentials);
                }

                if ($key !== null) {
                    if ($key instanceof PrivateKey && $server->credentials && $server->auth_type === 'password') {
                        $key->withPassword($server->credentials);
                    }

                    if (! $ssh->login($server->username, $key)) {
                        throw new RuntimeException($this->formatAuthError($server));
                    }
                } else {
                    throw new RuntimeException('SSH-Key nicht gefunden. Bitte hinterlege einen gültigen SSH-Key.');
                }
            } else {
                $password = $server->credentials;

                if (! $ssh->login($server->username, $password)) {
                    throw new RuntimeException($this->formatAuthError($server));
                }
            }

            $this->connections[$serverId] = $ssh;

            return $ssh;
        } catch (UnableToConnectException $e) {
            throw new RuntimeException($this->formatConnectionError($server, $e));
        } catch (RuntimeException $e) {
            throw $e;
        } catch (\Exception $e) {
            throw new RuntimeException("SSH-Fehler: {$e->getMessage()}");
        }
    }

    public function execute(Server $server, string $command, int $timeout = 30, bool $useSudo = true): SshResult
    {
        try {
            $ssh = $this->connect($server, $timeout);

            if ($useSudo && $server->use_sudo) {
                $command = $this->applySudo($command);
            }

            $output = $ssh->exec($command);
            $exitCode = $ssh->getExitStatus();

            $result = new SshResult(
                stdout: $output !== false ? trim($output) : '',
                stderr: '',
                exitCode: $exitCode ?? -1,
                success: $exitCode === 0,
            );

            if (! $result->success) {
                $errorMessage = $this->classifyError($result->stdout, $command);
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
            $ssh = $this->connect($server, $timeout);
            $output = $ssh->exec('echo OK');
            $elapsed = (hrtime(true) - $start) / 1e6;

            if (trim($output ?? '') === 'OK') {
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
        $serverId = $server->id;

        if (isset($this->connections[$serverId])) {
            try {
                $this->connections[$serverId]->disconnect();
            } catch (\Exception) {
            }

            unset($this->connections[$serverId]);
        }
    }

    public function disconnectAll(): void
    {
        foreach (array_keys($this->connections) as $serverId) {
            try {
                $this->connections[$serverId]->disconnect();
            } catch (\Exception) {
            }
        }

        $this->connections = [];
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

        if (preg_match('/^(.*?)(\s*&&\s*|\s*\|\|\s*|\s*;\s*)(.*)$/s', $command, $matches)) {
            $rest = $matches[3];

            return 'sudo DEBIAN_FRONTEND=noninteractive sh -c '.escapeshellarg($command);
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

        return $output;
    }

    private function formatConnectionError(Server $server, UnableToConnectException $e): string
    {
        $message = $e->getMessage();
        $lower = mb_strtolower($message);

        if (str_contains($lower, 'timed out') || str_contains($lower, 'timeout')) {
            return "Verbindung zu {$server->host}:{$server->port} konnte nicht hergestellt werden (Timeout).";
        }

        if (str_contains($lower, 'connection refused')) {
            return "Verbindung zu {$server->host}:{$server->port} verweigert (Connection refused). Der SSH-Dienst läuft möglicherweise nicht.";
        }

        if (str_contains($lower, 'network is unreachable')) {
            return "Netzwerk nicht erreichbar: {$server->host}";
        }

        if (str_contains($lower, 'name or service not known') || str_contains($lower, 'dns')) {
            return "DNS-Auflösung fehlgeschlagen: {$server->host} konnte nicht aufgelöst werden.";
        }

        return $e->getMessage();
    }

    private function formatAuthError(Server $server): string
    {
        if ($server->auth_type === 'key') {
            return 'SSH-Key-Authentifizierung fehlgeschlagen. Bitte prüfe den hinterlegten SSH-Key.';
        }

        return "SSH-Passwort-Authentifizierung für {$server->username}@{$server->host} fehlgeschlagen. Bitte Passwort prüfen.";
    }
}
