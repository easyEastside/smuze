package main

import (
	"context"
	"crypto/sha256"
	"encoding/hex"
	"fmt"
	"io"
	"net/http"
	"os"
	"path/filepath"
	"syscall"
)

type UpdateInfo struct {
	LatestVersion string `json:"latest_version"`
	DownloadURL   string `json:"download_url"`
	Checksum      string `json:"checksum,omitempty"`
}

func PerformUpdate(ctx context.Context, info *UpdateInfo, currentVersion string) error {
	if info == nil || info.DownloadURL == "" {
		return nil
	}

	if info.LatestVersion != "" && info.LatestVersion <= currentVersion {
		return nil
	}

	if info.LatestVersion != "" {
		fmt.Printf("Updating agent: %s -> %s\n", currentVersion, info.LatestVersion)
	} else {
		fmt.Println("Updating agent...")
	}

	executable, err := os.Executable()
	if err != nil {
		return fmt.Errorf("get executable path: %w", err)
	}

	absExec, err := filepath.Abs(executable)
	if err != nil {
		return fmt.Errorf("absolute path: %w", err)
	}

	tmpFile := absExec + ".update"
	downloaded, err := downloadBinary(ctx, info.DownloadURL, tmpFile)
	if err != nil {
		return fmt.Errorf("download binary: %w", err)
	}

	if info.Checksum != "" {
		calculated := sha256Hex(downloaded)

		if calculated != info.Checksum {
			os.Remove(tmpFile)

			return fmt.Errorf("checksum mismatch: got %s, expected %s", calculated, info.Checksum)
		}

		fmt.Println("Checksum verified")
	}

	if err := os.Rename(tmpFile, absExec); err != nil {
		return fmt.Errorf("replace binary: %w", err)
	}

	if err := os.Chmod(absExec, 0755); err != nil {
		return fmt.Errorf("chmod binary: %w", err)
	}

	fmt.Println("Update downloaded, restarting...")

	return syscall.Exec(absExec, os.Args, os.Environ())
}

func downloadBinary(ctx context.Context, url string, destPath string) ([]byte, error) {
	req, err := http.NewRequestWithContext(ctx, http.MethodGet, url, nil)
	if err != nil {
		return nil, err
	}

	res, err := http.DefaultClient.Do(req)
	if err != nil {
		return nil, err
	}
	defer res.Body.Close()

	if res.StatusCode != http.StatusOK {
		return nil, fmt.Errorf("download failed with status %d", res.StatusCode)
	}

	content, err := io.ReadAll(res.Body)
	if err != nil {
		return nil, fmt.Errorf("read response: %w", err)
	}

	if err := os.WriteFile(destPath, content, 0755); err != nil {
		return nil, fmt.Errorf("write temp file: %w", err)
	}

	return content, nil
}

func sha256Hex(data []byte) string {
	hash := sha256.Sum256(data)

	return hex.EncodeToString(hash[:])
}
