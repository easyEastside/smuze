<?php

namespace App\Modules\Server\Actions;

use App\Models\Server;
use App\Services\SshService;

class SystemRestart
{
    public function __construct(
        private SshService $ssh,
    ) {}

    /** @return array<string, mixed> */
    public function handle(Server $server): array
    {
        $this->ssh->execute($server, 'reboot', timeout: 15);

        return [
            'success' => true,
            'message' => 'Neustart-Befehl gesendet.',
        ];
    }
}
