<?php

namespace App\Modules\Server\Actions;

use App\Models\Server;
use App\Services\SshService;

class SystemUpdate
{
    public function __construct(
        private SshService $ssh,
    ) {}

    /** @return array<string, mixed> */
    public function handle(Server $server): array
    {
        $result = $this->ssh->execute($server, 'apt update', timeout: 120);

        return [
            'success' => $result->success,
            'message' => $result->success ? 'Paketlisten aktualisiert.' : $result->stderr,
        ];
    }
}
