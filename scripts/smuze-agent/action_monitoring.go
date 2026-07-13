package main

import (
	"errors"
	"strconv"
	"strings"
)

func monitoringProcessesAction() actionDefinition {
	return actionDefinition{
		Name: "monitoring.processes",
		BuildCommand: func(payload map[string]any) (string, error) {
			return `python3 - <<'PY'
import json, os, pwd, time

try:
    clk_tck = os.sysconf(os.sysconf_names['SC_CLK_TCK'])
except:
    clk_tck = 100

uptime_seconds = 0
try:
    with open('/proc/uptime') as f:
        uptime_seconds = float(f.read().split()[0])
except:
    pass

total_mem_kb = 0
try:
    with open('/proc/meminfo') as f:
        for line in f:
            if line.startswith('MemTotal:'):
                total_mem_kb = int(line.split()[1])
                break
except:
    pass

pids = sorted([d for d in os.listdir('/proc') if d.isdigit()], key=int)
processes = []
seen = set()

for pid in pids:
    if len(processes) >= 100:
        break
    try:
        with open(f'/proc/{pid}/stat') as f:
            raw = f.read()
        idx = raw.rindex(')')
        comm = raw[raw.index('(')+1:idx]
        fields = raw[idx+2:].split()
        status = fields[0]
        ppid = fields[1]
        utime = int(fields[11])
        stime = int(fields[12])
        rss_pages = int(fields[23])
        rss_kb = rss_pages * 4
        total_sec = uptime_seconds if uptime_seconds > 0 else 1
        cpu_pct = round((utime + stime) / (clk_tck * total_sec) * 100, 1)
        mem_pct = round(rss_kb / total_mem_kb * 100, 1) if total_mem_kb > 0 else 0.0
    except:
        continue

    command = comm
    try:
        with open(f'/proc/{pid}/cmdline', 'rb') as f:
            cmd = f.read().replace(b'\x00', b' ').strip()
            if cmd:
                command = cmd.decode('utf-8', errors='replace')
    except:
        pass

    user = ''
    try:
        with open(f'/proc/{pid}/status') as f:
            for line in f:
                if line.startswith('Uid:'):
                    uid = int(line.split()[1])
                    try:
                        user = pwd.getpwuid(uid).pw_name
                    except:
                        user = str(uid)
                    break
    except:
        pass

    pdata = (pid, command)
    if pdata in seen:
        continue
    seen.add(pdata)

    processes.append({
        "pid": str(pid),
        "ppid": str(ppid),
        "user": user,
        "stat": status,
        "cpu": str(cpu_pct),
        "mem": str(mem_pct),
        "command": command,
    })

processes.sort(key=lambda p: -float(p['cpu']))
processes = processes[:50]
print(json.dumps(processes))
PY`, nil
		},
		Timeout: 30,
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
	if service == "" || strings.HasPrefix(service, ".") || !apacheTokenPattern.MatchString(service) || !strings.HasSuffix(service, ".service") {
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
