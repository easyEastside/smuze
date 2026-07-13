<?php

namespace App\Console\Commands;

use App\Services\AgentRelease\AgentReleaseManager;
use Illuminate\Console\Command;

class AgentReleaseCommand extends Command
{
    protected $signature = 'agent:release
        {--release-version= : The new agent version}
        {--binary= : Path to a pre-compiled agent binary (overrides --build)}
        {--build : Build the Go agent before registering the release}';

    protected $description = 'Register a new agent release version';

    public function handle(AgentReleaseManager $manager): int
    {
        $version = $this->option('release-version');

        if ($version === null) {
            $this->error('The --release-version option is required.');

            return self::FAILURE;
        }

        $binary = $this->option('binary');

        if ($binary !== null) {
            if (! file_exists($binary)) {
                $this->error("Binary not found: {$binary}");

                return self::FAILURE;
            }

            copy($binary, $manager->binaryPath());
            chmod($manager->binaryPath(), 0755);

            $checksum = hash_file('sha256', $manager->binaryPath());

            $release = [
                'version' => $version,
                'checksum' => $checksum,
                'built_at' => now()->toIso8601String(),
            ];

            file_put_contents(storage_path('app/agent/version.json'), json_encode($release, JSON_PRETTY_PRINT)."\n");

            $this->info("Agent release {$version} registered.");
            $this->line("Checksum: {$checksum}");

            return self::SUCCESS;
        }

        if ($this->option('build')) {
            $this->info('Building agent...');
            $result = $manager->build($version);

            if (! $result['success']) {
                $this->error($result['message']);

                return self::FAILURE;
            }

            $this->info("Agent release {$version} built and registered.");
            $this->line("Checksum: {$result['checksum']}");

            return self::SUCCESS;
        }

        $this->error('Either --binary or --build must be provided.');

        return self::FAILURE;
    }
}
