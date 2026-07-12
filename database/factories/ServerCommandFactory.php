<?php

namespace Database\Factories;

use App\Models\Server;
use App\Models\ServerCommand;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<ServerCommand>
 */
class ServerCommandFactory extends Factory
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
            'uuid' => (string) Str::uuid(),
            'command' => 'echo '.fake()->word(),
            'use_sudo' => true,
            'timeout' => 30,
            'status' => ServerCommand::StatusQueued,
            'stdout' => null,
            'stderr' => null,
            'exit_code' => null,
            'started_at' => null,
            'completed_at' => null,
            'failed_at' => null,
        ];
    }

    public function running(): static
    {
        return $this->state(fn (): array => [
            'status' => ServerCommand::StatusRunning,
            'started_at' => now(),
        ]);
    }

    public function completed(): static
    {
        return $this->state(fn (): array => [
            'status' => ServerCommand::StatusCompleted,
            'stdout' => 'OK',
            'exit_code' => 0,
            'started_at' => now()->subSecond(),
            'completed_at' => now(),
        ]);
    }

    public function failed(): static
    {
        return $this->state(fn (): array => [
            'status' => ServerCommand::StatusFailed,
            'stderr' => 'Command failed',
            'exit_code' => 1,
            'started_at' => now()->subSecond(),
            'failed_at' => now(),
        ]);
    }
}
