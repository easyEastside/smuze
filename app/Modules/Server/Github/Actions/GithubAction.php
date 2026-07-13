<?php

namespace App\Modules\Server\Github\Actions;

use App\Models\Server;
use App\Services\ExecutionEngine\PushAgentEngine;

class GithubAction
{
    private const SAFE_NAME_REGEX = '/^[A-Za-z0-9._-]+$/';

    public function __construct(
        private PushAgentEngine $engine,
    ) {}

    public function defaultTargetName(string $repoUrl): string
    {
        $path = parse_url(trim($repoUrl), PHP_URL_PATH);
        $name = $path ? basename($path) : '';
        if (str_ends_with($name, '.git')) {
            $name = substr($name, 0, -4);
        }

        return trim($name);
    }

    /** @return array{valid: bool, error: string} */
    public function validateRepoUrl(string $repoUrl): array
    {
        $repoUrl = trim($repoUrl);

        if ($repoUrl === '') {
            return ['valid' => false, 'error' => 'Git-URL ist erforderlich.'];
        }

        if (preg_match('/[\r\n\t]/', $repoUrl)) {
            return ['valid' => false, 'error' => 'Git-URL darf keine Steuerzeichen enthalten.'];
        }

        $parsed = parse_url($repoUrl);

        if (! isset($parsed['scheme']) || $parsed['scheme'] !== 'https') {
            return ['valid' => false, 'error' => 'Bitte eine HTTPS-GitHub-URL verwenden.'];
        }

        $host = strtolower($parsed['host'] ?? '');
        if (! in_array($host, ['github.com', 'www.github.com'], true)) {
            return ['valid' => false, 'error' => 'Bitte eine URL von github.com verwenden.'];
        }

        $path = $parsed['path'] ?? '';
        $parts = array_values(array_filter(explode('/', $path), fn (string $p): bool => $p !== ''));
        if (count($parts) < 2) {
            return ['valid' => false, 'error' => 'GitHub-URL muss Owner und Repository enthalten.'];
        }

        if (isset($parsed['query']) || isset($parsed['fragment'])) {
            return ['valid' => false, 'error' => 'GitHub-URL darf keine Query-Parameter oder Fragmente enthalten.'];
        }

        return ['valid' => true, 'error' => ''];
    }

    /** @return array{valid: bool, error: string} */
    public function validateTargetName(string $targetName): array
    {
        $targetName = trim($targetName);

        if ($targetName === '') {
            return ['valid' => false, 'error' => 'Zielordner ist erforderlich.'];
        }

        if (in_array($targetName, ['.', '..'], true) || ! preg_match(self::SAFE_NAME_REGEX, $targetName)) {
            return ['valid' => false, 'error' => 'Zielordner darf nur Buchstaben, Zahlen, Punkt, Unterstrich und Bindestrich enthalten.'];
        }

        return ['valid' => true, 'error' => ''];
    }

    /** @return array{success: bool, message: string} */
    public function deploy(Server $server, string $repoUrl, string $targetName): array
    {
        $repoUrl = trim($repoUrl);
        $targetName = trim($targetName);

        $validation = $this->validateRepoUrl($repoUrl);
        if (! $validation['valid']) {
            return ['success' => false, 'message' => $validation['error']];
        }

        $validation = $this->validateTargetName($targetName);
        if (! $validation['valid']) {
            return ['success' => false, 'message' => $validation['error']];
        }

        $result = $this->engine->action($server, 'github.deploy', [
            'repo_url' => $repoUrl,
            'target_name' => $targetName,
        ]);

        $message = $result->stdout ?: $result->stderr;

        return [
            'success' => $result->success,
            'message' => $message,
        ];
    }
}
