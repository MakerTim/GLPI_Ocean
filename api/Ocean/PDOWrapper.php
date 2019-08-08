<?php

namespace Ocean;

use PDO;

class PDOWrapper {

	public static function setup() {
		/** @var PDO $DB */ global $DB;

		$databaseSettings = file_get_contents(GLPI_ROOT . '/config/config_db.php');

		$host = $user = $password = $default = null;
		$variables = ['host', 'user', 'password', 'default'];

		foreach ($variables as $variable) {
			if (preg_match('/db' . $variable . '\s*?=\s*?[\'|"](.+)[\'|"];/', $databaseSettings, $matches, PREG_OFFSET_CAPTURE)) {
				$$variable = $matches[1][0];
			}
		}

		if (!preg_match('/extends (.+?)\s*{/', $databaseSettings, $matches)) {
			sendError('No DB type found');
		}
		$dbType = strtolower(substr($matches[1], 2));

		$warningLevel = ini_get('error_reporting');
		try {
			error_reporting(E_ALL ^ E_WARNING);
			$DB = new \PDO("$dbType:host=$host;dbname=$default;charset=utf8", $user, $password, [ //
				PDO::ATTR_PERSISTENT => true, //
				PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, //
			]);
			$DB->setAttribute(\PDO::ATTR_DEFAULT_FETCH_MODE, \PDO::FETCH_ASSOC);
			ini_set('error_reporting', $warningLevel);

			$connectionTest = $DB->query('SELECT "1" as `test`;')->fetchAll();
			if (!count($connectionTest) || !array_key_exists('test', $connectionTest[0])) {
				sendError('Connection failed! ' . "$host | $user | $password | $default");
			}
		} catch (\Throwable $ex) {
			sendError($ex->getTraceAsString());
		}
	}
}
