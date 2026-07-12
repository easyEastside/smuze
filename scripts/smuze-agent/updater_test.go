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

func TestPerformUpdateSkipsWhenNoDownloadURL(t *testing.T) {
	info := &UpdateInfo{LatestVersion: "0.2.0"}
	if err := PerformUpdate(context.Background(), info, "0.1.0"); err != nil {
		t.Fatalf("expected nil error for empty download URL, got: %v", err)
	}
}

func TestPerformUpdateSkipsCurrentVersion(t *testing.T) {
	info := &UpdateInfo{LatestVersion: "0.1.0", DownloadURL: "http://example.test/binary"}
	if err := PerformUpdate(context.Background(), info, "0.1.0"); err != nil {
		t.Fatalf("expected nil error when versions match, got: %v", err)
	}
}

func TestPerformUpdateChecksumMismatch(t *testing.T) {
	server := httptest.NewServer(http.HandlerFunc(func(w http.ResponseWriter, r *http.Request) {
		w.Write([]byte("updated"))
	}))
	defer server.Close()

	dir := t.TempDir()
	oldBinary := filepath.Join(dir, "smuze-agent")
	if err := os.WriteFile(oldBinary, []byte("original"), 0755); err != nil {
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
		Checksum:      "0000000000000000000000000000000000000000000000000000000000000000",
	}

	err = PerformUpdate(context.Background(), info, "0.1.0")
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
