<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Symfony\Component\Process\Process;

class AgentReleaseCommand extends Command
{
    protected $signature = 'agent:release
        {--release-version= : The new agent version}
        {--binary= : Path to the compiled agent binary}
        {--build : Build the Go agent before registering the release}';

    protected $description = 'Register a new agent release version';

    public function handle(): int
    {
        $version = $this->option('release-version');

        if ($version === null) {
            $this->error('The --release-version option is required.');

            return self::FAILURE;
        }

        $dir = storage_path('app/agent');
        $targetPath = $dir.'/smuze-agent';
        $versionPath = $dir.'/version.json';
        $binary = $this->option('binary');

        if ($this->option('build')) {
            $binary = storage_path('app/agent/smuze-agent.build');

            if (! is_dir($dir)) {
                mkdir($dir, 0755, true);
            }

            $process = new Process([
                'go',
                'build',
                '-ldflags',
                '-X main.version='.$version,
                '-o',
                $binary,
                '.',
            ], base_path('scripts/smuze-agent'));
            $process->run();

            if (! $process->isSuccessful()) {
                $this->error($process->getErrorOutput() ?: $process->getOutput());

                return self::FAILURE;
            }
        }

        if ($binary) {
            if (! file_exists($binary)) {
                $this->error("Binary not found: {$binary}");

                return self::FAILURE;
            }

            copy($binary, $targetPath);
            chmod($targetPath, 0755);
        }

        $checksum = file_exists($targetPath) ? hash_file('sha256', $targetPath) : '';

        $versionData = [
            'version' => $version,
            'checksum' => $checksum,
        ];

        file_put_contents($versionPath, json_encode($versionData, JSON_PRETTY_PRINT)."\n");

        $this->info("Agent release {$version} registered.");
        $this->line("Checksum: {$checksum}");

        return self::SUCCESS;
    }
}
