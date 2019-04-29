<?php

namespace Ocean\Routing;

use Ocean\FastUser;
use Ocean\RoutingBase;
use PDO;

class GetSelf extends RoutingBase {

	/**
	 * @param FastUser $user
	 * @return mixed
	 */
	public function call($user) {
		/** @var PDO $DB */ global $DB;
		$user->rights = $user->getRightsTable();

		$id = $user->id;
		$groupsStatement = $DB->prepare('SELECT groups_id FROM glpi_groups_users WHERE users_id=:id');
		$groupsStatement->bindParam(':id', $id);
		$groupsStatement->execute();

		$user->groups = [];
		foreach ($groupsStatement->fetchAll() as $groupEntry) {
			$user->groups[] = $groupEntry['groups_id'];
		}

		return $user;
	}
}
