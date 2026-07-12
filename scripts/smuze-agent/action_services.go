package main

import (
	"errors"
	"strings"
)

type serviceCommand struct {
	Command string
	Timeout int
	UseSudo bool
}

var allowedPHPVersions = map[string]bool{
	"8.5": true,
	"8.4": true,
	"8.3": true,
	"8.2": true,
}

var phpPackages = []string{
	"cli",
	"common",
	"curl",
	"mbstring",
	"xml",
	"zip",
	"intl",
	"mysql",
	"opcache",
	"fpm",
	"bcmath",
	"gd",
	"pgsql",
	"sqlite3",
	"soap",
	"readline",
}

const mysqlDeinstallCommand = `systemctl stop mysql mariadb 2>/dev/null || true && packages="$(dpkg-query -W -f='${binary:Package}\n' 2>/dev/null | grep -E '^(mysql|mariadb|percona)[A-Za-z0-9+_.:-]*$' || true)" && if [ -n "$packages" ]; then DEBIAN_FRONTEND=noninteractive apt-get remove --purge -y $packages; fi && DEBIAN_FRONTEND=noninteractive apt-get autoremove -y && DEBIAN_FRONTEND=noninteractive apt-get autoclean && rm -rf /etc/mysql /var/lib/mysql /var/lib/mariadb && DEBIAN_FRONTEND=noninteractive apt-get update`

const nodeDeinstallCommand = `export NVM_DIR="$HOME/.nvm" && if [ -s "$NVM_DIR/nvm.sh" ]; then . "$NVM_DIR/nvm.sh" && nvm deactivate 2>/dev/null || true; fi && rm -rf "$NVM_DIR" "$HOME/.npm" "$HOME/.node-gyp" && for profile in "$HOME/.bashrc" "$HOME/.zshrc" "$HOME/.profile"; do [ -f "$profile" ] && sed -i '/NVM_DIR/d;/nvm.sh/d;/bash_completion/d' "$profile" || true; done && SUDO="" && if [ "$(id -u)" != "0" ]; then SUDO="sudo"; fi && packages="$(dpkg-query -W -f='${binary:Package}\n' 2>/dev/null | grep -E '^(nodejs|npm|libnode[0-9]*|nodejs-doc)(:|$)' || true)" && if [ -n "$packages" ]; then $SUDO DEBIAN_FRONTEND=noninteractive apt-get remove --purge -y $packages; fi && $SUDO DEBIAN_FRONTEND=noninteractive apt-get autoremove -y && $SUDO DEBIAN_FRONTEND=noninteractive apt-get autoclean`

var serviceInstallCommands = map[string]serviceCommand{
	"php": {
		Command: "",
		Timeout: 300,
		UseSudo: true,
	},
	"apache": {
		Command: "DEBIAN_FRONTEND=noninteractive apt-get update && DEBIAN_FRONTEND=noninteractive apt-get install -y apache2 && systemctl enable --now apache2",
		Timeout: 300,
		UseSudo: true,
	},
	"mysql": {
		Command: "DEBIAN_FRONTEND=noninteractive apt-get update && DEBIAN_FRONTEND=noninteractive apt-get install -y mysql-server && systemctl enable --now mysql && mysql -e 'CREATE DATABASE IF NOT EXISTS `database`;'",
		Timeout: 300,
		UseSudo: true,
	},
	"node": {
		Command: `export NVM_DIR="$HOME/.nvm" && curl -fsSL https://raw.githubusercontent.com/nvm-sh/nvm/master/install.sh | bash && . "$NVM_DIR/nvm.sh" && nvm install node && nvm alias default node && node --version && npm --version`,
		Timeout: 300,
		UseSudo: false,
	},
	"nvm": {
		Command: `export NVM_DIR="$HOME/.nvm" && curl -fsSL https://raw.githubusercontent.com/nvm-sh/nvm/master/install.sh | bash && . "$NVM_DIR/nvm.sh" && nvm --version`,
		Timeout: 120,
		UseSudo: false,
	},
	"npm": {
		Command: `export NVM_DIR="$HOME/.nvm" && curl -fsSL https://raw.githubusercontent.com/nvm-sh/nvm/master/install.sh | bash && . "$NVM_DIR/nvm.sh" && nvm install node && nvm use node && npm install -g npm@latest && npm --version`,
		Timeout: 300,
		UseSudo: false,
	},
	"composer": {
		Command: `EXPECTED_CHECKSUM="$(curl -sS https://composer.github.io/installer.sig)" && php -r "copy('https://getcomposer.org/installer', 'composer-setup.php');" && ACTUAL_CHECKSUM="$(php -r 'echo hash_file("sha384", "composer-setup.php");')" && if [ "$EXPECTED_CHECKSUM" != "$ACTUAL_CHECKSUM" ]; then >&2 echo "ERROR: Invalid installer checksum"; rm -f composer-setup.php; exit 1; fi && php composer-setup.php && php -r "unlink('composer-setup.php');" && mv composer.phar /usr/local/bin/composer`,
		Timeout: 120,
		UseSudo: true,
	},
}

var serviceDeinstallCommands = map[string]serviceCommand{
	"php": {
		Command: "systemctl stop php*-fpm php*-cgi 2>/dev/null || true && DEBIAN_FRONTEND=noninteractive apt-get remove --purge -y 'php*' && DEBIAN_FRONTEND=noninteractive apt-get autoremove -y && DEBIAN_FRONTEND=noninteractive apt-get autoclean && rm -rf /etc/php && DEBIAN_FRONTEND=noninteractive apt-get update",
		Timeout: 120,
		UseSudo: true,
	},
	"apache": {
		Command: "systemctl stop apache2 2>/dev/null || true && DEBIAN_FRONTEND=noninteractive apt-get remove --purge -y apache2 apache2-bin apache2-data apache2-utils && DEBIAN_FRONTEND=noninteractive apt-get autoremove -y && DEBIAN_FRONTEND=noninteractive apt-get autoclean && rm -rf /etc/apache2",
		Timeout: 180,
		UseSudo: true,
	},
	"mysql": {
		Command: mysqlDeinstallCommand,
		Timeout: 180,
		UseSudo: true,
	},
	"node": {
		Command: nodeDeinstallCommand,
		Timeout: 120,
		UseSudo: false,
	},
	"nvm": {
		Command: `rm -rf "$HOME/.nvm" && sed -i "/NVM_DIR/d" ~/.bashrc ~/.zshrc ~/.profile 2>/dev/null || true`,
		Timeout: 60,
		UseSudo: false,
	},
	"npm": {
		Command: nodeDeinstallCommand,
		Timeout: 120,
		UseSudo: false,
	},
	"composer": {
		Command: "rm -f /usr/local/bin/composer && rm -rf ~/.composer ~/.cache/composer 2>/dev/null || true",
		Timeout: 60,
		UseSudo: true,
	},
}

func servicesInstallAction() actionDefinition {
	return actionDefinition{
		Name: "services.install",
		BuildCommand: func(payload map[string]any) (string, error) {
			service, err := servicePayloadName(payload)
			if err != nil {
				return "", err
			}
			if service == "php" {
				return phpInstallCommand(payload)
			}

			definition, exists := serviceInstallCommands[service]
			if !exists {
				return "", errors.New("unknown service")
			}

			return definition.Command, nil
		},
		BuildTimeout: func(payload map[string]any) (int, error) {
			definition, err := servicePayloadCommand(payload, serviceInstallCommands)
			if err != nil {
				return 0, err
			}

			return definition.Timeout, nil
		},
		BuildUseSudo: func(payload map[string]any) (bool, error) {
			definition, err := servicePayloadCommand(payload, serviceInstallCommands)
			if err != nil {
				return false, err
			}

			return definition.UseSudo, nil
		},
	}
}

func servicesDeinstallAction() actionDefinition {
	return serviceAction("services.deinstall", serviceDeinstallCommands)
}

func serviceAction(name string, commands map[string]serviceCommand) actionDefinition {
	return actionDefinition{
		Name: name,
		BuildCommand: func(payload map[string]any) (string, error) {
			definition, err := servicePayloadCommand(payload, commands)
			if err != nil {
				return "", err
			}

			return definition.Command, nil
		},
		BuildTimeout: func(payload map[string]any) (int, error) {
			definition, err := servicePayloadCommand(payload, commands)
			if err != nil {
				return 0, err
			}

			return definition.Timeout, nil
		},
		BuildUseSudo: func(payload map[string]any) (bool, error) {
			definition, err := servicePayloadCommand(payload, commands)
			if err != nil {
				return false, err
			}

			return definition.UseSudo, nil
		},
	}
}

func servicePayloadCommand(payload map[string]any, commands map[string]serviceCommand) (serviceCommand, error) {
	service, err := servicePayloadName(payload)
	if err != nil {
		return serviceCommand{}, err
	}
	definition, exists := commands[service]
	if !exists {
		return serviceCommand{}, errors.New("unknown service")
	}

	return definition, nil
}

func servicePayloadName(payload map[string]any) (string, error) {
	service, err := payloadString(payload, "service")
	if err != nil {
		return "", err
	}
	service = strings.TrimSpace(strings.ToLower(service))
	if service == "" || !apacheTokenPattern.MatchString(service) {
		return "", errors.New("service must be valid")
	}

	return service, nil
}

func phpInstallCommand(payload map[string]any) (string, error) {
	version, err := phpPayloadVersion(payload)
	if err != nil {
		return "", err
	}

	packages := make([]string, 0, len(phpPackages)+1)
	for _, name := range phpPackages {
		packages = append(packages, "php"+version+"-"+name)
	}
	packages = append(packages, "php"+version+"-pear")
	packageList := strings.Join(packages, " ")
	module := "php" + version

	return `DEBIAN_FRONTEND=noninteractive apt-get update && DEBIAN_FRONTEND=noninteractive apt-get install -y software-properties-common ca-certificates lsb-release apt-transport-https && if [ -f /etc/os-release ]; then . /etc/os-release; if [ "${ID:-}" = "ubuntu" ] && [ "${VERSION_ID:-}" != "26.04" ]; then add-apt-repository -y ppa:ondrej/php; fi; fi && DEBIAN_FRONTEND=noninteractive apt-get update && DEBIAN_FRONTEND=noninteractive apt-get install -y ` + packageList + ` && if [ -x /usr/bin/php` + version + ` ]; then update-alternatives --set php /usr/bin/php` + version + ` || update-alternatives --install /usr/bin/php php /usr/bin/php` + version + ` 85; fi && systemctl enable --now php` + version + `-fpm && if command -v apache2 >/dev/null 2>&1; then DEBIAN_FRONTEND=noninteractive apt-get install -y libapache2-mod-` + module + ` && for enabled_module in $(find /etc/apache2/mods-enabled -maxdepth 1 -name 'php*.load' -printf '%f\n' 2>/dev/null | sed 's/\.load$//'); do [ "$enabled_module" = "` + module + `" ] || a2dismod "$enabled_module" || true; done && a2enmod ` + module + ` && systemctl restart apache2; fi && php --version`, nil
}

func phpPayloadVersion(payload map[string]any) (string, error) {
	version, err := payloadString(payload, "version")
	if err != nil {
		version = "8.5"
	}
	version = strings.TrimSpace(version)
	if !allowedPHPVersions[version] {
		return "", errors.New("unsupported php version")
	}

	return version, nil
}
