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
            'notes' => fake()->optional()->sentence(),
        ];
    }
}
