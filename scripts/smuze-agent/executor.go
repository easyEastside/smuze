package main

import "strings"

func applySudo(command string) string {
	if strings.HasPrefix(command, "sudo ") {
		return command
	}

	return "sudo DEBIAN_FRONTEND=noninteractive sh -lc " + shellQuote(command)
}

func shellQuote(value string) string {
	return "'" + strings.ReplaceAll(value, "'", "'\\''") + "'"
}
