<?php

namespace App\Modules\Server\Actions;

use App\Models\Server;
use App\Services\SshService;

class SystemUpgrade
{
    public function __construct(
        private SshService $ssh,
    ) {}

    /** @return array<string, mixed> */
    public function handle(Server $server): array
    {
        $result = $this->ssh->execute($server, 'apt upgrade -y', timeout: 900);

        return [
            'success' => $result->success,
            'message' => $result->success ? 'Pakete aktualisiert.' : $result->stderr,
        ];
    }
}
