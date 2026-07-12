<?php

namespace App\Services;

use App\Models\Server;

class ServerMonitoringService
{
    public function __construct(
        private SshService $ssh,
    ) {}

    public function collectSystemInfo(Server $server): SystemInfo
    {
        $script = $this->buildSystemInfoScript();
        $result = $this->ssh->execute($server, $script, timeout: 30, useSudo: false);

        if (! $result->success) {
            return new SystemInfo(error: $result->stderr);
        }

        return $this->parseSystemInfo($result->stdout);
    }

    public function testConnection(Server $server): ConnectionResult
    {
        return $this->ssh->test($server);
    }

    private function buildSystemInfoScript(): string
    {
        return <<<'SCRIPT'
export NVM_DIR="$HOME/.nvm"
[ -s "$NVM_DIR/nvm.sh" ] && . "$NVM_DIR/nvm.sh"

printf 'HOSTNAME=%s\n' "$(hostname)"

OS_VALUE=$(lsb_release -ds 2>/dev/null || awk -F= '/^PRETTY_NAME=/ {gsub(/"/, "", $2); print $2}' /etc/os-release 2>/dev/null)
printf 'OS=%s\n' "$OS_VALUE"

printf 'UPTIME=%s\n' "$(uptime -p 2>/dev/null || uptime)"

awk '{printf "LOAD=%s\n", $1}' /proc/loadavg

CPU_PREV=$(awk '/^cpu / {print $2+$3+$4+$5+$6+$7+$8+$9+$10+$11+$12+$13+$14+$15+$16+$17+$18+$19+$20+$21+$22+$23}' /proc/stat)
CPU_IDLE_PREV=$(awk '/^cpu / {print $5}' /proc/stat)
sleep 0.5
CPU_CURR=$(awk '/^cpu / {print $2+$3+$4+$5+$6+$7+$8+$9+$10+$11+$12+$13+$14+$15+$16+$17+$18+$19+$20+$21+$22+$23}' /proc/stat)
CPU_IDLE_CURR=$(awk '/^cpu / {print $5}' /proc/stat)

TOTAL_DELTA=$((CPU_CURR - CPU_PREV))
IDLE_DELTA=$((CPU_IDLE_CURR - CPU_IDLE_PREV))
if [ "$TOTAL_DELTA" -gt 0 ] 2>/dev/null; then
    CPU_PCT=$((100 * (TOTAL_DELTA - IDLE_DELTA) / TOTAL_DELTA))
else
    CPU_PCT=0
fi
printf 'CPU_PERCENT=%s\n' "$CPU_PCT"

free -m | awk '/^Mem:/ {printf "RAM_TOTAL_MB=%s\nRAM_USED_MB=%s\nRAM_PERCENT=%.0f\n", $2, $3, ($3/$2)*100}'

df -Pm / | awk 'NR==2 {printf "DISK_TOTAL_MB=%s\nDISK_USED_MB=%s\nDISK_PERCENT=%s\n", $2, $3, $5}' | tr -d '%'

printf 'PHP_VERSION=%s\n' "$(php -v 2>/dev/null | head -1)"
printf 'APACHE_VERSION=%s\n' "$(apache2 -v 2>/dev/null | sed -n '1p')"
printf 'MYSQL_VERSION=%s\n' "$(mysql --version 2>/dev/null)"
printf 'NODE_VERSION=%s\n' "$(node --version 2>/dev/null)"
printf 'NVM_VERSION=%s\n' "$(bash -c 'export NVM_DIR="$HOME/.nvm"; [ -s "$NVM_DIR/nvm.sh" ] && . "$NVM_DIR/nvm.sh" && nvm --version' 2>/dev/null)"
printf 'COMPOSER_VERSION=%s\n' "$(composer --version 2>/dev/null | head -1)"
SCRIPT;
    }

    private function parseSystemInfo(string $rawOutput): SystemInfo
    {
        $data = [];
        foreach (explode("\n", $rawOutput) as $line) {
            $line = trim($line);

            if ($line !== '' && str_contains($line, '=')) {
                $parts = explode('=', $line, 2);
                $data[trim($parts[0])] = trim($parts[1] ?? '');
            }
        }

        return new SystemInfo(
            hostname: $data['HOSTNAME'] ?? null,
            os: $data['OS'] ?? null,
            uptime: $data['UPTIME'] ?? null,
            load: $data['LOAD'] ?? null,
            cpuPercent: isset($data['CPU_PERCENT']) ? (int) $data['CPU_PERCENT'] : null,
            ramTotalMb: isset($data['RAM_TOTAL_MB']) ? (int) $data['RAM_TOTAL_MB'] : null,
            ramUsedMb: isset($data['RAM_USED_MB']) ? (int) $data['RAM_USED_MB'] : null,
            ramPercent: isset($data['RAM_PERCENT']) ? (int) $data['RAM_PERCENT'] : null,
            diskTotalMb: isset($data['DISK_TOTAL_MB']) ? (int) $data['DISK_TOTAL_MB'] : null,
            diskUsedMb: isset($data['DISK_USED_MB']) ? (int) $data['DISK_USED_MB'] : null,
            diskPercent: isset($data['DISK_PERCENT']) ? (int) $data['DISK_PERCENT'] : null,
            phpVersion: $data['PHP_VERSION'] ?? null,
            apacheVersion: $data['APACHE_VERSION'] ?? null,
            mysqlVersion: $data['MYSQL_VERSION'] ?? null,
            nodeVersion: $data['NODE_VERSION'] ?? null,
            nvmVersion: $data['NVM_VERSION'] ?? null,
            composerVersion: $data['COMPOSER_VERSION'] ?? null,
        );
    }
}
