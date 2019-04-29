<?php

namespace Ocean\Routing;

use Ocean\FastLog;
use Ocean\FastProfile;
use Ocean\FastUser;
use Ocean\RoutingBase;
use PDO;

class Logs extends RoutingBase {

	/**
	 * @param FastUser $user
	 * @return mixed
	 */
	public function call($user) {
		/** @var PDO $DB */ global $DB;

		if (!$user->hasRight('backup', FastProfile::READ)) {
			sendError('Needs backup rights', 403);
		}

		$db = 'glpi_logs';
		if (array_key_exists('HTTP_TYPE', $_SERVER)) {
			if ($_SERVER['HTTP_TYPE'] == 'events') {
				$db = 'glpi_events';
			}
		}

		$statement = $DB->query("SELECT * FROM $db ORDER BY id DESC");
		if (!$statement->execute()) {
			sendError("Can't get $db");
		}

		$logs = $statement->fetchAll();
		foreach ($logs as &$log) {
			if (array_key_exists('linked_action', $log)) {
				$log['linked_action'] = str_replace('_', '', strtolower(nameOfType(FastLog::HISTORY_OPTIONS, $log['linked_action'])));
			}
		}
		return $logs;
	}
}
