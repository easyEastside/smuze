package main

import (
	"context"
	"crypto/hmac"
	"crypto/sha256"
	"encoding/base64"
	"encoding/json"
	"errors"
	"io"
	"net/http"
	"net/url"
	"os"
	"os/exec"
	"strings"
	"time"

	"github.com/creack/pty"
	"github.com/gorilla/websocket"
)

const (
	terminalPurpose     = "terminal"
	terminalDefaultCols = 120
	terminalDefaultRows = 34
)

type terminalTokenPayload struct {
	ServerID int64  `json:"server_id"`
	Exp      int64  `json:"exp"`
	Purpose  string `json:"purpose"`
}

type terminalMessage struct {
	Type string `json:"type"`
	Data string `json:"data,omitempty"`
	Cols uint16 `json:"cols,omitempty"`
	Rows uint16 `json:"rows,omitempty"`
}

func (s *Server) handleTerminal(w http.ResponseWriter, r *http.Request) {
	if r.Method != http.MethodGet {
		http.Error(w, `{"error":"method not allowed"}`, http.StatusMethodNotAllowed)
		return
	}

	if err := s.validateTerminalToken(r.URL.Query().Get("token")); err != nil {
		http.Error(w, `{"error":"forbidden"}`, http.StatusForbidden)
		return
	}

	upgrader := websocket.Upgrader{
		CheckOrigin: func(req *http.Request) bool {
			return s.isAllowedTerminalOrigin(req.Header.Get("Origin"))
		},
	}

	conn, err := upgrader.Upgrade(w, r, nil)
	if err != nil {
		return
	}
	defer conn.Close()

	ctx, cancel := context.WithCancel(r.Context())
	defer cancel()

	shell := terminalShell()
	cmd := exec.CommandContext(ctx, shell)
	terminal, err := pty.StartWithSize(cmd, &pty.Winsize{Cols: terminalDefaultCols, Rows: terminalDefaultRows})
	if err != nil {
		_ = conn.WriteJSON(terminalMessage{Type: "error", Data: err.Error()})
		return
	}
	defer terminal.Close()
	defer cmd.Process.Kill()

	done := make(chan struct{})

	go func() {
		defer close(done)
		buf := make([]byte, 4096)
		for {
			n, err := terminal.Read(buf)
			if n > 0 {
				if writeErr := conn.WriteJSON(terminalMessage{Type: "output", Data: string(buf[:n])}); writeErr != nil {
					cancel()
					return
				}
			}
			if err != nil {
				if !errors.Is(err, io.EOF) {
					_ = conn.WriteJSON(terminalMessage{Type: "error", Data: err.Error()})
				}
				cancel()
				return
			}
		}
	}()

	for {
		select {
		case <-done:
			return
		case <-ctx.Done():
			return
		default:
		}

		var message terminalMessage
		if err := conn.ReadJSON(&message); err != nil {
			cancel()
			return
		}

		switch message.Type {
		case "input":
			_, _ = terminal.Write([]byte(message.Data))
		case "resize":
			if message.Cols > 0 && message.Rows > 0 {
				_ = pty.Setsize(terminal, &pty.Winsize{Cols: message.Cols, Rows: message.Rows})
			}
		case "close":
			cancel()
			return
		}
	}
}

func terminalShell() string {
	if shell := os.Getenv("SHELL"); shell != "" {
		return shell
	}
	if _, err := os.Stat("/bin/bash"); err == nil {
		return "/bin/bash"
	}

	return "/bin/sh"
}

func (s *Server) validateTerminalToken(token string) error {
	parts := strings.Split(token, ".")
	if len(parts) != 2 {
		return errors.New("invalid token")
	}

	payloadBytes, err := base64.RawURLEncoding.DecodeString(parts[0])
	if err != nil {
		return err
	}

	signature, err := base64.RawURLEncoding.DecodeString(parts[1])
	if err != nil {
		return err
	}

	mac := hmac.New(sha256.New, []byte(s.config.Token))
	mac.Write([]byte(parts[0]))
	if !hmac.Equal(signature, mac.Sum(nil)) {
		return errors.New("invalid signature")
	}

	var payload terminalTokenPayload
	if err := json.Unmarshal(payloadBytes, &payload); err != nil {
		return err
	}

	if payload.Purpose != terminalPurpose {
		return errors.New("invalid purpose")
	}
	if payload.ServerID != s.config.ServerID {
		return errors.New("invalid server")
	}
	if payload.Exp < time.Now().Unix() {
		return errors.New("expired token")
	}

	return nil
}

func (s *Server) isAllowedTerminalOrigin(origin string) bool {
	if origin == "" {
		return true
	}

	requestOrigin, err := url.Parse(origin)
	if err != nil {
		return false
	}

	appURL, err := url.Parse(s.config.AppURL)
	if err != nil {
		return false
	}

	return strings.EqualFold(requestOrigin.Scheme, appURL.Scheme) && strings.EqualFold(requestOrigin.Host, appURL.Host)
}
