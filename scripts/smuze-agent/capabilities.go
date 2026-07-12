package main

import (
	"encoding/json"
	"net/http"
	"sort"
)

type capabilitiesResponse struct {
	Version string   `json:"version"`
	Actions []string `json:"actions"`
}

func (s *Server) handleCapabilities(w http.ResponseWriter, r *http.Request) {
	if r.Method != http.MethodGet {
		http.Error(w, `{"error":"method not allowed"}`, http.StatusMethodNotAllowed)
		return
	}

	w.Header().Set("Content-Type", "application/json")
	json.NewEncoder(w).Encode(capabilitiesResponse{
		Version: version,
		Actions: availableActionNames(),
	})
}

func availableActionNames() []string {
	actions := make([]string, 0, len(systemActions))
	for action := range systemActions {
		actions = append(actions, action)
	}
	sort.Strings(actions)

	return actions
}
