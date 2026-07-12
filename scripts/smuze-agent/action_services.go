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

var serviceInstallCommands = map[string]serviceCommand{
	"php": {
		Command: "DEBIAN_FRONTEND=noninteractive apt-get update && DEBIAN_FRONTEND=noninteractive apt-get install -y php php-cli php-common php-fpm php-cgi php-mysql php-pgsql php-sqlite3 php-curl php-gd php-mbstring php-xml php-zip php-bcmath php-intl php-soap php-xmlrpc php-opcache php-readline php-pear",
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
		Command: "systemctl stop mysql 2>/dev/null || true && DEBIAN_FRONTEND=noninteractive apt-get remove --purge -y mysql-server mysql-client mysql-common && DEBIAN_FRONTEND=noninteractive apt-get autoremove -y && DEBIAN_FRONTEND=noninteractive apt-get autoclean && rm -rf /etc/mysql /var/lib/mysql && DEBIAN_FRONTEND=noninteractive apt-get update",
		Timeout: 120,
		UseSudo: true,
	},
	"node": {
		Command: `export NVM_DIR="$HOME/.nvm" && [ -s "$NVM_DIR/nvm.sh" ] && . "$NVM_DIR/nvm.sh" || true && nvm deactivate 2>/dev/null || true && rm -rf "$NVM_DIR" && sed -i "/NVM_DIR/d" ~/.bashrc ~/.zshrc ~/.profile 2>/dev/null || true`,
		Timeout: 120,
		UseSudo: false,
	},
	"nvm": {
		Command: `rm -rf "$HOME/.nvm" && sed -i "/NVM_DIR/d" ~/.bashrc ~/.zshrc ~/.profile 2>/dev/null || true`,
		Timeout: 60,
		UseSudo: false,
	},
	"npm": {
		Command: `export NVM_DIR="$HOME/.nvm" && [ -s "$NVM_DIR/nvm.sh" ] && . "$NVM_DIR/nvm.sh" || true && nvm deactivate 2>/dev/null || true && rm -rf "$NVM_DIR" && sed -i "/NVM_DIR/d" ~/.bashrc ~/.zshrc ~/.profile 2>/dev/null || true`,
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
	return serviceAction("services.install", serviceInstallCommands)
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
