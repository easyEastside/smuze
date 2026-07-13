package main

import (
	"errors"
	"fmt"
	"regexp"
	"strconv"
	"strings"
)

const cronjobsListCommand = `python3 - <<'PY'
import glob, json, os, re, subprocess

entries = []
current_user = subprocess.run(["id", "-un"], text=True, capture_output=True).stdout.strip() or "root"

def is_environment_assignment(line):
    return re.match(r"^[A-Za-z_][A-Za-z0-9_]*=", line) is not None

def parse_lines(lines, source, user=None, system=False):
    managed = False
    for line in lines:
        stripped = line.strip()
        if stripped == "# SMUZE MANAGED START":
            managed = True
            continue
        if stripped == "# SMUZE MANAGED END":
            managed = False
            continue
        if not stripped or stripped.startswith("#") or is_environment_assignment(stripped):
            continue

        fields = stripped.split(None, 6 if system else 5)
        if system and len(fields) >= 7:
            schedule = " ".join(fields[:5])
            entry_user = fields[5]
            command = fields[6]
        elif not system and len(fields) >= 6:
            schedule = " ".join(fields[:5])
            entry_user = user or "current"
            command = fields[5]
        else:
            schedule = ""
            entry_user = user or ""
            command = stripped

        entries.append({"managed": managed, "source": source, "user": entry_user, "schedule": schedule, "command": command, "line": line})

result = subprocess.run(["crontab", "-l"], text=True, capture_output=True)
if result.returncode == 0:
    parse_lines(result.stdout.splitlines(), "crontab", current_user)

if os.path.exists("/etc/crontab"):
    with open("/etc/crontab", encoding="utf-8", errors="ignore") as handle:
        parse_lines(handle.read().splitlines(), "/etc/crontab", system=True)

for path in sorted(glob.glob("/etc/cron.d/*")):
    if os.path.isfile(path):
        with open(path, encoding="utf-8", errors="ignore") as handle:
            parse_lines(handle.read().splitlines(), path, system=True)

for pattern in ("/var/spool/cron/crontabs/*", "/var/spool/cron/*"):
    for path in sorted(glob.glob(pattern)):
        if os.path.isfile(path):
            user = os.path.basename(path)
            if result.returncode == 0 and user == current_user:
                continue
            with open(path, encoding="utf-8", errors="ignore") as handle:
                parse_lines(handle.read().splitlines(), "user:" + user, user)

print(json.dumps(entries))
PY`

var cronFieldPattern = regexp.MustCompile(`^[A-Za-z0-9*/,#?\-]+$`)

type cronjobPayload struct {
	ID               int
	Name             string
	Schedule         string
	Command          string
	WorkingDirectory string
	RunAs            string
}

func cronjobsListAction() actionDefinition {
	return actionDefinition{
		Name:    "cronjobs.list",
		Command: cronjobsListCommand,
		Timeout: 15,
		UseSudo: true,
	}
}

func cronjobsInstallAction() actionDefinition {
	return actionDefinition{
		Name: "cronjobs.install",
		BuildCommand: func(payload map[string]any) (string, error) {
			jobs, err := cronjobsPayload(payload)
			if err != nil {
				return "", err
			}

			return cronjobsInstallCommand(jobs), nil
		},
		Timeout: 30,
		UseSudo: true,
	}
}

func cronjobsRemoveAction() actionDefinition {
	return actionDefinition{
		Name: "cronjobs.remove",
		BuildCommand: func(payload map[string]any) (string, error) {
			return cronjobsInstallCommand([]cronjobPayload{}), nil
		},
		Timeout: 30,
		UseSudo: true,
	}
}

func cronjobsRunAction() actionDefinition {
	return actionDefinition{
		Name: "cronjobs.run",
		BuildCommand: func(payload map[string]any) (string, error) {
			job, err := cronjobPayloadFromMap(payload)
			if err != nil {
				return "", err
			}

			return cronjobShellCommand(job), nil
		},
		Timeout: 3600,
		UseSudo: true,
	}
}

func cronjobsPayload(payload map[string]any) ([]cronjobPayload, error) {
	value, exists := payload["jobs"]
	if !exists {
		return nil, errors.New("jobs is required")
	}

	items, ok := value.([]any)
	if !ok {
		return nil, errors.New("jobs must be an array")
	}

	jobs := make([]cronjobPayload, 0, len(items))
	for _, item := range items {
		jobMap, ok := item.(map[string]any)
		if !ok {
			return nil, errors.New("job must be an object")
		}

		job, err := cronjobPayloadFromMap(jobMap)
		if err != nil {
			return nil, err
		}

		jobs = append(jobs, job)
	}

	return jobs, nil
}

func cronjobPayloadFromMap(payload map[string]any) (cronjobPayload, error) {
	idValue, err := payloadString(payload, "id")
	if err != nil {
		return cronjobPayload{}, err
	}

	id, err := strconv.Atoi(strings.TrimSpace(idValue))
	if err != nil || id < 1 {
		return cronjobPayload{}, errors.New("id must be a positive integer")
	}

	name, err := payloadString(payload, "name")
	if err != nil {
		name = fmt.Sprintf("Cronjob %d", id)
	}

	schedule, err := payloadString(payload, "schedule")
	if err != nil {
		schedule = "* * * * *"
	}

	command, err := payloadString(payload, "command")
	if err != nil {
		return cronjobPayload{}, err
	}

	workingDirectory, err := payloadOptionalString(payload, "working_directory", "")
	if err != nil {
		return cronjobPayload{}, err
	}

	runAs, err := payloadOptionalString(payload, "run_as", "")
	if err != nil {
		return cronjobPayload{}, err
	}

	job := cronjobPayload{
		ID:               id,
		Name:             strings.TrimSpace(name),
		Schedule:         strings.TrimSpace(schedule),
		Command:          strings.TrimSpace(command),
		WorkingDirectory: strings.TrimSpace(workingDirectory),
		RunAs:            strings.TrimSpace(runAs),
	}

	if err := validateCronjobPayload(job); err != nil {
		return cronjobPayload{}, err
	}

	return job, nil
}

func validateCronjobPayload(job cronjobPayload) error {
	if strings.ContainsAny(job.Name, "\r\n") || strings.ContainsAny(job.Command, "\r\n") {
		return errors.New("cronjob fields must not contain new lines")
	}

	fields := strings.Fields(job.Schedule)
	if len(fields) != 5 {
		return errors.New("schedule must contain 5 fields")
	}

	for _, field := range fields {
		if !cronFieldPattern.MatchString(field) {
			return errors.New("schedule contains invalid characters")
		}
	}

	if job.Command == "" {
		return errors.New("command is required")
	}

	if job.WorkingDirectory != "" {
		if !strings.HasPrefix(job.WorkingDirectory, "/") || strings.Contains(job.WorkingDirectory, "..") || strings.ContainsAny(job.WorkingDirectory, "\r\n") {
			return errors.New("working_directory must be an absolute path")
		}
	}

	if job.RunAs != "" && !apacheTokenPattern.MatchString(job.RunAs) {
		return errors.New("run_as must be a valid user name")
	}

	return nil
}

func cronjobsInstallCommand(jobs []cronjobPayload) string {
	if len(jobs) == 0 {
		return "set -e\n" +
			"existing=$(mktemp)\n" +
			"base=$(mktemp)\n" +
			"cleanup() { rm -f \"$existing\" \"$base\"; }\n" +
			"trap cleanup EXIT\n" +
			"crontab -l > \"$existing\" 2>/dev/null || true\n" +
			"awk 'BEGIN{skip=0} /^# SMUZE MANAGED START$/{skip=1; next} /^# SMUZE MANAGED END$/{skip=0; next} skip==0{print}' \"$existing\" > \"$base\"\n" +
			"if [ -s \"$base\" ]; then crontab \"$base\"; else crontab -r 2>/dev/null || true; fi"
	}

	var managed strings.Builder
	managed.WriteString("# SMUZE MANAGED START\n")
	for _, job := range jobs {
		managed.WriteString(fmt.Sprintf("# smuze:id=%d name=%q\n", job.ID, job.Name))
		managed.WriteString(job.Schedule + " " + escapeCronCommand(cronjobShellCommand(job)) + "\n")
	}
	managed.WriteString("# SMUZE MANAGED END\n")

	return "set -e\n" +
		"existing=$(mktemp)\n" +
		"base=$(mktemp)\n" +
		"next=$(mktemp)\n" +
		"cleanup() { rm -f \"$existing\" \"$base\" \"$next\"; }\n" +
		"trap cleanup EXIT\n" +
		"crontab -l > \"$existing\" 2>/dev/null || true\n" +
		"awk 'BEGIN{skip=0} /^# SMUZE MANAGED START$/{skip=1; next} /^# SMUZE MANAGED END$/{skip=0; next} skip==0{print}' \"$existing\" > \"$base\"\n" +
		"cat \"$base\" > \"$next\"\n" +
		"if [ -s \"$next\" ] && [ \"$(tail -c 1 \"$next\")\" != \"\" ]; then printf '\\n' >> \"$next\"; fi\n" +
		"cat >> \"$next\" <<'SMUZE_CRON'\n" +
		managed.String() +
		"SMUZE_CRON\n" +
		"crontab \"$next\""
}

func cronjobShellCommand(job cronjobPayload) string {
	command := job.Command
	if job.WorkingDirectory != "" {
		command = "cd " + shellQuote(job.WorkingDirectory) + " && " + command
	}

	if job.RunAs != "" {
		command = "sudo -u " + shellQuote(job.RunAs) + " sh -lc " + shellQuote(command)
	}

	return command
}

func escapeCronCommand(command string) string {
	var escaped strings.Builder
	previousWasBackslash := false

	for _, char := range command {
		if char == '%' && !previousWasBackslash {
			escaped.WriteRune('\\')
		}

		escaped.WriteRune(char)
		previousWasBackslash = char == '\\' && !previousWasBackslash
		if char != '\\' {
			previousWasBackslash = false
		}
	}

	return escaped.String()
}
