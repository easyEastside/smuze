package main

var systemActions = registerActions(
	aptUpdateAction(),
	aptUpgradeAction(),
	firewallStatusAction(),
	firewallRulesAction(),
	firewallInstallAction(),
	firewallEnableAction(),
	firewallDisableAction(),
	firewallAllowAction(),
	firewallDenyAction(),
	firewallDeleteAction(),
	firewallAllowStandardPortsAction(),
	rebootAction(),
	shutdownAction(),
)
