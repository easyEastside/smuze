<?php

namespace Database\Factories;

use App\Models\Server;
use App\Models\ServerDeployment;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ServerDeployment>
 */
class ServerDeploymentFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'server_id' => Server::factory(),
            'user_id' => User::factory(),
            'name' => fake()->words(2, true),
            'repo_url' => 'https://github.com/laravel/laravel.git',
            'target_path' => '/var/www/'.fake()->slug(),
            'domain' => fake()->domainName(),
            'webserver' => 'apache',
            'php_version' => '8.5',
            'install_node' => true,
            'run_build' => true,
            'run_migrations' => false,
            'write_env' => true,
            'env' => [
                'APP_ENV' => 'production',
                'APP_DEBUG' => 'false',
            ],
        ];
    }
}
