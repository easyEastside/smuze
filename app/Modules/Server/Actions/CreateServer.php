<?php

namespace App\Modules\Server\Actions;

use App\Models\Server;
use App\Modules\Server\Requests\StoreServerRequest;

class CreateServer
{
    public function handle(StoreServerRequest $request): Server
    {
        $data = $request->validated();
        $data['agent_port'] ??= config('agent.push_port', 9300);

        return $request->user()->servers()->create($data);
    }
}
