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
            'ssh_connect_timeout' => 5,
            'ssh_command_timeout' => 30,
            'ssh_control_persist' => 30,
            'ssh_server_alive_interval' => 15,
            'ssh_server_alive_count_max' => 3,
            'ssh_connection_attempts' => 2,
            'ssh_compression' => false,
            'notes' => fake()->optional()->sentence(),
        ];
    }
}
