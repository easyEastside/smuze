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
	"fpm",
	"common",
	"mysql",
	"curl",
	"mbstring",
	"xml",
	"zip",
	"gd",
	"intl",
	"bcmath",
}

var phpFpmVersions = []string{"8.2", "8.3", "8.4", "8.5"}

const apacheDeinstallCommand = `systemctl disable --now apache2 2>/dev/null || true && DEBIAN_FRONTEND=noninteractive apt-get purge -y apache2 apache2-bin apache2-data apache2-utils && DEBIAN_FRONTEND=noninteractive apt-get autoremove --purge -y && rm -rf /etc/apache2 && apt-get clean && systemctl daemon-reload && systemctl reset-failed`

const mysqlDeinstallCommand = `systemctl disable --now mysql 2>/dev/null || true && DEBIAN_FRONTEND=noninteractive apt-get purge -y mysql-server mysql-client mysql-common && packages="$(dpkg-query -W -f='${binary:Package}\n' 2>/dev/null | grep -E '^(mysql|mariadb|percona)[A-Za-z0-9+_.:-]*$' || true)" && if [ -n "$packages" ]; then DEBIAN_FRONTEND=noninteractive apt-get purge -y $packages; fi && DEBIAN_FRONTEND=noninteractive apt-get autoremove --purge -y && rm -rf /var/lib/mysql /etc/mysql /var/log/mysql /var/log/mysql.* && apt-get clean && systemctl daemon-reload && systemctl reset-failed`

const nodeDeinstallCommand = `export NVM_DIR="$HOME/.nvm" && if [ -s "$NVM_DIR/nvm.sh" ]; then . "$NVM_DIR/nvm.sh" && nvm deactivate 2>/dev/null || true; fi && rm -rf "$NVM_DIR" "$HOME/.npm" "$HOME/.node-gyp" && for profile in "$HOME/.bashrc" "$HOME/.zshrc" "$HOME/.profile"; do [ -f "$profile" ] && sed -i '/NVM_DIR/d;/nvm.sh/d;/bash_completion/d' "$profile" || true; done && SUDO="" && if [ "$(id -u)" != "0" ]; then SUDO="sudo"; fi && packages="$(dpkg-query -W -f='${binary:Package}\n' 2>/dev/null | grep -E '^(nodejs|npm|libnode[0-9]*|nodejs-doc)(:|$)' || true)" && if [ -n "$packages" ]; then $SUDO env DEBIAN_FRONTEND=noninteractive apt-get purge -y $packages; fi && $SUDO env DEBIAN_FRONTEND=noninteractive apt-get autoremove --purge -y && $SUDO apt-get clean && $SUDO systemctl daemon-reload && $SUDO systemctl reset-failed`

const nvmDeinstallCommand = `export NVM_DIR="$HOME/.nvm" && if [ -s "$NVM_DIR/nvm.sh" ]; then . "$NVM_DIR/nvm.sh" && nvm deactivate 2>/dev/null || true; fi && rm -rf "$NVM_DIR" && for profile in "$HOME/.bashrc" "$HOME/.zshrc" "$HOME/.profile"; do [ -f "$profile" ] && sed -i '/NVM_DIR/d;/nvm.sh/d;/bash_completion/d' "$profile" || true; done`

const composerDeinstallCommand = `SUDO="" && if [ "$(id -u)" != "0" ]; then SUDO="sudo"; fi && $SUDO rm -f /usr/local/bin/composer && if dpkg-query -W composer >/dev/null 2>&1; then $SUDO env DEBIAN_FRONTEND=noninteractive apt-get purge -y composer; fi && $SUDO env DEBIAN_FRONTEND=noninteractive apt-get autoremove --purge -y && $SUDO apt-get clean && rm -rf "$HOME/.composer" "$HOME/.cache/composer" "$HOME/.config/composer" && $SUDO systemctl daemon-reload && $SUDO systemctl reset-failed`

var phpDeinstallCommand = `for version in ` + strings.Join(phpFpmVersions, " ") + `; do a2disconf "php${version}-fpm" 2>/dev/null || true; systemctl disable --now "php${version}-fpm" 2>/dev/null || true; done && packages="$(dpkg-query -W -f='${binary:Package}\n' 2>/dev/null | grep -E '^php8\.[2345][A-Za-z0-9+_.:-]*$' || true)" && if [ -n "$packages" ]; then DEBIAN_FRONTEND=noninteractive apt-get purge -y $packages; fi && generic_packages="$(dpkg-query -W -f='${binary:Package}\n' 2>/dev/null | grep -E '^php([:-]|$)' || true)" && if [ -n "$generic_packages" ]; then DEBIAN_FRONTEND=noninteractive apt-get purge -y $generic_packages; fi && DEBIAN_FRONTEND=noninteractive apt-get autoremove --purge -y && systemctl reload apache2 2>/dev/null || true && rm -f /etc/apt/sources.list.d/php.list /usr/share/keyrings/debsuryorg-archive-keyring.gpg && if dpkg-query -W debsuryorg-archive-keyring >/dev/null 2>&1; then DEBIAN_FRONTEND=noninteractive apt-get purge -y debsuryorg-archive-keyring; fi && DEBIAN_FRONTEND=noninteractive apt-get update && apt-get clean && systemctl daemon-reload && systemctl reset-failed`

const nvmInstallCommand = `export NVM_DIR="$HOME/.nvm" && curl -o- https://raw.githubusercontent.com/nvm-sh/nvm/v0.40.5/install.sh | bash && . "$NVM_DIR/nvm.sh"`

const nodeInstallCommand = nvmInstallCommand + ` && nvm install 24 && nvm alias default 24 && nvm use 24 && node -v && npm -v`

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
	"nginx": {
		Command: "DEBIAN_FRONTEND=noninteractive apt-get update && DEBIAN_FRONTEND=noninteractive apt-get install -y nginx && systemctl enable --now nginx",
		Timeout: 300,
		UseSudo: true,
	},
	"mysql": {
		Command: "DEBIAN_FRONTEND=noninteractive apt-get update && DEBIAN_FRONTEND=noninteractive apt-get install -y mysql-server && systemctl enable --now mysql && mysql -e 'CREATE DATABASE IF NOT EXISTS `database`;'",
		Timeout: 300,
		UseSudo: true,
	},
	"node": {
		Command: nodeInstallCommand,
		Timeout: 300,
		UseSudo: false,
	},
	"nvm": {
		Command: nvmInstallCommand + ` && nvm --version`,
		Timeout: 120,
		UseSudo: false,
	},
	"npm": {
		Command: nodeInstallCommand,
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
		Command: phpDeinstallCommand,
		Timeout: 180,
		UseSudo: true,
	},
	"apache": {
		Command: apacheDeinstallCommand,
		Timeout: 180,
		UseSudo: true,
	},
	"nginx": {
		Command: nginxDeinstallCommand,
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
		Command: nvmDeinstallCommand,
		Timeout: 60,
		UseSudo: false,
	},
	"npm": {
		Command: nodeDeinstallCommand,
		Timeout: 120,
		UseSudo: false,
	},
	"composer": {
		Command: composerDeinstallCommand,
		Timeout: 60,
		UseSudo: false,
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

	packages := make([]string, 0, len(phpPackages))
	for _, name := range phpPackages {
		packages = append(packages, "php"+version+"-"+name)
	}
	packageList := strings.Join(packages, " ")
	fpmConf := "php" + version + "-fpm"

	return `DEBIAN_FRONTEND=noninteractive apt-get update && DEBIAN_FRONTEND=noninteractive apt-get install -y lsb-release ca-certificates curl && curl -sSLo /tmp/debsuryorg-archive-keyring.deb https://packages.sury.org/debsuryorg-archive-keyring.deb && dpkg -i /tmp/debsuryorg-archive-keyring.deb && echo "deb [signed-by=/usr/share/keyrings/debsuryorg-archive-keyring.gpg] https://packages.sury.org/php/ $(lsb_release -sc) main" > /etc/apt/sources.list.d/php.list && DEBIAN_FRONTEND=noninteractive apt-get update && DEBIAN_FRONTEND=noninteractive apt-get install -y ` + packageList + ` && systemctl enable --now php` + version + `-fpm && if [ -x /usr/bin/php` + version + ` ]; then update-alternatives --set php /usr/bin/php` + version + ` || update-alternatives --install /usr/bin/php php /usr/bin/php` + version + ` 85; fi && if command -v apache2 >/dev/null 2>&1; then a2enmod proxy_fcgi setenvif && for version in ` + strings.Join(phpFpmVersions, " ") + `; do a2disconf "php${version}-fpm" 2>/dev/null || true; done && a2enconf ` + fpmConf + ` && systemctl reload apache2; fi && php` + version + ` -v && php --version`, nil
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
