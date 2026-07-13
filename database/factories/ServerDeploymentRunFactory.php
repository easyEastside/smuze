<?php

namespace Database\Factories;

use App\Models\Server;
use App\Models\ServerDeployment;
use App\Models\ServerDeploymentRun;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ServerDeploymentRun>
 */
class ServerDeploymentRunFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'server_deployment_id' => ServerDeployment::factory(),
            'server_id' => Server::factory(),
            'user_id' => User::factory(),
            'status' => 'success',
            'exit_code' => 0,
            'output' => 'Deployment abgeschlossen.',
            'error_output' => '',
            'duration_ms' => fake()->numberBetween(1000, 60000),
            'started_at' => now()->subMinute(),
            'finished_at' => now(),
        ];
    }
}
