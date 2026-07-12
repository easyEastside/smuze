package main

import (
	"bufio"
	"bytes"
	"context"
	"fmt"
	"io"
	"os/exec"
	"strings"
	"sync"
	"time"
)

type Executor struct {
	client *Client
}

type ExecutionResult struct {
	ExitCode int
	Stdout   string
	Stderr   string
}

func NewExecutor(client *Client) *Executor {
	return &Executor{client: client}
}

func (e *Executor) Execute(ctx context.Context, command Command) ExecutionResult {
	timeout := time.Duration(command.Timeout) * time.Second
	if timeout <= 0 {
		timeout = 30 * time.Second
	}

	commandCtx, cancel := context.WithTimeout(ctx, timeout)
	defer cancel()

	localCommand := command.Command
	if command.UseSudo {
		localCommand = applySudo(command.Command)
	}

	cmd := exec.CommandContext(commandCtx, "sh", "-lc", localCommand)
	stdoutPipe, err := cmd.StdoutPipe()
	if err != nil {
		return ExecutionResult{ExitCode: -1, Stderr: err.Error()}
	}
	stderrPipe, err := cmd.StderrPipe()
	if err != nil {
		return ExecutionResult{ExitCode: -1, Stderr: err.Error()}
	}

	if err := cmd.Start(); err != nil {
		return ExecutionResult{ExitCode: -1, Stderr: err.Error()}
	}

	var stdout bytes.Buffer
	var stderr bytes.Buffer
	var wg sync.WaitGroup
	wg.Add(2)

	go e.stream(commandCtx, command.ID, "stdout", stdoutPipe, &stdout, &wg)
	go e.stream(commandCtx, command.ID, "stderr", stderrPipe, &stderr, &wg)

	err = cmd.Wait()
	wg.Wait()

	exitCode := 0
	if err != nil {
		exitCode = -1
		if exitErr, ok := err.(*exec.ExitError); ok {
			exitCode = exitErr.ExitCode()
		}
	}

	if commandCtx.Err() == context.DeadlineExceeded {
		return ExecutionResult{ExitCode: -1, Stdout: stdout.String(), Stderr: strings.TrimSpace(stderr.String() + "\nCommand timed out")}
	}

	return ExecutionResult{ExitCode: exitCode, Stdout: stdout.String(), Stderr: stderr.String()}
}

func (e *Executor) stream(ctx context.Context, commandID int64, stream string, reader io.Reader, output *bytes.Buffer, wg *sync.WaitGroup) {
	defer wg.Done()

	buffered := bufio.NewReader(reader)
	chunk := make([]byte, 4096)

	for {
		n, err := buffered.Read(chunk)
		if n > 0 {
			data := string(chunk[:n])
			output.WriteString(data)
			if postErr := e.client.CommandOutput(ctx, commandID, stream, data); postErr != nil {
				fmt.Printf("failed to post %s output for command %d: %v\n", stream, commandID, postErr)
			}
		}
		if err != nil {
			return
		}
	}
}

func applySudo(command string) string {
	if strings.HasPrefix(command, "sudo ") {
		return command
	}

	return "sudo DEBIAN_FRONTEND=noninteractive sh -lc " + shellQuote(command)
}

func shellQuote(value string) string {
	return "'" + strings.ReplaceAll(value, "'", "'\\''") + "'"
}
