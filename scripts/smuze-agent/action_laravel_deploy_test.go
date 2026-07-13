package main

import (
	"strings"
	"testing"
)

func TestLaravelDeployActionBuildsQuotedCommand(t *testing.T) {
	command, err := laravelDeployAction().command(map[string]any{
		"repo_url":       "https://github.com/laravel/laravel.git",
		"target_path":    "/var/www/production-app",
		"domain":         "example.com",
		"webserver":      "nginx",
		"php_version":    "8.5",
		"install_node":   true,
		"run_build":      true,
		"run_migrations": true,
		"write_env":      true,
		"env": map[string]any{
			"APP_ENV": "production",
		},
	})
	if err != nil {
		t.Fatalf("expected command, got error: %v", err)
	}

	if !strings.Contains(command, "git clone \"$repo_url\" \"$target_path\"") {
		t.Fatalf("expected safe git clone command: %s", command)
	}
	if !strings.Contains(command, "nginx -t") {
		t.Fatalf("expected nginx config test: %s", command)
	}
	if !strings.Contains(command, "php artisan migrate --force") {
		t.Fatalf("expected migrations command: %s", command)
	}
	if !strings.Contains(command, "export TERM=xterm CI=1 NO_COLOR=1 NPM_CONFIG_COLOR=false PATH=/usr/local/bin:$PATH") {
		t.Fatalf("expected non-interactive terminal environment: %s", command)
	}
	if !strings.Contains(command, "==> npm run build") || !strings.Contains(command, "==> Composer Dependencies installieren") {
		t.Fatalf("expected deployment step markers: %s", command)
	}
	if !strings.Contains(command, "pdo_sqlite") || !strings.Contains(command, "php${php_version}-sqlite3") {
		t.Fatalf("expected sqlite php extension validation and install package: %s", command)
	}
	if !strings.Contains(command, `"$target_path/database"`) || !strings.Contains(command, "chmod -R ug+rwX") {
		t.Fatalf("expected writable database directory permissions for sqlite: %s", command)
	}
	if !strings.Contains(command, "^APP_KEY=") || !strings.Contains(command, "APP_KEY=") {
		t.Fatalf("expected APP_KEY placeholder to be appended before key generation: %s", command)
	}
	if !strings.Contains(command, "getcomposer.org/installer") || !strings.Contains(command, "/usr/local/bin") {
		t.Fatalf("expected official composer installation path: %s", command)
	}
	if !strings.Contains(command, "/usr/local/bin/composer install --no-dev") {
		t.Fatalf("expected deployment to use official composer phar explicitly: %s", command)
	}
	if strings.Contains(command, "\ncomposer install --no-dev") {
		t.Fatalf("expected deployment not to call composer from PATH on its own line: %s", command)
	}
	if !strings.Contains(command, "nvm install 24") || !strings.Contains(command, "nvm use 24") {
		t.Fatalf("expected node 24 via nvm before build: %s", command)
	}
	if !strings.Contains(command, ". \"$NVM_DIR/nvm.sh\" && nvm use 24 && npm run build") {
		t.Fatalf("expected build to run with nvm node 24: %s", command)
	}
	if strings.Contains(command, "apt-get install -y -qq nodejs npm") {
		t.Fatalf("expected deployment to avoid apt node/npm fallback: %s", command)
	}
}

func TestLaravelDeployInstallNodeUsesNvmNode24WithoutBuild(t *testing.T) {
	command, err := laravelDeployAction().command(map[string]any{
		"repo_url":     "https://github.com/laravel/laravel.git",
		"target_path":  "/var/www/production-app",
		"install_node": true,
	})
	if err != nil {
		t.Fatalf("expected command, got error: %v", err)
	}

	if !strings.Contains(command, "nvm install 24") || !strings.Contains(command, "nvm alias default 24") {
		t.Fatalf("expected install_node to ensure nvm node 24: %s", command)
	}
	if strings.Contains(command, "npm run build") {
		t.Fatalf("expected install_node without run_build not to build: %s", command)
	}
}

func TestLaravelDeployRejectsUnsafeTargetPath(t *testing.T) {
	_, err := laravelDeployAction().command(map[string]any{
		"repo_url":    "https://github.com/laravel/laravel.git",
		"target_path": "/tmp/app",
	})
	if err == nil {
		t.Fatal("expected unsafe target path to be rejected")
	}
}

func TestLaravelDeployRequiresDomainForVhost(t *testing.T) {
	_, err := laravelDeployAction().command(map[string]any{
		"repo_url":    "https://github.com/laravel/laravel.git",
		"target_path": "/var/www/app",
		"webserver":   "apache",
	})
	if err == nil {
		t.Fatal("expected missing domain to be rejected")
	}
}

func TestLaravelDeployRejectsUnsafeEnvKeys(t *testing.T) {
	_, err := laravelDeployAction().command(map[string]any{
		"repo_url":    "https://github.com/laravel/laravel.git",
		"target_path": "/var/www/app",
		"env": map[string]any{
			"bad-key": "value",
		},
	})
	if err == nil {
		t.Fatal("expected unsafe env key to be rejected")
	}
}
