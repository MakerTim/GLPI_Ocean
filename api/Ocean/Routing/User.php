<?php

namespace Ocean\Routing;

use Ocean\FastProfile;
use Ocean\FastUser;
use Ocean\RoutingBase;
use PDO;

class User extends RoutingBase {

	/**
	 * @param FastUser $user
	 * @return mixed
	 */
	public function call($user) {
		/** @var PDO $DB */ global $DB;

		if (!$user->hasRight('backup', FastProfile::READ)) {
			sendError('Needs backup rights', 403);
		}

		if (array_key_exists('HTTP_TYPE', $_SERVER)) {
			if ($_SERVER['HTTP_TYPE'] == 'events') {
			}
			if ($_SERVER['HTTP_TYPE'] == 'file') {
			}
		}

		$statement = $DB->prepare('SELECT id, name, firstname, realname, date_creation, date_mod, picture FROM glpi_users WHERE id=:id');
		$statement->bindParam(':id', $_SERVER['HTTP_ID']);
		if (!$statement->execute()) {
			sendError("Can't get id");
		}
		$user = $statement->fetch();

		$statement = $DB->prepare('SELECT t.id, t.name FROM glpi_tickets t LEFT JOIN glpi_tickets_users tu ON tu.tickets_id = t.id WHERE tu.users_id=:id ORDER BY t.date_mod DESC LIMIT 5');
		$statement->bindParam(':id', $_SERVER['HTTP_ID']);
		$statement->execute();
		$user['tickets'] = $statement->fetchAll();

		$statement = $DB->prepare('SELECT g.id, g.name FROM glpi_groups g LEFT JOIN glpi_groups_users gu ON gu.groups_id = g.id WHERE gu.users_id=:id');
		$statement->bindParam(':id', $_SERVER['HTTP_ID']);
		$statement->execute();
		$user['groups'] = $statement->fetchAll();
		return $user;
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
