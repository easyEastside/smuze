<?php

namespace App\Modules\Server\Github\Actions;

use App\Models\Server;
use App\Services\ExecutionEngine\ExecutionEngine;

class GithubAction
{
    private const APACHE_ROOT = '/var/www';

    private const HOST_REGEX = '/^(?=.{1,253}$)[A-Za-z0-9](?:[A-Za-z0-9\-]{0,61}[A-Za-z0-9])?(?:\.[A-Za-z0-9](?:[A-Za-z0-9\-]{0,61}[A-Za-z0-9])?)*$/';

    private const SAFE_NAME_REGEX = '/^[A-Za-z0-9._-]+$/';

    public function __construct(
        private ExecutionEngine $engine,
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
    public function validateHost(string $host): array
    {
        $host = trim($host);

        if ($host === '') {
            return ['valid' => false, 'error' => 'Domain / Hostname ist erforderlich.'];
        }

        if (! preg_match(self::HOST_REGEX, $host)) {
            return ['valid' => false, 'error' => 'Domain / Hostname darf nur gültige DNS-Zeichen enthalten, z. B. example.com.'];
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
    public function deploy(Server $server, string $repoUrl, string $host, string $targetName, bool $useSsl = false, string $email = ''): array
    {
        $repoUrl = trim($repoUrl);
        $host = trim($host);
        $targetName = trim($targetName);
        $email = trim($email);

        $validation = $this->validateRepoUrl($repoUrl);
        if (! $validation['valid']) {
            return ['success' => false, 'message' => $validation['error']];
        }

        $validation = $this->validateHost($host);
        if (! $validation['valid']) {
            return ['success' => false, 'message' => $validation['error']];
        }

        $validation = $this->validateTargetName($targetName);
        if (! $validation['valid']) {
            return ['success' => false, 'message' => $validation['error']];
        }

        if ($useSsl && $email === '') {
            return ['success' => false, 'message' => 'E-Mail-Adresse für Let\'s Encrypt ist erforderlich.'];
        }

        $targetPath = self::APACHE_ROOT.'/'.$targetName;
        $siteName = $host.'.conf';
        $sitePath = '/etc/apache2/sites-available/'.$siteName;

        $vhostConfig = $this->buildVhostConfig($host, $targetPath, $useSsl);

        $script = $this->buildDeployScript($repoUrl, $targetPath, $host, $siteName, $sitePath, $vhostConfig, $useSsl, $email);

        $result = $this->engine->execute($server, 'sh -c '.escapeshellarg($script), timeout: 300, useSudo: true);

        $message = $result->stdout ?: $result->stderr;

        if ($result->success && $useSsl) {
            $message .= "\nSSL-Zertifikat (Let's Encrypt) ausgestellt.";
        }

        return [
            'success' => $result->success,
            'message' => $message,
        ];
    }

    private function buildVhostConfig(string $host, string $documentRoot, bool $useSsl): string
    {
        if ($useSsl) {
            return "<VirtualHost *:80>\n"
                ."    ServerName {$host}\n"
                ."    RewriteEngine On\n"
                ."    RewriteRule ^ https://%{HTTP_HOST}\${REQUEST_URI} [R=301,L]\n"
                ."</VirtualHost>\n\n"
                ."<VirtualHost *:443>\n"
                ."    ServerName {$host}\n"
                ."    DocumentRoot {$documentRoot}\n\n"
                ."    <Directory {$documentRoot}>\n"
                ."        AllowOverride All\n"
                ."        Require all granted\n"
                ."    </Directory>\n\n"
                ."    ErrorLog \${APACHE_LOG_DIR}/{$host}_error.log\n"
                ."    CustomLog \${APACHE_LOG_DIR}/{$host}_access.log combined\n\n"
                ."    SSLEngine On\n"
                ."    SSLCertificateFile /etc/letsencrypt/live/{$host}/fullchain.pem\n"
                ."    SSLCertificateKeyFile /etc/letsencrypt/live/{$host}/privkey.pem\n"
                ."</VirtualHost>\n";
        }

        return "<VirtualHost *:80>\n"
            ."    ServerName {$host}\n"
            ."    DocumentRoot {$documentRoot}\n\n"
            ."    <Directory {$documentRoot}>\n"
            ."        AllowOverride All\n"
            ."        Require all granted\n"
            ."    </Directory>\n\n"
            ."    ErrorLog \${APACHE_LOG_DIR}/{$host}_error.log\n"
            ."    CustomLog \${APACHE_LOG_DIR}/{$host}_access.log combined\n"
            ."</VirtualHost>\n";
    }

    private function buildDeployScript(string $repoUrl, string $targetPath, string $host, string $siteName, string $sitePath, string $vhostConfig, bool $useSsl, string $email): string
    {
        $lines = [
            'set -e',
            'repo_url='.escapeshellarg($repoUrl),
            'target_path='.escapeshellarg($targetPath),
            'host_name='.escapeshellarg($host),
            'site_name='.escapeshellarg($siteName),
            'site_path='.escapeshellarg($sitePath),
            '',
            'if [ -e "$target_path" ]; then',
            '    printf "Ziel existiert bereits: %s\\n" "$target_path"',
            '    exit 2',
            'fi',
            'if [ -e "$site_path" ]; then',
            '    printf "Apache-Site existiert bereits: %s\\n" "$site_path"',
            '    exit 3',
            'fi',
            '',
            'if ! command -v git >/dev/null 2>&1; then',
            '    apt update',
            '    apt install git -y',
            'fi',
            'if ! command -v apache2 >/dev/null 2>&1; then',
            '    printf "Apache ist nicht installiert.\\n"',
            '    exit 4',
            'fi',
            '',
            'mkdir -p '.escapeshellarg(self::APACHE_ROOT),
            'git clone "$repo_url" "$target_path"',
            '',
            'document_root="$target_path"',
            'if [ -d "$target_path/public" ]; then',
            '    document_root="$target_path/public"',
            'fi',
            '',
        ];

        if ($useSsl) {
            $config = str_replace('$document_root', '${document_root}', $vhostConfig);
            $config = 'cat > "$site_path" <<EOF_VHOST'."\n".$config."\nEOF_VHOST";

            $lines[] = 'if ! command -v certbot >/dev/null 2>&1; then';
            $lines[] = '    DEBIAN_FRONTEND=noninteractive apt-get update -qq && DEBIAN_FRONTEND=noninteractive apt-get install -y -qq certbot python3-certbot-apache';
            $lines[] = 'fi';
            $lines[] = '';
        } else {
            $config = 'cat > "$site_path" <<EOF_VHOST'."\n".$vhostConfig."\nEOF_VHOST";
        }

        $lines[] = $config;
        $lines[] = '';
        $lines[] = 'apache2ctl configtest';
        $lines[] = 'a2ensite "$site_name"';
        $lines[] = 'systemctl reload apache2';
        $lines[] = '';
        $lines[] = 'printf "Projekt geklont: %s\\n" "$target_path"';
        $lines[] = 'printf "Apache Host eingerichtet: %s\\n" "$host_name"';
        $lines[] = 'printf "DocumentRoot erkannt: %s\\n" "$document_root"';

        return implode("\n", $lines);
    }
}
