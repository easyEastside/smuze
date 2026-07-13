<?php

namespace Database\Factories;

use App\Models\Server;
use App\Models\ServerBackup;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ServerBackup>
 */
class ServerBackupFactory extends Factory
{
    public function definition(): array
    {
        return [
            'server_id' => Server::factory(),
            'user_id' => User::factory(),
            'name' => fake()->words(2, true),
            'type' => fake()->randomElement(['mysql', 'files', 'both']),
            'targets' => fake()->randomElement([
                ['database', 'blog'],
                ['/var/www', '/etc/nginx'],
                ['database'],
            ]),
            'storage' => 'local',
            's3_config' => null,
            'schedule' => '0 3 * * *',
            'enabled' => true,
            'retention_days' => 7,
            'last_status' => null,
            'last_run_at' => null,
        ];
    }

    public function mysql(): static
    {
        return $this->state(fn (array $attributes): array => [
            'type' => 'mysql',
            'targets' => ['database'],
        ]);
    }

    public function files(): static
    {
        return $this->state(fn (array $attributes): array => [
            'type' => 'files',
            'targets' => ['/var/www'],
        ]);
    }
}
