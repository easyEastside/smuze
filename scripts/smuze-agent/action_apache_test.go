package main

import (
	"strings"
	"testing"
)

func TestApacheCreateVhostReloadsOnlyWhenServiceIsActive(t *testing.T) {
	definition := apacheCreateVhostAction()
	command, err := definition.BuildCommand(map[string]any{
		"domain":        "example.com",
		"document_root": "/var/www/example/public",
		"config":        "<VirtualHost *:80>\nServerName example.com\n</VirtualHost>\n",
	})
	if err != nil {
		t.Fatalf("BuildCommand returned error: %v", err)
	}

	if !strings.Contains(command, apacheReloadIfActiveCommand) {
		t.Fatalf("expected reload guard in command: %s", command)
	}

	if strings.Contains(command, "&& systemctl reload apache2") {
		t.Fatalf("expected no unconditional apache reload in command: %s", command)
	}
}

func TestApacheSiteToggleReloadsOnlyWhenServiceIsActive(t *testing.T) {
	definition := apacheEnableSiteAction()
	command, err := definition.BuildCommand(map[string]any{"site": "example.com.conf"})
	if err != nil {
		t.Fatalf("BuildCommand returned error: %v", err)
	}

	if !strings.Contains(command, apacheReloadIfActiveCommand) {
		t.Fatalf("expected reload guard in command: %s", command)
	}

	if strings.Contains(command, "&& systemctl reload apache2") {
		t.Fatalf("expected no unconditional apache reload in command: %s", command)
	}
}
