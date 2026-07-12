package main

import (
	"encoding/json"
	"errors"
	"flag"
	"fmt"
	"os"
	"path/filepath"
	"strings"
)

type InstallOptions struct {
	AppURL                 string `json:"app_url"`
	ServerID               int64  `json:"server_id"`
	Token                  string `json:"token"`
	PollIntervalSeconds    int    `json:"poll_interval_seconds"`
	MetricsIntervalSeconds int    `json:"metrics_interval_seconds"`
	ConfigPath             string `json:"-"`
	ServicePath            string `json:"-"`
	BinaryPath             string `json:"-"`
	ServiceUser            string `json:"-"`
}

func runInstall(args []string) error {
	flags := flag.NewFlagSet("install", flag.ContinueOnError)
	options := InstallOptions{}
	flags.StringVar(&options.AppURL, "app-url", "", "Smuze application URL")
	flags.Int64Var(&options.ServerID, "server-id", 0, "Smuze server ID")
	flags.StringVar(&options.Token, "token", "", "Smuze agent token")
	flags.StringVar(&options.ConfigPath, "config", "/etc/smuze/agent.json", "Agent config path")
	flags.StringVar(&options.ServicePath, "service", "/etc/systemd/system/smuze-agent.service", "systemd service path")
	flags.StringVar(&options.BinaryPath, "binary", "/usr/local/bin/smuze-agent", "Agent binary path")
	flags.StringVar(&options.ServiceUser, "user", "root", "systemd service user")
	flags.IntVar(&options.PollIntervalSeconds, "poll-interval", 2, "Polling interval in seconds")
	flags.IntVar(&options.MetricsIntervalSeconds, "metrics-interval", 10, "Metrics interval in seconds")

	if err := flags.Parse(args); err != nil {
		return err
	}

	if err := validateInstallOptions(options); err != nil {
		return err
	}

	if err := writeInstallFiles(options); err != nil {
		return err
	}

	fmt.Printf("Config written to %s\n", options.ConfigPath)
	fmt.Printf("systemd service written to %s\n", options.ServicePath)
	fmt.Println("Run: systemctl daemon-reload && systemctl enable --now smuze-agent")

	return nil
}

func validateInstallOptions(options InstallOptions) error {
	if strings.TrimSpace(options.AppURL) == "" {
		return errors.New("--app-url is required")
	}
	if options.ServerID <= 0 {
		return errors.New("--server-id is required")
	}
	if strings.TrimSpace(options.Token) == "" {
		return errors.New("--token is required")
	}
	if strings.TrimSpace(options.ConfigPath) == "" {
		return errors.New("--config is required")
	}
	if strings.TrimSpace(options.ServicePath) == "" {
		return errors.New("--service is required")
	}
	if strings.TrimSpace(options.BinaryPath) == "" {
		return errors.New("--binary is required")
	}
	if options.PollIntervalSeconds <= 0 {
		return errors.New("--poll-interval must be greater than zero")
	}
	if options.MetricsIntervalSeconds <= 0 {
		return errors.New("--metrics-interval must be greater than zero")
	}

	return nil
}

func writeInstallFiles(options InstallOptions) error {
	if err := os.MkdirAll(filepath.Dir(options.ConfigPath), 0o700); err != nil {
		return err
	}
	if err := os.MkdirAll(filepath.Dir(options.ServicePath), 0o755); err != nil {
		return err
	}

	configContent, err := json.MarshalIndent(fileConfig{
		AppURL:                 strings.TrimRight(options.AppURL, "/"),
		ServerID:               options.ServerID,
		Token:                  options.Token,
		PollIntervalSeconds:    options.PollIntervalSeconds,
		MetricsIntervalSeconds: options.MetricsIntervalSeconds,
	}, "", "  ")
	if err != nil {
		return err
	}

	if err := os.WriteFile(options.ConfigPath, append(configContent, '\n'), 0o600); err != nil {
		return err
	}

	return os.WriteFile(options.ServicePath, []byte(systemdService(options)), 0o644)
}

func systemdService(options InstallOptions) string {
	userLine := ""
	if options.ServiceUser != "" && options.ServiceUser != "root" {
		userLine = "User=" + options.ServiceUser + "\n"
	}

	return "[Unit]\n" +
		"Description=Smuze Agent\n" +
		"After=network-online.target\n" +
		"Wants=network-online.target\n\n" +
		"[Service]\n" +
		userLine +
		"ExecStart=" + options.BinaryPath + " --config " + options.ConfigPath + "\n" +
		"Restart=always\n" +
		"RestartSec=5\n\n" +
		"[Install]\n" +
		"WantedBy=multi-user.target\n"
}
