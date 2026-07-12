package main

import "testing"

func TestShellQuoteEscapesSingleQuotes(t *testing.T) {
	quoted := shellQuote("echo 'hello'")
	if quoted != "'echo '\\''hello'\\'''" {
		t.Fatalf("unexpected shell quote: %s", quoted)
	}
}

func TestApplySudoWrapsCommand(t *testing.T) {
	wrapped := applySudo("apt update && echo 'ok'")
	expected := "sudo env DEBIAN_FRONTEND=noninteractive sh -lc 'apt update && echo '\\''ok'\\'''"
	if wrapped != expected {
		t.Fatalf("unexpected sudo wrapper:\n%s", wrapped)
	}
}

func TestApplySudoLeavesExistingSudoCommand(t *testing.T) {
	command := "sudo systemctl restart apache2"
	if applySudo(command) != command {
		t.Fatal("existing sudo command should remain unchanged")
	}
}
