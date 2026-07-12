package main

import (
	"os"
	"path/filepath"
	"testing"
)

func TestLoadConfigFromEnvironment(t *testing.T) {
	t.Setenv("SMUZE_APP_URL", "https://example.test/")
	t.Setenv("SMUZE_SERVER_ID", "42")
	t.Setenv("SMUZE_AGENT_TOKEN", "secret")
	t.Setenv("SMUZE_AGENT_PORT", "9500")

	cfg, err := loadConfig("")
	if err != nil {
		t.Fatalf("loadConfig returned error: %v", err)
	}

	if cfg.AppURL != "https://example.test" {
		t.Fatalf("unexpected app URL: %s", cfg.AppURL)
	}
	if cfg.ServerID != 42 {
		t.Fatalf("unexpected server id: %d", cfg.ServerID)
	}
	if cfg.Token != "secret" {
		t.Fatalf("unexpected token: %s", cfg.Token)
	}
	if cfg.Port != 9500 {
		t.Fatalf("unexpected port: %d", cfg.Port)
	}
}

func TestLoadConfigFileOverridesEnvironment(t *testing.T) {
	t.Setenv("SMUZE_APP_URL", "https://env.test")
	t.Setenv("SMUZE_SERVER_ID", "1")
	t.Setenv("SMUZE_AGENT_TOKEN", "env-token")

	path := filepath.Join(t.TempDir(), "agent.json")
	content := `{"app_url":"https://file.test/","server_id":99,"token":"file-token","port":9400}`
	if err := os.WriteFile(path, []byte(content), 0o600); err != nil {
		t.Fatalf("failed to write config file: %v", err)
	}

	cfg, err := loadConfig(path)
	if err != nil {
		t.Fatalf("loadConfig returned error: %v", err)
	}

	if cfg.AppURL != "https://file.test" || cfg.ServerID != 99 || cfg.Token != "file-token" {
		t.Fatalf("file config did not override env: %+v", cfg)
	}
	if cfg.Port != 9400 {
		t.Fatalf("file port not applied: %+v", cfg)
	}
}

func TestLoadConfigRequiresCredentials(t *testing.T) {
	_, err := loadConfig("")
	if err == nil {
		t.Fatal("expected missing config error")
	}
}
