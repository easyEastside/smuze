package main

import (
	"encoding/base64"
	"errors"
	"fmt"
	"path/filepath"
	"regexp"
	"strings"
)

var (
	apacheTokenPattern = regexp.MustCompile(`^[A-Za-z0-9._-]+$`)
	apacheHostPattern  = regexp.MustCompile(`^[A-Za-z0-9](?:[A-Za-z0-9\-]{0,61}[A-Za-z0-9])?(?:\.[A-Za-z0-9](?:[A-Za-z0-9\-]{0,61}[A-Za-z0-9])?)*$`)
)

func apacheStatusAction() actionDefinition {
	return actionDefinition{Name: "apache.status", Command: `printf "ACTIVE=%s\n" "$(systemctl is-active apache2 2>/dev/null || echo unknown)" && printf "INSTALLED=%s\n" "$(command -v apache2 >/dev/null 2>&1 && echo yes || echo no)" && (apache2 -v 2>/dev/null | sed -n "1p" | sed "s/^/VERSION=/")`, Timeout: 15, UseSudo: true}
}

func apacheInstallAction() actionDefinition {
	return actionDefinition{Name: "apache.install", Command: "DEBIAN_FRONTEND=noninteractive apt update && DEBIAN_FRONTEND=noninteractive apt install apache2 -y && systemctl enable --now apache2", Timeout: 300, UseSudo: true}
}

func apacheDeinstallAction() actionDefinition {
	return actionDefinition{Name: "apache.deinstall", Command: "systemctl stop apache2 2>/dev/null || true; DEBIAN_FRONTEND=noninteractive apt remove --purge apache2 apache2-bin apache2-data apache2-utils -y && apt autoremove -y && apt autoclean && rm -rf /etc/apache2", Timeout: 180, UseSudo: true}
}

func apacheStartAction() actionDefinition   { return apacheServiceAction("apache.start", "start") }
func apacheStopAction() actionDefinition    { return apacheServiceAction("apache.stop", "stop") }
func apacheRestartAction() actionDefinition { return apacheServiceAction("apache.restart", "restart") }
func apacheReloadAction() actionDefinition  { return apacheServiceAction("apache.reload", "reload") }

func apacheServiceAction(name string, action string) actionDefinition {
	return actionDefinition{Name: name, Command: "systemctl " + action + " apache2", Timeout: 30, UseSudo: true}
}

func apacheConfigtestAction() actionDefinition {
	return actionDefinition{Name: "apache.configtest", Command: "apache2ctl configtest 2>&1", Timeout: 20, UseSudo: true}
}

func apacheSitesAction() actionDefinition {
	return actionDefinition{Name: "apache.sites", Command: `for f in /etc/apache2/sites-available/*.conf; do [ -e "$f" ] || continue; name=$(basename "$f"); if [ -e "/etc/apache2/sites-enabled/$name" ]; then enabled=yes; else enabled=no; fi; server_name=$(awk 'tolower($1)=="servername" {print $2; exit}' "$f" 2>/dev/null || true); doc_root=$(awk 'tolower($1)=="documentroot" {print $2; exit}' "$f" 2>/dev/null || true); printf '%s\t%s\t%s\t%s\n' "$name" "$enabled" "$server_name" "$doc_root"; done`, Timeout: 20, UseSudo: true}
}

func apacheSiteConfigAction() actionDefinition {
	return actionDefinition{Name: "apache.site_config", BuildCommand: func(payload map[string]any) (string, error) {
		path, err := apachePayloadSitePath(payload)
		if err != nil {
			return "", err
		}

		return "cat " + shellQuote(path), nil
	}, Timeout: 15, UseSudo: true}
}

func apacheSaveSiteConfigAction() actionDefinition {
	return actionDefinition{Name: "apache.save_site_config", BuildCommand: func(payload map[string]any) (string, error) {
		path, err := apachePayloadSitePath(payload)
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

		return "cp " + shellQuote(path) + " " + shellQuote(backup) + " && printf '%s' " + shellQuote(encoded) + " | base64 -d > " + shellQuote(path) + " && apache2ctl configtest || (mv " + shellQuote(backup) + " " + shellQuote(path) + "; false) && rm -f " + shellQuote(backup) + " && systemctl reload apache2", nil
	}, Timeout: 30, UseSudo: true}
}

func apacheEnableSiteAction() actionDefinition {
	return apacheSiteToggleAction("apache.enable_site", "a2ensite")
}
func apacheDisableSiteAction() actionDefinition {
	return apacheSiteToggleAction("apache.disable_site", "a2dissite")
}

func apacheSiteToggleAction(name string, command string) actionDefinition {
	return actionDefinition{Name: name, BuildCommand: func(payload map[string]any) (string, error) {
		site, err := apachePayloadSiteName(payload)
		if err != nil {
			return "", err
		}

		return command + " " + shellQuote(site) + " && systemctl reload apache2", nil
	}, Timeout: 30, UseSudo: true}
}

func apacheDeleteSiteAction() actionDefinition {
	return actionDefinition{Name: "apache.delete_site", BuildCommand: func(payload map[string]any) (string, error) {
		path, err := apachePayloadSitePath(payload)
		if err != nil {
			return "", err
		}
		site := filepath.Base(path)
		commands := []string{"a2dissite " + shellQuote(site) + " 2>/dev/null || true", "rm -f " + shellQuote(path)}

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

		commands = append(commands, "apache2ctl configtest", "systemctl reload apache2")

		return strings.Join(commands, " && "), nil
	}, Timeout: 30, UseSudo: true}
}

func apacheCreateVhostAction() actionDefinition {
	return actionDefinition{Name: "apache.create_vhost", BuildCommand: func(payload map[string]any) (string, error) {
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
		path := "/etc/apache2/sites-available/" + siteName
		encoded := base64.StdEncoding.EncodeToString([]byte(config))
		commands := []string{"mkdir -p " + shellQuote(documentRoot), "printf '%s' " + shellQuote(encoded) + " | base64 -d > " + shellQuote(path), "apache2ctl configtest", "a2ensite " + shellQuote(siteName), "systemctl reload apache2"}

		return strings.Join(commands, " && "), nil
	}, Timeout: 45, UseSudo: true}
}

func apacheModulesAction() actionDefinition {
	return actionDefinition{Name: "apache.modules", Command: `for mod in /etc/apache2/mods-available/*.load; do [ -e "$mod" ] || continue; name=$(basename "$mod" .load); if [ -e "/etc/apache2/mods-enabled/${name}.load" ]; then printf '%s\tenabled\n' "$name"; else printf '%s\tdisabled\n' "$name"; fi; done`, Timeout: 20, UseSudo: true}
}

func apacheEnableModuleAction() actionDefinition {
	return apacheModuleAction("apache.enable_module", "a2enmod")
}
func apacheDisableModuleAction() actionDefinition {
	return apacheModuleAction("apache.disable_module", "a2dismod")
}

func apacheModuleAction(name string, command string) actionDefinition {
	return actionDefinition{Name: name, BuildCommand: func(payload map[string]any) (string, error) {
		module, err := apachePayloadToken(payload, "module")
		if err != nil {
			return "", err
		}

		return command + " " + shellQuote(module) + " && systemctl reload apache2", nil
	}, Timeout: 30, UseSudo: true}
}

func apacheInstallCertbotAction() actionDefinition {
	return actionDefinition{Name: "apache.install_certbot", Command: "DEBIAN_FRONTEND=noninteractive apt-get update -qq && DEBIAN_FRONTEND=noninteractive apt-get install -y -qq certbot python3-certbot-apache", Timeout: 120, UseSudo: true}
}

func apacheObtainSslAction() actionDefinition {
	return actionDefinition{Name: "apache.obtain_ssl", BuildCommand: func(payload map[string]any) (string, error) {
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

		return "DEBIAN_FRONTEND=noninteractive certbot --apache --non-interactive --agree-tos -m " + shellQuote(email) + " -d " + shellQuote(domain) + " --redirect --keep-until-expiring && systemctl reload apache2", nil
	}, Timeout: 120, UseSudo: true}
}

func apachePayloadSitePath(payload map[string]any) (string, error) {
	site, err := apachePayloadSiteName(payload)
	if err != nil {
		return "", err
	}

	return "/etc/apache2/sites-available/" + site, nil
}

func apachePayloadSiteName(payload map[string]any) (string, error) {
	return apachePayloadToken(payload, "site")
}

func apachePayloadToken(payload map[string]any, key string) (string, error) {
	value, err := payloadString(payload, key)
	if err != nil {
		return "", err
	}
	value = strings.TrimSpace(value)
	if value == "" || strings.Contains(value, "/") || strings.Contains(value, "..") {
		return "", fmt.Errorf("%s must be valid", key)
	}
	if key == "site" && !strings.HasSuffix(value, ".conf") {
		value += ".conf"
	}
	if !apacheTokenPattern.MatchString(value) {
		return "", fmt.Errorf("%s must be valid", key)
	}

	return value, nil
}

func apacheValidateHost(value string) error {
	value = strings.TrimSpace(value)
	if len(value) > 253 || !apacheHostPattern.MatchString(value) {
		return errors.New("host must be valid")
	}

	return nil
}

func apacheValidatePath(value string) error {
	if !strings.HasPrefix(value, "/") || strings.ContainsAny(value, "\r\n\t\x00") || strings.Contains(value, "/..") {
		return errors.New("path must be valid")
	}

	return nil
}

func apacheProjectRoot(documentRoot string) (string, error) {
	if err := apacheValidatePath(documentRoot); err != nil {
		return "", err
	}
	root := strings.TrimRight(documentRoot, "/")
	if !strings.HasPrefix(root, "/var/www/") {
		return "", errors.New("project root must be under /var/www")
	}
	if strings.EqualFold(filepath.Base(root), "public") {
		root = filepath.Dir(root)
	}
	if root == "/var/www" || root == "/var/www/html" {
		return "", errors.New("project root cannot be removed")
	}

	return root, nil
}

func payloadBool(payload map[string]any, key string, fallback bool) (bool, error) {
	value, exists := payload[key]
	if !exists || value == nil {
		return fallback, nil
	}
	typed, ok := value.(bool)
	if !ok {
		return false, fmt.Errorf("%s must be a boolean", key)
	}

	return typed, nil
}
