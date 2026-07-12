package main

var systemActions = registerActions(
	aptUpdateAction(),
	aptUpgradeAction(),
	rebootAction(),
	shutdownAction(),
)
