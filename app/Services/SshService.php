<?php

namespace App\Services;

use App\Models\Server;
use Spatie\Ssh\Ssh;

class SshService
{
    public function execute(Server $server, string $command, int $timeout = 30, bool $useSudo = true): SshResult
    {
        try {
            $ssh = Ssh::create($server->username, $server->host, $server->port)
                ->setTimeout($timeout)
                ->addExtraOption('-o ConnectTimeout=10')
                ->addExtraOption('-o StrictHostKeyChecking=no')
                ->addExtraOption('-o UserKnownHostsFile=/dev/null');

            if ($server->auth_type === 'key' || $server->key_content) {
                $keyPath = $this->resolveKeyPath($server);
                $ssh->usePrivateKey($keyPath);
            } else {
                $ssh->usePassword($server->credentials);
            }

            $process = $ssh->execute($command);

            $exitCode = $process->getExitCode();

            return new SshResult(
                stdout: trim($process->getOutput() ?? ''),
                stderr: trim($process->getErrorOutput() ?? ''),
                exitCode: $exitCode ?? -1,
                success: $exitCode === 0,
            );
        } catch (\Exception $e) {
            return new SshResult(
                stdout: '',
                stderr: $e->getMessage(),
                exitCode: -1,
                success: false,
            );
        }
    }

    private function resolveKeyPath(Server $server): string
    {
        $content = $server->key_content ?? $server->credentials;

        $path = storage_path('ssh-sockets/keys/'.$server->id.'_key');

        if (! is_dir(dirname($path))) {
            mkdir(dirname($path), 0700, true);
        }

        file_put_contents($path, $content);
        chmod($path, 0600);

        return $path;
    }
}
