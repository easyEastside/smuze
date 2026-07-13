<?php

namespace App\Modules\Server\Nginx\Actions;

use App\Models\Server;
use App\Services\ExecutionEngine\PushAgentEngine;

class NginxAction
{
    private const HOST_REGEX = '/^(?=.{1,253}$)[A-Za-z0-9](?:[A-Za-z0-9\-]{0,61}[A-Za-z0-9])?(?:\.[A-Za-z0-9](?:[A-Za-z0-9\-]{0,61}[A-Za-z0-9])?)*$/';

    public function __construct(
        private PushAgentEngine $engine,
    ) {}

    /** @return array<string, mixed> */
    public function status(Server $server): array
    {
        $result = $this->engine->action($server, 'nginx.status', []);

        if (! $result->success) {
            return ['success' => false, 'error' => $result->stderr];
        }

        $data = [];
        foreach (explode("\n", $result->stdout) as $line) {
            if (str_contains($line, '=')) {
                [$key, $value] = explode('=', $line, 2);
                $data[trim($key)] = trim($value ?? '');
            }
        }

        $installed = isset($data['INSTALLED'])
            ? $data['INSTALLED'] === 'yes'
            : ($data['ACTIVE'] ?? '') !== 'unknown';

        return [
            'success' => true,
            'installed' => $installed,
            'active' => ($data['ACTIVE'] ?? '') === 'active',
            'version' => $data['VERSION'] ?? null,
        ];
    }

    /** @return array<string, mixed> */
    public function install(Server $server): array
    {
        $result = $this->engine->action($server, 'nginx.install', []);

        return [
            'success' => $result->success,
            'message' => $result->success ? 'Nginx wurde installiert.' : $result->stderr,
        ];
    }

    /** @return array<string, mixed> */
    public function deinstall(Server $server): array
    {
        $result = $this->engine->action($server, 'nginx.deinstall', []);

        return [
            'success' => $result->success,
            'message' => $result->success ? 'Nginx wurde deinstalliert.' : $result->stderr,
        ];
    }

    /** @return array<string, mixed> */
    public function start(Server $server): array
    {
        return $this->serviceAction($server, 'start');
    }

    /** @return array<string, mixed> */
    public function stop(Server $server): array
    {
        return $this->serviceAction($server, 'stop');
    }

    /** @return array<string, mixed> */
    public function restart(Server $server): array
    {
        return $this->serviceAction($server, 'restart');
    }

    /** @return array<string, mixed> */
    public function reload(Server $server): array
    {
        return $this->serviceAction($server, 'reload');
    }

    /** @return array<string, mixed> */
    private function serviceAction(Server $server, string $action): array
    {
        $result = $this->engine->action($server, "nginx.{$action}", []);
        $labels = ['start' => 'gestartet', 'stop' => 'gestoppt', 'restart' => 'neugestartet', 'reload' => 'neugeladen'];

        return [
            'success' => $result->success,
            'message' => $result->success ? "Nginx wurde {$labels[$action]}." : $result->stderr,
        ];
    }

    /** @return array<string, mixed> */
    public function configtest(Server $server): array
    {
        $result = $this->engine->action($server, 'nginx.configtest', []);

        return [
            'success' => $result->success,
            'output' => $result->stdout ?: $result->stderr,
        ];
    }

    /** @return array<string, mixed> */
    public function sites(Server $server): array
    {
        $result = $this->engine->action($server, 'nginx.sites', []);

        if (! $result->success) {
            return ['success' => false, 'sites' => []];
        }

        $sites = [];
        foreach (explode("\n", $result->stdout) as $line) {
            $parts = explode("\t", $line, 4);
            if (count($parts) < 4) {
                continue;
            }
            $sites[] = [
                'name' => $parts[0],
                'enabled' => $parts[1],
                'server_name' => $parts[2] ?: '-',
                'document_root' => $parts[3] ?: '-',
            ];
        }

        return ['success' => true, 'sites' => $sites];
    }

    /** @return array<string, mixed> */
    public function siteConfig(Server $server, string $site): array
    {
        $siteName = $this->siteName($site);
        if ($siteName === null) {
            return ['success' => false, 'message' => 'Ungültiger Site-Name.'];
        }

        $result = $this->engine->action($server, 'nginx.site_config', ['site' => $siteName]);

        return [
            'success' => $result->success,
            'content' => $result->stdout,
            'message' => $result->success ? '' : $result->stderr,
        ];
    }

    /** @return array<string, mixed> */
    public function saveSiteConfig(Server $server, string $site, string $content): array
    {
        if (trim($content) === '') {
            return ['success' => false, 'message' => 'Die Nginx-Konfiguration darf nicht leer sein.'];
        }

        $siteName = $this->siteName($site);
        if ($siteName === null) {
            return ['success' => false, 'message' => 'Ungültiger Site-Name.'];
        }

        $result = $this->engine->action($server, 'nginx.save_site_config', [
            'site' => $siteName,
            'content' => $content,
        ]);

        return [
            'success' => $result->success,
            'message' => $result->success ? 'Konfiguration gespeichert.' : $result->stderr,
        ];
    }

    /** @return array<string, mixed> */
    public function enableSite(Server $server, string $site): array
    {
        $siteName = $this->siteName($site);
        if ($siteName === null) {
            return ['success' => false, 'message' => 'Ungültiger Site-Name.'];
        }

        $result = $this->engine->action($server, 'nginx.enable_site', ['site' => $siteName]);

        return [
            'success' => $result->success,
            'message' => $result->success ? "Site {$siteName} wurde aktiviert." : $result->stderr,
        ];
    }

    /** @return array<string, mixed> */
    public function disableSite(Server $server, string $site): array
    {
        $siteName = $this->siteName($site);
        if ($siteName === null) {
            return ['success' => false, 'message' => 'Ungültiger Site-Name.'];
        }

        $result = $this->engine->action($server, 'nginx.disable_site', ['site' => $siteName]);

        return [
            'success' => $result->success,
            'message' => $result->success ? "Site {$siteName} wurde deaktiviert." : $result->stderr,
        ];
    }

    /** @return array<string, mixed> */
    public function deleteSite(Server $server, string $site, bool $deleteProject = false, string $documentRoot = ''): array
    {
        $siteName = $this->siteName($site);
        if ($siteName === null) {
            return ['success' => false, 'message' => 'Ungültiger Site-Name.'];
        }

        if ($deleteProject && $documentRoot !== '' && $this->projectRoot($documentRoot) === null) {
            return ['success' => false, 'message' => 'Projektordner kann nur unter /var/www automatisch gelöscht werden.'];
        }

        $result = $this->engine->action($server, 'nginx.delete_site', [
            'site' => $siteName,
            'delete_project' => $deleteProject,
            'document_root' => $documentRoot,
        ]);

        return [
            'success' => $result->success,
            'message' => $result->success ? "Site {$siteName} wurde gelöscht." : $result->stderr,
        ];
    }

    /** @return array<string, mixed> */
    public function createVhost(Server $server, string $domain, string $documentRoot, string $serverAlias = '', bool $useSsl = false, string $email = ''): array
    {
        $domain = trim($domain);
        $documentRoot = trim($documentRoot);
        $serverAlias = trim($serverAlias);
        $email = trim($email);

        $validation = $this->validateVhostInput($domain, $documentRoot, $serverAlias);
        if (! $validation['valid']) {
            return ['success' => false, 'message' => $validation['error']];
        }

        if ($useSsl && $email === '') {
            return ['success' => false, 'message' => 'E-Mail-Adresse für Let\'s Encrypt ist erforderlich.'];
        }

        $config = $this->buildVhostConfig($domain, $documentRoot, $serverAlias);
        $result = $this->engine->action($server, 'nginx.create_vhost', [
            'domain' => $domain,
            'document_root' => $documentRoot,
            'config' => $config,
        ]);

        if (! $result->success) {
            return ['success' => false, 'message' => $result->stderr];
        }

        if ($useSsl) {
            return $this->obtainSsl($server, $domain, $email);
        }

        return ['success' => true, 'message' => "VHost für {$domain} wurde erstellt."];
    }

    /** @return array<string, mixed> */
    public function installCertbot(Server $server): array
    {
        $result = $this->engine->action($server, 'nginx.install_certbot', []);

        return [
            'success' => $result->success,
            'message' => $result->success ? 'Certbot wurde installiert.' : $result->stderr,
        ];
    }

    /** @return array<string, mixed> */
    public function obtainSsl(Server $server, string $domain, string $email): array
    {
        $domain = trim($domain);
        $email = trim($email);

        if (! preg_match(self::HOST_REGEX, $domain)) {
            return ['success' => false, 'message' => 'Domain darf nur gültige DNS-Zeichen enthalten, z. B. example.com.'];
        }

        if (! filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return ['success' => false, 'message' => 'Bitte eine gültige E-Mail-Adresse angeben.'];
        }

        $result = $this->engine->action($server, 'nginx.obtain_ssl', [
            'domain' => $domain,
            'email' => $email,
        ]);

        return [
            'success' => $result->success,
            'message' => $result->success ? "SSL-Zertifikat für {$domain} wurde ausgestellt." : $result->stderr,
        ];
    }

    /** @return array{valid: bool, error: string} */
    private function validateVhostInput(string $domain, string $documentRoot, string $serverAlias): array
    {
        if ($domain === '' || $documentRoot === '') {
            return ['valid' => false, 'error' => 'Domain und DocumentRoot sind erforderlich.'];
        }

        if (! preg_match(self::HOST_REGEX, $domain)) {
            return ['valid' => false, 'error' => 'Domain darf nur gültige DNS-Zeichen enthalten, z. B. example.com.'];
        }

        if (! str_starts_with($documentRoot, '/')) {
            return ['valid' => false, 'error' => 'DocumentRoot muss ein absoluter Pfad sein, z. B. /var/www/example.'];
        }

        if (str_contains($documentRoot, "\r") || str_contains($documentRoot, "\n") || str_contains($documentRoot, "\t") || str_contains($documentRoot, "\0") || str_contains($documentRoot, '/..')) {
            return ['valid' => false, 'error' => 'DocumentRoot darf keine Steuerzeichen oder \'..\' enthalten.'];
        }

        if ($serverAlias !== '') {
            $aliases = preg_split('/\s+/', $serverAlias);
            foreach ($aliases as $alias) {
                if (! preg_match(self::HOST_REGEX, $alias)) {
                    return ['valid' => false, 'error' => "ServerAlias ist ungültig: {$alias}"];
                }
            }
        }

        return ['valid' => true, 'error' => ''];
    }

    private function siteName(string $site): ?string
    {
        $name = trim($site);
        if ($name === '' || str_contains($name, '/') || str_contains($name, '..')) {
            return null;
        }

        if (! str_ends_with($name, '.conf')) {
            $name .= '.conf';
        }

        return preg_match('/^[A-Za-z0-9._-]+$/', $name) === 1 ? $name : null;
    }

    private function projectRoot(string $documentRoot): ?string
    {
        $root = rtrim($documentRoot, '/');

        if (! str_starts_with($root, '/var/www/')) {
            return null;
        }

        if (strtolower(basename($root)) === 'public') {
            $root = dirname($root);
        }

        if (in_array($root, ['/var/www', '/var/www/html'], true)) {
            return null;
        }

        return $root;
    }

    private function buildVhostConfig(string $domain, string $documentRoot, string $serverAlias): string
    {
        $aliases = trim($domain.' '.$serverAlias);

        return "server {\n"
            ."    listen 80;\n"
            ."    listen [::]:80;\n\n"
            ."    server_name {$aliases};\n"
            ."    root {$documentRoot};\n"
            ."    index index.php index.html index.htm;\n\n"
            ."    access_log /var/log/nginx/{$domain}.access.log;\n"
            ."    error_log /var/log/nginx/{$domain}.error.log;\n\n"
            ."    location / {\n"
            ."        try_files \$uri \$uri/ /index.php?\$query_string;\n"
            ."    }\n\n"
            ."    location ~ \\.php$ {\n"
            ."        include snippets/fastcgi-php.conf;\n"
            ."        fastcgi_pass unix:/run/php/php8.5-fpm.sock;\n"
            ."    }\n\n"
            ."    location ~ /\\.ht {\n"
            ."        deny all;\n"
            ."    }\n"
            ."}\n";
    }
}
