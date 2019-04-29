<?php

function plugin_ocean_replaceRegexInFile($fileLocation, $regex, $replaceWith) {
	$mailRuleClassFile = file_get_contents($fileLocation);

	$mailRuleClassFile = preg_replace($regex, $replaceWith, $mailRuleClassFile);

	file_put_contents($fileLocation, $mailRuleClassFile);
}

function plugin_ocean_install() {
	/** @var DBmysql $DB */
	global $DB;

	// Category
	if (!$DB->TableExists("glpi_plugin_ocean_category")) {
		$query = "CREATE TABLE `glpi_plugin_ocean_category` (
					`id` INT(3) UNSIGNED NOT NULL AUTO_INCREMENT,
					`category_i18n` VARCHAR(128) NOT NULL,
					`data` TEXT NOT NULL COMMENT 'json_array [{i18n_subcat: {... forms to autofill ...}}]',
					PRIMARY KEY (`id`),
					UNIQUE INDEX `category_i18n` (`category_i18n`)
				)
				COLLATE='utf8_bin'
				;";

		$DB->query($query) or die("error creating glpi_plugin_ocean_category " . $DB->error());

		$insert = "INSERT INTO `glpi`.`glpi_plugin_ocean_category` (`category_i18n`, `data`) VALUES ('ticket.other', '[{\"ticket.other\":{\"type\":[\"pre\",\"1\",{\"ticket.incident\":\"1\"},{\"ticket.request\":\"2\"}],\"category\":[\"empty\",\"glpi_itilcategories\",\"completename\",false],\"assignedToUser\":[\"empty\",\"glpi_users\",\"name\",false],\"assignedToGroup\":[\"empty\",\"glpi_groups\",\"name\",false],\"status\":[\"pre\",\"1\",{\"ticket.incoming\":\"1\"},{\"ticket.assigned\":\"2\"},{\"ticket.planned\":\"3\"},{\"ticket.waiting\":\"4\"},{\"ticket.solved\":\"5\"},{\"ticket.closed\":\"6\"},{\"ticket.accepted\":\"7\"},{\"ticket.observed\":\"8\"},{\"ticket.evaluation\":\"9\"},{\"ticket.approval\":\"10\"},{\"ticket.test\":\"11\"},{\"ticket.qualification\":\"12\"}],\"urgency\":[\"pre\",\"1\",{\"ticket.lowest\":\"1\"},{\"ticket.low\":\"2\"},{\"ticket.medium\":\"3\"},{\"ticket.high\":\"4\"},{\"ticket.highest\":\"5\"}],\"impact\":[\"pre\",\"1\",{\"ticket.lowest\":\"1\"},{\"ticket.low\":\"2\"},{\"ticket.medium\":\"3\"},{\"ticket.high\":\"4\"},{\"ticket.highest\":\"5\"}],\"priority\":[\"pre\",\"1\",{\"ticket.lowest\":\"1\"},{\"ticket.low\":\"2\"},{\"ticket.medium\":\"3\"},{\"ticket.high\":\"4\"},{\"ticket.highest\":\"5\"}],\"source\":[\"pre\",\"1\",\"name\",false],\"other-ticket\":[\"empty\",\"glpi_tickets\",\"name\",false],\"SLA-max-time\":[\"pre\",\"0\",{\"ticket.notime\":\"0\"},{\"00h15m\":\"900\"},{\"00h30m\":\"1800\"},{\"00h45m\":\"2700\"},{\"01h00m\":\"3600\"},{\"01h15m\":\"4500\"},{\"01h30m\":\"5400\"},{\"01h45m\":\"6300\"},{\"02h00m\":\"7200\"},{\"02h15m\":\"8100\"},{\"02h30m\":\"9000\"},{\"02h45m\":\"9900\"},{\"03h00m\":\"10800\"},{\"03h15m\":\"11700\"},{\"03h30m\":\"12600\"},{\"03h45m\":\"13500\"},{\"04h00m\":\"14400\"},{\"04h15m\":\"15300\"},{\"04h30m\":\"16200\"},{\"04h45m\":\"17100\"},{\"05h00m\":\"18000\"},{\"05h15m\":\"18900\"},{\"05h30m\":\"19800\"},{\"05h45m\":\"20700\"},{\"06h00m\":\"21600\"},{\"06h15m\":\"22500\"},{\"06h30m\":\"23400\"},{\"06h45m\":\"24300\"},{\"07h00m\":\"25200\"},{\"07h15m\":\"26100\"},{\"07h30m\":\"27000\"},{\"07h45m\":\"27900\"},{\"08h00m\":\"28800\"},{\"08h15m\":\"29700\"},{\"08h30m\":\"30600\"},{\"08h45m\":\"31500\"},{\"09h00m\":\"32400\"},{\"09h15m\":\"33300\"},{\"09h30m\":\"34200\"},{\"09h45m\":\"35100\"},{\"10h00m\":\"36000\"},{\"10h15m\":\"36900\"},{\"10h30m\":\"37800\"},{\"10h45m\":\"38700\"},{\"11h00m\":\"39600\"},{\"11h15m\":\"40500\"},{\"11h30m\":\"41400\"},{\"11h45m\":\"42300\"},{\"12h00m\":\"43200\"},{\"12h15m\":\"44100\"},{\"12h30m\":\"45000\"},{\"12h45m\":\"45900\"},{\"13h00m\":\"46800\"},{\"13h15m\":\"47700\"},{\"13h30m\":\"48600\"},{\"13h45m\":\"49500\"},{\"14h00m\":\"50400\"},{\"14h15m\":\"51300\"},{\"14h30m\":\"52200\"},{\"14h45m\":\"53100\"},{\"15h00m\":\"54000\"},{\"15h15m\":\"54900\"},{\"15h30m\":\"55800\"},{\"15h45m\":\"56700\"},{\"16h00m\":\"57600\"},{\"16h15m\":\"58500\"},{\"16h30m\":\"59400\"},{\"16h45m\":\"60300\"},{\"17h00m\":\"61200\"},{\"17h15m\":\"62100\"},{\"17h30m\":\"63000\"},{\"17h45m\":\"63900\"},{\"18h00m\":\"64800\"},{\"18h15m\":\"65700\"},{\"18h30m\":\"66600\"},{\"18h45m\":\"67500\"},{\"19h00m\":\"68400\"},{\"19h15m\":\"69300\"},{\"19h30m\":\"70200\"},{\"19h45m\":\"71100\"},{\"20h00m\":\"72000\"},{\"20h15m\":\"72900\"},{\"20h30m\":\"73800\"},{\"20h45m\":\"74700\"},{\"21h00m\":\"75600\"},{\"21h15m\":\"76500\"},{\"21h30m\":\"77400\"},{\"21h45m\":\"78300\"},{\"22h00m\":\"79200\"},{\"22h15m\":\"80100\"},{\"22h30m\":\"81000\"},{\"22h45m\":\"81900\"},{\"23h00m\":\"82800\"},{\"23h15m\":\"83700\"},{\"23h30m\":\"84600\"},{\"23h45m\":\"85500\"},{\"24h00m\":\"86400\"},{\"24h15m\":\"87300\"},{\"24h30m\":\"88200\"},{\"24h45m\":\"89100\"},{\"25h00m\":\"90000\"},{\"25h15m\":\"90900\"},{\"25h30m\":\"91800\"},{\"25h45m\":\"92700\"},{\"26h00m\":\"93600\"},{\"26h15m\":\"94500\"},{\"26h30m\":\"95400\"},{\"26h45m\":\"96300\"},{\"27h00m\":\"97200\"},{\"27h15m\":\"98100\"},{\"27h30m\":\"99000\"},{\"27h45m\":\"99900\"},{\"28h00m\":\"100800\"},{\"28h15m\":\"101700\"},{\"28h30m\":\"102600\"},{\"28h45m\":\"103500\"},{\"29h00m\":\"104400\"},{\"29h15m\":\"105300\"},{\"29h30m\":\"106200\"},{\"29h45m\":\"107100\"},{\"30h00m\":\"108000\"},{\"30h15m\":\"108900\"},{\"30h30m\":\"109800\"},{\"30h45m\":\"110700\"},{\"31h00m\":\"111600\"},{\"31h15m\":\"112500\"},{\"31h30m\":\"113400\"},{\"31h45m\":\"114300\"},{\"32h00m\":\"115200\"},{\"32h15m\":\"116100\"},{\"32h30m\":\"117000\"},{\"32h45m\":\"117900\"},{\"33h00m\":\"118800\"},{\"33h15m\":\"119700\"},{\"33h30m\":\"120600\"},{\"33h45m\":\"121500\"},{\"34h00m\":\"122400\"},{\"34h15m\":\"123300\"},{\"34h30m\":\"124200\"},{\"34h45m\":\"125100\"},{\"35h00m\":\"126000\"},{\"35h15m\":\"126900\"},{\"35h30m\":\"127800\"},{\"35h45m\":\"128700\"},{\"36h00m\":\"129600\"},{\"36h15m\":\"130500\"},{\"36h30m\":\"131400\"},{\"36h45m\":\"132300\"},{\"37h00m\":\"133200\"},{\"37h15m\":\"134100\"},{\"37h30m\":\"135000\"},{\"37h45m\":\"135900\"},{\"38h00m\":\"136800\"},{\"38h15m\":\"137700\"},{\"38h30m\":\"138600\"},{\"38h45m\":\"139500\"},{\"39h00m\":\"140400\"},{\"39h15m\":\"141300\"},{\"39h30m\":\"142200\"},{\"39h45m\":\"143100\"},{\"40h00m\":\"144000\"},{\"40h15m\":\"144900\"},{\"40h30m\":\"145800\"},{\"40h45m\":\"146700\"},{\"41h00m\":\"147600\"},{\"41h15m\":\"148500\"},{\"41h30m\":\"149400\"},{\"41h45m\":\"150300\"},{\"42h00m\":\"151200\"},{\"42h15m\":\"152100\"},{\"42h30m\":\"153000\"},{\"42h45m\":\"153900\"},{\"43h00m\":\"154800\"},{\"43h15m\":\"155700\"},{\"43h30m\":\"156600\"},{\"43h45m\":\"157500\"},{\"44h00m\":\"158400\"},{\"44h15m\":\"159300\"},{\"44h30m\":\"160200\"},{\"44h45m\":\"161100\"},{\"45h00m\":\"162000\"},{\"45h15m\":\"162900\"},{\"45h30m\":\"163800\"},{\"45h45m\":\"164700\"},{\"46h00m\":\"165600\"},{\"46h15m\":\"166500\"},{\"46h30m\":\"167400\"},{\"46h45m\":\"168300\"},{\"47h00m\":\"169200\"},{\"47h15m\":\"170100\"},{\"47h30m\":\"171000\"},{\"47h45m\":\"171900\"}],\"title\":[\"input\",\"text\"],\"description\":[\"text\"],\"file\":[\"file\"]}}]');";
		$DB->query($insert);
	}

	// Config
	if (!$DB->TableExists("glpi_plugin_ocean_config")) {
		$query = "CREATE TABLE `glpi_plugin_ocean_config` (
					`id` INT(3) UNSIGNED NOT NULL AUTO_INCREMENT,
					`what` VARCHAR(128) NOT NULL,
					`setting` VARCHAR(128) NOT NULL,
					PRIMARY KEY (`id`),
					UNIQUE INDEX `what` (`what`)
				)
				COLLATE='utf8_bin'
				;";

		$DB->query($query) or die("error creating glpi_plugin_ocean_config " . $DB->error());

		$DB->query("INSERT INTO `glpi`.`glpi_plugin_ocean_config` (`what`, `setting`) VALUES ('overwrite_login', '1');") or die('insert config error');
		$DB->query("INSERT INTO `glpi`.`glpi_plugin_ocean_config` (`what`, `setting`) VALUES ('inject_header', '1');") or die('insert config error');
	}

	return true;
}

function plugin_ocean_uninstall() {
	/** @var DBmysql $DB */
	global $DB;

	$drop_category = "DROP TABLE glpi_plugin_ocean_category;";
	$DB->query($drop_category);

	$drop_config = "DROP TABLE glpi_plugin_ocean_config;";
	$DB->query($drop_config);

	return true;
}
