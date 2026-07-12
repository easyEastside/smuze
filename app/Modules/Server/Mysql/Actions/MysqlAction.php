<?php

namespace App\Modules\Server\Mysql\Actions;

use App\Models\Server;
use App\Services\ExecutionEngine\PushAgentEngine;

class MysqlAction
{
    private const SYSTEM_DBS = ['information_schema', 'mysql', 'performance_schema', 'sys'];

    private const IDENTIFIER_REGEX = '/^[A-Za-z0-9_][A-Za-z0-9_-]{0,63}$/';

    private const USER_REGEX = '/^[A-Za-z0-9_][A-Za-z0-9_.-]{0,31}$/';

    private const HOST_REGEX = '/^(%|localhost|[A-Za-z0-9](?:[A-Za-z0-9.\-]{0,251}[A-Za-z0-9])?)$/';

    public function __construct(
        private PushAgentEngine $engine,
    ) {}

    public function status(Server $server): array
    {
        $result = $this->engine->action($server, 'mysql.status', []);

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

        $version = null;
        if (isset($data['VERSION'])) {
            $version = $data['VERSION'];
        } else {
            $lines = explode("\n", $result->stdout);
            foreach ($lines as $line) {
                if (str_contains($line, 'mysql')) {
                    $version = trim($line);
                    break;
                }
            }
        }

        return [
            'success' => true,
            'installed' => ($data['ACTIVE'] ?? '') !== 'unknown' || ($version !== null && $version !== 'NOT_INSTALLED'),
            'active' => ($data['ACTIVE'] ?? '') === 'active',
            'version' => $version && $version !== 'NOT_INSTALLED' ? $version : null,
        ];
    }

    public function install(Server $server, string $dbName = 'database'): array
    {
        $validation = $this->validateIdentifier($dbName, 'Datenbankname');
        if (! $validation['valid']) {
            return ['success' => false, 'message' => $validation['error']];
        }

        $result = $this->engine->action($server, 'mysql.install', [
            'db_name' => $dbName,
        ]);

        return [
            'success' => $result->success,
            'message' => $result->success ? 'MySQL wurde installiert.' : $result->stderr,
        ];
    }

    public function deinstall(Server $server): array
    {
        $result = $this->engine->action($server, 'mysql.deinstall', []);

        return [
            'success' => $result->success,
            'message' => $result->success ? 'MySQL wurde deinstalliert.' : $result->stderr,
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
        $result = $this->engine->action($server, "mysql.{$action}", []);

        $labels = ['start' => 'gestartet', 'stop' => 'gestoppt', 'restart' => 'neugestartet'];

        return [
            'success' => $result->success,
            'message' => $result->success ? "MySQL wurde {$labels[$action]}." : $result->stderr,
        ];
    }

    public function databases(Server $server): array
    {
        [$success, $data] = $this->execWithRows($server, 'mysql.databases');

        if (! $success) {
            return ['success' => false, 'databases' => $data];
        }

        $databases = array_values(array_filter($data, fn (string $db): bool => ! in_array(strtolower($db), self::SYSTEM_DBS, true)));

        return ['success' => true, 'databases' => $databases];
    }

    public function createDatabase(Server $server, string $dbName): array
    {
        $validation = $this->validateIdentifier($dbName, 'Datenbankname');
        if (! $validation['valid']) {
            return ['success' => false, 'message' => $validation['error']];
        }

        $result = $this->exec($server, 'mysql.create_database', ['db_name' => $dbName]);

        return [
            'success' => $result['success'],
            'message' => $result['success'] ? "Datenbank {$dbName} wurde erstellt." : $result['message'],
        ];
    }

    public function dropDatabase(Server $server, string $dbName): array
    {
        $validation = $this->validateIdentifier($dbName, 'Datenbankname');
        if (! $validation['valid']) {
            return ['success' => false, 'message' => $validation['error']];
        }

        if (in_array(strtolower($dbName), self::SYSTEM_DBS, true)) {
            return ['success' => false, 'message' => 'Systemdatenbanken können nicht gelöscht werden.'];
        }

        $result = $this->exec($server, 'mysql.drop_database', ['db_name' => $dbName]);

        return [
            'success' => $result['success'],
            'message' => $result['success'] ? "Datenbank {$dbName} wurde gelöscht." : $result['message'],
        ];
    }

    public function tables(Server $server, string $dbName): array
    {
        $validation = $this->validateIdentifier($dbName, 'Datenbankname');
        if (! $validation['valid']) {
            return ['success' => false, 'tables' => [], 'message' => $validation['error']];
        }

        [$success, $data] = $this->execWithRows($server, 'mysql.tables', ['database' => $dbName]);

        if (! $success) {
            return ['success' => false, 'tables' => [], 'message' => is_array($data) ? ($data[0] ?? 'Fehler') : $data];
        }

        return ['success' => true, 'tables' => $data, 'database' => $dbName];
    }

    public function createTable(Server $server, string $dbName, string $sql): array
    {
        $validation = $this->validateIdentifier($dbName, 'Datenbankname');
        if (! $validation['valid']) {
            return ['success' => false, 'message' => $validation['error']];
        }

        $result = $this->exec($server, 'mysql.create_table', [
            'database' => $dbName,
            'sql' => $sql,
        ]);

        return [
            'success' => $result['success'],
            'message' => $result['success'] ? 'Tabelle wurde erstellt.' : $result['message'],
        ];
    }

    public function dropTable(Server $server, string $dbName, string $table): array
    {
        $validation = $this->validateIdentifier($dbName, 'Datenbankname');
        if (! $validation['valid']) {
            return ['success' => false, 'message' => $validation['error']];
        }

        $tableValidation = $this->validateIdentifier($table, 'Tabellenname');
        if (! $tableValidation['valid']) {
            return ['success' => false, 'message' => $tableValidation['error']];
        }

        $result = $this->exec($server, 'mysql.drop_table', [
            'database' => $dbName,
            'table' => $table,
        ]);

        return [
            'success' => $result['success'],
            'message' => $result['success'] ? "Tabelle {$table} wurde gelöscht." : $result['message'],
        ];
    }

    public function users(Server $server): array
    {
        $result = $this->engine->action($server, 'mysql.users', []);

        if (! $result->success) {
            return ['success' => false, 'users' => []];
        }

        $users = [];
        foreach (explode("\n", $result->stdout) as $line) {
            $line = trim($line);
            if ($line === '') {
                continue;
            }
            $parts = explode("\t", $line);
            if (count($parts) >= 2) {
                $users[] = [
                    'username' => trim($parts[0], "'"),
                    'host' => trim($parts[1], "'"),
                ];
            }
        }

        return ['success' => true, 'users' => $users];
    }

    public function createUser(Server $server, string $username, string $host, string $password): array
    {
        $validation = $this->validateUser($username, $host, $password);
        if (! $validation['valid']) {
            return ['success' => false, 'message' => $validation['error']];
        }

        $result = $this->exec($server, 'mysql.create_user', [
            'username' => $username,
            'host' => $host,
            'password' => $password,
        ]);

        return [
            'success' => $result['success'],
            'message' => $result['success'] ? "Benutzer {$username}@{$host} wurde erstellt." : $result['message'],
        ];
    }

    public function dropUser(Server $server, string $username, string $host): array
    {
        $validation = $this->validateUser($username, $host);
        if (! $validation['valid']) {
            return ['success' => false, 'message' => $validation['error']];
        }

        $result = $this->exec($server, 'mysql.drop_user', [
            'username' => $username,
            'host' => $host,
        ]);

        return [
            'success' => $result['success'],
            'message' => $result['success'] ? "Benutzer {$username}@{$host} wurde gelöscht." : $result['message'],
        ];
    }

    public function setPassword(Server $server, string $username, string $host, string $password): array
    {
        $validation = $this->validateUser($username, $host, $password);
        if (! $validation['valid']) {
            return ['success' => false, 'message' => $validation['error']];
        }

        $result = $this->exec($server, 'mysql.set_password', [
            'username' => $username,
            'host' => $host,
            'password' => $password,
        ]);

        return [
            'success' => $result['success'],
            'message' => $result['success'] ? 'Passwort wurde geändert.' : $result['message'],
        ];
    }

    public function grantAll(Server $server, string $username, string $host): array
    {
        $validation = $this->validateUser($username, $host);
        if (! $validation['valid']) {
            return ['success' => false, 'message' => $validation['error']];
        }

        $result = $this->exec($server, 'mysql.grant_all', [
            'username' => $username,
            'host' => $host,
        ]);

        return [
            'success' => $result['success'],
            'message' => $result['success'] ? "Alle Rechte für {$username}@{$host} wurden erteilt." : $result['message'],
        ];
    }

    /** @return array{success: bool, message: string} */
    private function exec(Server $server, string $action, array $payload = []): array
    {
        $result = $this->engine->action($server, $action, $payload);

        return [
            'success' => $result->success,
            'message' => $result->success ? '' : $result->stderr,
        ];
    }

    /** @return array{0: bool, 1: array<int, string>} */
    private function execWithRows(Server $server, string $action, array $payload = []): array
    {
        $result = $this->engine->action($server, $action, $payload);

        if (! $result->success) {
            return [false, [$result->stderr]];
        }

        $lines = array_filter(explode("\n", $result->stdout), fn (string $line): bool => trim($line) !== '');

        return [true, array_values(array_map('trim', $lines))];
    }

    /** @return array{valid: bool, error: string} */
    private function validateIdentifier(string $name, string $label): array
    {
        $name = trim($name);

        if ($name === '') {
            return ['valid' => false, 'error' => "{$label} darf nicht leer sein."];
        }

        if (! preg_match(self::IDENTIFIER_REGEX, $name)) {
            return ['valid' => false, 'error' => "{$label} darf nur Buchstaben, Zahlen, Unterstrich und Bindestrich enthalten und maximal 64 Zeichen lang sein."];
        }

        return ['valid' => true, 'error' => ''];
    }

    /** @return array{valid: bool, error: string} */
    private function validateUser(string $username, string $host, ?string $password = null): array
    {
        $username = trim($username);
        $host = trim($host);

        if (! preg_match(self::USER_REGEX, $username)) {
            return ['valid' => false, 'error' => 'Benutzername darf nur Buchstaben, Zahlen, Punkt, Unterstrich und Bindestrich enthalten und maximal 32 Zeichen lang sein.'];
        }

        if (! preg_match(self::HOST_REGEX, $host) || str_contains($host, '..')) {
            return ['valid' => false, 'error' => 'Host muss localhost, %, eine IP-Adresse oder ein gültiger DNS-Name sein.'];
        }

        if ($password !== null && $password === '') {
            return ['valid' => false, 'error' => 'Passwort darf nicht leer sein.'];
        }

        return ['valid' => true, 'error' => ''];
    }
}
