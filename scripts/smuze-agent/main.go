package main

import (
	"context"
	"flag"
	"fmt"
	"net/http"
	"os"
	"os/signal"
	"path/filepath"
	"syscall"
	"time"
)

const version = "0.1.0"

func main() {
	if len(os.Args) > 1 {
		switch os.Args[1] {
		case "install":
			if err := runInstall(os.Args[2:]); err != nil {
				fmt.Fprintln(os.Stderr, err)
				os.Exit(1)
			}

			return

		case "update":
			config, err := loadConfig("")
			if err != nil {
				fmt.Fprintln(os.Stderr, err)
				os.Exit(1)
			}

			ctx, cancel := context.WithTimeout(context.Background(), 60*time.Second)
			defer cancel()

			downloadURL := config.AppURL + "/agent/download"

			if err := performDirectUpdate(ctx, downloadURL); err != nil {
				fmt.Fprintf(os.Stderr, "update failed: %v\n", err)
				os.Exit(1)
			}

			return
		}
	}

	configPath := flag.String("config", "", "Path to JSON config file")
	showVersion := flag.Bool("version", false, "Print version and exit")
	flag.Parse()

	if *showVersion {
		fmt.Println(version)
		return
	}

	config, err := loadConfig(*configPath)
	if err != nil {
		fmt.Fprintln(os.Stderr, err)
		os.Exit(1)
	}

	ctx, stop := signal.NotifyContext(context.Background(), os.Interrupt, syscall.SIGTERM)
	defer stop()

	srv := NewServer(config)
	httpServer := &http.Server{
		Addr:    fmt.Sprintf(":%d", config.Port),
		Handler: srv.Handler(),
	}

	go func() {
		fmt.Printf("Agent listening on port %d\n", config.Port)

		if err := httpServer.ListenAndServe(); err != nil && err != http.ErrServerClosed {
			fmt.Fprintf(os.Stderr, "server error: %v\n", err)
			os.Exit(1)
		}
	}()

	<-ctx.Done()

	shutdownCtx, cancel := context.WithTimeout(context.Background(), 5*time.Second)
	defer cancel()

	httpServer.Shutdown(shutdownCtx)
}

func performDirectUpdate(ctx context.Context, downloadURL string) error {
	executable, err := os.Executable()
	if err != nil {
		return fmt.Errorf("get executable path: %w", err)
	}

	absExec, err := filepath.Abs(executable)
	if err != nil {
		return fmt.Errorf("absolute path: %w", err)
	}

	tmpFile := absExec + ".update"

	downloaded, err := downloadBinary(ctx, downloadURL, tmpFile)
	if err != nil {
		return fmt.Errorf("download binary: %w", err)
	}

	if len(downloaded) < 1024 {
		os.Remove(tmpFile)

		return fmt.Errorf("downloaded file too small (%d bytes), aborting", len(downloaded))
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
