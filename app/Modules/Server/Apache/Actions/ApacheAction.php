<?php

namespace App\Modules\Server\Apache\Actions;

use App\Models\Server;
use App\Services\ExecutionEngine\PushAgentEngine;

class ApacheAction
{
    private const HOST_REGEX = '/^(?=.{1,253}$)[A-Za-z0-9](?:[A-Za-z0-9\-]{0,61}[A-Za-z0-9])?(?:\.[A-Za-z0-9](?:[A-Za-z0-9\-]{0,61}[A-Za-z0-9])?)*$/';

    public function __construct(
        private PushAgentEngine $engine,
    ) {}

    public function status(Server $server): array
    {
        $result = $this->engine->action($server, 'apache.status', []);

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

    public function install(Server $server): array
    {
        $result = $this->engine->action($server, 'apache.install', []);

        return [
            'success' => $result->success,
            'message' => $result->success ? 'Apache wurde installiert.' : $result->stderr,
        ];
    }

    public function deinstall(Server $server): array
    {
        $result = $this->engine->action($server, 'apache.deinstall', []);

        return [
            'success' => $result->success,
            'message' => $result->success ? 'Apache wurde deinstalliert.' : $result->stderr,
        ];
    }

    public function start(Server $server): array
    {
        return $this->serviceAction($server, 'start');
    }

    public function stop(Server $server): array
    {
        return $this->serviceAction($server, 'stop');
    }

    public function restart(Server $server): array
    {
        return $this->serviceAction($server, 'restart');
    }

    public function reload(Server $server): array
    {
        return $this->serviceAction($server, 'reload');
    }

    private function serviceAction(Server $server, string $action): array
    {
        $result = $this->engine->action($server, "apache.{$action}", []);

        $labels = ['start' => 'gestartet', 'stop' => 'gestoppt', 'restart' => 'neugestartet', 'reload' => 'neugeladen'];

        return [
            'success' => $result->success,
            'message' => $result->success ? "Apache wurde {$labels[$action]}." : $result->stderr,
        ];
    }

    public function configtest(Server $server): array
    {
        $result = $this->engine->action($server, 'apache.configtest', []);

        return [
            'success' => $result->success,
            'output' => $result->stdout ?: $result->stderr,
        ];
    }

    public function sites(Server $server): array
    {
        $result = $this->engine->action($server, 'apache.sites', []);

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

    public function siteConfig(Server $server, string $site): array
    {
        $path = $this->sitePath($site);
        if ($path === null) {
            return ['success' => false, 'message' => 'Ungültiger Site-Name.'];
        }

        $result = $this->engine->action($server, 'apache.site_config', ['site' => basename($path)]);

        return [
            'success' => $result->success,
            'content' => $result->stdout,
            'message' => $result->success ? '' : $result->stderr,
        ];
    }

    public function saveSiteConfig(Server $server, string $site, string $content): array
    {
        if (trim($content) === '') {
            return ['success' => false, 'message' => 'Die Apache-Konfiguration darf nicht leer sein.'];
        }

        $path = $this->sitePath($site);
        if ($path === null) {
            return ['success' => false, 'message' => 'Ungültiger Site-Name.'];
        }

        $result = $this->engine->action($server, 'apache.save_site_config', [
            'site' => basename($path),
            'content' => $content,
        ]);

        return [
            'success' => $result->success,
            'message' => $result->success ? 'Konfiguration gespeichert.' : $result->stderr,
        ];
    }

    public function enableSite(Server $server, string $site): array
    {
        $siteName = $this->siteName($site);
        if ($siteName === null) {
            return ['success' => false, 'message' => 'Ungültiger Site-Name.'];
        }

        $result = $this->engine->action($server, 'apache.enable_site', ['site' => $siteName]);

        return [
            'success' => $result->success,
            'message' => $result->success ? "Site {$siteName} wurde aktiviert." : $result->stderr,
        ];
    }

    public function disableSite(Server $server, string $site): array
    {
        $siteName = $this->siteName($site);
        if ($siteName === null) {
            return ['success' => false, 'message' => 'Ungültiger Site-Name.'];
        }

        $result = $this->engine->action($server, 'apache.disable_site', ['site' => $siteName]);

        return [
            'success' => $result->success,
            'message' => $result->success ? "Site {$siteName} wurde deaktiviert." : $result->stderr,
        ];
    }

    public function deleteSite(Server $server, string $site, bool $deleteProject = false, string $documentRoot = ''): array
    {
        $path = $this->sitePath($site);
        if ($path === null) {
            return ['success' => false, 'message' => 'Ungültiger Site-Name.'];
        }

        $payload = [
            'site' => basename($path),
            'delete_project' => $deleteProject,
            'document_root' => $documentRoot,
        ];

        if ($deleteProject && $documentRoot !== '') {
            $root = $this->projectRoot($documentRoot);
            if ($root === null) {
                return ['success' => false, 'message' => 'Projektordner kann nur unter /var/www automatisch gelöscht werden.'];
            }
        }

        $result = $this->engine->action($server, 'apache.delete_site', $payload);

        return [
            'success' => $result->success,
            'message' => $result->success ? "Site {$site} wurde gelöscht." : $result->stderr,
        ];
    }

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

        $siteName = "{$domain}.conf";
        $path = "/etc/apache2/sites-available/{$siteName}";
        $config = $this->buildVhostConfig($domain, $documentRoot, $serverAlias, $useSsl);
        $result = $this->engine->action($server, 'apache.create_vhost', [
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

    public function modules(Server $server): array
    {
        $result = $this->engine->action($server, 'apache.modules', []);

        if (! $result->success) {
            return ['success' => false, 'modules' => [], 'message' => $result->stderr];
        }

        $modules = [];
        foreach (explode("\n", $result->stdout) as $line) {
            $line = trim($line);
            if ($line === '') {
                continue;
            }
            $parts = explode("\t", $line, 2);
            if (count($parts) < 2) {
                continue;
            }
            $modules[] = [
                'name' => $parts[0],
                'enabled' => $parts[1],
            ];
        }

        return ['success' => true, 'modules' => $modules];
    }

    public function enableModule(Server $server, string $module): array
    {
        if (! $this->validApacheToken($module)) {
            return ['success' => false, 'message' => 'Ungültiger Modulname.'];
        }

        $result = $this->engine->action($server, 'apache.enable_module', ['module' => $module]);

        return [
            'success' => $result->success,
            'message' => $result->success ? "Modul {$module} wurde aktiviert." : $result->stderr,
        ];
    }

    public function disableModule(Server $server, string $module): array
    {
        if (! $this->validApacheToken($module)) {
            return ['success' => false, 'message' => 'Ungültiger Modulname.'];
        }

        $result = $this->engine->action($server, 'apache.disable_module', ['module' => $module]);

        return [
            'success' => $result->success,
            'message' => $result->success ? "Modul {$module} wurde deaktiviert." : $result->stderr,
        ];
    }

    public function installCertbot(Server $server): array
    {
        $result = $this->engine->action($server, 'apache.install_certbot', []);

        return [
            'success' => $result->success,
            'message' => $result->success ? 'Certbot wurde installiert.' : $result->stderr,
        ];
    }

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

        $result = $this->engine->action($server, 'apache.obtain_ssl', [
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

    private function sitePath(string $site): ?string
    {
        $name = $this->siteName($site);
        if ($name === null) {
            return null;
        }

        return '/etc/apache2/sites-available/'.$name;
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

        return $this->validApacheToken($name) ? $name : null;
    }

    private function validApacheToken(string $value): bool
    {
        return preg_match('/^[A-Za-z0-9._-]+$/', $value) === 1;
    }

    private function projectRoot(string $documentRoot): ?string
    {
        $root = rtrim($documentRoot, '/');

        if (! str_starts_with($root, '/var/www/')) {
            return null;
        }

        $basename = basename($root);
        if (strtolower($basename) === 'public') {
            $root = dirname($root);
        }

        if (in_array($root, ['/var/www', '/var/www/html'], true)) {
            return null;
        }

        return $root;
    }

    private function buildVhostConfig(string $domain, string $documentRoot, string $serverAlias, bool $useSsl): string
    {
        $aliasLine = $serverAlias !== '' ? "    ServerAlias {$serverAlias}\n" : '';
        $errorLog = "\${APACHE_LOG_DIR}/{$domain}_error.log";
        $accessLog = "\${APACHE_LOG_DIR}/{$domain}_access.log";

        if ($useSsl) {
            return "<VirtualHost *:80>\n"
                ."    ServerName {$domain}\n"
                .$aliasLine
                ."    RewriteEngine On\n"
                ."    RewriteRule ^ https://%{HTTP_HOST}\${REQUEST_URI} [R=301,L]\n"
                ."</VirtualHost>\n\n"
                ."<VirtualHost *:443>\n"
                ."    ServerName {$domain}\n"
                .$aliasLine
                ."    DocumentRoot {$documentRoot}\n\n"
                ."    <Directory {$documentRoot}>\n"
                ."        AllowOverride All\n"
                ."        Require all granted\n"
                ."    </Directory>\n\n"
                ."    ErrorLog {$errorLog}\n"
                ."    CustomLog {$accessLog} combined\n\n"
                ."    SSLEngine On\n"
                ."    SSLCertificateFile /etc/letsencrypt/live/{$domain}/fullchain.pem\n"
                ."    SSLCertificateKeyFile /etc/letsencrypt/live/{$domain}/privkey.pem\n"
                ."</VirtualHost>\n";
        }

        return "<VirtualHost *:80>\n"
            ."    ServerName {$domain}\n"
            .$aliasLine
            ."    DocumentRoot {$documentRoot}\n\n"
            ."    <Directory {$documentRoot}>\n"
            ."        AllowOverride All\n"
            ."        Require all granted\n"
            ."    </Directory>\n\n"
            ."    ErrorLog {$errorLog}\n"
            ."    CustomLog {$accessLog} combined\n"
            ."</VirtualHost>\n";
    }
}
