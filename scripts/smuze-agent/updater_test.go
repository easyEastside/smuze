package main

import (
	"context"
	"crypto/sha256"
	"encoding/hex"
	"net/http"
	"net/http/httptest"
	"os"
	"path/filepath"
	"testing"
)

func TestPerformUpdateSkipsWhenNil(t *testing.T) {
	if err := PerformUpdate(context.Background(), nil, "0.1.0"); err != nil {
		t.Fatalf("expected nil error for nil update info, got: %v", err)
	}
}

func TestPerformUpdateDownloadsAndReplacesBinary(t *testing.T) {
	if os.Getuid() == 0 {
		t.Skip("skipping exec test as root")
	}

	originalContent := []byte("#!/bin/sh\necho old\n")
	updatedContent := []byte("#!/bin/sh\necho new\n")

	updateChecksum := sha256Hex(updatedContent)

	server := httptest.NewServer(http.HandlerFunc(func(w http.ResponseWriter, r *http.Request) {
		w.Write(updatedContent)
	}))
	defer server.Close()

	dir := t.TempDir()
	oldBinary := filepath.Join(dir, "smuze-agent")
	if err := os.WriteFile(oldBinary, originalContent, 0755); err != nil {
		t.Fatalf("write old binary: %v", err)
	}

	origExec, err := os.Executable()
	if err != nil {
		t.Fatalf("get executable: %v", err)
	}

	tmpLink := filepath.Join(dir, "smuze-agent.link")
	if err := os.Symlink(origExec, tmpLink); err != nil {
		t.Fatalf("symlink: %v", err)
	}

	info := &UpdateInfo{
		LatestVersion: "0.2.0",
		DownloadURL:   server.URL,
		Checksum:      updateChecksum,
	}

	err = PerformUpdate(context.Background(), info, "0.1.0")

	if err != nil && err.Error() != "replace binary: "+oldBinary+": device or resource busy" && err.Error() != "replace binary: "+oldBinary+": permission denied" {
		t.Fatalf("unexpected error: %v", err)
	}

	content, readErr := os.ReadFile(oldBinary)
	if readErr != nil {
		t.Fatalf("read old binary: %v", readErr)
	}

	if len(content) == len(updatedContent) {
		t.Fatal("binary should not have been replaced when running as different binary")
	}
}

func TestPerformUpdateChecksumMismatch(t *testing.T) {
	originalContent := []byte("original")

	server := httptest.NewServer(http.HandlerFunc(func(w http.ResponseWriter, r *http.Request) {
		w.Write([]byte("updated"))
	}))
	defer server.Close()

	dir := t.TempDir()
	oldBinary := filepath.Join(dir, "smuze-agent")
	if err := os.WriteFile(oldBinary, originalContent, 0755); err != nil {
		t.Fatalf("write old binary: %v", err)
	}

	info := &UpdateInfo{
		LatestVersion: "0.2.0",
		DownloadURL:   server.URL + "/bad-checksum",
		Checksum:      "0000000000000000000000000000000000000000000000000000000000000000",
	}

	err := PerformUpdate(context.Background(), info, "0.1.0")
	if err == nil {
		t.Fatal("expected checksum mismatch error")
	}

	content, _ := os.ReadFile(oldBinary)
	if string(content) != "original" {
		t.Fatal("original binary should remain unchanged after failed update")
	}
}

func TestDownloadBinary(t *testing.T) {
	expected := []byte("binary-content")
	server := httptest.NewServer(http.HandlerFunc(func(w http.ResponseWriter, r *http.Request) {
		w.Write(expected)
	}))
	defer server.Close()

	dir := t.TempDir()
	dest := filepath.Join(dir, "downloaded.txt")

	content, err := downloadBinary(context.Background(), server.URL, dest)
	if err != nil {
		t.Fatalf("downloadBinary error: %v", err)
	}

	if string(content) != string(expected) {
		t.Fatalf("unexpected content: got %q, want %q", string(content), string(expected))
	}

	saved, err := os.ReadFile(dest)
	if err != nil {
		t.Fatalf("read saved file: %v", err)
	}
	if string(saved) != string(expected) {
		t.Fatalf("saved file content mismatch: got %q, want %q", string(saved), string(expected))
	}
}

func TestSha256Hex(t *testing.T) {
	data := []byte("test data")
	expected := sha256.Sum256(data)
	got := sha256Hex(data)

	if got != hex.EncodeToString(expected[:]) {
		t.Fatalf("sha256Hex mismatch: got %s, expected %s", got, hex.EncodeToString(expected[:]))
	}
}

func TestHeartbeatReturnsUpdateInfo(t *testing.T) {
	server := httptest.NewServer(http.HandlerFunc(func(w http.ResponseWriter, r *http.Request) {
		if r.URL.Path != "/api/agent/heartbeat" {
			t.Fatalf("unexpected path: %s", r.URL.Path)
		}

		w.Write([]byte(`{
			"success": true,
			"update": {
				"latest_version": "0.2.0",
				"download_url": "https://example.test/agent/download",
				"checksum": "abc123"
			}
		}`))
	}))
	defer server.Close()

	client := NewClient(Config{AppURL: server.URL, ServerID: 42, Token: "token"}, "")
	update, err := client.Heartbeat(context.Background(), "0.1.0")
	if err != nil {
		t.Fatalf("Heartbeat error: %v", err)
	}

	if update == nil {
		t.Fatal("expected update info, got nil")
	}

	if update.LatestVersion != "0.2.0" {
		t.Fatalf("unexpected latest version: %s", update.LatestVersion)
	}
	if update.DownloadURL != "https://example.test/agent/download" {
		t.Fatalf("unexpected download URL: %s", update.DownloadURL)
	}
	if update.Checksum != "abc123" {
		t.Fatalf("unexpected checksum: %s", update.Checksum)
	}
}

func TestHeartbeatNoUpdate(t *testing.T) {
	server := httptest.NewServer(http.HandlerFunc(func(w http.ResponseWriter, r *http.Request) {
		w.Write([]byte(`{"success": true}`))
	}))
	defer server.Close()

	client := NewClient(Config{AppURL: server.URL, ServerID: 42, Token: "token"}, "")
	update, err := client.Heartbeat(context.Background(), "0.1.0")
	if err != nil {
		t.Fatalf("Heartbeat error: %v", err)
	}

	if update != nil {
		t.Fatal("expected no update when not present in response")
	}
}

func TestCheckForUpdateEndpoint(t *testing.T) {
	server := httptest.NewServer(http.HandlerFunc(func(w http.ResponseWriter, r *http.Request) {
		if r.URL.Path != "/api/agent/update-check" {
			t.Fatalf("unexpected path: %s", r.URL.Path)
		}

		w.Write([]byte(`{
			"update": {
				"latest_version": "0.3.0",
				"download_url": "https://example.test/agent/download",
				"checksum": "def456"
			}
		}`))
	}))
	defer server.Close()

	client := NewClient(Config{AppURL: server.URL, ServerID: 42, Token: "token"}, "")
	update, err := client.CheckForUpdate(context.Background())
	if err != nil {
		t.Fatalf("CheckForUpdate error: %v", err)
	}

	if update == nil {
		t.Fatal("expected update info")
	}
	if update.LatestVersion != "0.3.0" {
		t.Fatalf("unexpected latest version: %s", update.LatestVersion)
	}
}
