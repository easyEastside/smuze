package main

func aptUpdateAction() actionDefinition {
	return actionDefinition{
		Name:    "system.apt_update",
		Command: "apt-get update -y --allow-releaseinfo-change",
		Timeout: 300,
		UseSudo: true,
	}
}

func aptUpgradeAction() actionDefinition {
	return actionDefinition{
		Name:    "system.apt_upgrade",
		Command: "DEBIAN_FRONTEND=noninteractive apt-get upgrade -y",
		Timeout: 3600,
		UseSudo: true,
	}
}
