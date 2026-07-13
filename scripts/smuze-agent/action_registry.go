package main

var systemActions = registerActionGroups(
	apacheActionDefinitions(),
	aptActionDefinitions(),
	backupActionDefinitions(),
	cronjobActionDefinitions(),
	dockerActionDefinitions(),
	fileActionDefinitions(),
	firewallActionDefinitions(),
	githubActionDefinitions(),
	laravelDeployActionDefinitions(),
	monitoringActionDefinitions(),
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

func backupActionDefinitions() []actionDefinition {
	return []actionDefinition{backupRunAction(), backupListAction(), backupDeleteAction(), backupRestoreAction(), backupPruneAction()}
}

func cronjobActionDefinitions() []actionDefinition {
	return []actionDefinition{cronjobsListAction(), cronjobsInstallAction(), cronjobsRemoveAction(), cronjobsRunAction()}
}

func firewallActionDefinitions() []actionDefinition {
	return []actionDefinition{firewallStatusAction(), firewallRulesAction(), firewallInstallAction(), firewallEnableAction(), firewallDisableAction(), firewallAllowAction(), firewallDenyAction(), firewallDeleteAction(), firewallAllowStandardPortsAction()}
}

func githubActionDefinitions() []actionDefinition {
	return []actionDefinition{githubDeployAction()}
}

func laravelDeployActionDefinitions() []actionDefinition {
	return []actionDefinition{laravelDeployAction()}
}

func monitoringActionDefinitions() []actionDefinition {
	return []actionDefinition{monitoringProcessesAction(), monitoringServicesAction(), monitoringServiceStartAction(), monitoringServiceStopAction(), monitoringServiceRestartAction(), monitoringProcessKillAction()}
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

func dockerActionDefinitions() []actionDefinition {
	return []actionDefinition{
		dockerStatusAction(), dockerInfoAction(), dockerInstallAction(), dockerDeinstallAction(),
		dockerStartAction(), dockerStopAction(), dockerRestartAction(),
		dockerPsAction(), dockerContainerStartAction(), dockerContainerStopAction(),
		dockerContainerRestartAction(), dockerContainerRemoveAction(), dockerContainerLogsAction(),
		dockerContainerExecAction(), dockerContainerCreateAction(), dockerContainerInspectAction(),
		dockerImagesAction(), dockerImagePullAction(), dockerImageRemoveAction(),
		dockerNetworksAction(),
		dockerComposePsAction(), dockerComposeUpAction(), dockerComposeDownAction(),
		dockerSystemPruneAction(), dockerStatsAction(),
	}
}

func systemActionDefinitions() []actionDefinition {
	return []actionDefinition{rebootAction(), shutdownAction()}
}
