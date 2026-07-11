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

        return $request->user()->servers()->create($data);
    }
}
