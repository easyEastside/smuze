package main

func rebootAction() actionDefinition {
	return actionDefinition{
		Name:    "system.reboot",
		Command: "reboot",
		Timeout: 30,
		UseSudo: true,
	}
}

func shutdownAction() actionDefinition {
	return actionDefinition{
		Name:    "system.shutdown",
		Command: "shutdown -h now",
		Timeout: 30,
		UseSudo: true,
	}
}
