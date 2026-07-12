package main

import (
	"encoding/json"
	"fmt"
	"io"
	"net/http"
	"net/http/httptest"
	"strings"
	"testing"
)

func newTestServer(t *testing.T) *httptest.Server {
	t.Helper()
	srv := NewServer(Config{Token: "test-token", Port: 9300})
	return httptest.NewServer(srv.Handler())
}

func authHeader() (string, string) {
	return "Authorization", "Bearer test-token"
}

func TestHealthEndpoint(t *testing.T) {
	ts := newTestServer(t)
	defer ts.Close()

	res, err := http.Get(ts.URL + "/health")
	if err != nil {
		t.Fatalf("GET /health: %v", err)
	}
	defer res.Body.Close()

	if res.StatusCode != 403 {
		t.Fatalf("expected 403 without auth, got %d", res.StatusCode)
	}
}

func TestHealthEndpointWithAuth(t *testing.T) {
	ts := newTestServer(t)
	defer ts.Close()

	req, _ := http.NewRequest("GET", ts.URL+"/health", nil)
	req.Header.Set(authHeader())

	res, err := http.DefaultClient.Do(req)
	if err != nil {
		t.Fatalf("GET /health: %v", err)
	}
	defer res.Body.Close()

	if res.StatusCode != 200 {
		t.Fatalf("expected 200, got %d", res.StatusCode)
	}

	var body map[string]any
	if err := json.NewDecoder(res.Body).Decode(&body); err != nil {
		t.Fatalf("decode: %v", err)
	}

	if body["status"] != "ok" {
		t.Fatalf("unexpected status: %v", body["status"])
	}
	if body["version"] != version {
		t.Fatalf("unexpected version: %v", body["version"])
	}
}

func TestMetricsEndpoint(t *testing.T) {
	ts := newTestServer(t)
	defer ts.Close()

	req, _ := http.NewRequest("GET", ts.URL+"/metrics", nil)
	req.Header.Set(authHeader())

	res, err := http.DefaultClient.Do(req)
	if err != nil {
		t.Fatalf("GET /metrics: %v", err)
	}
	defer res.Body.Close()

	if res.StatusCode != 200 {
		t.Fatalf("expected 200, got %d", res.StatusCode)
	}

	var body map[string]any
	if err := json.NewDecoder(res.Body).Decode(&body); err != nil {
		t.Fatalf("decode: %v", err)
	}

	if body["hostname"] == nil {
		t.Fatal("expected hostname in metrics")
	}
}

func TestCancelEndpoint(t *testing.T) {
	ts := newTestServer(t)
	defer ts.Close()

	req, _ := http.NewRequest("POST", ts.URL+"/cancel", nil)
	req.Header.Set(authHeader())

	res, err := http.DefaultClient.Do(req)
	if err != nil {
		t.Fatalf("POST /cancel: %v", err)
	}
	defer res.Body.Close()

	if res.StatusCode != 200 {
		t.Fatalf("expected 200, got %d", res.StatusCode)
	}

	var body map[string]any
	if err := json.NewDecoder(res.Body).Decode(&body); err != nil {
		t.Fatalf("decode: %v", err)
	}

	if body["success"] != true {
		t.Fatal("expected success")
	}
}

func TestExecuteEndpoint(t *testing.T) {
	ts := newTestServer(t)
	defer ts.Close()

	body := `{"command":"echo hello","timeout":10,"use_sudo":false}`
	req, _ := http.NewRequest("POST", ts.URL+"/execute", strings.NewReader(body))
	req.Header.Set(authHeader())
	req.Header.Set("Content-Type", "application/json")

	res, err := http.DefaultClient.Do(req)
	if err != nil {
		t.Fatalf("POST /execute: %v", err)
	}
	defer res.Body.Close()

	if res.StatusCode != 200 {
		t.Fatalf("expected 200, got %d", res.StatusCode)
	}

	respBody, _ := io.ReadAll(res.Body)
	lines := strings.Split(strings.TrimSpace(string(respBody)), "\n")

	var lastChunk map[string]any
	for _, line := range lines {
		json.Unmarshal([]byte(line), &lastChunk)
	}

	if lastChunk["done"] != true {
		t.Fatal("expected done=true in last chunk")
	}
	if lastChunk["exit_code"] != float64(0) {
		t.Fatalf("expected exit_code=0, got %v", lastChunk["exit_code"])
	}
}

func TestExecuteEndpointStderr(t *testing.T) {
	ts := newTestServer(t)
	defer ts.Close()

	body := `{"command":"echo stderr message >&2","timeout":10,"use_sudo":false}`
	req, _ := http.NewRequest("POST", ts.URL+"/execute", strings.NewReader(body))
	req.Header.Set(authHeader())
	req.Header.Set("Content-Type", "application/json")

	res, err := http.DefaultClient.Do(req)
	if err != nil {
		t.Fatalf("POST /execute: %v", err)
	}
	defer res.Body.Close()

	respBody, _ := io.ReadAll(res.Body)

	if !strings.Contains(string(respBody), "stderr message") {
		t.Fatal("expected stderr message in response")
	}
}

func TestExecuteEndpointFailsOnNonPost(t *testing.T) {
	ts := newTestServer(t)
	defer ts.Close()

	req, _ := http.NewRequest("GET", ts.URL+"/execute", nil)
	req.Header.Set(authHeader())

	res, _ := http.DefaultClient.Do(req)
	if res.StatusCode != 405 {
		t.Fatalf("expected 405, got %d", res.StatusCode)
	}
}

func TestUpdateEndpoint(t *testing.T) {
	ts := newTestServer(t)
	defer ts.Close()

	req, _ := http.NewRequest("POST", ts.URL+"/update", nil)
	req.Header.Set(authHeader())

	res, err := http.DefaultClient.Do(req)
	if err != nil {
		t.Fatalf("POST /update: %v", err)
	}
	defer res.Body.Close()

	if res.StatusCode != 200 {
		t.Fatalf("expected 200, got %d", res.StatusCode)
	}

	var body map[string]any
	if err := json.NewDecoder(res.Body).Decode(&body); err != nil {
		t.Fatalf("decode: %v", err)
	}

	if body["success"] != true {
		t.Fatal("expected success")
	}
}

func TestAuthRejectsWrongToken(t *testing.T) {
	ts := newTestServer(t)
	defer ts.Close()

	req, _ := http.NewRequest("GET", ts.URL+"/health", nil)
	req.Header.Set("Authorization", "Bearer wrong-token")

	res, _ := http.DefaultClient.Do(req)
	if res.StatusCode != 403 {
		t.Fatalf("expected 403, got %d", res.StatusCode)
	}
}

func TestAuthRejectsEmptyConfiguredToken(t *testing.T) {
	srv := NewServer(Config{Token: "", Port: 9300})
	ts := httptest.NewServer(srv.Handler())
	defer ts.Close()

	req, _ := http.NewRequest("GET", ts.URL+"/health", nil)
	req.Header.Set("Authorization", "Bearer ")

	res, _ := http.DefaultClient.Do(req)
	if res.StatusCode != 403 {
		t.Fatalf("expected 403, got %d", res.StatusCode)
	}
}

func TestExecuteRejectsLargeCommand(t *testing.T) {
	ts := newTestServer(t)
	defer ts.Close()

	body := fmt.Sprintf(`{"command":"%s","timeout":10,"use_sudo":false}`, strings.Repeat("a", maxCommandBytes+1))
	req, _ := http.NewRequest("POST", ts.URL+"/execute", strings.NewReader(body))
	req.Header.Set(authHeader())
	req.Header.Set("Content-Type", "application/json")

	res, err := http.DefaultClient.Do(req)
	if err != nil {
		t.Fatalf("POST /execute: %v", err)
	}
	defer res.Body.Close()

	if res.StatusCode != 400 {
		t.Fatalf("expected 400, got %d", res.StatusCode)
	}
}

func TestExecuteRejectsOversizedBody(t *testing.T) {
	ts := newTestServer(t)
	defer ts.Close()

	body := `{"command":"` + strings.Repeat("a", maxExecuteBodyBytes) + `"}`
	req, _ := http.NewRequest("POST", ts.URL+"/execute", strings.NewReader(body))
	req.Header.Set(authHeader())
	req.Header.Set("Content-Type", "application/json")

	res, err := http.DefaultClient.Do(req)
	if err != nil {
		t.Fatalf("POST /execute: %v", err)
	}
	defer res.Body.Close()

	if res.StatusCode != 400 {
		t.Fatalf("expected 400, got %d", res.StatusCode)
	}
}

func TestExecuteWithSudo(t *testing.T) {
	ts := newTestServer(t)
	defer ts.Close()

	body := fmt.Sprintf(`{"command":"echo %s","timeout":10,"use_sudo":true}`, "sudo-test")
	req, _ := http.NewRequest("POST", ts.URL+"/execute", strings.NewReader(body))
	req.Header.Set(authHeader())
	req.Header.Set("Content-Type", "application/json")

	res, err := http.DefaultClient.Do(req)
	if err != nil {
		t.Fatalf("POST /execute: %v", err)
	}
	defer res.Body.Close()

	if res.StatusCode != 200 {
		t.Fatalf("expected 200, got %d", res.StatusCode)
	}

	respBody, _ := io.ReadAll(res.Body)
	if !strings.Contains(string(respBody), `"done":true`) {
		t.Fatal("expected done chunk in response")
	}
}
