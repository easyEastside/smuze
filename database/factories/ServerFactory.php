<?php

namespace Database\Factories;

use App\Models\Server;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class ServerFactory extends Factory
{
    protected $model = Server::class;

    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'name' => fake()->domainWord().' Server',
            'host' => fake()->ipv4(),
            'port' => 22,
            'username' => 'root',
            'auth_type' => 'key',
            'credentials' => fake()->optional()->text(),
            'use_sudo' => true,
            'agent_enabled' => false,
            'agent_token' => null,
            'agent_version' => null,
            'agent_last_seen_at' => null,
            'agent_status' => 'disconnected',
            'agent_transport' => 'polling',
            'agent_metrics' => null,
            'agent_metrics_collected_at' => null,
            'execution_driver' => 'ssh',
            'notes' => fake()->optional()->sentence(),
        ];
    }

    public function withAgent(): static
    {
        return $this->state(fn (): array => [
            'agent_enabled' => true,
            'agent_token' => 'smz_'.fake()->sha256(),
            'agent_version' => '0.1.0',
            'agent_last_seen_at' => now(),
            'agent_status' => 'connected',
            'agent_metrics_collected_at' => now(),
            'execution_driver' => 'agent',
        ]);
    }
}
