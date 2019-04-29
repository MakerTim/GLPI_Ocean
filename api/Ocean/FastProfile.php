<?php

namespace Ocean;

use PDO;

/**
 * @property string $id
 * @property string|null $name
 * @property string|null $interface
 * @property string $is_default
 * @property string $helpdesk_hardware
 * @property string|null $helpdesk_item_type
 * @property string|null $ticket_status
 * @property string|null $date_mod
 * @property string|null $comment
 * @property string|null $problem_status
 * @property string $create_ticket_on_login
 * @property string $tickettemplates_id
 * @property string|null $change_status
 * @property string|null $date_creation
 */
class FastProfile extends FastModel {

	const READ = 1;
	const UPDATE = 2;
	const CREATE = 4;
	const DELETE = 8;
	const PURGE = 16;
	const READ_NOTE = 32;
	const UPDATE_NOTE = 64;
	const UNLOCK = 128;

	public function __construct(array $DBEntry) {
		parent::__construct($DBEntry);
	}

	/**
	 * @param array $rights
	 * @param string $name
	 * @param int $permission
	 * @return bool if has right
	 */
	public static function hasRight(array $rights, $name, $permission) {
		$name = strtolower($name);
		if (strpos($name, 'network') !== false) {
			$name = 'networking';
		}

		if (key_exists($name, $rights)) {
			return ($rights[$name] & intval($permission)) !== 0;
		}
		return true;
	}

	public function getRights() {
		/** @var PDO $DB */ global $DB;

		$statement = $DB->prepare('SELECT name, rights ' . //
			'FROM glpi_profilerights ' . //
			'WHERE profiles_id = :profileId');
		$statement->bindParam(':profileId', $this->id, PDO::PARAM_INT);
		$statement->execute();
		$rightsTable = [];
		foreach ($statement->fetchAll() as $rightsRow) {
			$rightsTable[$rightsRow['name']] = intval($rightsRow['rights']);
		}
		return $rightsTable;
	}

	/**
	 * @param FastProfile|array $otherProfile
	 * @return array
	 */
	public function getRightsCombined($otherProfile) {
		$thisRights = $this->getRights();
		if (is_array($otherProfile)) {
			$otherRights = $otherProfile;
		} else {
			$otherRights = $otherProfile->getRights();
		}
		$keys = array_merge(array_keys($thisRights), array_keys($otherRights));

		$rightsTable = [];
		foreach ($keys as $name) {
			$rightsTable[$name] = //
				(key_exists($name, $thisRights) ? $thisRights[$name] : 0) | //
				(key_exists($name, $otherRights) ? $otherRights[$name] : 0);
		}
		return $rightsTable;
	}
}
