package main

import (
	"errors"
	"fmt"
	"strconv"
	"strings"
)

func firewallStatusAction() actionDefinition {
	return actionDefinition{
		Name:    "firewall.status",
		Command: "ufw status verbose 2>/dev/null || echo 'NOT_INSTALLED'",
		Timeout: 15,
		UseSudo: true,
	}
}

func firewallRulesAction() actionDefinition {
	return actionDefinition{
		Name:    "firewall.rules",
		Command: "ufw status numbered 2>/dev/null || echo 'NOT_INSTALLED'",
		Timeout: 15,
		UseSudo: true,
	}
}

func firewallInstallAction() actionDefinition {
	return actionDefinition{
		Name:    "firewall.install",
		Command: "DEBIAN_FRONTEND=noninteractive apt install ufw -y",
		Timeout: 120,
		UseSudo: true,
	}
}

func firewallEnableAction() actionDefinition {
	return actionDefinition{
		Name:    "firewall.enable",
		Command: "ufw --force enable",
		Timeout: 30,
		UseSudo: true,
	}
}

func firewallDisableAction() actionDefinition {
	return actionDefinition{
		Name:    "firewall.disable",
		Command: "ufw --force disable",
		Timeout: 15,
		UseSudo: true,
	}
}

func firewallAllowAction() actionDefinition {
	return firewallPortAction("firewall.allow", "allow")
}

func firewallDenyAction() actionDefinition {
	return firewallPortAction("firewall.deny", "deny")
}

func firewallPortAction(name string, action string) actionDefinition {
	return actionDefinition{
		Name: name,
		BuildCommand: func(payload map[string]any) (string, error) {
			spec, err := firewallPortSpec(payload)
			if err != nil {
				return "", err
			}

			return fmt.Sprintf("ufw %s %s", action, shellQuote(spec)), nil
		},
		Timeout: 15,
		UseSudo: true,
	}
}

func firewallDeleteAction() actionDefinition {
	return actionDefinition{
		Name: "firewall.delete",
		BuildCommand: func(payload map[string]any) (string, error) {
			ruleNumber, err := firewallRuleNumber(payload)
			if err != nil {
				return "", err
			}

			return fmt.Sprintf("ufw --force delete %d", ruleNumber), nil
		},
		Timeout: 15,
		UseSudo: true,
	}
}

func firewallAllowStandardPortsAction() actionDefinition {
	return actionDefinition{
		Name:    "firewall.allow_standard_ports",
		Command: "ufw allow 22/tcp && ufw allow 80/tcp && ufw allow 443/tcp && ufw allow 3306/tcp && ufw allow 5432/tcp && ufw allow 8080/tcp && ufw allow 3000/tcp && ufw allow 5000/tcp",
		Timeout: 60,
		UseSudo: true,
	}
}

func firewallPortSpec(payload map[string]any) (string, error) {
	port, err := payloadString(payload, "port")
	if err != nil {
		return "", err
	}

	port = strings.TrimSpace(port)
	if port == "" {
		return "", errors.New("port is required")
	}

	portNumber, err := strconv.Atoi(port)
	if err != nil || portNumber < 1 || portNumber > 65535 || strconv.Itoa(portNumber) != port {
		return "", errors.New("port must be a number between 1 and 65535")
	}

	protocol, err := payloadOptionalString(payload, "protocol", "tcp")
	if err != nil {
		return "", err
	}

	protocol = strings.TrimSpace(strings.ToLower(protocol))
	if protocol != "tcp" && protocol != "udp" && protocol != "" {
		return "", errors.New("protocol must be tcp or udp")
	}

	if protocol == "" {
		return port, nil
	}

	return port + "/" + protocol, nil
}

func firewallRuleNumber(payload map[string]any) (int, error) {
	value, exists := payload["rule"]
	if !exists {
		value, exists = payload["rule_number"]
	}
	if !exists {
		return 0, errors.New("rule is required")
	}

	var ruleNumber int
	switch typed := value.(type) {
	case float64:
		ruleNumber = int(typed)
		if typed != float64(ruleNumber) {
			return 0, errors.New("rule must be a positive integer")
		}
	case string:
		parsed, err := strconv.Atoi(strings.TrimSpace(typed))
		if err != nil {
			return 0, errors.New("rule must be a positive integer")
		}
		ruleNumber = parsed
	default:
		return 0, errors.New("rule must be a positive integer")
	}

	if ruleNumber < 1 {
		return 0, errors.New("rule must be a positive integer")
	}

	return ruleNumber, nil
}

func payloadString(payload map[string]any, key string) (string, error) {
	value, exists := payload[key]
	if !exists {
		return "", fmt.Errorf("%s is required", key)
	}

	switch typed := value.(type) {
	case string:
		return typed, nil
	case float64:
		if typed != float64(int(typed)) {
			return "", fmt.Errorf("%s must be a string or integer", key)
		}

		return strconv.Itoa(int(typed)), nil
	default:
		return "", fmt.Errorf("%s must be a string or integer", key)
	}
}

func payloadOptionalString(payload map[string]any, key string, fallback string) (string, error) {
	value, exists := payload[key]
	if !exists || value == nil {
		return fallback, nil
	}

	switch typed := value.(type) {
	case string:
		return typed, nil
	default:
		return "", fmt.Errorf("%s must be a string", key)
	}
}
