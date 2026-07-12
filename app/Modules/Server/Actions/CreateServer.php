<?php

namespace App\Modules\Server\Actions;

use App\Models\Server;
use App\Modules\Server\Requests\StoreServerRequest;
use App\Services\SshService;
use Illuminate\Support\Str;

class CreateServer
{
    public function __construct(
        private SshService $ssh,
    ) {}

    public function handle(StoreServerRequest $request): Server
    {
        $data = $request->validated();
        $data['use_sudo'] = $request->boolean('use_sudo');
        $data['ssh_compression'] = $request->boolean('ssh_compression');
        $data['execution_driver'] = 'ssh';

        $server = $request->user()->servers()->create($data);

        if ($request->boolean('install_agent')) {
            $this->installAgent($request, $server);
        }

        return $server;
    }

    private function installAgent(StoreServerRequest $request, Server $server): void
    {
        $token = 'smz_'.Str::random(64);
        $appUrl = $request->getSchemeAndHttpHost();
        $port = config('agent.push_port', 9300);

        $server->forceFill([
            'agent_enabled' => true,
            'agent_token' => $token,
            'agent_status' => 'disconnected',
            'agent_transport' => 'push',
            'agent_port' => $port,
        ])->save();

        $downloadUrl = rtrim($appUrl, '/').'/agent/download';
        $binaryPath = '/usr/local/bin/smuze-agent';
        $tmpPath = $binaryPath.'.tmp';

        $script = implode(' && ', [
            '(command -v curl >/dev/null 2>&1 || (apt-get update -qq && apt-get install -y -qq curl))',
            'curl -fsSL '.escapeshellarg($downloadUrl).' -o '.$tmpPath,
            'chmod +x '.$tmpPath,
            'mv '.$tmpPath.' '.$binaryPath,
            $binaryPath.' install'
                .' --app-url '.escapeshellarg($appUrl)
                .' --server-id '.escapeshellarg((string) $server->id)
                .' --token '.escapeshellarg($token)
                .' --port '.escapeshellarg((string) $port),
            'systemctl daemon-reload',
            'systemctl restart smuze-agent',
        ]);

        $result = $this->ssh->execute($server, $script, timeout: 120, useSudo: true);

        if ($result->success) {
            $server->forceFill([
                'execution_driver' => 'agent',
            ])->save();
        }
    }
}
