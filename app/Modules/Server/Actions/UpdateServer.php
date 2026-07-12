<?php

namespace App\Modules\Server\Actions;

use App\Models\Server;
use App\Modules\Server\Requests\UpdateServerRequest;

class UpdateServer
{
    public function handle(UpdateServerRequest $request, Server $server): Server
    {
        $data = $request->validated();
        $data['use_sudo'] = $request->boolean('use_sudo');

        $server->update($data);

        return $server;
    }
}
