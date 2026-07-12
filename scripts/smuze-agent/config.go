package main

import (
	"encoding/json"
	"errors"
	"fmt"
	"os"
	"strconv"
	"strings"
)

type Config struct {
	AppURL   string `json:"app_url"`
	ServerID int64  `json:"server_id"`
	Token    string `json:"token"`
	Port     int    `json:"port"`
}

type fileConfig struct {
	AppURL   string `json:"app_url"`
	ServerID int64  `json:"server_id"`
	Token    string `json:"token"`
	Port     int    `json:"port"`
}

func loadConfig(path string) (Config, error) {
	port := intEnv("SMUZE_AGENT_PORT", 9300)

	cfg := Config{
		AppURL: os.Getenv("SMUZE_APP_URL"),
		Token:  os.Getenv("SMUZE_AGENT_TOKEN"),
		Port:   port,
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
		if fileCfg.Port > 0 {
			cfg.Port = fileCfg.Port
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

func intEnv(key string, fallback int) int {
	value := os.Getenv(key)
	if value == "" {
		return fallback
	}

	n, err := strconv.Atoi(value)
	if err != nil || n <= 0 {
		return fallback
	}

	return n
}
