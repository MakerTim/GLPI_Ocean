<?php

namespace Ocean;

use PDO;

class FastGroup extends FastModel {

	public function __construct(array $DBEntry) {
		parent::__construct($DBEntry);
	}

	public static function exists($name) {
		/** @var PDO $DB */ global $DB;

		$statement = $DB->prepare('SELECT id FROM glpi_groups WHERE name=:name');
		$statement->bindParam(':name', $name);
		if (!$statement->execute()) {
			sendError('Cant find group');
		}
		if ($statement->rowCount() >= 1) {
			return $statement->fetch()['id'];
		}
		return null;
	}

	public static function createGroup($name) {
		/** @var PDO $DB */ global $DB;

		$statement = $DB->prepare('INSERT INTO glpi_groups (entities_id, is_recursive, name, comment, ldap_field, ldap_value, ldap_group_dn, date_mod, groups_id, completename, level, ancestors_cache, sons_cache, is_requester, is_watcher, is_assign, is_task, is_notify, is_itemgroup, is_usergroup, is_manager, date_creation) VALUES ' . //
			"(0, 0, :name, '', null, null, null, now(), 0, :name, 1, '[]', null, 0, 0, 0, 0, 1, 0, 1, 0, now())");
		$statement->bindParam(':name', $name);
		if (!$statement->execute()) {
			sendError('Cant add group');
		}
		$statement = $DB->query('SELECT LAST_INSERT_ID() AS id;');
		if (!$statement->execute()) {
			sendError('No group id found');
		}
		return intval($statement->fetchAll()[0]['id']);
	}

	public static function setUserInGroup($userId, $groupId) {
		/** @var PDO $DB */ global $DB;

		$statement = $DB->prepare('SELECT id FROM glpi_groups_users WHERE users_id=:uid AND groups_id=:gid');
		$statement->bindParam(':uid', $userId);
		$statement->bindParam(':gid', $groupId);
		if (!$statement->execute()) {
			sendError('Cant find user in group');
		}
		if ($statement->rowCount() === 0) {
			$statement = $DB->prepare('INSERT INTO glpi_groups_users (users_id, groups_id) VALUES (:uid, :gid)');
			$statement->bindParam(':uid', $userId);
			$statement->bindParam(':gid', $groupId);
			if (!$statement->execute()) {
				sendError('Cant add user in group');
			}
		}
	}
}
