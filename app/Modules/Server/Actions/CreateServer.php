<?php

namespace App\Modules\Server\Actions;

use App\Models\Server;
use App\Modules\Server\Requests\StoreServerRequest;

class CreateServer
{
    public function handle(StoreServerRequest $request): Server
    {
        $data = $request->validated();
        $data['use_sudo'] = $request->boolean('use_sudo');
        $data['ssh_compression'] = $request->boolean('ssh_compression');

        return $request->user()->servers()->create($data);
    }
}
