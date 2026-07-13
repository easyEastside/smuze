<?php

namespace Database\Factories;

use App\Models\ServerBackup;
use App\Models\ServerBackupArchive;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ServerBackupArchive>
 */
class ServerBackupArchiveFactory extends Factory
{
    public function definition(): array
    {
        return [
            'server_backup_id' => ServerBackup::factory(),
            'filename' => 'backup-'.fake()->date('Y-m-d').'-'.fake()->bothify('??##').'.tar.gz',
            'type' => 'mysql',
            'size_bytes' => fake()->numberBetween(10000, 500000000),
            'storage_path' => '/var/backups/'.fake()->bothify('??##').'.tar.gz',
            'storage' => 'local',
            'status' => 'success',
            'exit_code' => 0,
            'output' => 'Backup completed successfully.',
            'error_output' => null,
            'completed_at' => now(),
        ];
    }

    public function failed(): static
    {
        return $this->state(fn (array $attributes): array => [
            'status' => 'failed',
            'exit_code' => 1,
            'output' => null,
            'error_output' => 'mysqldump: Error 1045: Access denied',
        ]);
    }

    public function pending(): static
    {
        return $this->state(fn (array $attributes): array => [
            'status' => 'pending',
            'exit_code' => null,
            'output' => null,
            'error_output' => null,
            'completed_at' => null,
        ]);
    }
}
