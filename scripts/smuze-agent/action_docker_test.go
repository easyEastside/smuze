package main

import (
	"strings"
	"testing"
)

func TestDockerContainerActionQuotesContainerName(t *testing.T) {
	command, err := dockerContainerStartAction().command(map[string]any{"container": "smuze-test"})
	if err != nil {
		t.Fatalf("expected command, got error: %v", err)
	}

	if !strings.Contains(command, "docker start 'smuze-test'") {
		t.Fatalf("expected quoted container in command: %s", command)
	}
}

func TestDockerContainerInspectActionQuotesContainerName(t *testing.T) {
	command, err := dockerContainerInspectAction().command(map[string]any{"container": "smuze-test"})
	if err != nil {
		t.Fatalf("expected command, got error: %v", err)
	}

	if !strings.Contains(command, "docker inspect 'smuze-test'") {
		t.Fatalf("expected quoted container in inspect command: %s", command)
	}
}

func TestDockerInstallStartsSocketBeforeService(t *testing.T) {
	command := dockerInstallAction().Command

	if !strings.Contains(command, "systemctl enable --now docker.socket") {
		t.Fatalf("expected docker socket activation in install command: %s", command)
	}
}

func TestDockerStartStartsSocketBeforeService(t *testing.T) {
	command := dockerStartAction().Command

	if !strings.Contains(command, "systemctl enable --now docker.socket && systemctl start docker") {
		t.Fatalf("expected docker socket activation in start command: %s", command)
	}
}

func TestDockerDeinstallIgnoresBusyDockerDataDirectory(t *testing.T) {
	command := dockerDeinstallAction().Command

	if !strings.Contains(command, "rm -rf /var/lib/docker 2>/dev/null || true") {
		t.Fatalf("expected robust docker data cleanup in deinstall command: %s", command)
	}
}

func TestDockerContainerActionRejectsUnsafeContainerName(t *testing.T) {
	_, err := dockerContainerStartAction().command(map[string]any{"container": "bad; reboot"})
	if err == nil {
		t.Fatal("expected unsafe container name to be rejected")
	}
}

func TestDockerImageActionsQuoteImageName(t *testing.T) {
	command, err := dockerImagePullAction().command(map[string]any{"image": "nginx:alpine"})
	if err != nil {
		t.Fatalf("expected command, got error: %v", err)
	}

	if !strings.Contains(command, "docker pull 'nginx:alpine'") {
		t.Fatalf("expected quoted image in command: %s", command)
	}
}

func TestDockerImageActionsRejectUnsafeImageName(t *testing.T) {
	_, err := dockerImagePullAction().command(map[string]any{"image": "nginx; reboot"})
	if err == nil {
		t.Fatal("expected unsafe image name to be rejected")
	}
}

func TestDockerComposeActionsQuoteProjectPath(t *testing.T) {
	command, err := dockerComposePsAction().command(map[string]any{"project_path": "/opt/smuze-app"})
	if err != nil {
		t.Fatalf("expected command, got error: %v", err)
	}

	if !strings.Contains(command, "cd '/opt/smuze-app'") {
		t.Fatalf("expected quoted project path in command: %s", command)
	}
}

func TestDockerComposeActionsRejectUnsafeProjectPath(t *testing.T) {
	_, err := dockerComposePsAction().command(map[string]any{"project_path": "/tmp/../etc"})
	if err == nil {
		t.Fatal("expected unsafe project path to be rejected")
	}
}
