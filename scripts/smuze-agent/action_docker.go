package main

import (
	"fmt"
	"strconv"
	"strings"
)

func dockerStatusAction() actionDefinition {
	return actionDefinition{
		Name: "docker.status",
		Command: `printf "ACTIVE=%s\n" "$(systemctl is-active docker 2>/dev/null || echo unknown)" && ` +
			`printf "INSTALLED=%s\n" "$(command -v docker >/dev/null 2>&1 && echo yes || echo no)" && ` +
			`docker version --format 'VERSION={{.Server.Version}}' 2>/dev/null && ` +
			`(docker compose version --short 2>/dev/null | sed 's/^/COMPOSE_VERSION=/') 2>/dev/null || true`,
		Timeout: 15,
		UseSudo: true,
	}
}

func dockerInfoAction() actionDefinition {
	return actionDefinition{
		Name: "docker.info",
		Command: `docker info --format 'CONTAINERS_TOTAL={{.Containers}}' 2>/dev/null && ` +
			`docker info --format 'CONTAINERS_RUNNING={{.ContainersRunning}}' 2>/dev/null && ` +
			`docker info --format 'CONTAINERS_PAUSED={{.ContainersPaused}}' 2>/dev/null && ` +
			`docker info --format 'CONTAINERS_STOPPED={{.ContainersStopped}}' 2>/dev/null && ` +
			`docker info --format 'IMAGES_TOTAL={{.Images}}' 2>/dev/null && ` +
			`docker info --format 'SERVER_VERSION={{.ServerVersion}}' 2>/dev/null && ` +
			`docker info --format 'STORAGE_DRIVER={{.Driver}}' 2>/dev/null && ` +
			`docker info --format 'LOGGING_DRIVER={{.LoggingDriver}}' 2>/dev/null && ` +
			`docker info --format 'CGROUP_DRIVER={{.CgroupDriver}}' 2>/dev/null && ` +
			`docker info --format 'OS_TYPE={{.OSType}}' 2>/dev/null && ` +
			`docker info --format 'KERNEL_VERSION={{.KernelVersion}}' 2>/dev/null && ` +
			`docker info --format 'CPU_COUNT={{.NCPU}}' 2>/dev/null && ` +
			`docker info --format 'MEMORY_TOTAL={{.MemTotal}}' 2>/dev/null || true`,
		Timeout: 15,
		UseSudo: true,
	}
}

func dockerInstallAction() actionDefinition {
	return actionDefinition{
		Name:    "docker.install",
		Command: "DEBIAN_FRONTEND=noninteractive apt install docker.io docker-compose-v2 -y && systemctl enable --now docker",
		Timeout: 300,
		UseSudo: true,
	}
}

func dockerDeinstallAction() actionDefinition {
	return actionDefinition{
		Name:    "docker.deinstall",
		Command: "systemctl stop docker 2>/dev/null || true; DEBIAN_FRONTEND=noninteractive apt remove --purge docker.io docker-compose-v2 -y && apt autoremove -y && apt autoclean && rm -rf /var/lib/docker",
		Timeout: 180,
		UseSudo: true,
	}
}

func dockerStartAction() actionDefinition   { return dockerServiceAction("docker.start", "start") }
func dockerStopAction() actionDefinition    { return dockerServiceAction("docker.stop", "stop") }
func dockerRestartAction() actionDefinition { return dockerServiceAction("docker.restart", "restart") }

func dockerServiceAction(name, action string) actionDefinition {
	return actionDefinition{
		Name:    name,
		Command: "systemctl " + action + " docker",
		Timeout: 60,
		UseSudo: true,
	}
}

func dockerPsAction() actionDefinition {
	return actionDefinition{
		Name: "docker.ps",
		BuildCommand: func(payload map[string]any) (string, error) {
			all := true
			if v, ok := payload["all"]; ok {
				switch typed := v.(type) {
				case bool:
					all = typed
				case string:
					all = typed == "true"
				}
			}

			allFlag := ""
			if all {
				allFlag = "-a"
			}

			return fmt.Sprintf("docker ps %s --format 'CONTAINER_ID={{.ID}}\tIMAGE={{.Image}}\tSTATUS={{.Status}}\tPORTS={{.Ports}}\tNAMES={{.Names}}\tCREATED={{.CreatedAt}}' 2>/dev/null || echo ''", allFlag), nil
		},
		Timeout: 15,
		UseSudo: true,
	}
}

func dockerContainerStartAction() actionDefinition {
	return dockerContainerAction("docker.container_start", "start")
}

func dockerContainerStopAction() actionDefinition {
	return dockerContainerAction("docker.container_stop", "stop")
}

func dockerContainerRestartAction() actionDefinition {
	return dockerContainerAction("docker.container_restart", "restart")
}

func dockerContainerAction(name, action string) actionDefinition {
	return actionDefinition{
		Name: name,
		BuildCommand: func(payload map[string]any) (string, error) {
			container, err := payloadString(payload, "container")
			if err != nil {
				return "", err
			}
			return dockerQuote(fmt.Sprintf("docker %s %s", action, container)), nil
		},
		Timeout: 60,
		UseSudo: true,
	}
}

func dockerContainerRemoveAction() actionDefinition {
	return actionDefinition{
		Name: "docker.container_remove",
		BuildCommand: func(payload map[string]any) (string, error) {
			container, err := payloadString(payload, "container")
			if err != nil {
				return "", err
			}
			force := false
			if v, ok := payload["force"]; ok {
				switch typed := v.(type) {
				case bool:
					force = typed
				case string:
					force = typed == "true"
				}
			}
			cmd := "docker rm"
			if force {
				cmd += " -f"
			}
			return dockerQuote(cmd + " " + container), nil
		},
		Timeout: 30,
		UseSudo: true,
	}
}

func dockerContainerLogsAction() actionDefinition {
	return actionDefinition{
		Name: "docker.container_logs",
		BuildCommand: func(payload map[string]any) (string, error) {
			container, err := payloadString(payload, "container")
			if err != nil {
				return "", err
			}
			tail := 100
			if v, ok := payload["tail"]; ok {
				switch typed := v.(type) {
				case float64:
					tail = int(typed)
				case string:
					if n, err := strconv.Atoi(typed); err == nil && n > 0 {
						tail = n
					}
				}
			}
			return dockerQuote(fmt.Sprintf("docker logs --tail %d %s", tail, container)), nil
		},
		Timeout: 30,
		UseSudo: true,
	}
}

func dockerContainerExecAction() actionDefinition {
	return actionDefinition{
		Name: "docker.container_exec",
		BuildCommand: func(payload map[string]any) (string, error) {
			container, err := payloadString(payload, "container")
			if err != nil {
				return "", err
			}
			command, err := payloadString(payload, "command")
			if err != nil {
				return "", err
			}
			return dockerQuote(fmt.Sprintf("docker exec %s sh -lc %s", container, shellQuote(command))), nil
		},
		Timeout: 60,
		UseSudo: true,
	}
}

func dockerContainerCreateAction() actionDefinition {
	return actionDefinition{
		Name: "docker.container_create",
		BuildCommand: func(payload map[string]any) (string, error) {
			image, err := payloadString(payload, "image")
			if err != nil {
				return "", err
			}

			cmd := "docker run -d"

			if name, err := payloadOptionalString(payload, "name", ""); err == nil && name != "" {
				cmd += " --name " + shellQuote(name)
			}

			if ports, err := payloadOptionalString(payload, "ports", ""); err == nil && ports != "" {
				cmd += " -p " + shellQuote(ports)
			}

			if volume, err := payloadOptionalString(payload, "volume", ""); err == nil && volume != "" {
				cmd += " -v " + shellQuote(volume)
			}

			envList, ok := payload["env"].([]any)
			if ok {
				for _, e := range envList {
					envStr, ok := e.(string)
					if ok && strings.TrimSpace(envStr) != "" {
						cmd += " -e " + shellQuote(envStr)
					}
				}
			}

			cmd += " " + shellQuote(image)

			return dockerQuote(cmd), nil
		},
		Timeout: 120,
		UseSudo: true,
	}
}

func dockerImagesAction() actionDefinition {
	return actionDefinition{
		Name:    "docker.images",
		Command: `docker images --format 'REPOSITORY={{.Repository}}\tTAG={{.Tag}}\tIMAGE_ID={{.ID}}\tSIZE={{.Size}}' 2>/dev/null || echo ''`,
		Timeout: 15,
		UseSudo: true,
	}
}

func dockerImagePullAction() actionDefinition {
	return actionDefinition{
		Name: "docker.image_pull",
		BuildCommand: func(payload map[string]any) (string, error) {
			image, err := payloadString(payload, "image")
			if err != nil {
				return "", err
			}
			return dockerQuote(fmt.Sprintf("docker pull %s", image)), nil
		},
		Timeout: 300,
		UseSudo: true,
	}
}

func dockerImageRemoveAction() actionDefinition {
	return actionDefinition{
		Name: "docker.image_remove",
		BuildCommand: func(payload map[string]any) (string, error) {
			image, err := payloadString(payload, "image")
			if err != nil {
				return "", err
			}
			force := false
			if v, ok := payload["force"]; ok {
				switch typed := v.(type) {
				case bool:
					force = typed
				case string:
					force = typed == "true"
				}
			}
			cmd := "docker rmi"
			if force {
				cmd += " -f"
			}
			return dockerQuote(cmd + " " + image), nil
		},
		Timeout: 60,
		UseSudo: true,
	}
}

func dockerNetworksAction() actionDefinition {
	return actionDefinition{
		Name:    "docker.networks",
		Command: `docker network ls --format 'NAME={{.Name}}\tDRIVER={{.Driver}}\tSCOPE={{.Scope}}' 2>/dev/null || echo ''`,
		Timeout: 15,
		UseSudo: true,
	}
}

func dockerComposePsAction() actionDefinition {
	return actionDefinition{
		Name: "docker.compose_ps",
		BuildCommand: func(payload map[string]any) (string, error) {
			projectPath, _ := payloadOptionalString(payload, "project_path", "")
			if projectPath != "" {
				return dockerQuote(fmt.Sprintf("cd %s && docker compose ps --format 'NAME={{.Name}}\tIMAGE={{.Image}}\tSTATUS={{.Status}}\tPORTS={{.Ports}}'", projectPath)), nil
			}
			return `docker compose ps --format 'NAME={{.Name}}\tIMAGE={{.Image}}\tSTATUS={{.Status}}\tPORTS={{.Ports}}' 2>/dev/null || echo ''`, nil
		},
		Timeout: 30,
		UseSudo: true,
	}
}

func dockerComposeUpAction() actionDefinition {
	return actionDefinition{
		Name: "docker.compose_up",
		BuildCommand: func(payload map[string]any) (string, error) {
			projectPath, _ := payloadOptionalString(payload, "project_path", "")
			detach := true
			if v, ok := payload["detach"]; ok {
				switch typed := v.(type) {
				case bool:
					detach = typed
				case string:
					detach = typed == "true"
				}
			}
			detachFlag := ""
			if detach {
				detachFlag = "-d"
			}
			if projectPath != "" {
				return dockerQuote(fmt.Sprintf("cd %s && docker compose up %s", projectPath, detachFlag)), nil
			}
			return dockerQuote(fmt.Sprintf("docker compose up %s", detachFlag)), nil
		},
		Timeout: 300,
		UseSudo: true,
	}
}

func dockerComposeDownAction() actionDefinition {
	return actionDefinition{
		Name: "docker.compose_down",
		BuildCommand: func(payload map[string]any) (string, error) {
			projectPath, _ := payloadOptionalString(payload, "project_path", "")
			if projectPath != "" {
				return dockerQuote(fmt.Sprintf("cd %s && docker compose down", projectPath)), nil
			}
			return "docker compose down", nil
		},
		Timeout: 120,
		UseSudo: true,
	}
}

func dockerSystemPruneAction() actionDefinition {
	return actionDefinition{
		Name: "docker.system_prune",
		BuildCommand: func(payload map[string]any) (string, error) {
			all := false
			if v, ok := payload["all"]; ok {
				switch typed := v.(type) {
				case bool:
					all = typed
				case string:
					all = typed == "true"
				}
			}
			cmd := "docker system prune -f"
			if all {
				cmd += " -a"
			}
			return dockerQuote(cmd), nil
		},
		Timeout: 120,
		UseSudo: true,
	}
}

func dockerStatsAction() actionDefinition {
	return actionDefinition{
		Name:    "docker.stats",
		Command: `docker stats --no-stream --format 'CONTAINER_ID={{.ID}}\tNAME={{.Name}}\tCPU={{.CPUPerc}}\tMEM_USAGE={{.MemUsage}}\tMEM_PERC={{.MemPerc}}\tNET_IO={{.NetIO}}\tBLOCK_IO={{.BlockIO}}' 2>/dev/null || echo ''`,
		Timeout: 15,
		UseSudo: true,
	}
}

func dockerQuote(command string) string {
	return "DEBIAN_FRONTEND=noninteractive " + command
}
