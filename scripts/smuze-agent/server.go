package main

import (
	"context"
	"encoding/json"
	"fmt"
	"io"
	"net/http"
	"os"
	"os/exec"
	"sync"
	"time"
)

const (
	maxExecuteBodyBytes = 64 * 1024
	maxCommandBytes     = 5000
	maxCommandTimeout   = 3600
)

type Server struct {
	config  Config
	started time.Time
	mu      sync.Mutex
	running *exec.Cmd
}

type executeRequest struct {
	Command string `json:"command"`
	Timeout int    `json:"timeout"`
	UseSudo bool   `json:"use_sudo"`
}

type streamChunk struct {
	Stream string `json:"stream,omitempty"`
	Data   string `json:"data,omitempty"`
	Done   bool   `json:"done,omitempty"`
	Exit   *int   `json:"exit_code,omitempty"`
	Error  string `json:"error,omitempty"`
}

func NewServer(config Config) *Server {
	return &Server{
		config:  config,
		started: time.Now(),
	}
}

func (s *Server) Handler() http.Handler {
	mux := http.NewServeMux()
	mux.HandleFunc("/execute", authMiddleware(s.config.Token, s.handleExecute))
	mux.HandleFunc("/actions", authMiddleware(s.config.Token, s.handleActions))
	mux.HandleFunc("/health", authMiddleware(s.config.Token, s.handleHealth))
	mux.HandleFunc("/metrics", authMiddleware(s.config.Token, s.handleMetrics))
	mux.HandleFunc("/cancel", authMiddleware(s.config.Token, s.handleCancel))
	mux.HandleFunc("/update", authMiddleware(s.config.Token, s.handleUpdate))

	return mux
}

func (s *Server) writeChunk(w io.Writer, chunk streamChunk) {
	data, _ := json.Marshal(chunk)
	fmt.Fprintf(w, "%s\n", data)

	if flusher, ok := w.(http.Flusher); ok {
		flusher.Flush()
	}
}

func (s *Server) handleExecute(w http.ResponseWriter, r *http.Request) {
	if r.Method != http.MethodPost {
		http.Error(w, `{"error":"method not allowed"}`, http.StatusMethodNotAllowed)
		return
	}

	var req executeRequest
	r.Body = http.MaxBytesReader(w, r.Body, maxExecuteBodyBytes)
	if err := json.NewDecoder(r.Body).Decode(&req); err != nil {
		http.Error(w, fmt.Sprintf(`{"error":"invalid request: %s"}`, err), http.StatusBadRequest)
		return
	}

	if len(req.Command) > maxCommandBytes {
		http.Error(w, `{"error":"command too large"}`, http.StatusBadRequest)
		return
	}

	w.Header().Set("Content-Type", "application/json")
	w.WriteHeader(http.StatusOK)

	timeout := req.Timeout
	if timeout <= 0 {
		timeout = 30
	}
	if timeout > maxCommandTimeout {
		timeout = maxCommandTimeout
	}

	commandCtx, cancel := context.WithTimeout(r.Context(), time.Duration(timeout)*time.Second)
	defer cancel()

	localCommand := req.Command
	if req.UseSudo {
		localCommand = applySudo(req.Command)
	}

	cmd := exec.CommandContext(commandCtx, "sh", "-lc", localCommand)

	stdoutPipe, err := cmd.StdoutPipe()
	if err != nil {
		s.writeChunk(w, streamChunk{Error: err.Error(), Done: true})
		return
	}

	stderrPipe, err := cmd.StderrPipe()
	if err != nil {
		s.writeChunk(w, streamChunk{Error: err.Error(), Done: true})
		return
	}

	s.mu.Lock()
	s.running = cmd
	s.mu.Unlock()

	defer func() {
		s.mu.Lock()
		s.running = nil
		s.mu.Unlock()
	}()

	if err := cmd.Start(); err != nil {
		s.writeChunk(w, streamChunk{Error: err.Error(), Done: true})
		return
	}

	var wg sync.WaitGroup
	wg.Add(2)

	go func() {
		defer wg.Done()
		buf := make([]byte, 4096)
		for {
			n, err := stdoutPipe.Read(buf)
			if n > 0 {
				s.writeChunk(w, streamChunk{Stream: "stdout", Data: string(buf[:n])})
			}
			if err != nil {
				return
			}
		}
	}()

	go func() {
		defer wg.Done()
		buf := make([]byte, 4096)
		for {
			n, err := stderrPipe.Read(buf)
			if n > 0 {
				s.writeChunk(w, streamChunk{Stream: "stderr", Data: string(buf[:n])})
			}
			if err != nil {
				return
			}
		}
	}()

	err = cmd.Wait()
	wg.Wait()

	exitCode := 0
	if err != nil {
		exitCode = -1
		if exitErr, ok := err.(*exec.ExitError); ok {
			exitCode = exitErr.ExitCode()
		}
	}

	if commandCtx.Err() == context.DeadlineExceeded {
		exit := -1
		s.writeChunk(w, streamChunk{Exit: &exit, Done: true, Error: "Command timed out"})
		return
	}

	s.writeChunk(w, streamChunk{Exit: &exitCode, Done: true})
}

func (s *Server) handleHealth(w http.ResponseWriter, r *http.Request) {
	if r.Method != http.MethodGet {
		http.Error(w, `{"error":"method not allowed"}`, http.StatusMethodNotAllowed)
		return
	}

	uptime := time.Since(s.started).Round(time.Second).String()

	w.Header().Set("Content-Type", "application/json")
	json.NewEncoder(w).Encode(map[string]any{
		"status":  "ok",
		"version": version,
		"uptime":  uptime,
	})
}

func (s *Server) handleMetrics(w http.ResponseWriter, r *http.Request) {
	if r.Method != http.MethodGet {
		http.Error(w, `{"error":"method not allowed"}`, http.StatusMethodNotAllowed)
		return
	}

	w.Header().Set("Content-Type", "application/json")
	json.NewEncoder(w).Encode(collectMetrics())
}

func (s *Server) handleCancel(w http.ResponseWriter, r *http.Request) {
	if r.Method != http.MethodPost {
		http.Error(w, `{"error":"method not allowed"}`, http.StatusMethodNotAllowed)
		return
	}

	s.mu.Lock()
	cmd := s.running
	s.mu.Unlock()

	if cmd != nil && cmd.Process != nil {
		cmd.Process.Kill()
	}

	w.Header().Set("Content-Type", "application/json")
	json.NewEncoder(w).Encode(map[string]any{"success": true})
}

func (s *Server) handleUpdate(w http.ResponseWriter, r *http.Request) {
	if r.Method != http.MethodPost {
		http.Error(w, `{"error":"method not allowed"}`, http.StatusMethodNotAllowed)
		return
	}

	var info UpdateInfo
	r.Body = http.MaxBytesReader(w, r.Body, maxActionBodyBytes)
	if err := json.NewDecoder(r.Body).Decode(&info); err != nil {
		http.Error(w, `{"error":"invalid request"}`, http.StatusBadRequest)
		return
	}

	if info.DownloadURL == "" {
		http.Error(w, `{"error":"download_url is required"}`, http.StatusBadRequest)
		return
	}

	if info.LatestVersion != "" && !isNewerVersion(info.LatestVersion, version) {
		w.Header().Set("Content-Type", "application/json")
		json.NewEncoder(w).Encode(map[string]any{"success": true, "message": "Agent is already up to date"})
		return
	}

	w.Header().Set("Content-Type", "application/json")
	w.WriteHeader(http.StatusOK)
	json.NewEncoder(w).Encode(map[string]any{"success": true, "message": "Update started"})

	go func() {
		if err := PerformUpdate(context.Background(), &info, version); err != nil {
			fmt.Fprintf(os.Stderr, "update failed: %v\n", err)
		}
	}()
}
