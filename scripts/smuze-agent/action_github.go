package main

import (
	"errors"
	"fmt"
	"net/url"
	"strings"
)

const githubApacheRoot = "/var/www"

func githubDeployAction() actionDefinition {
	return actionDefinition{
		Name: "github.deploy",
		BuildCommand: func(payload map[string]any) (string, error) {
			repoURL, err := githubPayloadRepoURL(payload)
			if err != nil {
				return "", err
			}
			host, err := payloadString(payload, "host")
			if err != nil {
				return "", err
			}
			host = strings.TrimSpace(host)
			if err := apacheValidateHost(host); err != nil {
				return "", err
			}
			targetName, err := apachePayloadToken(payload, "target_name")
			if err != nil {
				return "", err
			}
			useSSL, err := payloadBool(payload, "use_ssl", false)
			if err != nil {
				return "", err
			}
			email, err := payloadOptionalString(payload, "email", "")
			if err != nil {
				return "", err
			}
			email = strings.TrimSpace(email)
			if useSSL && email == "" {
				return "", errors.New("email is required when ssl is enabled")
			}
			if email != "" && (!strings.Contains(email, "@") || strings.ContainsAny(email, "\r\n\t\x00")) {
				return "", errors.New("email must be valid")
			}

			return githubDeployScript(repoURL, host, targetName, useSSL, email), nil
		},
		Timeout: 300,
		UseSudo: true,
	}
}

func githubPayloadRepoURL(payload map[string]any) (string, error) {
	repoURL, err := payloadString(payload, "repo_url")
	if err != nil {
		return "", err
	}
	repoURL = strings.TrimSpace(repoURL)
	if repoURL == "" {
		return "", errors.New("repo_url is required")
	}
	if strings.ContainsAny(repoURL, "\r\n\t\x00") {
		return "", errors.New("repo_url must not contain control characters")
	}

	parsed, err := url.Parse(repoURL)
	if err != nil {
		return "", errors.New("repo_url must be valid")
	}
	if parsed.Scheme != "https" {
		return "", errors.New("repo_url must use https")
	}
	host := strings.ToLower(parsed.Hostname())
	if host != "github.com" && host != "www.github.com" {
		return "", errors.New("repo_url must be from github.com")
	}
	if parsed.RawQuery != "" || parsed.Fragment != "" {
		return "", errors.New("repo_url must not contain query or fragment")
	}
	parts := strings.FieldsFunc(parsed.Path, func(r rune) bool { return r == '/' })
	if len(parts) < 2 {
		return "", errors.New("repo_url must contain owner and repository")
	}

	return repoURL, nil
}

func githubDeployScript(repoURL string, host string, targetName string, useSSL bool, email string) string {
	targetPath := githubApacheRoot + "/" + targetName
	siteName := host + ".conf"
	sitePath := "/etc/apache2/sites-available/" + siteName

	lines := []string{
		"set -e",
		"repo_url=" + shellQuote(repoURL),
		"target_path=" + shellQuote(targetPath),
		"host_name=" + shellQuote(host),
		"site_name=" + shellQuote(siteName),
		"site_path=" + shellQuote(sitePath),
		"",
		`if [ -e "$target_path" ]; then`,
		`    printf "Ziel existiert bereits: %s\n" "$target_path"`,
		"    exit 2",
		"fi",
		`if [ -e "$site_path" ]; then`,
		`    printf "Apache-Site existiert bereits: %s\n" "$site_path"`,
		"    exit 3",
		"fi",
		"",
		"if ! command -v git >/dev/null 2>&1; then",
		"    apt update",
		"    apt install git -y",
		"fi",
		"if ! command -v apache2 >/dev/null 2>&1; then",
		`    printf "Apache ist nicht installiert.\n"`,
		"    exit 4",
		"fi",
		"",
		"mkdir -p " + shellQuote(githubApacheRoot),
		`git clone "$repo_url" "$target_path"`,
		"",
		`document_root="$target_path"`,
		`if [ -d "$target_path/public" ]; then`,
		`    document_root="$target_path/public"`,
		"fi",
		"",
	}

	if useSSL {
		lines = append(lines,
			"if ! command -v certbot >/dev/null 2>&1; then",
			"    DEBIAN_FRONTEND=noninteractive apt-get update -qq && DEBIAN_FRONTEND=noninteractive apt-get install -y -qq certbot python3-certbot-apache",
			"fi",
			"",
		)
	}

	lines = append(lines, githubVhostHereDoc(host, useSSL))
	lines = append(lines,
		"",
		"apache2ctl configtest",
		`a2ensite "$site_name"`,
		"systemctl reload apache2",
	)

	if useSSL {
		lines = append(lines,
			"DEBIAN_FRONTEND=noninteractive certbot --apache --non-interactive --agree-tos -m "+shellQuote(email)+" -d "+shellQuote(host)+" --redirect --keep-until-expiring",
			"systemctl reload apache2",
		)
	}

	lines = append(lines,
		"",
		`printf "Projekt geklont: %s\n" "$target_path"`,
		`printf "Apache Host eingerichtet: %s\n" "$host_name"`,
		`printf "DocumentRoot erkannt: %s\n" "$document_root"`,
	)

	return "sh -c " + shellQuote(strings.Join(lines, "\n"))
}

func githubVhostHereDoc(host string, useSSL bool) string {
	if useSSL {
		return fmt.Sprintf(`cat > "$site_path" <<EOF_VHOST
<VirtualHost *:80>
    ServerName %s
    RewriteEngine On
    RewriteRule ^ https://%%{HTTP_HOST}${REQUEST_URI} [R=301,L]
</VirtualHost>

<VirtualHost *:443>
    ServerName %s
    DocumentRoot ${document_root}

    <Directory ${document_root}>
        AllowOverride All
        Require all granted
    </Directory>

    ErrorLog ${APACHE_LOG_DIR}/%s_error.log
    CustomLog ${APACHE_LOG_DIR}/%s_access.log combined

    SSLEngine On
    SSLCertificateFile /etc/letsencrypt/live/%s/fullchain.pem
    SSLCertificateKeyFile /etc/letsencrypt/live/%s/privkey.pem
</VirtualHost>
EOF_VHOST`, host, host, host, host, host, host)
	}

	return fmt.Sprintf(`cat > "$site_path" <<EOF_VHOST
<VirtualHost *:80>
    ServerName %s
    DocumentRoot ${document_root}

    <Directory ${document_root}>
        AllowOverride All
        Require all granted
    </Directory>

    ErrorLog ${APACHE_LOG_DIR}/%s_error.log
    CustomLog ${APACHE_LOG_DIR}/%s_access.log combined
</VirtualHost>
EOF_VHOST`, host, host, host)
}
