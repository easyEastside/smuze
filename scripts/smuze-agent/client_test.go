package main

import (
	"context"
	"encoding/json"
	"net/http"
	"net/http/httptest"
	"strconv"
	"testing"
	"time"
)

func TestClientSendsAuthHeadersAndFetchesPendingCommands(t *testing.T) {
	server := httptest.NewServer(http.HandlerFunc(func(w http.ResponseWriter, r *http.Request) {
		if r.Header.Get("Authorization") != "Bearer token" {
			t.Fatalf("missing auth header: %s", r.Header.Get("Authorization"))
		}
		if r.Header.Get("X-Smuze-Server-Id") != "42" {
			t.Fatalf("missing server header: %s", r.Header.Get("X-Smuze-Server-Id"))
		}
		if r.URL.Path != "/api/agent/commands/pending" {
			t.Fatalf("unexpected path: %s", r.URL.Path)
		}
		if r.URL.Query().Get("limit") != "2" {
			t.Fatalf("unexpected limit: %s", r.URL.Query().Get("limit"))
		}

		json.NewEncoder(w).Encode(map[string]any{
			"commands": []map[string]any{{
				"id":       5,
				"uuid":     "uuid-1",
				"command":  "echo OK",
				"use_sudo": true,
				"timeout":  30,
			}},
		})
	}))
	defer server.Close()

	client := NewClient(Config{AppURL: server.URL, ServerID: 42, Token: "token"}, "")
	commands, err := client.PendingCommands(context.Background(), 2)
	if err != nil {
		t.Fatalf("PendingCommands returned error: %v", err)
	}

	if len(commands) != 1 || commands[0].ID != 5 || commands[0].Command != "echo OK" || !commands[0].UseSudo {
		t.Fatalf("unexpected commands: %+v", commands)
	}
}

func TestClientPostsCommandCompletion(t *testing.T) {
	server := httptest.NewServer(http.HandlerFunc(func(w http.ResponseWriter, r *http.Request) {
		if r.URL.Path != "/api/agent/commands/7/complete" {
			t.Fatalf("unexpected path: %s", r.URL.Path)
		}

		var payload map[string]any
		if err := json.NewDecoder(r.Body).Decode(&payload); err != nil {
			t.Fatalf("decode payload: %v", err)
		}

		if payload["status"] != "completed" || int(payload["exit_code"].(float64)) != 0 || payload["stdout"] != "OK" {
			t.Fatalf("unexpected payload: %+v", payload)
		}

		w.WriteHeader(http.StatusOK)
	}))
	defer server.Close()

	client := NewClient(Config{AppURL: server.URL, ServerID: 42, Token: "token"}, "")
	if err := client.CompleteCommand(context.Background(), 7, "completed", 0, "OK", ""); err != nil {
		t.Fatalf("CompleteCommand returned error: %v", err)
	}
}

func TestClientPostsMetrics(t *testing.T) {
	server := httptest.NewServer(http.HandlerFunc(func(w http.ResponseWriter, r *http.Request) {
		if r.URL.Path != "/api/agent/metrics" {
			t.Fatalf("unexpected path: %s", r.URL.Path)
		}

		var payload map[string]any
		if err := json.NewDecoder(r.Body).Decode(&payload); err != nil {
			t.Fatalf("decode payload: %v", err)
		}

		metrics := payload["metrics"].(map[string]any)
		if strconv.Itoa(int(metrics["cpu_percent"].(float64))) != "12" {
			t.Fatalf("unexpected metrics: %+v", metrics)
		}

		w.WriteHeader(http.StatusOK)
	}))
	defer server.Close()

	client := NewClient(Config{AppURL: server.URL, ServerID: 42, Token: "token"}, "")
	if err := client.Metrics(context.Background(), map[string]any{"cpu_percent": 12}, time.Now()); err != nil {
		t.Fatalf("Metrics returned error: %v", err)
	}
}
