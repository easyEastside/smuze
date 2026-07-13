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
        $data['agent_public_url'] = filled($data['agent_public_url'] ?? null) ? rtrim($data['agent_public_url'], '/') : null;

        return $request->user()->servers()->create($data);
    }
}
