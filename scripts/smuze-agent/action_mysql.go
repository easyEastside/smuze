package main

import (
	"errors"
	"fmt"
	"regexp"
	"strings"
)

var (
	mysqlIdentifierPattern = regexp.MustCompile(`^[A-Za-z0-9_][A-Za-z0-9_-]{0,63}$`)
	mysqlUserPattern       = regexp.MustCompile(`^[A-Za-z0-9_][A-Za-z0-9_.-]{0,31}$`)
	mysqlHostPattern       = regexp.MustCompile(`^(%|localhost|[A-Za-z0-9](?:[A-Za-z0-9.\-]{0,251}[A-Za-z0-9])?)$`)
)

var mysqlSystemDatabases = map[string]bool{
	"information_schema": true,
	"mysql":              true,
	"performance_schema": true,
	"sys":                true,
}

func mysqlStatusAction() actionDefinition {
	return actionDefinition{
		Name:    "mysql.status",
		Command: `printf "ACTIVE=%s\n" "$(systemctl is-active mysql 2>/dev/null || echo unknown)" && (mysql --version 2>/dev/null || echo "NOT_INSTALLED")`,
		Timeout: 15,
		UseSudo: true,
	}
}

func mysqlInstallAction() actionDefinition {
	return actionDefinition{
		Name: "mysql.install",
		BuildCommand: func(payload map[string]any) (string, error) {
			database, err := mysqlPayloadIdentifier(payload, "db_name", "database")
			if err != nil {
				return "", err
			}

			sql := fmt.Sprintf("CREATE DATABASE IF NOT EXISTS %s CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci", mysqlQuoteIdentifier(database))

			return "DEBIAN_FRONTEND=noninteractive apt update && DEBIAN_FRONTEND=noninteractive apt install mysql-server -y && systemctl start mysql && systemctl enable mysql && mysql -e " + shellQuote(sql), nil
		},
		Timeout: 300,
		UseSudo: true,
	}
}

func mysqlDeinstallAction() actionDefinition {
	return actionDefinition{
		Name:    "mysql.deinstall",
		Command: "systemctl stop mysql 2>/dev/null || true && DEBIAN_FRONTEND=noninteractive apt remove --purge mysql-server mysql-client mysql-common -y && DEBIAN_FRONTEND=noninteractive apt autoremove -y && DEBIAN_FRONTEND=noninteractive apt autoclean && rm -rf /etc/mysql /var/lib/mysql",
		Timeout: 180,
		UseSudo: true,
	}
}

func mysqlStartAction() actionDefinition {
	return mysqlServiceAction("mysql.start", "start")
}

func mysqlStopAction() actionDefinition {
	return mysqlServiceAction("mysql.stop", "stop")
}

func mysqlRestartAction() actionDefinition {
	return mysqlServiceAction("mysql.restart", "restart")
}

func mysqlServiceAction(name string, action string) actionDefinition {
	return actionDefinition{
		Name:    name,
		Command: "systemctl " + action + " mysql",
		Timeout: 30,
		UseSudo: true,
	}
}

func mysqlDatabasesAction() actionDefinition {
	return mysqlRowsAction("mysql.databases", "SHOW DATABASES", 15)
}

func mysqlCreateDatabaseAction() actionDefinition {
	return actionDefinition{
		Name: "mysql.create_database",
		BuildCommand: func(payload map[string]any) (string, error) {
			database, err := mysqlPayloadIdentifier(payload, "db_name", "")
			if err != nil {
				return "", err
			}

			return mysqlExecCommand(fmt.Sprintf("CREATE DATABASE %s CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci", mysqlQuoteIdentifier(database))), nil
		},
		Timeout: 15,
		UseSudo: true,
	}
}

func mysqlDropDatabaseAction() actionDefinition {
	return actionDefinition{
		Name: "mysql.drop_database",
		BuildCommand: func(payload map[string]any) (string, error) {
			database, err := mysqlPayloadIdentifier(payload, "db_name", "")
			if err != nil {
				return "", err
			}

			if mysqlSystemDatabases[strings.ToLower(database)] {
				return "", errors.New("system databases cannot be dropped")
			}

			return mysqlExecCommand(fmt.Sprintf("DROP DATABASE IF EXISTS %s", mysqlQuoteIdentifier(database))), nil
		},
		Timeout: 15,
		UseSudo: true,
	}
}

func mysqlTablesAction() actionDefinition {
	return actionDefinition{
		Name: "mysql.tables",
		BuildCommand: func(payload map[string]any) (string, error) {
			database, err := mysqlPayloadIdentifier(payload, "database", "")
			if err != nil {
				return "", err
			}

			return mysqlRowsCommand(fmt.Sprintf("SHOW TABLES FROM %s", mysqlQuoteIdentifier(database))), nil
		},
		Timeout: 15,
		UseSudo: true,
	}
}

func mysqlCreateTableAction() actionDefinition {
	return actionDefinition{
		Name: "mysql.create_table",
		BuildCommand: func(payload map[string]any) (string, error) {
			database, err := mysqlPayloadIdentifier(payload, "database", "")
			if err != nil {
				return "", err
			}

			sql, err := payloadString(payload, "sql")
			if err != nil {
				return "", err
			}
			if len(strings.TrimSpace(sql)) < 10 {
				return "", errors.New("sql is too short")
			}

			return mysqlExecCommand(fmt.Sprintf("USE %s; %s", mysqlQuoteIdentifier(database), sql)), nil
		},
		Timeout: 30,
		UseSudo: true,
	}
}

func mysqlDropTableAction() actionDefinition {
	return actionDefinition{
		Name: "mysql.drop_table",
		BuildCommand: func(payload map[string]any) (string, error) {
			database, err := mysqlPayloadIdentifier(payload, "database", "")
			if err != nil {
				return "", err
			}
			table, err := mysqlPayloadIdentifier(payload, "table", "")
			if err != nil {
				return "", err
			}

			return mysqlExecCommand(fmt.Sprintf("DROP TABLE IF EXISTS %s.%s", mysqlQuoteIdentifier(database), mysqlQuoteIdentifier(table))), nil
		},
		Timeout: 15,
		UseSudo: true,
	}
}

func mysqlUsersAction() actionDefinition {
	return mysqlRowsAction("mysql.users", "SELECT User, Host, '*****' FROM mysql.user ORDER BY User", 15)
}

func mysqlCreateUserAction() actionDefinition {
	return actionDefinition{
		Name: "mysql.create_user",
		BuildCommand: func(payload map[string]any) (string, error) {
			username, host, password, err := mysqlPayloadUser(payload, true)
			if err != nil {
				return "", err
			}

			return mysqlExecCommand(fmt.Sprintf("CREATE USER %s@%s IDENTIFIED BY %s", mysqlQuoteLiteral(username), mysqlQuoteLiteral(host), mysqlQuoteLiteral(password))), nil
		},
		Timeout: 15,
		UseSudo: true,
	}
}

func mysqlDropUserAction() actionDefinition {
	return actionDefinition{
		Name: "mysql.drop_user",
		BuildCommand: func(payload map[string]any) (string, error) {
			username, host, _, err := mysqlPayloadUser(payload, false)
			if err != nil {
				return "", err
			}

			return mysqlExecCommand(fmt.Sprintf("DROP USER IF EXISTS %s@%s", mysqlQuoteLiteral(username), mysqlQuoteLiteral(host))), nil
		},
		Timeout: 15,
		UseSudo: true,
	}
}

func mysqlSetPasswordAction() actionDefinition {
	return actionDefinition{
		Name: "mysql.set_password",
		BuildCommand: func(payload map[string]any) (string, error) {
			username, host, password, err := mysqlPayloadUser(payload, true)
			if err != nil {
				return "", err
			}

			return mysqlExecCommand(fmt.Sprintf("ALTER USER %s@%s IDENTIFIED BY %s", mysqlQuoteLiteral(username), mysqlQuoteLiteral(host), mysqlQuoteLiteral(password))), nil
		},
		Timeout: 15,
		UseSudo: true,
	}
}

func mysqlGrantAllAction() actionDefinition {
	return actionDefinition{
		Name: "mysql.grant_all",
		BuildCommand: func(payload map[string]any) (string, error) {
			username, host, _, err := mysqlPayloadUser(payload, false)
			if err != nil {
				return "", err
			}

			return mysqlExecCommand(fmt.Sprintf("GRANT ALL PRIVILEGES ON *.* TO %s@%s WITH GRANT OPTION", mysqlQuoteLiteral(username), mysqlQuoteLiteral(host))), nil
		},
		Timeout: 15,
		UseSudo: true,
	}
}

func mysqlRowsAction(name string, sql string, timeout int) actionDefinition {
	return actionDefinition{
		Name:    name,
		Command: mysqlRowsCommand(sql),
		Timeout: timeout,
		UseSudo: true,
	}
}

func mysqlExecCommand(sql string) string {
	return "mysql -e " + shellQuote(sql) + " 2>&1"
}

func mysqlRowsCommand(sql string) string {
	return "mysql -NBe " + shellQuote(sql) + " 2>&1"
}

func mysqlPayloadIdentifier(payload map[string]any, key string, fallback string) (string, error) {
	value := fallback
	if fallback == "" || payload[key] != nil {
		payloadValue, err := payloadString(payload, key)
		if err != nil {
			return "", err
		}
		value = payloadValue
	}

	value = strings.TrimSpace(value)
	if !mysqlIdentifierPattern.MatchString(value) {
		return "", fmt.Errorf("%s must be a valid identifier", key)
	}

	return value, nil
}

func mysqlPayloadUser(payload map[string]any, requirePassword bool) (string, string, string, error) {
	username, err := payloadString(payload, "username")
	if err != nil {
		return "", "", "", err
	}
	host, err := payloadString(payload, "host")
	if err != nil {
		return "", "", "", err
	}

	username = strings.TrimSpace(username)
	host = strings.TrimSpace(host)

	if !mysqlUserPattern.MatchString(username) {
		return "", "", "", errors.New("username must be valid")
	}
	if !mysqlHostPattern.MatchString(host) || strings.Contains(host, "..") {
		return "", "", "", errors.New("host must be valid")
	}

	password := ""
	if requirePassword {
		password, err = payloadString(payload, "password")
		if err != nil {
			return "", "", "", err
		}
		if password == "" {
			return "", "", "", errors.New("password is required")
		}
	}

	return username, host, password, nil
}

func mysqlQuoteIdentifier(value string) string {
	return "`" + strings.ReplaceAll(value, "`", "``") + "`"
}

func mysqlQuoteLiteral(value string) string {
	value = strings.ReplaceAll(value, `\`, `\\`)
	value = strings.ReplaceAll(value, `'`, `\'`)

	return "'" + value + "'"
}
