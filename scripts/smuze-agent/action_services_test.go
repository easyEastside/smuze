package main

import (
	"strings"
	"testing"
)

func TestServicesPhpInstallReloadsApacheOnlyWhenServiceIsActive(t *testing.T) {
	definition := servicesInstallAction()
	command, err := definition.BuildCommand(map[string]any{
		"service": "php",
		"version": "8.5",
	})
	if err != nil {
		t.Fatalf("BuildCommand returned error: %v", err)
	}

	if !strings.Contains(command, apacheReloadIfActiveCommand) {
		t.Fatalf("expected apache reload guard in command: %s", command)
	}

	if strings.Contains(command, "&& systemctl reload apache2; fi") {
		t.Fatalf("expected no unconditional apache reload in command: %s", command)
	}
}

func TestServicesPhpInstallRejectsUnsupportedVersion(t *testing.T) {
	definition := servicesInstallAction()
	_, err := definition.BuildCommand(map[string]any{
		"service": "php",
		"version": "9.9",
	})
	if err == nil {
		t.Fatal("expected unsupported php version to be rejected")
	}
}
