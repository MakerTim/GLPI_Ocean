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
			if ($_SERVER['HTTP_TYPE'] == 'file') {
				return $this->logfile();
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

	public function logfile() {
		if (!array_key_exists('HTTP_SUBTYPE', $_SERVER)) {
			return [];
		}
		$subtype = $_SERVER['HTTP_SUBTYPE'];
		if (!in_array($subtype, ['cron', 'event', 'php-errors', 'mail', 'sql-errors'])) {
			sendError('Not supported log type');
		}

		$readFile = file_get_contents(GLPI_ROOT . 'files/_log/' . $subtype . '.log');
		$regex = '/^\[?(\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2})\]? ([^\n]+)\n([^\n]+)(?:\n? {2}([^\n]+))*/m';
		preg_match_all($regex, $readFile, $matches, PREG_SET_ORDER);
		foreach ($matches as &$match) {
			$match['full'] = array_shift($match);
		}

		return $matches;
	}
}
