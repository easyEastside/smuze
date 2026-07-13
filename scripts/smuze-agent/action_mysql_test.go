package main

import (
	"strings"
	"testing"
)

func TestMysqlStatusCommandContainsInstalled(t *testing.T) {
	command := mysqlStatusAction().Command

	if !strings.Contains(command, "INSTALLED=") {
		t.Fatalf("expected INSTALLED in status command, got %s", command)
	}

	if !strings.Contains(command, "command -v mysql") {
		t.Fatalf("expected mysql binary check in status command, got %s", command)
	}

	if !strings.Contains(command, "VERSION=") {
		t.Fatalf("expected VERSION in status command, got %s", command)
	}
}
