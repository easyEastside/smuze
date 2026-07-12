package main

import (
	"bytes"
	"context"
	"encoding/json"
	"fmt"
	"io"
	"net/http"
	"strconv"
	"time"
)

type Client struct {
	config Config
	http   *http.Client
}

type Command struct {
	ID      int64  `json:"id"`
	UUID    string `json:"uuid"`
	Command string `json:"command"`
	UseSudo bool   `json:"use_sudo"`
	Timeout int    `json:"timeout"`
}

type pendingResponse struct {
	Commands []Command `json:"commands"`
}

func NewClient(config Config) *Client {
	return &Client{
		config: config,
		http: &http.Client{
			Timeout: 15 * time.Second,
		},
	}
}

func (c *Client) Heartbeat(ctx context.Context, version string) error {
	return c.post(ctx, "/api/agent/heartbeat", map[string]any{"version": version}, nil)
}

func (c *Client) Metrics(ctx context.Context, metrics map[string]any, collectedAt time.Time) error {
	payload := map[string]any{
		"metrics":      metrics,
		"collected_at": collectedAt.Format(time.RFC3339),
	}

	return c.post(ctx, "/api/agent/metrics", payload, nil)
}

func (c *Client) PendingCommands(ctx context.Context, limit int) ([]Command, error) {
	if limit <= 0 {
		limit = 1
	}

	var response pendingResponse
	path := fmt.Sprintf("/api/agent/commands/pending?limit=%d", limit)
	if err := c.get(ctx, path, &response); err != nil {
		return nil, err
	}

	return response.Commands, nil
}

func (c *Client) CommandOutput(ctx context.Context, commandID int64, stream string, data string) error {
	path := fmt.Sprintf("/api/agent/commands/%d/output", commandID)
	payload := map[string]any{
		"stream": stream,
		"data":   data,
	}

	return c.post(ctx, path, payload, nil)
}

func (c *Client) CompleteCommand(ctx context.Context, commandID int64, status string, exitCode int, stdout string, stderr string) error {
	path := fmt.Sprintf("/api/agent/commands/%d/complete", commandID)
	payload := map[string]any{
		"status":    status,
		"exit_code": exitCode,
		"stdout":    stdout,
		"stderr":    stderr,
	}

	return c.post(ctx, path, payload, nil)
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

func (c *Client) post(ctx context.Context, path string, payload any, target any) error {
	body, err := json.Marshal(payload)
	if err != nil {
		return err
	}

	req, err := http.NewRequestWithContext(ctx, http.MethodPost, c.config.AppURL+path, bytes.NewReader(body))
	if err != nil {
		return err
	}
	c.authorize(req)
	req.Header.Set("Content-Type", "application/json")
	req.Header.Set("Accept", "application/json")

	res, err := c.http.Do(req)
	if err != nil {
		return err
	}
	defer res.Body.Close()

	if res.StatusCode < 200 || res.StatusCode >= 300 {
		body, _ := io.ReadAll(io.LimitReader(res.Body, 4096))
		return fmt.Errorf("POST %s failed with %d: %s", path, res.StatusCode, string(body))
	}

	if target == nil {
		io.Copy(io.Discard, res.Body)
		return nil
	}

	return json.NewDecoder(res.Body).Decode(target)
}

func (c *Client) authorize(req *http.Request) {
	req.Header.Set("Authorization", "Bearer "+c.config.Token)
	req.Header.Set("X-Smuze-Server-Id", strconv.FormatInt(c.config.ServerID, 10))
}
