package main

import (
	"context"
	"encoding/json"
	"fmt"
	"io"
	"net/http"
	"net/http/httptest"
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
