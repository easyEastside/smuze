package main

import (
	"context"
	"encoding/json"
	"fmt"
	"io"
	"net/http"
	"strconv"
	"time"
)

type Client struct {
	config     Config
	configPath string
	http       *http.Client
}

func NewClient(config Config, configPath string) *Client {
	return &Client{
		config:     config,
		configPath: configPath,
		http: &http.Client{
			Timeout: 15 * time.Second,
		},
	}
}

func (c *Client) CheckForUpdate(ctx context.Context) (*UpdateInfo, error) {
	var response struct {
		Update *UpdateInfo `json:"update"`
	}

	if err := c.get(ctx, "/api/agent/update-check", &response); err != nil {
		return nil, err
	}

	return response.Update, nil
}

func (c *Client) get(ctx context.Context, path string, target any) error {
	req, err := http.NewRequestWithContext(ctx, http.MethodGet, c.config.AppURL+path, nil)
	if err != nil {
		return err
	}

	c.authorize(req)

	res, err := c.http.Do(req)
	if err != nil {
		return err
	}
	defer res.Body.Close()

	if res.StatusCode < 200 || res.StatusCode >= 300 {
		body, _ := io.ReadAll(io.LimitReader(res.Body, 4096))
		return fmt.Errorf("GET %s failed with %d: %s", path, res.StatusCode, string(body))
	}

	return json.NewDecoder(res.Body).Decode(target)
}

func (c *Client) authorize(req *http.Request) {
	req.Header.Set("Authorization", "Bearer "+c.config.Token)
	req.Header.Set("X-Smuze-Server-Id", strconv.FormatInt(c.config.ServerID, 10))
}
