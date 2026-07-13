package main

import (
	"errors"
	"strconv"
	"strings"
)

func monitoringProcessesAction() actionDefinition {
	return actionDefinition{
		Name:    "monitoring.processes",
		Command: `ps -eo pid=,ppid=,user=,stat=,pcpu=,pmem=,comm=,args= --sort=-pcpu | head -n 50`,
		Timeout: 15,
		UseSudo: false,
	}
}

func monitoringServicesAction() actionDefinition {
	return actionDefinition{
		Name:    "monitoring.services",
		Command: `systemctl list-units --type=service --all --no-pager --no-legend | head -n 100`,
		Timeout: 15,
		UseSudo: false,
	}
}

func monitoringServiceStartAction() actionDefinition {
	return monitoringServiceControlAction("monitoring.service_start", "start")
}

func monitoringServiceStopAction() actionDefinition {
	return monitoringServiceControlAction("monitoring.service_stop", "stop")
}

func monitoringServiceRestartAction() actionDefinition {
	return monitoringServiceControlAction("monitoring.service_restart", "restart")
}

func monitoringServiceControlAction(name string, verb string) actionDefinition {
	return actionDefinition{
		Name: name,
		BuildCommand: func(payload map[string]any) (string, error) {
			service, err := monitoringServicePayload(payload)
			if err != nil {
				return "", err
			}

			return "systemctl " + verb + " " + shellQuote(service), nil
		},
		Timeout: 60,
		UseSudo: true,
	}
}

func monitoringProcessKillAction() actionDefinition {
	return actionDefinition{
		Name: "monitoring.process_kill",
		BuildCommand: func(payload map[string]any) (string, error) {
			pid, err := monitoringPidPayload(payload)
			if err != nil {
				return "", err
			}

			return "kill -TERM " + strconv.Itoa(pid), nil
		},
		Timeout: 15,
		UseSudo: true,
	}
}

func monitoringServicePayload(payload map[string]any) (string, error) {
	service, err := payloadString(payload, "service")
	if err != nil {
		return "", err
	}

	service = strings.TrimSpace(service)
	if service == "" || !apacheTokenPattern.MatchString(service) || !strings.HasSuffix(service, ".service") {
		return "", errors.New("service must be a valid systemd service unit")
	}

	return service, nil
}

func monitoringPidPayload(payload map[string]any) (int, error) {
	value, err := payloadString(payload, "pid")
	if err != nil {
		return 0, err
	}

	pid, err := strconv.Atoi(strings.TrimSpace(value))
	if err != nil || pid <= 1 {
		return 0, errors.New("pid must be greater than 1")
	}

	return pid, nil
}
