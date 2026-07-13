package main

import (
	"errors"
	"net/url"
	"strings"
)

const githubProjectRoot = "/var/www"

func githubDeployAction() actionDefinition {
	return actionDefinition{
		Name: "github.deploy",
		BuildCommand: func(payload map[string]any) (string, error) {
			repoURL, err := githubPayloadRepoURL(payload)
			if err != nil {
				return "", err
			}
			targetName, err := apachePayloadToken(payload, "target_name")
			if err != nil {
				return "", err
			}

			return githubDeployScript(repoURL, targetName), nil
		},
		Timeout: 300,
		UseSudo: true,
	}
}

func githubPayloadRepoURL(payload map[string]any) (string, error) {
	repoURL, err := payloadString(payload, "repo_url")
	if err != nil {
		return "", err
	}
	repoURL = strings.TrimSpace(repoURL)
	if repoURL == "" {
		return "", errors.New("repo_url is required")
	}
	if strings.ContainsAny(repoURL, "\r\n\t\x00") {
		return "", errors.New("repo_url must not contain control characters")
	}

	parsed, err := url.Parse(repoURL)
	if err != nil {
		return "", errors.New("repo_url must be valid")
	}
	if parsed.Scheme != "https" {
		return "", errors.New("repo_url must use https")
	}
	host := strings.ToLower(parsed.Hostname())
	if host != "github.com" && host != "www.github.com" {
		return "", errors.New("repo_url must be from github.com")
	}
	if parsed.RawQuery != "" || parsed.Fragment != "" {
		return "", errors.New("repo_url must not contain query or fragment")
	}
	parts := strings.FieldsFunc(parsed.Path, func(r rune) bool { return r == '/' })
	if len(parts) < 2 {
		return "", errors.New("repo_url must contain owner and repository")
	}

	return repoURL, nil
}

func githubDeployScript(repoURL string, targetName string) string {
	targetPath := githubProjectRoot + "/" + targetName

	lines := []string{
		"set -e",
		"repo_url=" + shellQuote(repoURL),
		"target_path=" + shellQuote(targetPath),
		"",
		`if [ -e "$target_path" ]; then`,
		`    printf "Ziel existiert bereits: %s\n" "$target_path"`,
		"    exit 2",
		"fi",
		"",
		"if ! command -v git >/dev/null 2>&1; then",
		"    apt update",
		"    apt install git -y",
		"fi",
		"",
		"mkdir -p " + shellQuote(githubProjectRoot),
		`git clone "$repo_url" "$target_path"`,
		"",
		`printf "Projekt geklont: %s\n" "$target_path"`,
	}

	return "sh -c " + shellQuote(strings.Join(lines, "\n"))
}
