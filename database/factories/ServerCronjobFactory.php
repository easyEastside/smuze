<?php

namespace Database\Factories;

use App\Models\Server;
use App\Models\ServerCronjob;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ServerCronjob>
 */
class ServerCronjobFactory extends Factory
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
            'schedule' => '0 * * * *',
            'command' => 'php artisan schedule:run',
            'working_directory' => '/var/www/html',
            'run_as' => null,
            'enabled' => true,
            'last_run_at' => null,
            'last_exit_code' => null,
            'last_stdout' => null,
            'last_stderr' => null,
        ];
    }
}
