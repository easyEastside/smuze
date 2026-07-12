package main

import (
	"crypto/subtle"
	"net/http"
)

func authMiddleware(token string, next http.HandlerFunc) http.HandlerFunc {
	return func(w http.ResponseWriter, r *http.Request) {
		got := r.Header.Get("Authorization")

		if len(got) < 7 || got[:7] != "Bearer " || subtle.ConstantTimeCompare([]byte(got[7:]), []byte(token)) != 1 {
			http.Error(w, `{"error":"unauthorized"}`, http.StatusForbidden)

			return
		}

		next(w, r)
	}
}
