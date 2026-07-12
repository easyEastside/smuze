package main

import (
	"context"
	"encoding/json"
	"fmt"
	"io"
	"net/http"
	"net/http/httptest"
	"slices"
	"strings"
	"testing"
)

func newTestServer(t *testing.T) *httptest.Server {
	t.Helper()
	srv := NewServer(Config{Token: "test-token", Port: 9300})
	return httptest.NewServer(srv.Handler())
}

func authHeader() (string, string) {
	return "Authorization", "Bearer test-token"
}

func TestHealthEndpoint(t *testing.T) {
	ts := newTestServer(t)
	defer ts.Close()

	res, err := http.Get(ts.URL + "/health")
	if err != nil {
		t.Fatalf("GET /health: %v", err)
	}
	defer res.Body.Close()

	if res.StatusCode != 403 {
		t.Fatalf("expected 403 without auth, got %d", res.StatusCode)
	}
}

func TestHealthEndpointWithAuth(t *testing.T) {
	ts := newTestServer(t)
	defer ts.Close()

	req, _ := http.NewRequest("GET", ts.URL+"/health", nil)
	req.Header.Set(authHeader())

	res, err := http.DefaultClient.Do(req)
	if err != nil {
		t.Fatalf("GET /health: %v", err)
	}
	defer res.Body.Close()

	if res.StatusCode != 200 {
		t.Fatalf("expected 200, got %d", res.StatusCode)
	}

	var body map[string]any
	if err := json.NewDecoder(res.Body).Decode(&body); err != nil {
		t.Fatalf("decode: %v", err)
	}

	if body["status"] != "ok" {
		t.Fatalf("unexpected status: %v", body["status"])
	}
	if body["version"] != version {
		t.Fatalf("unexpected version: %v", body["version"])
	}
}

func TestCapabilitiesEndpointReturnsVersionAndActions(t *testing.T) {
	ts := newTestServer(t)
	defer ts.Close()

	req, _ := http.NewRequest("GET", ts.URL+"/capabilities", nil)
	req.Header.Set(authHeader())

	res, err := http.DefaultClient.Do(req)
	if err != nil {
		t.Fatalf("GET /capabilities: %v", err)
	}
	defer res.Body.Close()

	if res.StatusCode != 200 {
		t.Fatalf("expected 200, got %d", res.StatusCode)
	}

	var body capabilitiesResponse
	if err := json.NewDecoder(res.Body).Decode(&body); err != nil {
		t.Fatalf("decode capabilities: %v", err)
	}

	if body.Version == "" {
		t.Fatal("expected version")
	}
	if !slices.Contains(body.Actions, "services.install") || !slices.Contains(body.Actions, "github.deploy") {
		t.Fatalf("expected registered actions, got %v", body.Actions)
	}
}

func TestMetricsEndpoint(t *testing.T) {
	ts := newTestServer(t)
	defer ts.Close()

	req, _ := http.NewRequest("GET", ts.URL+"/metrics", nil)
	req.Header.Set(authHeader())

	res, err := http.DefaultClient.Do(req)
	if err != nil {
		t.Fatalf("GET /metrics: %v", err)
	}
	defer res.Body.Close()

	if res.StatusCode != 200 {
		t.Fatalf("expected 200, got %d", res.StatusCode)
	}

	var body map[string]any
	if err := json.NewDecoder(res.Body).Decode(&body); err != nil {
		t.Fatalf("decode: %v", err)
	}

	if body["hostname"] == nil {
		t.Fatal("expected hostname in metrics")
	}
}

func TestReadCommandVersionTrimsOutput(t *testing.T) {
	version := readCommandVersion("printf ' 1.2.3\\n'")

	if version != "1.2.3" {
		t.Fatalf("expected trimmed version, got %q", version)
	}
}

func TestCancelEndpoint(t *testing.T) {
	ts := newTestServer(t)
	defer ts.Close()

	req, _ := http.NewRequest("POST", ts.URL+"/cancel", nil)
	req.Header.Set(authHeader())

	res, err := http.DefaultClient.Do(req)
	if err != nil {
		t.Fatalf("POST /cancel: %v", err)
	}
	defer res.Body.Close()

	if res.StatusCode != 200 {
		t.Fatalf("expected 200, got %d", res.StatusCode)
	}

	var body map[string]any
	if err := json.NewDecoder(res.Body).Decode(&body); err != nil {
		t.Fatalf("decode: %v", err)
	}

	if body["success"] != true {
		t.Fatal("expected success")
	}
}

func TestExecuteEndpoint(t *testing.T) {
	ts := newTestServer(t)
	defer ts.Close()

	body := `{"command":"echo hello","timeout":10,"use_sudo":false}`
	req, _ := http.NewRequest("POST", ts.URL+"/execute", strings.NewReader(body))
	req.Header.Set(authHeader())
	req.Header.Set("Content-Type", "application/json")

	res, err := http.DefaultClient.Do(req)
	if err != nil {
		t.Fatalf("POST /execute: %v", err)
	}
	defer res.Body.Close()

	if res.StatusCode != 200 {
		t.Fatalf("expected 200, got %d", res.StatusCode)
	}

	respBody, _ := io.ReadAll(res.Body)
	lines := strings.Split(strings.TrimSpace(string(respBody)), "\n")

	var lastChunk map[string]any
	for _, line := range lines {
		json.Unmarshal([]byte(line), &lastChunk)
	}

	if lastChunk["done"] != true {
		t.Fatal("expected done=true in last chunk")
	}
	if lastChunk["exit_code"] != float64(0) {
		t.Fatalf("expected exit_code=0, got %v", lastChunk["exit_code"])
	}
}

func TestActionEndpointRejectsUnknownAction(t *testing.T) {
	ts := newTestServer(t)
	defer ts.Close()

	body := `{"action":"system.unknown","payload":{}}`
	req, _ := http.NewRequest("POST", ts.URL+"/actions", strings.NewReader(body))
	req.Header.Set(authHeader())
	req.Header.Set("Content-Type", "application/json")

	res, err := http.DefaultClient.Do(req)
	if err != nil {
		t.Fatalf("POST /actions: %v", err)
	}
	defer res.Body.Close()

	if res.StatusCode != 404 {
		t.Fatalf("expected 404, got %d", res.StatusCode)
	}
}

func TestSystemActionsAreRegisteredByName(t *testing.T) {
	expectedActions := []string{
		"apache.status",
		"apache.install",
		"apache.deinstall",
		"apache.start",
		"apache.stop",
		"apache.restart",
		"apache.reload",
		"apache.configtest",
		"apache.sites",
		"apache.site_config",
		"apache.save_site_config",
		"apache.enable_site",
		"apache.disable_site",
		"apache.delete_site",
		"apache.create_vhost",
		"apache.modules",
		"apache.enable_module",
		"apache.disable_module",
		"apache.install_certbot",
		"apache.obtain_ssl",
		"firewall.status",
		"firewall.rules",
		"firewall.install",
		"firewall.enable",
		"firewall.disable",
		"firewall.allow",
		"firewall.deny",
		"firewall.delete",
		"firewall.allow_standard_ports",
		"github.deploy",
		"mysql.status",
		"mysql.install",
		"mysql.deinstall",
		"mysql.start",
		"mysql.stop",
		"mysql.restart",
		"mysql.databases",
		"mysql.create_database",
		"mysql.drop_database",
		"mysql.tables",
		"mysql.create_table",
		"mysql.drop_table",
		"mysql.users",
		"mysql.create_user",
		"mysql.drop_user",
		"mysql.set_password",
		"mysql.grant_all",
		"services.install",
		"services.deinstall",
		"system.apt_update",
		"system.apt_upgrade",
		"system.reboot",
		"system.shutdown",
	}

	for _, actionName := range expectedActions {
		definition, ok := systemActions[actionName]
		if !ok {
			t.Fatalf("expected %s to be registered", actionName)
		}
		if definition.Name != actionName {
			t.Fatalf("expected %s name, got %s", actionName, definition.Name)
		}
	}
}

func TestRunActionExecutesWhitelistedDefinition(t *testing.T) {
	srv := NewServer(Config{Token: "test-token", Port: 9300})

	result := srv.runAction(context.Background(), "test.echo", actionDefinition{
		Name:    "test.echo",
		Command: "echo action-ok",
		Timeout: 10,
		UseSudo: false,
	}, nil)

	if !result.Success {
		t.Fatalf("expected success, got error %q", result.Error)
	}
	if result.ExitCode != 0 {
		t.Fatalf("expected exit_code=0, got %d", result.ExitCode)
	}
	if !strings.Contains(result.Stdout, "action-ok") {
		t.Fatalf("expected stdout to contain action-ok, got %q", result.Stdout)
	}
}

func TestFirewallAllowBuildsValidatedCommand(t *testing.T) {
	command, err := firewallAllowAction().command(map[string]any{
		"port":     "443",
		"protocol": "tcp",
	})
	if err != nil {
		t.Fatalf("expected command, got error %v", err)
	}

	if command != "ufw allow '443/tcp'" {
		t.Fatalf("unexpected command: %s", command)
	}
}

func TestFirewallAllowRejectsUnsafeProtocol(t *testing.T) {
	_, err := firewallAllowAction().command(map[string]any{
		"port":     "443",
		"protocol": "tcp; reboot",
	})
	if err == nil {
		t.Fatal("expected unsafe protocol to be rejected")
	}
}

func TestFirewallAllowRejectsInvalidPort(t *testing.T) {
	_, err := firewallAllowAction().command(map[string]any{
		"port":     "65536",
		"protocol": "tcp",
	})
	if err == nil {
		t.Fatal("expected invalid port to be rejected")
	}
}

func TestFirewallDeleteBuildsValidatedCommand(t *testing.T) {
	command, err := firewallDeleteAction().command(map[string]any{"rule": float64(12)})
	if err != nil {
		t.Fatalf("expected command, got error %v", err)
	}

	if command != "ufw --force delete 12" {
		t.Fatalf("unexpected command: %s", command)
	}
}

func TestFirewallDeleteRejectsInvalidRule(t *testing.T) {
	_, err := firewallDeleteAction().command(map[string]any{"rule": "1; reboot"})
	if err == nil {
		t.Fatal("expected invalid rule to be rejected")
	}
}

func TestMysqlCreateDatabaseBuildsValidatedCommand(t *testing.T) {
	command, err := mysqlCreateDatabaseAction().command(map[string]any{"db_name": "app_db"})
	if err != nil {
		t.Fatalf("expected command, got error %v", err)
	}

	if command != "mysql -e 'CREATE DATABASE `app_db` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci' 2>&1" {
		t.Fatalf("unexpected command: %s", command)
	}
}

func TestMysqlCreateDatabaseRejectsUnsafeName(t *testing.T) {
	_, err := mysqlCreateDatabaseAction().command(map[string]any{"db_name": "app db; drop"})
	if err == nil {
		t.Fatal("expected unsafe database name to be rejected")
	}
}

func TestMysqlDropDatabaseRejectsSystemDatabase(t *testing.T) {
	_, err := mysqlDropDatabaseAction().command(map[string]any{"db_name": "mysql"})
	if err == nil {
		t.Fatal("expected system database to be rejected")
	}
}

func TestMysqlCreateUserBuildsValidatedCommand(t *testing.T) {
	command, err := mysqlCreateUserAction().command(map[string]any{
		"username": "app_user",
		"host":     "localhost",
		"password": "secret",
	})
	if err != nil {
		t.Fatalf("expected command, got error %v", err)
	}

	expected := `mysql -e 'CREATE USER '\''app_user'\''@'\''localhost'\'' IDENTIFIED BY '\''secret'\''' 2>&1`
	if command != expected {
		t.Fatalf("unexpected command: %s", command)
	}
}

func TestMysqlCreateUserRejectsUnsafeHost(t *testing.T) {
	_, err := mysqlCreateUserAction().command(map[string]any{
		"username": "app_user",
		"host":     "local..host",
		"password": "secret",
	})
	if err == nil {
		t.Fatal("expected unsafe host to be rejected")
	}
}

func TestApacheEnableModuleBuildsValidatedCommand(t *testing.T) {
	command, err := apacheEnableModuleAction().command(map[string]any{"module": "rewrite"})
	if err != nil {
		t.Fatalf("expected command, got error %v", err)
	}

	if command != "a2enmod 'rewrite' && systemctl reload apache2" {
		t.Fatalf("unexpected command: %s", command)
	}
}

func TestApacheEnableModuleRejectsUnsafeModule(t *testing.T) {
	_, err := apacheEnableModuleAction().command(map[string]any{"module": "rewrite; reboot"})
	if err == nil {
		t.Fatal("expected unsafe module to be rejected")
	}
}

func TestApacheCreateVhostRejectsUnsafeDocumentRoot(t *testing.T) {
	_, err := apacheCreateVhostAction().command(map[string]any{
		"domain":        "example.com",
		"document_root": "/var/www/../root",
		"config":        "<VirtualHost *:80></VirtualHost>",
	})
	if err == nil {
		t.Fatal("expected unsafe document root to be rejected")
	}
}

func TestGithubDeployBuildsValidatedCommand(t *testing.T) {
	command, err := githubDeployAction().command(map[string]any{
		"repo_url":    "https://github.com/owner/repo.git",
		"host":        "example.com",
		"target_name": "repo",
	})
	if err != nil {
		t.Fatalf("expected command, got error %v", err)
	}

	if !strings.Contains(command, "git clone") || !strings.Contains(command, "https://github.com/owner/repo.git") || !strings.Contains(command, "/var/www/repo") {
		t.Fatalf("unexpected command: %s", command)
	}
}

func TestGithubDeployRejectsNonGithubURL(t *testing.T) {
	_, err := githubDeployAction().command(map[string]any{
		"repo_url":    "https://example.com/owner/repo.git",
		"host":        "example.com",
		"target_name": "repo",
	})
	if err == nil {
		t.Fatal("expected non-github url to be rejected")
	}
}

func TestGithubDeployRejectsUnsafeTargetName(t *testing.T) {
	_, err := githubDeployAction().command(map[string]any{
		"repo_url":    "https://github.com/owner/repo.git",
		"host":        "example.com",
		"target_name": "../repo",
	})
	if err == nil {
		t.Fatal("expected unsafe target name to be rejected")
	}
}

func TestServicesInstallNodeUsesLatestNode(t *testing.T) {
	definition := servicesInstallAction()
	command, err := definition.command(map[string]any{"service": "node"})
	if err != nil {
		t.Fatalf("expected command, got error %v", err)
	}
	useSudo, err := definition.useSudo(map[string]any{"service": "node"})
	if err != nil {
		t.Fatalf("expected sudo flag, got error %v", err)
	}

	if !strings.Contains(command, "nvm/v0.40.5/install.sh") || !strings.Contains(command, "nvm install 24") {
		t.Fatalf("expected node 24 nvm install command, got %s", command)
	}
	if !strings.Contains(command, "node -v") || !strings.Contains(command, "npm -v") {
		t.Fatalf("expected node and npm verification command, got %s", command)
	}
	if strings.Contains(command, "nvm install node") || strings.Contains(command, "apt-get install") {
		t.Fatalf("expected node install to avoid latest alias and apt, got %s", command)
	}
	if useSudo {
		t.Fatal("expected node install to run without sudo")
	}
}

func TestServicesInstallNpmUsesNode24Npm(t *testing.T) {
	command, err := servicesInstallAction().command(map[string]any{"service": "npm"})
	if err != nil {
		t.Fatalf("expected command, got error %v", err)
	}

	if !strings.Contains(command, "nvm/v0.40.5/install.sh") || !strings.Contains(command, "nvm install 24") || !strings.Contains(command, "npm -v") {
		t.Fatalf("expected npm install via node 24 command, got %s", command)
	}
	if strings.Contains(command, "npm install -g npm@latest") || strings.Contains(command, "apt-get install") {
		t.Fatalf("expected npm install to use bundled node 24 npm, got %s", command)
	}
}

func TestServicesInstallPhpBuildsSelectedVersionCommand(t *testing.T) {
	command, err := servicesInstallAction().command(map[string]any{"service": "php", "version": "8.5"})
	if err != nil {
		t.Fatalf("expected command, got error %v", err)
	}

	if !strings.Contains(command, "php8.5-cli") || !strings.Contains(command, "php8.5-fpm") || !strings.Contains(command, "update-alternatives --set php /usr/bin/php8.5") {
		t.Fatalf("expected php 8.5 install command, got %s", command)
	}
	if !strings.Contains(command, "packages.sury.org/php/") || !strings.Contains(command, "debsuryorg-archive-keyring.deb") {
		t.Fatalf("expected sury repository setup, got %s", command)
	}
	if !strings.Contains(command, "a2enmod proxy_fcgi setenvif") || !strings.Contains(command, "a2enconf php8.5-fpm") {
		t.Fatalf("expected apache fpm configuration, got %s", command)
	}
	if strings.Contains(command, " php-cli ") || strings.Contains(command, " php-fpm ") || strings.Contains(command, "php-pear") || strings.Contains(command, "php8.5-pear") {
		t.Fatalf("expected only listed versioned php packages, got %s", command)
	}
	if strings.Contains(command, "ppa:ondrej/php") || strings.Contains(command, "libapache2-mod-php") || strings.Contains(command, "a2enmod php8.5") {
		t.Fatalf("expected no ondrej ppa or mod_php setup, got %s", command)
	}
	for _, packageName := range []string{"php8.5-opcache", "php8.5-pgsql", "php8.5-sqlite3", "php8.5-soap", "php8.5-readline"} {
		if strings.Contains(command, packageName) {
			t.Fatalf("expected %s to be excluded from listed php packages, got %s", packageName, command)
		}
	}
}

func TestServicesInstallPhpSupportsLastFourVersions(t *testing.T) {
	for _, version := range []string{"8.5", "8.4", "8.3", "8.2"} {
		command, err := servicesInstallAction().command(map[string]any{"service": "php", "version": version})
		if err != nil {
			t.Fatalf("expected php %s command, got error %v", version, err)
		}
		if !strings.Contains(command, "php"+version+"-cli") {
			t.Fatalf("expected php %s packages, got %s", version, command)
		}
	}
}

func TestServicesInstallPhpRejectsUnsupportedVersion(t *testing.T) {
	_, err := servicesInstallAction().command(map[string]any{"service": "php", "version": "8.5; reboot"})
	if err == nil {
		t.Fatal("expected unsafe php version to be rejected")
	}
}

func TestServicesInstallRejectsUnknownService(t *testing.T) {
	_, err := servicesInstallAction().command(map[string]any{"service": "unknown"})
	if err == nil {
		t.Fatal("expected unknown service to be rejected")
	}
}

func TestServicesDeinstallMysqlRemovesInstalledMysqlFamilyPackages(t *testing.T) {
	command, err := servicesDeinstallAction().command(map[string]any{"service": "mysql"})
	if err != nil {
		t.Fatalf("expected command, got error %v", err)
	}

	if !strings.Contains(command, "systemctl disable --now mysql") || !strings.Contains(command, "apt-get purge -y mysql-server mysql-client mysql-common") {
		t.Fatalf("expected mysql service disable and package purge command, got %s", command)
	}
	if !strings.Contains(command, "dpkg-query") || !strings.Contains(command, "mariadb") || !strings.Contains(command, "percona") {
		t.Fatalf("expected robust mysql family removal command, got %s", command)
	}
	if !strings.Contains(command, "/var/lib/mysql") || !strings.Contains(command, "/etc/mysql") || !strings.Contains(command, "/var/log/mysql") {
		t.Fatalf("expected mysql data and config cleanup command, got %s", command)
	}
}

func TestServicesDeinstallApachePurgesPackagesAndKeepsWebroot(t *testing.T) {
	command, err := servicesDeinstallAction().command(map[string]any{"service": "apache"})
	if err != nil {
		t.Fatalf("expected command, got error %v", err)
	}

	if !strings.Contains(command, "systemctl disable --now apache2") || !strings.Contains(command, "apt-get purge -y apache2 apache2-bin apache2-data apache2-utils") {
		t.Fatalf("expected apache disable and purge command, got %s", command)
	}
	if !strings.Contains(command, "rm -rf /etc/apache2") {
		t.Fatalf("expected apache config cleanup command, got %s", command)
	}
	if strings.Contains(command, "/var/www") {
		t.Fatalf("expected apache deinstall to preserve webroot, got %s", command)
	}
}

func TestServicesDeinstallPhpRemovesFpmVersionsAndSuryRepository(t *testing.T) {
	command, err := servicesDeinstallAction().command(map[string]any{"service": "php"})
	if err != nil {
		t.Fatalf("expected command, got error %v", err)
	}

	for _, version := range []string{"8.2", "8.3", "8.4", "8.5"} {
		if !strings.Contains(command, version) {
			t.Fatalf("expected php %s fpm cleanup, got %s", version, command)
		}
	}
	if !strings.Contains(command, "a2disconf") || !strings.Contains(command, "systemctl disable --now") || !strings.Contains(command, "^php8\\.[2345]") {
		t.Fatalf("expected php fpm and versioned package cleanup command, got %s", command)
	}
	if !strings.Contains(command, "/etc/apt/sources.list.d/php.list") || !strings.Contains(command, "debsuryorg-archive-keyring") {
		t.Fatalf("expected sury repository removal command, got %s", command)
	}
}

func TestServicesDeinstallNvmOnlyRemovesNvmFiles(t *testing.T) {
	command, err := servicesDeinstallAction().command(map[string]any{"service": "nvm"})
	if err != nil {
		t.Fatalf("expected command, got error %v", err)
	}

	if !strings.Contains(command, `rm -rf "$NVM_DIR"`) || !strings.Contains(command, "NVM_DIR") || !strings.Contains(command, "nvm.sh") {
		t.Fatalf("expected nvm shell cleanup command, got %s", command)
	}
	if strings.Contains(command, "apt-get") || strings.Contains(command, "nodejs") || strings.Contains(command, "npm") {
		t.Fatalf("expected nvm deinstall to avoid apt node/npm removal, got %s", command)
	}
}

func TestServicesDeinstallNodeRemovesNvmAndAptNodePackages(t *testing.T) {
	definition := servicesDeinstallAction()
	command, err := definition.command(map[string]any{"service": "node"})
	if err != nil {
		t.Fatalf("expected command, got error %v", err)
	}
	useSudo, err := definition.useSudo(map[string]any{"service": "node"})
	if err != nil {
		t.Fatalf("expected sudo flag, got error %v", err)
	}

	if !strings.Contains(command, `rm -rf "$NVM_DIR"`) || !strings.Contains(command, `"$HOME/.npm"`) || !strings.Contains(command, `"$HOME/.node-gyp"`) {
		t.Fatalf("expected nvm and node cache cleanup command, got %s", command)
	}
	if !strings.Contains(command, "dpkg-query") || !strings.Contains(command, "nodejs") || !strings.Contains(command, "npm") || !strings.Contains(command, "apt-get purge") {
		t.Fatalf("expected nvm and apt node removal command, got %s", command)
	}
	if useSudo {
		t.Fatal("expected node deinstall command to manage sudo internally")
	}
}

func TestServicesDeinstallNpmUsesNodeRemovalCommand(t *testing.T) {
	nodeCommand, err := servicesDeinstallAction().command(map[string]any{"service": "node"})
	if err != nil {
		t.Fatalf("expected node command, got error %v", err)
	}
	npmCommand, err := servicesDeinstallAction().command(map[string]any{"service": "npm"})
	if err != nil {
		t.Fatalf("expected npm command, got error %v", err)
	}

	if npmCommand != nodeCommand {
		t.Fatal("expected npm deinstall to remove the same node/npm runtime")
	}
}

func TestServicesDeinstallComposerRemovesGlobalBinaryAptPackageAndCaches(t *testing.T) {
	definition := servicesDeinstallAction()
	command, err := definition.command(map[string]any{"service": "composer"})
	if err != nil {
		t.Fatalf("expected command, got error %v", err)
	}
	useSudo, err := definition.useSudo(map[string]any{"service": "composer"})
	if err != nil {
		t.Fatalf("expected sudo flag, got error %v", err)
	}

	if !strings.Contains(command, "/usr/local/bin/composer") || !strings.Contains(command, "apt-get purge -y composer") {
		t.Fatalf("expected global and apt composer removal command, got %s", command)
	}
	if !strings.Contains(command, `"$HOME/.composer"`) || !strings.Contains(command, `"$HOME/.cache/composer"`) || !strings.Contains(command, `"$HOME/.config/composer"`) {
		t.Fatalf("expected composer user cache cleanup command, got %s", command)
	}
	if useSudo {
		t.Fatal("expected composer deinstall command to manage sudo internally")
	}
}

func TestExecuteEndpointStderr(t *testing.T) {
	ts := newTestServer(t)
	defer ts.Close()

	body := `{"command":"echo stderr message >&2","timeout":10,"use_sudo":false}`
	req, _ := http.NewRequest("POST", ts.URL+"/execute", strings.NewReader(body))
	req.Header.Set(authHeader())
	req.Header.Set("Content-Type", "application/json")

	res, err := http.DefaultClient.Do(req)
	if err != nil {
		t.Fatalf("POST /execute: %v", err)
	}
	defer res.Body.Close()

	respBody, _ := io.ReadAll(res.Body)

	if !strings.Contains(string(respBody), "stderr message") {
		t.Fatal("expected stderr message in response")
	}
}

func TestExecuteEndpointFailsOnNonPost(t *testing.T) {
	ts := newTestServer(t)
	defer ts.Close()

	req, _ := http.NewRequest("GET", ts.URL+"/execute", nil)
	req.Header.Set(authHeader())

	res, _ := http.DefaultClient.Do(req)
	if res.StatusCode != 405 {
		t.Fatalf("expected 405, got %d", res.StatusCode)
	}
}

func TestUpdateEndpoint(t *testing.T) {
	ts := newTestServer(t)
	defer ts.Close()

	requestBody := `{"latest_version":"0.2.0","download_url":"http://example.test/agent/download","checksum":"abc"}`
	req, _ := http.NewRequest("POST", ts.URL+"/update", strings.NewReader(requestBody))
	req.Header.Set(authHeader())
	req.Header.Set("Content-Type", "application/json")

	res, err := http.DefaultClient.Do(req)
	if err != nil {
		t.Fatalf("POST /update: %v", err)
	}
	defer res.Body.Close()

	if res.StatusCode != 200 {
		t.Fatalf("expected 200, got %d", res.StatusCode)
	}

	var responseBody map[string]any
	if err := json.NewDecoder(res.Body).Decode(&responseBody); err != nil {
		t.Fatalf("decode: %v", err)
	}

	if responseBody["success"] != true {
		t.Fatal("expected success")
	}
}

func TestUpdateEndpointRejectsMissingDownloadURL(t *testing.T) {
	ts := newTestServer(t)
	defer ts.Close()

	req, _ := http.NewRequest("POST", ts.URL+"/update", strings.NewReader(`{"latest_version":"0.2.0"}`))
	req.Header.Set(authHeader())
	req.Header.Set("Content-Type", "application/json")

	res, err := http.DefaultClient.Do(req)
	if err != nil {
		t.Fatalf("POST /update: %v", err)
	}
	defer res.Body.Close()

	if res.StatusCode != 400 {
		t.Fatalf("expected 400, got %d", res.StatusCode)
	}
}

func TestAuthRejectsWrongToken(t *testing.T) {
	ts := newTestServer(t)
	defer ts.Close()

	req, _ := http.NewRequest("GET", ts.URL+"/health", nil)
	req.Header.Set("Authorization", "Bearer wrong-token")

	res, _ := http.DefaultClient.Do(req)
	if res.StatusCode != 403 {
		t.Fatalf("expected 403, got %d", res.StatusCode)
	}
}

func TestAuthRejectsEmptyConfiguredToken(t *testing.T) {
	srv := NewServer(Config{Token: "", Port: 9300})
	ts := httptest.NewServer(srv.Handler())
	defer ts.Close()

	req, _ := http.NewRequest("GET", ts.URL+"/health", nil)
	req.Header.Set("Authorization", "Bearer ")

	res, _ := http.DefaultClient.Do(req)
	if res.StatusCode != 403 {
		t.Fatalf("expected 403, got %d", res.StatusCode)
	}
}

func TestExecuteRejectsLargeCommand(t *testing.T) {
	ts := newTestServer(t)
	defer ts.Close()

	body := fmt.Sprintf(`{"command":"%s","timeout":10,"use_sudo":false}`, strings.Repeat("a", maxCommandBytes+1))
	req, _ := http.NewRequest("POST", ts.URL+"/execute", strings.NewReader(body))
	req.Header.Set(authHeader())
	req.Header.Set("Content-Type", "application/json")

	res, err := http.DefaultClient.Do(req)
	if err != nil {
		t.Fatalf("POST /execute: %v", err)
	}
	defer res.Body.Close()

	if res.StatusCode != 400 {
		t.Fatalf("expected 400, got %d", res.StatusCode)
	}
}

func TestExecuteRejectsOversizedBody(t *testing.T) {
	ts := newTestServer(t)
	defer ts.Close()

	body := `{"command":"` + strings.Repeat("a", maxExecuteBodyBytes) + `"}`
	req, _ := http.NewRequest("POST", ts.URL+"/execute", strings.NewReader(body))
	req.Header.Set(authHeader())
	req.Header.Set("Content-Type", "application/json")

	res, err := http.DefaultClient.Do(req)
	if err != nil {
		t.Fatalf("POST /execute: %v", err)
	}
	defer res.Body.Close()

	if res.StatusCode != 400 {
		t.Fatalf("expected 400, got %d", res.StatusCode)
	}
}

func TestExecuteWithSudo(t *testing.T) {
	ts := newTestServer(t)
	defer ts.Close()

	body := fmt.Sprintf(`{"command":"echo %s","timeout":10,"use_sudo":true}`, "sudo-test")
	req, _ := http.NewRequest("POST", ts.URL+"/execute", strings.NewReader(body))
	req.Header.Set(authHeader())
	req.Header.Set("Content-Type", "application/json")

	res, err := http.DefaultClient.Do(req)
	if err != nil {
		t.Fatalf("POST /execute: %v", err)
	}
	defer res.Body.Close()

	if res.StatusCode != 200 {
		t.Fatalf("expected 200, got %d", res.StatusCode)
	}

	respBody, _ := io.ReadAll(res.Body)
	if !strings.Contains(string(respBody), `"done":true`) {
		t.Fatal("expected done chunk in response")
	}
}
