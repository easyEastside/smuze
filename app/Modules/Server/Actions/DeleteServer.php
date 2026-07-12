<?php

namespace App\Modules\Server\Actions;

use App\Models\Server;

class DeleteServer
{
    public function handle(Server $server): void
    {
        $server->delete();
    }
}
