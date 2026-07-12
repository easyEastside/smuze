package main

import (
	"bytes"
	"context"
	"encoding/json"
	"errors"
	"net/http"
	"os/exec"
	"time"
)

const maxActionBodyBytes = 16 * 1024

type actionRequest struct {
	Action  string         `json:"action"`
	Payload map[string]any `json:"payload"`
}

type actionDefinition struct {
	Name    string
	Command string
	Timeout int
	UseSudo bool
}

type actionResponse struct {
	Success    bool   `json:"success"`
	Action     string `json:"action"`
	ExitCode   int    `json:"exit_code"`
	Stdout     string `json:"stdout"`
	Stderr     string `json:"stderr"`
	DurationMs int64  `json:"duration_ms"`
	Error      string `json:"error,omitempty"`
}

func (s *Server) handleActions(w http.ResponseWriter, r *http.Request) {
	if r.Method != http.MethodPost {
		http.Error(w, `{"error":"method not allowed"}`, http.StatusMethodNotAllowed)
		return
	}

	var req actionRequest
	r.Body = http.MaxBytesReader(w, r.Body, maxActionBodyBytes)
	if err := json.NewDecoder(r.Body).Decode(&req); err != nil {
		http.Error(w, `{"error":"invalid request"}`, http.StatusBadRequest)
		return
	}

	definition, ok := systemActions[req.Action]
	if !ok {
		http.Error(w, `{"error":"unknown action"}`, http.StatusNotFound)
		return
	}

	response := s.runAction(r.Context(), req.Action, definition)

	w.Header().Set("Content-Type", "application/json")
	status := http.StatusOK
	if !response.Success {
		status = http.StatusUnprocessableEntity
	}
	w.WriteHeader(status)
	json.NewEncoder(w).Encode(response)
}

func registerActions(actions ...actionDefinition) map[string]actionDefinition {
	registered := make(map[string]actionDefinition, len(actions))

	for _, action := range actions {
		registered[action.Name] = action
	}

	return registered
}

func (s *Server) runAction(ctx context.Context, action string, definition actionDefinition) actionResponse {
	started := time.Now()
	timeout := definition.Timeout
	if timeout <= 0 {
		timeout = 30
	}
	if timeout > maxCommandTimeout {
		timeout = maxCommandTimeout
	}

	commandCtx, cancel := context.WithTimeout(ctx, time.Duration(timeout)*time.Second)
	defer cancel()

	command := definition.Command
	if definition.UseSudo {
		command = applySudo(command)
	}

	cmd := exec.CommandContext(commandCtx, "sh", "-lc", command)
	stdout, stderr, exitCode, err := runCommand(cmd)

	response := actionResponse{
		Success:    exitCode == 0 && err == nil,
		Action:     action,
		ExitCode:   exitCode,
		Stdout:     stdout,
		Stderr:     stderr,
		DurationMs: time.Since(started).Milliseconds(),
	}

	if commandCtx.Err() == context.DeadlineExceeded {
		response.Success = false
		response.ExitCode = -1
		response.Error = "action timed out"
		return response
	}

	if err != nil {
		response.Error = err.Error()
	}

	return response
}

func runCommand(cmd *exec.Cmd) (string, string, int, error) {
	var stdout bytes.Buffer
	var stderr bytes.Buffer
	cmd.Stdout = &stdout
	cmd.Stderr = &stderr

	waitErr := cmd.Run()

	exitCode := 0
	if waitErr != nil {
		exitCode = -1
		var exitErr *exec.ExitError
		if errors.As(waitErr, &exitErr) {
			exitCode = exitErr.ExitCode()
		}
	}

	return stdout.String(), stderr.String(), exitCode, waitErr
}
