<?php

namespace App\Modules\Server\Actions;

use App\Models\Server;
use App\Services\ServerMonitoringService;

class RefreshDashboard
{
    public function __construct(
        private ServerMonitoringService $monitoring,
    ) {}

    /** @return array<string, mixed> */
    public function handle(Server $server): array
    {
        $info = $this->monitoring->collectSystemInfo($server);

        if ($info->hasError()) {
            return ['error' => $info->error, 'server_id' => $server->id];
        }

        return [
            'server_id' => $server->id,
            'hostname' => $info->hostname,
            'os' => $info->os,
            'uptime' => $info->uptime,
            'load' => $info->load,
            'cpu_percent' => $info->cpuPercent,
            'ram_total_mb' => $info->ramTotalMb,
            'ram_used_mb' => $info->ramUsedMb,
            'ram_percent' => $info->ramPercent,
            'disk_total_mb' => $info->diskTotalMb,
            'disk_used_mb' => $info->diskUsedMb,
            'disk_percent' => $info->diskPercent,
            'php_version' => $info->phpVersion,
            'apache_version' => $info->apacheVersion,
            'mysql_version' => $info->mysqlVersion,
            'node_version' => $info->nodeVersion,
            'nvm_version' => $info->nvmVersion,
            'composer_version' => $info->composerVersion,
        ];
    }
}
