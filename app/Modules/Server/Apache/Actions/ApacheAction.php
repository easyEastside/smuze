<?php

namespace App\Modules\Server\Apache\Actions;

use App\Models\Server;
use App\Services\SshService;

class ApacheAction
{
    private const HOST_REGEX = '/^(?=.{1,253}$)[A-Za-z0-9](?:[A-Za-z0-9\-]{0,61}[A-Za-z0-9])?(?:\.[A-Za-z0-9](?:[A-Za-z0-9\-]{0,61}[A-Za-z0-9])?)*$/';

    public function __construct(
        private SshService $ssh,
    ) {}

    public function status(Server $server): array
    {
        $script = 'printf "ACTIVE=%s\n" "$(systemctl is-active apache2 2>/dev/null || echo unknown)" && (apache2 -v 2>/dev/null | sed -n "1p" | sed "s/^/VERSION=/")';

        $result = $this->ssh->execute($server, $script, timeout: 15, useSudo: true);

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

        return [
            'success' => true,
            'installed' => ($data['ACTIVE'] ?? '') !== 'unknown',
            'active' => ($data['ACTIVE'] ?? '') === 'active',
            'version' => $data['VERSION'] ?? null,
        ];
    }

    public function install(Server $server): array
    {
        $result = $this->ssh->execute($server, 'DEBIAN_FRONTEND=noninteractive apt update && DEBIAN_FRONTEND=noninteractive apt install apache2 -y && systemctl enable --now apache2', timeout: 300, useSudo: true);

        return [
            'success' => $result->success,
            'message' => $result->success ? 'Apache wurde installiert.' : $result->stderr,
        ];
    }

    public function deinstall(Server $server): array
    {
        $command = 'systemctl stop apache2 2>/dev/null || true; DEBIAN_FRONTEND=noninteractive apt remove --purge apache2 apache2-bin apache2-data apache2-utils -y && apt autoremove -y && apt autoclean && rm -rf /etc/apache2';

        $result = $this->ssh->execute($server, $command, timeout: 180, useSudo: true);

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
        $result = $this->ssh->execute($server, "systemctl {$action} apache2", timeout: 30, useSudo: true);

        $labels = ['start' => 'gestartet', 'stop' => 'gestoppt', 'restart' => 'neugestartet', 'reload' => 'neugeladen'];

        return [
            'success' => $result->success,
            'message' => $result->success ? "Apache wurde {$labels[$action]}." : $result->stderr,
        ];
    }

    public function configtest(Server $server): array
    {
        $result = $this->ssh->execute($server, 'apache2ctl configtest 2>&1', timeout: 20, useSudo: true);

        return [
            'success' => $result->success,
            'output' => $result->stdout ?: $result->stderr,
        ];
    }

    public function sites(Server $server): array
    {
        $script = <<<'SCRIPT'
for f in /etc/apache2/sites-available/*.conf; do
    [ -e "$f" ] || continue
    name=$(basename "$f")
    if [ -e "/etc/apache2/sites-enabled/$name" ]; then
        enabled=yes
    else
        enabled=no
    fi
    server_name=$(awk 'tolower($1)=="servername" {print $2; exit}' "$f" 2>/dev/null || true)
    doc_root=$(awk 'tolower($1)=="documentroot" {print $2; exit}' "$f" 2>/dev/null || true)
    printf '%s\t%s\t%s\t%s\n' "$name" "$enabled" "$server_name" "$doc_root"
done
SCRIPT;

        $result = $this->ssh->execute($server, $script, timeout: 20, useSudo: true);

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

        $result = $this->ssh->execute($server, 'cat '.escapeshellarg($path), timeout: 15, useSudo: true);

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

        $backup = $path.'.smuzecp.bak';
        $encoded = base64_encode($content);
        $escaped = escapeshellarg($path);
        $escapedBackup = escapeshellarg($backup);

        $command = "cp {$escaped} {$escapedBackup} && printf '%s' ".escapeshellarg($encoded)." | base64 -d > {$escaped} && apache2ctl configtest || (mv {$escapedBackup} {$escaped}; false) && rm -f {$escapedBackup} && systemctl reload apache2";

        $result = $this->ssh->execute($server, $command, timeout: 30, useSudo: true);

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

        $escaped = escapeshellarg($siteName);

        $result = $this->ssh->execute($server, "a2ensite {$escaped} && systemctl reload apache2", timeout: 30, useSudo: true);

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

        $escaped = escapeshellarg($siteName);

        $result = $this->ssh->execute($server, "a2dissite {$escaped} && systemctl reload apache2", timeout: 30, useSudo: true);

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

        $siteFile = basename($path);
        $commands = [
            'a2dissite '.escapeshellarg($siteFile).' 2>/dev/null || true',
            'rm -f '.escapeshellarg($path),
        ];

        if ($deleteProject && $documentRoot !== '') {
            $root = $this->projectRoot($documentRoot);
            if ($root === null) {
                return ['success' => false, 'message' => 'Projektordner kann nur unter /var/www automatisch gelöscht werden.'];
            }
            $commands[] = 'rm -rf -- '.escapeshellarg($root);
        }

        $commands[] = 'apache2ctl configtest';
        $commands[] = 'systemctl reload apache2';

        $result = $this->ssh->execute($server, implode(' && ', $commands), timeout: 30, useSudo: true);

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
        $escaped = escapeshellarg($path);
        $encoded = base64_encode($config);

        $commands = [
            'mkdir -p '.escapeshellarg($documentRoot),
            "printf '%s' ".$encoded.' | base64 -d > '.$escaped,
            'apache2ctl configtest',
            'a2ensite '.escapeshellarg($siteName),
            'systemctl reload apache2',
        ];

        $result = $this->ssh->execute($server, implode(' && ', $commands), timeout: 45, useSudo: true);

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
        $script = <<<'SCRIPT'
for mod in /etc/apache2/mods-available/*.load; do
    [ -e "$mod" ] || continue
    name=$(basename "$mod" .load)
    if [ -e "/etc/apache2/mods-enabled/${name}.load" ]; then
        printf '%s\tenabled\n' "$name"
    else
        printf '%s\tdisabled\n' "$name"
    fi
done
SCRIPT;

        $result = $this->ssh->execute($server, $script, timeout: 20, useSudo: true);

        if (! $result->success) {
            return ['success' => false, 'modules' => []];
        }

        $modules = [];
        foreach (explode("\n", $result->stdout) as $line) {
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

        $escaped = escapeshellarg($module);
        $result = $this->ssh->execute($server, "a2enmod {$escaped} && systemctl reload apache2", timeout: 30, useSudo: true);

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

        $escaped = escapeshellarg($module);
        $result = $this->ssh->execute($server, "a2dismod {$escaped} && systemctl reload apache2", timeout: 30, useSudo: true);

        return [
            'success' => $result->success,
            'message' => $result->success ? "Modul {$module} wurde deaktiviert." : $result->stderr,
        ];
    }

    public function installCertbot(Server $server): array
    {
        $result = $this->ssh->execute($server, 'DEBIAN_FRONTEND=noninteractive apt-get update -qq && DEBIAN_FRONTEND=noninteractive apt-get install -y -qq certbot python3-certbot-apache', timeout: 120, useSudo: true);

        return [
            'success' => $result->success,
            'message' => $result->success ? 'Certbot wurde installiert.' : $result->stderr,
        ];
    }

    public function obtainSsl(Server $server, string $domain, string $email): array
    {
        $command = 'DEBIAN_FRONTEND=noninteractive certbot --apache --non-interactive --agree-tos -m '.escapeshellarg($email).' -d '.escapeshellarg($domain).' --redirect --keep-until-expiring && systemctl reload apache2';

        $result = $this->ssh->execute($server, $command, timeout: 120, useSudo: true);

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
