<?php

namespace Ocean;

use PDO;

class FastConfig {

	public static function getConfig() {
		/** @var PDO $DB */ global $DB;

		$GLPI_CONFIG = [];
		foreach ($DB->query("SELECT * FROM glpi_configs WHERE context='core'")->fetchAll() as $configRow){
			$GLPI_CONFIG[$configRow['name']] = $configRow['value'];
		}

		return $GLPI_CONFIG;
	}

}
