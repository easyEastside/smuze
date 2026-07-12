package main

import (
	"context"
	"encoding/json"
	"flag"
	"fmt"
	"net/http"
	"os"
	"os/signal"
	"syscall"
	"time"
)

var version = "dev"

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
			updateFlags := flag.NewFlagSet("update", flag.ExitOnError)
			configPath := updateFlags.String("config", "/etc/smuze/agent.json", "Path to JSON config file")
			updateFlags.Parse(os.Args[2:])

			config, err := loadConfig(*configPath)
			if err != nil {
				fmt.Fprintln(os.Stderr, err)
				os.Exit(1)
			}

			ctx, cancel := context.WithTimeout(context.Background(), 60*time.Second)
			defer cancel()

			info, err := fetchUpdateInfo(ctx, config.AppURL)
			if err != nil {
				fmt.Fprintf(os.Stderr, "update check failed: %v\n", err)
				os.Exit(1)
			}

			if err := PerformUpdate(ctx, info, version); err != nil {
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

func fetchUpdateInfo(ctx context.Context, appURL string) (*UpdateInfo, error) {
	req, err := http.NewRequestWithContext(ctx, http.MethodGet, appURL+"/agent/version", nil)
	if err != nil {
		return nil, err
	}

	res, err := http.DefaultClient.Do(req)
	if err != nil {
		return nil, err
	}
	defer res.Body.Close()

	if res.StatusCode != http.StatusOK {
		return nil, fmt.Errorf("version check failed with status %d", res.StatusCode)
	}

	var info UpdateInfo
	if err := json.NewDecoder(res.Body).Decode(&info); err != nil {
		return nil, err
	}

	return &info, nil
}
