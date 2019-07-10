<?php

namespace KaceDirect;

use PDO;

class PDOWrapper {

	public static function setup() {
		/** @var PDO $DB */ global $DB;

		$host = 'KACE';
		$user = 'R1';
		$password = 'box747';
		$default = 'ORG1';

		$warningLevel = ini_get('error_reporting');
		try {
			$DB = new \PDO("mysql:host=$host;dbname=$default;charset=utf8", $user, $password, [ //
				PDO::ATTR_PERSISTENT => true, //
				PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, //
			]);
			$DB->setAttribute(\PDO::ATTR_DEFAULT_FETCH_MODE, \PDO::FETCH_ASSOC);
			ini_set('error_reporting', $warningLevel);

			$connectionTest = $DB->query('SELECT "1" as `test`;')->fetchAll();
			if (!count($connectionTest) || !array_key_exists('test', $connectionTest[0])) {
				sendError('Connection failed! ' . "$host | $user | $password | $default");
			}
		} catch (\Exception $ex) {
			sendError($ex->getTraceAsString());
		}
	}
}
