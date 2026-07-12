<?php

namespace App\Modules\Server\Services\Actions;

use App\Models\Server;
use App\Services\ExecutionEngine\ExecutionEngine;

class InstallService
{
    private const COMMANDS = [
        'php' => [
            'DEBIAN_FRONTEND=noninteractive apt update',
            'DEBIAN_FRONTEND=noninteractive apt install php php-cli php-common php-fpm php-cgi php-mysql php-pgsql php-sqlite3 php-curl php-gd php-mbstring php-xml php-zip php-bcmath php-intl php-soap php-xmlrpc php-opcache php-readline php-pear -y',
        ],
        'apache' => [
            'apt update',
            'apt install apache2 -y',
            'systemctl enable --now apache2',
        ],
        'mysql' => [
            'DEBIAN_FRONTEND=noninteractive apt update',
            'DEBIAN_FRONTEND=noninteractive apt install mysql-server -y',
            'systemctl start mysql',
            'systemctl enable mysql',
            "mysql -e 'CREATE DATABASE IF NOT EXISTS `database`;'",
        ],
        'node' => [
            'export NVM_DIR="$HOME/.nvm"',
            'curl -o- https://raw.githubusercontent.com/nvm-sh/nvm/v0.40.5/install.sh | bash',
            '. "$NVM_DIR/nvm.sh"',
            'nvm install 24',
        ],
        'nvm' => [
            'export NVM_DIR="$HOME/.nvm"',
            'curl -o- https://raw.githubusercontent.com/nvm-sh/nvm/v0.40.5/install.sh | bash',
            '. "$NVM_DIR/nvm.sh"',
            'nvm --version',
        ],
        'npm' => [
            'export NVM_DIR="$HOME/.nvm"',
            'curl -o- https://raw.githubusercontent.com/nvm-sh/nvm/v0.40.5/install.sh | bash',
            '. "$NVM_DIR/nvm.sh"',
            'nvm install 24',
        ],
        'composer' => [
            'EXPECTED_CHECKSUM="$(curl -sS https://composer.github.io/installer.sig)"',
            'php -r "copy(\'https://getcomposer.org/installer\', \'composer-setup.php\');"',
            'ACTUAL_CHECKSUM="$(php -r \'echo hash_file("sha384", "composer-setup.php");\')"',
            'if [ "$EXPECTED_CHECKSUM" != "$ACTUAL_CHECKSUM" ]; then >&2 echo "ERROR: Invalid installer checksum"; rm -f composer-setup.php; exit 1; fi',
            'php composer-setup.php',
            'php -r "unlink(\'composer-setup.php\');"',
            'mv composer.phar /usr/local/bin/composer',
        ],
    ];

    private const TIMEOUTS = [
        'php' => 300,
        'apache' => 300,
        'mysql' => 300,
        'node' => 300,
        'nvm' => 120,
        'npm' => 300,
        'composer' => 120,
    ];

    private const NO_SUDO = ['node', 'nvm', 'npm'];

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
        private ExecutionEngine $engine,
    ) {}

    /** @return array<string, mixed> */
    public function handle(Server $server, string $service, ?callable $onOutput = null): array
    {
        $commands = self::COMMANDS[$service] ?? null;

        if ($commands === null) {
            return ['success' => false, 'message' => "Unbekannter Service: {$service}"];
        }

        $command = implode(' && ', $commands);
        $timeout = self::TIMEOUTS[$service] ?? 120;
        $useSudo = ! in_array($service, self::NO_SUDO, true);

        $result = $this->engine->execute($server, $command, timeout: $timeout, useSudo: $useSudo, onOutput: $onOutput);

        $label = self::LABELS[$service] ?? $service;

        return [
            'success' => $result->success,
            'message' => $result->success
                ? "{$label} wurde installiert."
                : $result->stderr,
        ];
    }
}
