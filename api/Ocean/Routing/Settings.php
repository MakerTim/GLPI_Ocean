<?php

namespace Ocean\Routing;

use Ocean\FastProfile;
use Ocean\FastUser;
use Ocean\RoutingBase;
use PDO;

class Settings extends RoutingBase {

	/**
	 * @param FastUser $user
	 * @return mixed
	 */
	public function call($user) {
		if (!array_key_exists('HTTP_SETTING', $_SERVER)) {
			sendError('Missing setting header');
		}

		switch ($_SERVER['HTTP_SETTING']) {
			case 'list':
				return $this->listSettings($user);
			case 'setItem':
				return $this->setItem($user);
			default:
				sendError('Setting ' . $_SERVER['HTTP_SETTING'] . ' not supported');
		}

		return [];
	}

	/**
	 * @param FastUser $user
	 * @return mixed
	 */
	private function listSettings($user) {
		/** @var PDO $DB */ global $DB;

		if (!$user->hasRight('backup', FastProfile::READ)) {
			sendError('Needs backup rights', 403);
		}

		$statement = $DB->query('SELECT * FROM glpi_plugin_ocean_config ORDER BY id ASC');
		if (!$statement->execute()) {
			sendError('No config found');
		}

		return $statement->fetchAll();
	}

	/**
	 * @param FastUser $user
	 * @return mixed
	 */
	private function setItem($user) {
		/** @var PDO $DB */ global $DB;

		if (!$user->hasRight('backup', FastProfile::CREATE)) {
			sendError('Needs backup rights', 403);
		}

		$existingStatement = $DB->prepare('SELECT * FROM glpi_plugin_ocean_config WHERE id=:id');
		$existingStatement->bindParam(':id', $_SERVER['HTTP_ID']);
		if (!$existingStatement->execute()) {
			sendError('No setting found');
		}
		if (!$existingStatement->columnCount()) {
			sendError('Setting not found');
		}
		$existingStatement->fetchAll();

		$statement = $DB->prepare('UPDATE glpi_plugin_ocean_config SET setting=:setting WHERE id=:id;');
		$statement->bindParam(':id', $_SERVER['HTTP_ID']);
		$setting = $_SERVER['HTTP_VALUE'] ? 1 : 0;
		$statement->bindParam(':setting', $setting);
		if (!$statement->execute()) {
			return false;
		}
		$statement->fetchAll();

		return true;
	}
}
