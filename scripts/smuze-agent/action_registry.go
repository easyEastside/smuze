package main

var systemActions = registerActionGroups(
	apacheActionDefinitions(),
	aptActionDefinitions(),
	firewallActionDefinitions(),
	githubActionDefinitions(),
	mysqlActionDefinitions(),
	nginxActionDefinitions(),
	serviceActionDefinitions(),
	systemActionDefinitions(),
)

func apacheActionDefinitions() []actionDefinition {
	return []actionDefinition{
		apacheStatusAction(), apacheInstallAction(), apacheDeinstallAction(), apacheStartAction(), apacheStopAction(), apacheRestartAction(), apacheReloadAction(), apacheConfigtestAction(), apacheSitesAction(), apacheSiteConfigAction(), apacheSaveSiteConfigAction(), apacheEnableSiteAction(), apacheDisableSiteAction(), apacheDeleteSiteAction(), apacheCreateVhostAction(), apacheModulesAction(), apacheEnableModuleAction(), apacheDisableModuleAction(), apacheInstallCertbotAction(), apacheObtainSslAction(),
	}
}

func aptActionDefinitions() []actionDefinition {
	return []actionDefinition{aptUpdateAction(), aptUpgradeAction()}
}

func firewallActionDefinitions() []actionDefinition {
	return []actionDefinition{firewallStatusAction(), firewallRulesAction(), firewallInstallAction(), firewallEnableAction(), firewallDisableAction(), firewallAllowAction(), firewallDenyAction(), firewallDeleteAction(), firewallAllowStandardPortsAction()}
}

func githubActionDefinitions() []actionDefinition {
	return []actionDefinition{githubDeployAction()}
}

func mysqlActionDefinitions() []actionDefinition {
	return []actionDefinition{mysqlStatusAction(), mysqlInstallAction(), mysqlDeinstallAction(), mysqlStartAction(), mysqlStopAction(), mysqlRestartAction(), mysqlDatabasesAction(), mysqlCreateDatabaseAction(), mysqlDropDatabaseAction(), mysqlTablesAction(), mysqlCreateTableAction(), mysqlDropTableAction(), mysqlUsersAction(), mysqlCreateUserAction(), mysqlDropUserAction(), mysqlSetPasswordAction(), mysqlGrantAllAction()}
}

func nginxActionDefinitions() []actionDefinition {
	return []actionDefinition{nginxStatusAction(), nginxInstallAction(), nginxDeinstallAction(), nginxStartAction(), nginxStopAction(), nginxRestartAction(), nginxReloadAction(), nginxConfigtestAction(), nginxSitesAction(), nginxSiteConfigAction(), nginxSaveSiteConfigAction(), nginxEnableSiteAction(), nginxDisableSiteAction(), nginxDeleteSiteAction(), nginxCreateVhostAction(), nginxInstallCertbotAction(), nginxObtainSslAction()}
}

func serviceActionDefinitions() []actionDefinition {
	return []actionDefinition{servicesInstallAction(), servicesDeinstallAction()}
}

func systemActionDefinitions() []actionDefinition {
	return []actionDefinition{rebootAction(), shutdownAction()}
}
