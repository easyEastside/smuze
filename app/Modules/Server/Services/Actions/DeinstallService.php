<?php

namespace App\Modules\Server\Services\Actions;

use App\Models\Server;
use App\Services\SshService;

class DeinstallService
{
    private const COMMANDS = [
        'php' => [
            'systemctl stop php*-fpm php*-cgi 2>/dev/null || true',
            'DEBIAN_FRONTEND=noninteractive apt remove --purge php* -y',
            'DEBIAN_FRONTEND=noninteractive apt autoremove -y',
            'DEBIAN_FRONTEND=noninteractive apt autoclean',
            'rm -rf /etc/php',
            'DEBIAN_FRONTEND=noninteractive apt update',
        ],
        'apache' => [
            'systemctl stop apache2 2>/dev/null || true',
            'DEBIAN_FRONTEND=noninteractive apt remove --purge apache2 apache2-bin apache2-data apache2-utils -y',
            'DEBIAN_FRONTEND=noninteractive apt autoremove -y',
            'DEBIAN_FRONTEND=noninteractive apt autoclean',
            'rm -rf /etc/apache2',
        ],
        'mysql' => [
            'systemctl stop mysql',
            'DEBIAN_FRONTEND=noninteractive apt remove --purge mysql-server mysql-client mysql-common -y',
            'DEBIAN_FRONTEND=noninteractive apt autoremove -y',
            'DEBIAN_FRONTEND=noninteractive apt autoclean',
            'rm -rf /etc/mysql',
            'rm -rf /var/lib/mysql',
            'DEBIAN_FRONTEND=noninteractive apt update',
        ],
        'node' => [
            'export NVM_DIR="$HOME/.nvm"',
            '[ -s "$NVM_DIR/nvm.sh" ] && . "$NVM_DIR/nvm.sh"',
            'nvm deactivate 2>/dev/null || true',
            'nvm uninstall 24 2>/dev/null || true',
            'rm -rf "$NVM_DIR"',
            'sed -i "/NVM_DIR/d" ~/.bashrc ~/.zshrc ~/.profile 2>/dev/null || true',
        ],
        'nvm' => [
            'rm -rf "$HOME/.nvm"',
            'sed -i "/NVM_DIR/d" ~/.bashrc ~/.zshrc ~/.profile 2>/dev/null || true',
        ],
        'npm' => [
            'export NVM_DIR="$HOME/.nvm"',
            '[ -s "$NVM_DIR/nvm.sh" ] && . "$NVM_DIR/nvm.sh"',
            'nvm deactivate 2>/dev/null || true',
            'nvm uninstall 24 2>/dev/null || true',
            'rm -rf "$NVM_DIR"',
            'sed -i "/NVM_DIR/d" ~/.bashrc ~/.zshrc ~/.profile 2>/dev/null || true',
        ],
        'composer' => [
            'rm -f /usr/local/bin/composer',
            'rm -rf ~/.composer ~/.cache/composer 2>/dev/null || true',
        ],
    ];

    private const TIMEOUTS = [
        'php' => 120,
        'apache' => 180,
        'mysql' => 120,
        'node' => 120,
        'nvm' => 60,
        'npm' => 120,
        'composer' => 60,
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
        private SshService $ssh,
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

        $result = $this->ssh->execute($server, $command, timeout: $timeout, useSudo: $useSudo, onOutput: $onOutput);

        $label = self::LABELS[$service] ?? $service;

        return [
            'success' => $result->success,
            'message' => $result->success
                ? "{$label} wurde deinstalliert."
                : $result->stderr,
        ];
    }
}
