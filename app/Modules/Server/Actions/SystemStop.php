<?php

namespace App\Modules\Server\Actions;

use App\Models\Server;
use App\Services\SshService;

class SystemStop
{
    public function __construct(
        private SshService $ssh,
    ) {}

    /** @return array<string, mixed> */
    public function handle(Server $server): array
    {
        $this->ssh->execute($server, 'shutdown -h now', timeout: 15);

        return [
            'success' => true,
            'message' => 'Herunterfahr-Befehl gesendet.',
        ];
    }
}
