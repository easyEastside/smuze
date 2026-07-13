<?php

namespace App\Modules\Server\Deployments\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class StoreDeploymentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:120', 'not_regex:/[\r\n]/'],
            'repo_url' => ['required', 'string', 'max:500', 'not_regex:/[\r\n\t]/', function (string $attribute, mixed $value, callable $fail): void {
                if (! $this->isValidGithubUrl((string) $value)) {
                    $fail('Bitte eine HTTPS-GitHub-URL mit Owner und Repository angeben.');
                }
            }],
            'target_path' => ['required', 'string', 'max:255', function (string $attribute, mixed $value, callable $fail): void {
                if (! $this->isValidTargetPath((string) $value)) {
                    $fail('Zielpfad muss absolut sein und unter /var/www liegen.');
                }
            }],
            'domain' => ['nullable', 'string', 'max:253', function (string $attribute, mixed $value, callable $fail): void {
                if ($value !== null && trim((string) $value) !== '' && ! $this->isValidDomain((string) $value)) {
                    $fail('Domain darf nur gültige DNS-Zeichen enthalten, z. B. example.com.');
                }
            }],
            'webserver' => ['required', Rule::in(['none', 'apache', 'nginx'])],
            'php_version' => ['required', Rule::in(['8.4', '8.5'])],
            'install_node' => ['nullable', Rule::in(['1', '0', 1, 0, true, false])],
            'run_build' => ['nullable', Rule::in(['1', '0', 1, 0, true, false])],
            'run_migrations' => ['nullable', Rule::in(['1', '0', 1, 0, true, false])],
            'write_env' => ['nullable', Rule::in(['1', '0', 1, 0, true, false])],
            'env' => ['nullable', 'string', 'max:20000', function (string $attribute, mixed $value, callable $fail): void {
                if ($value !== null && ! $this->isValidEnv((string) $value)) {
                    $fail('Env-Werte müssen als KEY=value ohne Steuerzeichen angegeben werden.');
                }
            }],
        ];
    }

    /** @return array<int, callable(Validator): void> */
    public function after(): array
    {
        return [
            function (Validator $validator): void {
                if ($this->input('webserver') !== 'none' && blank($this->input('domain'))) {
                    $validator->errors()->add('domain', 'Domain ist erforderlich, wenn ein VHost erstellt wird.');
                }
            },
        ];
    }

    private function isValidGithubUrl(string $repoUrl): bool
    {
        $repoUrl = trim($repoUrl);
        $parsed = parse_url($repoUrl);

        if (($parsed['scheme'] ?? '') !== 'https') {
            return false;
        }

        $host = strtolower((string) ($parsed['host'] ?? ''));
        if (! in_array($host, ['github.com', 'www.github.com'], true)) {
            return false;
        }

        if (isset($parsed['query']) || isset($parsed['fragment'])) {
            return false;
        }

        $parts = array_values(array_filter(explode('/', (string) ($parsed['path'] ?? ''))));

        return count($parts) >= 2;
    }

    private function isValidTargetPath(string $path): bool
    {
        $path = rtrim(trim($path), '/');

        return str_starts_with($path, '/var/www/')
            && ! str_contains($path, '/..')
            && $path !== '/var/www/html'
            && ! preg_match('/[\r\n\t\x00]/', $path);
    }

    private function isValidDomain(string $domain): bool
    {
        $domain = trim($domain);

        return strlen($domain) <= 253
            && preg_match('/^[A-Za-z0-9](?:[A-Za-z0-9\-]{0,61}[A-Za-z0-9])?(?:\.[A-Za-z0-9](?:[A-Za-z0-9\-]{0,61}[A-Za-z0-9])?)*$/', $domain) === 1;
    }

    private function isValidEnv(string $env): bool
    {
        foreach (preg_split('/\r\n|\r|\n/', $env) ?: [] as $line) {
            $line = trim($line);
            if ($line === '' || str_starts_with($line, '#')) {
                continue;
            }

            if (! str_contains($line, '=')) {
                return false;
            }

            [$key, $value] = explode('=', $line, 2);
            if (! preg_match('/^[A-Z_][A-Z0-9_]*$/', $key) || preg_match('/[\x00]/', $value)) {
                return false;
            }
        }

        return true;
    }
}
