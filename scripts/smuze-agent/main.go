package main

import (
	"context"
	"flag"
	"fmt"
	"os"
	"os/signal"
	"syscall"
	"time"
)

const version = "0.1.0"

func main() {
	configPath := flag.String("config", "", "Path to JSON config file")
	once := flag.Bool("once", false, "Run one polling iteration and exit")
	showVersion := flag.Bool("version", false, "Print version and exit")
	flag.Parse()

	if *showVersion {
		fmt.Println(version)
		return
	}

	config, err := loadConfig(*configPath)
	if err != nil {
		fmt.Fprintln(os.Stderr, err)
		os.Exit(1)
	}

	ctx, stop := signal.NotifyContext(context.Background(), os.Interrupt, syscall.SIGTERM)
	defer stop()

	client := NewClient(config)
	executor := NewExecutor(client)

	if err := client.Heartbeat(ctx, version); err != nil {
		fmt.Fprintf(os.Stderr, "heartbeat failed: %v\n", err)
	}
	if err := client.Metrics(ctx, collectMetrics(), time.Now()); err != nil {
		fmt.Fprintf(os.Stderr, "metrics failed: %v\n", err)
	}

	if *once {
		runPoll(ctx, client, executor)
		return
	}

	pollTicker := time.NewTicker(config.PollInterval)
	defer pollTicker.Stop()
	metricsTicker := time.NewTicker(config.MetricsInterval)
	defer metricsTicker.Stop()
	heartbeatTicker := time.NewTicker(30 * time.Second)
	defer heartbeatTicker.Stop()

	for {
		select {
		case <-ctx.Done():
			return
		case <-pollTicker.C:
			runPoll(ctx, client, executor)
		case <-metricsTicker.C:
			if err := client.Metrics(ctx, collectMetrics(), time.Now()); err != nil {
				fmt.Fprintf(os.Stderr, "metrics failed: %v\n", err)
			}
		case <-heartbeatTicker.C:
			if err := client.Heartbeat(ctx, version); err != nil {
				fmt.Fprintf(os.Stderr, "heartbeat failed: %v\n", err)
			}
		}
	}
}

func runPoll(ctx context.Context, client *Client, executor *Executor) {
	commands, err := client.PendingCommands(ctx, 1)
	if err != nil {
		fmt.Fprintf(os.Stderr, "poll failed: %v\n", err)
		return
	}

	for _, command := range commands {
		result := executor.Execute(ctx, command)
		status := "completed"
		if result.ExitCode != 0 {
			status = "failed"
		}
		if err := client.CompleteCommand(ctx, command.ID, status, result.ExitCode, result.Stdout, result.Stderr); err != nil {
			fmt.Fprintf(os.Stderr, "complete command %d failed: %v\n", command.ID, err)
		}
	}
}
