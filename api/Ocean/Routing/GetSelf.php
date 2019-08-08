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
		$groupsStatement = $DB->prepare('SELECT g.id, g.is_assign, g.name FROM glpi_groups_users gu JOIN glpi_groups g ON g.id = gu.groups_id WHERE gu.users_id=:id');
		$groupsStatement->bindParam(':id', $id);
		$groupsStatement->execute();

		$user->groups = [];
		$user->groupsService = [];
		foreach ($groupsStatement->fetchAll() as $groupEntry) {
			$user->groups[] = $groupEntry['id'];
			if ($groupEntry['is_assign'] == 1) {
				$user->groupsService[] = $groupEntry['name'];
			}
		}

		return $user;
	}
}
