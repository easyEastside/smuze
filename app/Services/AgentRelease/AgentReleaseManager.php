<?php

namespace App\Services\AgentRelease;

use Symfony\Component\Process\Process;

class AgentReleaseManager
{
    private string $storageDir;

    private string $binaryPath;

    private string $versionPath;

    public function __construct()
    {
        $this->storageDir = storage_path('app/agent');
        $this->binaryPath = $this->storageDir.'/smuze-agent';
        $this->versionPath = $this->storageDir.'/version.json';
    }

    public function build(string $version): array
    {
        if (! is_dir($this->storageDir)) {
            mkdir($this->storageDir, 0755, true);
        }

        $homeDir = $this->storageDir.'/home';
        $goCacheDir = $this->storageDir.'/go-build-cache';
        $goModCacheDir = $this->storageDir.'/go-mod-cache';

        foreach ([$homeDir, $goCacheDir, $goModCacheDir] as $dir) {
            if (! is_dir($dir)) {
                mkdir($dir, 0755, true);
            }
        }

        $buildBinary = $this->storageDir.'/smuze-agent.build';

        $process = new Process([
            'go',
            'build',
            '-ldflags',
            '-X main.version='.$version,
            '-o',
            $buildBinary,
            '.',
        ], base_path('scripts/smuze-agent'));

        $process->setEnv([
            'HOME' => $homeDir,
            'GOCACHE' => $goCacheDir,
            'GOMODCACHE' => $goModCacheDir,
            'PATH' => $_SERVER['PATH'] ?? getenv('PATH'),
        ]);

        $process->run();

        if (! $process->isSuccessful()) {
            return [
                'success' => false,
                'message' => $process->getErrorOutput() ?: $process->getOutput(),
            ];
        }

        copy($buildBinary, $this->binaryPath);
        chmod($this->binaryPath, 0755);

        $checksum = hash_file('sha256', $this->binaryPath);

        $release = [
            'version' => $version,
            'checksum' => $checksum,
            'built_at' => now()->toIso8601String(),
        ];

        file_put_contents($this->versionPath, json_encode($release, JSON_PRETTY_PRINT)."\n");

        return [
            'success' => true,
            'version' => $version,
            'checksum' => $checksum,
            'path' => $this->binaryPath,
        ];
    }

    public function currentRelease(): array
    {
        if (! file_exists($this->versionPath)) {
            return [];
        }

        $release = json_decode(file_get_contents($this->versionPath), true);

        return is_array($release) ? $release : [];
    }

    public function binaryExists(): bool
    {
        return file_exists($this->binaryPath);
    }

    public function binaryPath(): string
    {
        return $this->binaryPath;
    }
}
