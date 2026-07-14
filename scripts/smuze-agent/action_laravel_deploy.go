package main

import (
	"encoding/base64"
	"errors"
	"fmt"
	"path/filepath"
	"regexp"
	"sort"
	"strings"
)

var envKeyPattern = regexp.MustCompile(`^[A-Z_][A-Z0-9_]*$`)

func laravelDeployAction() actionDefinition {
	return actionDefinition{
		Name: "laravel.deploy",
		BuildCommand: func(payload map[string]any) (string, error) {
			config, err := laravelDeployPayload(payload)
			if err != nil {
				return "", err
			}

			return laravelDeployScript(config), nil
		},
		Timeout: 900,
		UseSudo: true,
	}
}

type laravelDeployConfig struct {
	RepoURL       string
	TargetPath    string
	Domain        string
	Webserver     string
	PHPVersion    string
	InstallNode   bool
	RunBuild      bool
	RunMigrations bool
	WriteEnv      bool
	Env           map[string]string
}

func laravelDeployPayload(payload map[string]any) (laravelDeployConfig, error) {
	repoURL, err := githubPayloadRepoURL(payload)
	if err != nil {
		return laravelDeployConfig{}, err
	}

	targetPath, err := payloadString(payload, "target_path")
	if err != nil {
		return laravelDeployConfig{}, err
	}
	targetPath = strings.TrimRight(strings.TrimSpace(targetPath), "/")
	if err := laravelValidateProjectPath(targetPath); err != nil {
		return laravelDeployConfig{}, err
	}

	domain, err := payloadOptionalString(payload, "domain", "")
	if err != nil {
		return laravelDeployConfig{}, err
	}
	domain = strings.TrimSpace(domain)
	if domain != "" {
		if err := apacheValidateHost(domain); err != nil {
			return laravelDeployConfig{}, err
		}
	}

	webserver, err := payloadOptionalString(payload, "webserver", "none")
	if err != nil {
		return laravelDeployConfig{}, err
	}
	webserver = strings.TrimSpace(webserver)
	if webserver != "none" && webserver != "apache" && webserver != "nginx" {
		return laravelDeployConfig{}, errors.New("webserver must be none, apache, or nginx")
	}
	if webserver != "none" && domain == "" {
		return laravelDeployConfig{}, errors.New("domain is required when creating a vhost")
	}

	phpVersion, err := payloadOptionalString(payload, "php_version", "8.5")
	if err != nil {
		return laravelDeployConfig{}, err
	}
	if phpVersion != "8.4" && phpVersion != "8.5" {
		return laravelDeployConfig{}, errors.New("php_version must be 8.4 or 8.5")
	}

	installNode, err := payloadBool(payload, "install_node", false)
	if err != nil {
		return laravelDeployConfig{}, err
	}
	runBuild, err := payloadBool(payload, "run_build", false)
	if err != nil {
		return laravelDeployConfig{}, err
	}
	runMigrations, err := payloadBool(payload, "run_migrations", false)
	if err != nil {
		return laravelDeployConfig{}, err
	}
	writeEnv, err := payloadBool(payload, "write_env", true)
	if err != nil {
		return laravelDeployConfig{}, err
	}
	env, err := payloadEnv(payload)
	if err != nil {
		return laravelDeployConfig{}, err
	}

	return laravelDeployConfig{
		RepoURL:       repoURL,
		TargetPath:    targetPath,
		Domain:        domain,
		Webserver:     webserver,
		PHPVersion:    phpVersion,
		InstallNode:   installNode,
		RunBuild:      runBuild,
		RunMigrations: runMigrations,
		WriteEnv:      writeEnv,
		Env:           env,
	}, nil
}

func laravelDeployScript(config laravelDeployConfig) string {
	documentRoot := config.TargetPath + "/public"
	phpFpmSocket := fmt.Sprintf("unix:/run/php/php%s-fpm.sock", config.PHPVersion)
	commands := []string{
		"set -e",
		"export TERM=xterm CI=1 NO_COLOR=1 NPM_CONFIG_COLOR=false PATH=/usr/local/bin:$PATH",
		"repo_url=" + shellQuote(config.RepoURL),
		"target_path=" + shellQuote(config.TargetPath),
		"php_version=" + shellQuote(config.PHPVersion),
		"export COMPOSER_ALLOW_SUPERUSER=1 COMPOSER_NO_INTERACTION=1 COMPOSER_DISABLE_XDEBUG_WARN=1",
		"",
		laravelStepCommand("Systempakete pruefen", "true"),
		"if ! command -v git >/dev/null 2>&1; then apt-get update -qq && apt-get install -y -qq git; fi",
		laravelEnsurePhpCommand(),
		laravelEnsureComposerCommand(),
		"mkdir -p " + shellQuote(filepath.Dir(config.TargetPath)),
		laravelStepCommand("Repository synchronisieren", `if [ -d "$target_path/.git" ]; then git -C "$target_path" pull --ff-only; elif [ -e "$target_path" ]; then printf "Ziel existiert, ist aber kein Git-Repository: %s\n" "$target_path" >&2; exit 2; else git clone "$repo_url" "$target_path"; fi`),
	}

	if config.WriteEnv {
		commands = append(commands, laravelEnvCommand(config.TargetPath, config.Env))
	}

	commands = append(commands,
		`cd "$target_path"`,
		laravelStepCommand("Composer Dependencies installieren", laravelComposerCommand("install --no-dev --prefer-dist --no-interaction --optimize-autoloader")),
		laravelStepCommand("Laravel APP_KEY pruefen", `if [ -f artisan ]; then touch .env; if ! grep -q '^APP_KEY=' .env; then printf '\nAPP_KEY=\n' >> .env; fi; if ! grep -q '^APP_KEY=base64:' .env; then php artisan key:generate --force; fi; fi`),
	)

	if config.InstallNode || config.RunBuild {
		commands = append(commands, laravelStepCommand("Node 24 vorbereiten", laravelEnsureNodeCommand()))
	}
	if config.RunBuild {
		commands = append(commands, laravelStepCommand("npm install", laravelNodeCommand("npm install")), laravelStepCommand("npm run build", laravelNodeCommand("npm run build")))
	}
	if config.RunMigrations {
		commands = append(commands, laravelStepCommand("Datenbankmigrationen", "php artisan migrate --force"))
	}

	commands = append(commands,
		laravelStepCommand("Laravel Caches", "php artisan optimize || true"),
		laravelStepCommand("Dateirechte setzen", `chown -R www-data:www-data "$target_path/storage" "$target_path/bootstrap/cache" "$target_path/database" 2>/dev/null || true; chmod -R ug+rwX "$target_path/storage" "$target_path/bootstrap/cache" "$target_path/database" 2>/dev/null || true`),
	)

	if config.Webserver == "apache" {
		commands = append(commands, laravelApacheVhostCommands(config.Domain, documentRoot, config.PHPVersion)...)
	}
	if config.Webserver == "nginx" {
		commands = append(commands, laravelNginxVhostCommands(config.Domain, documentRoot, phpFpmSocket)...)
	}

	commands = append(commands, `printf "Laravel deployment abgeschlossen: %s\n" "$target_path"`)

	return "sh -c " + shellQuote(strings.Join(commands, "\n"))
}

func laravelEnvCommand(targetPath string, env map[string]string) string {
	content := laravelEnvContent(env)
	encoded := base64.StdEncoding.EncodeToString([]byte(content))

	return "printf '%s' " + shellQuote(encoded) + " | base64 -d > " + shellQuote(targetPath+"/.env")
}

func laravelStepCommand(name string, command string) string {
	return "printf '\\n==> " + name + "\\n' && " + command
}

func laravelEnsurePhpCommand() string {
	return strings.Join([]string{
		`needs_php_packages=0`,
		`if ! command -v php >/dev/null 2>&1 || ! php -v | grep -q "PHP ${php_version}"; then needs_php_packages=1; fi`,
		`for extension in mbstring xml curl zip pdo_mysql pdo_sqlite; do if ! php -m 2>/dev/null | grep -qi "^${extension}$"; then needs_php_packages=1; fi; done`,
		`if [ "$needs_php_packages" = "1" ]; then apt-get update -qq && apt-get install -y -qq php${php_version}-cli php${php_version}-fpm php${php_version}-mbstring php${php_version}-xml php${php_version}-curl php${php_version}-zip php${php_version}-sqlite3 php${php_version}-mysql; fi`,
	}, " && ")
}

func laravelEnsureNodeCommand() string {
	return strings.Join([]string{
		`export NVM_DIR="${NVM_DIR:-$HOME/.nvm}"`,
		`if [ ! -s "$NVM_DIR/nvm.sh" ]; then curl -o- https://raw.githubusercontent.com/nvm-sh/nvm/v0.40.5/install.sh | bash; fi`,
		`. "$NVM_DIR/nvm.sh"`,
		`nvm install 24`,
		`nvm alias default 24`,
		`nvm use 24`,
		`node -v`,
		`npm -v`,
	}, " && ")
}

func laravelEnsureComposerCommand() string {
	return strings.Join([]string{
		`if ! command -v curl >/dev/null 2>&1; then apt-get update -qq && apt-get install -y -qq curl ca-certificates; fi`,
		`EXPECTED_CHECKSUM="$(curl -sS https://composer.github.io/installer.sig)"; php -r "copy('https://getcomposer.org/installer', '/tmp/composer-setup.php');"; ACTUAL_CHECKSUM="$(php -r 'echo hash_file("sha384", "/tmp/composer-setup.php");')"; if [ "$EXPECTED_CHECKSUM" != "$ACTUAL_CHECKSUM" ]; then printf "Invalid Composer installer checksum\n" >&2; rm -f /tmp/composer-setup.php; exit 1; fi; php /tmp/composer-setup.php --quiet --install-dir=/usr/local/bin --filename=composer; rm -f /tmp/composer-setup.php; chmod 755 /usr/local/bin/composer; hash -r`,
		laravelComposerCommand("--version"),
	}, " && ")
}

func laravelComposerCommand(arguments string) string {
	return "php -d error_reporting='E_ALL & ~E_DEPRECATED & ~E_USER_DEPRECATED' /usr/local/bin/composer " + arguments
}

func laravelNodeCommand(command string) string {
	return strings.Join([]string{
		`export NVM_DIR="${NVM_DIR:-$HOME/.nvm}"`,
		`. "$NVM_DIR/nvm.sh"`,
		`nvm use 24`,
		command,
	}, " && ")
}

func laravelEnvContent(env map[string]string) string {
	keys := make([]string, 0, len(env))
	for key := range env {
		keys = append(keys, key)
	}
	sort.Strings(keys)

	lines := make([]string, 0, len(keys))
	for _, key := range keys {
		lines = append(lines, key+"="+env[key])
	}

	return strings.Join(lines, "\n") + "\n"
}

func laravelApacheVhostCommands(domain string, documentRoot string, phpVersion string) []string {
	config := fmt.Sprintf(`<VirtualHost *:80>
    ServerName %s
    DocumentRoot %s
    <Directory %s>
        AllowOverride All
        Require all granted
    </Directory>
    ErrorLog ${APACHE_LOG_DIR}/%s-error.log
    CustomLog ${APACHE_LOG_DIR}/%s-access.log combined
</VirtualHost>
`, domain, documentRoot, documentRoot, domain, domain)
	encoded := base64.StdEncoding.EncodeToString([]byte(config))
	path := "/etc/apache2/sites-available/" + domain + ".conf"

	return []string{
		"apt-get update -qq && apt-get install -y -qq apache2 " + shellQuote("php"+phpVersion+"-fpm"),
		"a2enmod rewrite proxy_fcgi setenvif",
		"for version in 8.2 8.3 8.4 8.5; do a2disconf \"php${version}-fpm\" 2>/dev/null || true; done",
		"a2enconf " + shellQuote("php"+phpVersion+"-fpm"),
		"mkdir -p " + shellQuote(documentRoot),
		"printf '%s' " + shellQuote(encoded) + " | base64 -d > " + shellQuote(path),
		"apache2ctl configtest",
		"a2ensite " + shellQuote(domain+".conf"),
		apacheReloadIfActiveCommand,
	}
}

func laravelNginxVhostCommands(domain string, documentRoot string, phpFpmSocket string) []string {
	config := fmt.Sprintf(`server {
    listen 80;
    server_name %s;
    root %s;
    index index.php index.html;

    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    location ~ \.php$ {
        include snippets/fastcgi-php.conf;
        fastcgi_pass %s;
    }
}
`, domain, documentRoot, phpFpmSocket)
	encoded := base64.StdEncoding.EncodeToString([]byte(config))
	path := "/etc/nginx/sites-available/" + domain + ".conf"

	return []string{
		"if ! command -v nginx >/dev/null 2>&1; then apt-get update -qq && apt-get install -y -qq nginx; fi",
		"mkdir -p " + shellQuote(documentRoot) + " /etc/nginx/sites-available /etc/nginx/sites-enabled",
		"printf '%s' " + shellQuote(encoded) + " | base64 -d > " + shellQuote(path),
		"ln -sfn " + shellQuote(path) + " " + shellQuote("/etc/nginx/sites-enabled/"+domain+".conf"),
		"nginx -t",
		nginxReloadIfActiveCommand,
	}
}

func laravelValidateProjectPath(path string) error {
	if !strings.HasPrefix(path, "/var/www/") || strings.ContainsAny(path, "\r\n\t\x00") || strings.Contains(path, "/..") {
		return errors.New("target_path must be under /var/www")
	}
	if path == "/var/www/html" {
		return errors.New("target_path cannot be /var/www/html")
	}

	return nil
}

func payloadEnv(payload map[string]any) (map[string]string, error) {
	value, exists := payload["env"]
	if !exists || value == nil {
		return map[string]string{}, nil
	}

	raw, ok := value.(map[string]any)
	if !ok {
		return nil, errors.New("env must be an object")
	}

	env := make(map[string]string, len(raw))
	for key, value := range raw {
		if !envKeyPattern.MatchString(key) {
			return nil, errors.New("env keys must be valid")
		}
		stringValue, ok := value.(string)
		if !ok || strings.ContainsAny(stringValue, "\x00") {
			return nil, errors.New("env values must be strings")
		}
		env[key] = stringValue
	}

	return env, nil
}
