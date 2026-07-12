package main

import (
	"encoding/json"
	"os"
	"path/filepath"
	"strings"
	"testing"
)

func TestWriteInstallFilesCreatesConfigAndService(t *testing.T) {
	dir := t.TempDir()
	configPath := filepath.Join(dir, "etc", "smuze", "agent.json")
	servicePath := filepath.Join(dir, "systemd", "smuze-agent.service")

	options := InstallOptions{
		AppURL:                 "https://app.example/",
		ServerID:               42,
		Token:                  "token",
		PollIntervalSeconds:    2,
		MetricsIntervalSeconds: 10,
		ConfigPath:             configPath,
		ServicePath:            servicePath,
		BinaryPath:             "/usr/local/bin/smuze-agent",
		ServiceUser:            "smuze",
	}

	if err := writeInstallFiles(options); err != nil {
		t.Fatalf("writeInstallFiles returned error: %v", err)
	}

	configContent, err := os.ReadFile(configPath)
	if err != nil {
		t.Fatalf("failed to read config: %v", err)
	}

	var cfg fileConfig
	if err := json.Unmarshal(configContent, &cfg); err != nil {
		t.Fatalf("failed to decode config: %v", err)
	}

	if cfg.AppURL != "https://app.example" || cfg.ServerID != 42 || cfg.Token != "token" {
		t.Fatalf("unexpected config: %+v", cfg)
	}

	serviceContent, err := os.ReadFile(servicePath)
	if err != nil {
		t.Fatalf("failed to read service: %v", err)
	}
	service := string(serviceContent)

	for _, expected := range []string{
		"Description=Smuze Agent",
		"User=smuze",
		"ExecStart=/usr/local/bin/smuze-agent --config " + configPath,
		"Restart=always",
	} {
		if !strings.Contains(service, expected) {
			t.Fatalf("service missing %q:\n%s", expected, service)
		}
	}
}

func TestValidateInstallOptionsRequiresToken(t *testing.T) {
	err := validateInstallOptions(InstallOptions{AppURL: "https://app.example", ServerID: 1, ConfigPath: "config", ServicePath: "service", BinaryPath: "binary"})
	if err == nil || err.Error() != "--token is required" {
		t.Fatalf("unexpected error: %v", err)
	}
}
