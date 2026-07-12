package main

import (
	"encoding/json"
	"errors"
	"fmt"
	"os"
	"strconv"
	"strings"
	"time"
)

type Config struct {
	AppURL          string        `json:"app_url"`
	ServerID        int64         `json:"server_id"`
	Token           string        `json:"token"`
	PollInterval    time.Duration `json:"-"`
	MetricsInterval time.Duration `json:"-"`
}

type fileConfig struct {
	AppURL                 string `json:"app_url"`
	ServerID               int64  `json:"server_id"`
	Token                  string `json:"token"`
	PollIntervalSeconds    int    `json:"poll_interval_seconds"`
	MetricsIntervalSeconds int    `json:"metrics_interval_seconds"`
}

func loadConfig(path string) (Config, error) {
	cfg := Config{
		AppURL:          os.Getenv("SMUZE_APP_URL"),
		Token:           os.Getenv("SMUZE_AGENT_TOKEN"),
		PollInterval:    durationFromEnv("SMUZE_POLL_INTERVAL_SECONDS", 2*time.Second),
		MetricsInterval: durationFromEnv("SMUZE_METRICS_INTERVAL_SECONDS", 10*time.Second),
	}

	if value := os.Getenv("SMUZE_SERVER_ID"); value != "" {
		serverID, err := strconv.ParseInt(value, 10, 64)
		if err != nil {
			return Config{}, fmt.Errorf("invalid SMUZE_SERVER_ID: %w", err)
		}
		cfg.ServerID = serverID
	}

	if path != "" {
		fileCfg, err := loadFileConfig(path)
		if err != nil {
			return Config{}, err
		}

		if fileCfg.AppURL != "" {
			cfg.AppURL = fileCfg.AppURL
		}
		if fileCfg.ServerID > 0 {
			cfg.ServerID = fileCfg.ServerID
		}
		if fileCfg.Token != "" {
			cfg.Token = fileCfg.Token
		}
		if fileCfg.PollIntervalSeconds > 0 {
			cfg.PollInterval = time.Duration(fileCfg.PollIntervalSeconds) * time.Second
		}
		if fileCfg.MetricsIntervalSeconds > 0 {
			cfg.MetricsInterval = time.Duration(fileCfg.MetricsIntervalSeconds) * time.Second
		}
	}

	cfg.AppURL = strings.TrimRight(cfg.AppURL, "/")

	if cfg.AppURL == "" {
		return Config{}, errors.New("SMUZE_APP_URL or config app_url is required")
	}
	if cfg.ServerID <= 0 {
		return Config{}, errors.New("SMUZE_SERVER_ID or config server_id is required")
	}
	if cfg.Token == "" {
		return Config{}, errors.New("SMUZE_AGENT_TOKEN or config token is required")
	}

	return cfg, nil
}

func loadFileConfig(path string) (fileConfig, error) {
	content, err := os.ReadFile(path)
	if err != nil {
		return fileConfig{}, err
	}

	var cfg fileConfig
	if err := json.Unmarshal(content, &cfg); err != nil {
		return fileConfig{}, err
	}

	return cfg, nil
}

func durationFromEnv(key string, fallback time.Duration) time.Duration {
	value := os.Getenv(key)
	if value == "" {
		return fallback
	}

	seconds, err := strconv.Atoi(value)
	if err != nil || seconds <= 0 {
		return fallback
	}

	return time.Duration(seconds) * time.Second
}
