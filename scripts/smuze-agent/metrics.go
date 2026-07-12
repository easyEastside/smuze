package main

import (
	"bufio"
	"bytes"
	"os"
	"strconv"
	"strings"
	"syscall"
	"time"
)

func collectMetrics() map[string]any {
	metrics := map[string]any{}

	if hostname, err := os.Hostname(); err == nil {
		metrics["hostname"] = hostname
	}
	if osName := readOSName(); osName != "" {
		metrics["os"] = osName
	}
	if uptime := readUptime(); uptime != "" {
		metrics["uptime"] = uptime
	}
	if load := readLoad(); load != "" {
		metrics["load"] = load
	}
	if cpu, ok := readCPUPercent(); ok {
		metrics["cpu_percent"] = cpu
	}
	if total, used, percent, ok := readMemory(); ok {
		metrics["ram_total_mb"] = total
		metrics["ram_used_mb"] = used
		metrics["ram_percent"] = percent
	}
	if total, used, percent, ok := readDisk("/"); ok {
		metrics["disk_total_mb"] = total
		metrics["disk_used_mb"] = used
		metrics["disk_percent"] = percent
	}

	return metrics
}

func readOSName() string {
	content, err := os.ReadFile("/etc/os-release")
	if err != nil {
		return ""
	}

	for _, line := range strings.Split(string(content), "\n") {
		if strings.HasPrefix(line, "PRETTY_NAME=") {
			return strings.Trim(strings.TrimPrefix(line, "PRETTY_NAME="), "\"")
		}
	}

	return ""
}

func readUptime() string {
	content, err := os.ReadFile("/proc/uptime")
	if err != nil {
		return ""
	}

	fields := strings.Fields(string(content))
	if len(fields) == 0 {
		return ""
	}

	seconds, err := strconv.ParseFloat(fields[0], 64)
	if err != nil {
		return ""
	}

	duration := time.Duration(seconds) * time.Second
	days := int(duration.Hours()) / 24
	hours := int(duration.Hours()) % 24
	minutes := int(duration.Minutes()) % 60

	if days > 0 {
		return "up " + strconv.Itoa(days) + " days, " + strconv.Itoa(hours) + " hours"
	}
	if hours > 0 {
		return "up " + strconv.Itoa(hours) + " hours, " + strconv.Itoa(minutes) + " minutes"
	}

	return "up " + strconv.Itoa(minutes) + " minutes"
}

func readLoad() string {
	content, err := os.ReadFile("/proc/loadavg")
	if err != nil {
		return ""
	}

	fields := strings.Fields(string(content))
	if len(fields) == 0 {
		return ""
	}

	return fields[0]
}

func readCPUPercent() (int, bool) {
	totalA, idleA, ok := readCPUStat()
	if !ok {
		return 0, false
	}
	time.Sleep(200 * time.Millisecond)
	totalB, idleB, ok := readCPUStat()
	if !ok {
		return 0, false
	}

	totalDelta := totalB - totalA
	idleDelta := idleB - idleA
	if totalDelta <= 0 {
		return 0, true
	}

	return int(100 * (totalDelta - idleDelta) / totalDelta), true
}

func readCPUStat() (int64, int64, bool) {
	content, err := os.ReadFile("/proc/stat")
	if err != nil {
		return 0, 0, false
	}

	scanner := bufio.NewScanner(bytes.NewReader(content))
	for scanner.Scan() {
		fields := strings.Fields(scanner.Text())
		if len(fields) < 5 || fields[0] != "cpu" {
			continue
		}

		var total int64
		for _, value := range fields[1:] {
			parsed, err := strconv.ParseInt(value, 10, 64)
			if err != nil {
				return 0, 0, false
			}
			total += parsed
		}

		idle, err := strconv.ParseInt(fields[4], 10, 64)
		if err != nil {
			return 0, 0, false
		}

		return total, idle, true
	}

	return 0, 0, false
}

func readMemory() (int, int, int, bool) {
	content, err := os.ReadFile("/proc/meminfo")
	if err != nil {
		return 0, 0, 0, false
	}

	values := map[string]int{}
	scanner := bufio.NewScanner(bytes.NewReader(content))
	for scanner.Scan() {
		fields := strings.Fields(scanner.Text())
		if len(fields) < 2 {
			continue
		}
		value, err := strconv.Atoi(fields[1])
		if err != nil {
			continue
		}
		values[strings.TrimSuffix(fields[0], ":")] = value
	}

	totalKB := values["MemTotal"]
	availableKB := values["MemAvailable"]
	if totalKB <= 0 || availableKB < 0 {
		return 0, 0, 0, false
	}

	usedKB := totalKB - availableKB
	totalMB := totalKB / 1024
	usedMB := usedKB / 1024
	percent := 0
	if totalKB > 0 {
		percent = int(100 * usedKB / totalKB)
	}

	return totalMB, usedMB, percent, true
}

func readDisk(path string) (int, int, int, bool) {
	var stat syscall.Statfs_t
	if err := syscall.Statfs(path, &stat); err != nil {
		return 0, 0, 0, false
	}

	total := int((stat.Blocks * uint64(stat.Bsize)) / 1024 / 1024)
	free := int((stat.Bavail * uint64(stat.Bsize)) / 1024 / 1024)
	used := total - free
	percent := 0
	if total > 0 {
		percent = int(100 * used / total)
	}

	return total, used, percent, true
}
