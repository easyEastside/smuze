package main

import (
	"encoding/base64"
	"errors"
	"path/filepath"
	"strings"
)

const nginxDeinstallCommand = `systemctl disable --now nginx 2>/dev/null || true && DEBIAN_FRONTEND=noninteractive apt-get purge -y nginx nginx-common nginx-core && DEBIAN_FRONTEND=noninteractive apt-get autoremove --purge -y && rm -rf /etc/nginx && apt-get clean && systemctl daemon-reload && systemctl reset-failed`

func nginxStatusAction() actionDefinition {
	return actionDefinition{Name: "nginx.status", Command: `printf "ACTIVE=%s\n" "$(systemctl is-active nginx 2>/dev/null || echo unknown)" && if command -v nginx >/dev/null 2>&1; then nginx -v 2>&1 | sed -n "1p" | sed "s/^/VERSION=/"; fi`, Timeout: 15, UseSudo: true}
}

func nginxInstallAction() actionDefinition {
	return actionDefinition{Name: "nginx.install", Command: "DEBIAN_FRONTEND=noninteractive apt-get update && DEBIAN_FRONTEND=noninteractive apt-get install -y nginx && systemctl enable --now nginx", Timeout: 300, UseSudo: true}
}

func nginxDeinstallAction() actionDefinition {
	return actionDefinition{Name: "nginx.deinstall", Command: nginxDeinstallCommand, Timeout: 180, UseSudo: true}
}

func nginxStartAction() actionDefinition   { return nginxServiceAction("nginx.start", "start") }
func nginxStopAction() actionDefinition    { return nginxServiceAction("nginx.stop", "stop") }
func nginxRestartAction() actionDefinition { return nginxServiceAction("nginx.restart", "restart") }
func nginxReloadAction() actionDefinition  { return nginxServiceAction("nginx.reload", "reload") }

func nginxServiceAction(name string, action string) actionDefinition {
	return actionDefinition{Name: name, Command: "systemctl " + action + " nginx", Timeout: 30, UseSudo: true}
}

func nginxConfigtestAction() actionDefinition {
	return actionDefinition{Name: "nginx.configtest", Command: "nginx -t 2>&1", Timeout: 20, UseSudo: true}
}

func nginxSitesAction() actionDefinition {
	return actionDefinition{Name: "nginx.sites", Command: `for f in /etc/nginx/sites-available/*.conf; do [ -e "$f" ] || continue; name=$(basename "$f"); if [ -e "/etc/nginx/sites-enabled/$name" ]; then enabled=yes; else enabled=no; fi; server_name=$(awk '$1=="server_name" {gsub(";", "", $2); print $2; exit}' "$f" 2>/dev/null || true); doc_root=$(awk '$1=="root" {gsub(";", "", $2); print $2; exit}' "$f" 2>/dev/null || true); printf '%s\t%s\t%s\t%s\n' "$name" "$enabled" "$server_name" "$doc_root"; done`, Timeout: 20, UseSudo: true}
}

func nginxSiteConfigAction() actionDefinition {
	return actionDefinition{Name: "nginx.site_config", BuildCommand: func(payload map[string]any) (string, error) {
		path, err := nginxPayloadSitePath(payload)
		if err != nil {
			return "", err
		}

		return "cat " + shellQuote(path), nil
	}, Timeout: 15, UseSudo: true}
}

func nginxSaveSiteConfigAction() actionDefinition {
	return actionDefinition{Name: "nginx.save_site_config", BuildCommand: func(payload map[string]any) (string, error) {
		path, err := nginxPayloadSitePath(payload)
		if err != nil {
			return "", err
		}
		content, err := payloadString(payload, "content")
		if err != nil {
			return "", err
		}
		if strings.TrimSpace(content) == "" {
			return "", errors.New("content is required")
		}

		backup := path + ".smuzecp.bak"
		encoded := base64.StdEncoding.EncodeToString([]byte(content))

		return "cp " + shellQuote(path) + " " + shellQuote(backup) + " && printf '%s' " + shellQuote(encoded) + " | base64 -d > " + shellQuote(path) + " && nginx -t || (mv " + shellQuote(backup) + " " + shellQuote(path) + "; false) && rm -f " + shellQuote(backup) + " && systemctl reload nginx", nil
	}, Timeout: 30, UseSudo: true}
}

func nginxEnableSiteAction() actionDefinition {
	return actionDefinition{Name: "nginx.enable_site", BuildCommand: func(payload map[string]any) (string, error) {
		site, err := nginxPayloadSiteName(payload)
		if err != nil {
			return "", err
		}

		return "ln -sfn " + shellQuote("/etc/nginx/sites-available/"+site) + " " + shellQuote("/etc/nginx/sites-enabled/"+site) + " && nginx -t && systemctl reload nginx", nil
	}, Timeout: 30, UseSudo: true}
}

func nginxDisableSiteAction() actionDefinition {
	return actionDefinition{Name: "nginx.disable_site", BuildCommand: func(payload map[string]any) (string, error) {
		site, err := nginxPayloadSiteName(payload)
		if err != nil {
			return "", err
		}

		return "rm -f " + shellQuote("/etc/nginx/sites-enabled/"+site) + " && nginx -t && systemctl reload nginx", nil
	}, Timeout: 30, UseSudo: true}
}

func nginxDeleteSiteAction() actionDefinition {
	return actionDefinition{Name: "nginx.delete_site", BuildCommand: func(payload map[string]any) (string, error) {
		path, err := nginxPayloadSitePath(payload)
		if err != nil {
			return "", err
		}
		site := filepath.Base(path)
		commands := []string{"rm -f " + shellQuote("/etc/nginx/sites-enabled/"+site), "rm -f " + shellQuote(path)}

		deleteProject, _ := payloadBool(payload, "delete_project", false)
		if deleteProject {
			documentRoot, err := payloadString(payload, "document_root")
			if err != nil {
				return "", err
			}
			root, err := apacheProjectRoot(documentRoot)
			if err != nil {
				return "", err
			}
			commands = append(commands, "rm -rf -- "+shellQuote(root))
		}

		commands = append(commands, "nginx -t", "systemctl reload nginx")

		return strings.Join(commands, " && "), nil
	}, Timeout: 30, UseSudo: true}
}

func nginxCreateVhostAction() actionDefinition {
	return actionDefinition{Name: "nginx.create_vhost", BuildCommand: func(payload map[string]any) (string, error) {
		domain, err := payloadString(payload, "domain")
		if err != nil {
			return "", err
		}
		documentRoot, err := payloadString(payload, "document_root")
		if err != nil {
			return "", err
		}
		config, err := payloadString(payload, "config")
		if err != nil {
			return "", err
		}
		if err := apacheValidateHost(domain); err != nil {
			return "", err
		}
		if err := apacheValidatePath(documentRoot); err != nil {
			return "", err
		}

		siteName := domain + ".conf"
		path := "/etc/nginx/sites-available/" + siteName
		encoded := base64.StdEncoding.EncodeToString([]byte(config))
		commands := []string{"mkdir -p " + shellQuote(documentRoot), "mkdir -p /etc/nginx/sites-available /etc/nginx/sites-enabled", "printf '%s' " + shellQuote(encoded) + " | base64 -d > " + shellQuote(path), "ln -sfn " + shellQuote(path) + " " + shellQuote("/etc/nginx/sites-enabled/"+siteName), "nginx -t", "systemctl reload nginx"}

		return strings.Join(commands, " && "), nil
	}, Timeout: 45, UseSudo: true}
}

func nginxInstallCertbotAction() actionDefinition {
	return actionDefinition{Name: "nginx.install_certbot", Command: "DEBIAN_FRONTEND=noninteractive apt-get update -qq && DEBIAN_FRONTEND=noninteractive apt-get install -y -qq certbot python3-certbot-nginx", Timeout: 120, UseSudo: true}
}

func nginxObtainSslAction() actionDefinition {
	return actionDefinition{Name: "nginx.obtain_ssl", BuildCommand: func(payload map[string]any) (string, error) {
		domain, err := payloadString(payload, "domain")
		if err != nil {
			return "", err
		}
		email, err := payloadString(payload, "email")
		if err != nil {
			return "", err
		}
		if err := apacheValidateHost(domain); err != nil {
			return "", err
		}
		if !strings.Contains(email, "@") || strings.ContainsAny(email, "\r\n\t\x00") {
			return "", errors.New("email must be valid")
		}

		return "DEBIAN_FRONTEND=noninteractive certbot --nginx --non-interactive --agree-tos -m " + shellQuote(email) + " -d " + shellQuote(domain) + " --redirect --keep-until-expiring && systemctl reload nginx", nil
	}, Timeout: 120, UseSudo: true}
}

func nginxPayloadSitePath(payload map[string]any) (string, error) {
	site, err := nginxPayloadSiteName(payload)
	if err != nil {
		return "", err
	}

	return "/etc/nginx/sites-available/" + site, nil
}

func nginxPayloadSiteName(payload map[string]any) (string, error) {
	return apachePayloadToken(payload, "site")
}
