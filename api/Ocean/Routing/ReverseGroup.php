<?php

namespace Ocean\Routing;

use Ocean\FastGroup;
use Ocean\FastLDAP;
use Ocean\FastUser;
use Ocean\RoutingBase;
use PDO;

class ReverseGroup extends RoutingBase {

	/**
	 * @param FastUser $user
	 * @return mixed
	 */
	public function call($user) {
		/** @var PDO $DB */ global $DB;
		$statement = $DB->query('SELECT id, name FROM glpi_users WHERE authtype=3');
		$statement->execute();
		$log = [];
		foreach ($statement->fetchAll() as $userLdapLogin) {
			$log[] = 'START ' . $userLdapLogin['name'];
			try {
				$ldapUser = FastLDAP::login($userLdapLogin['name'], null, $log);
				if (is_array($ldapUser)) {
					$group = FastGroup::exists($ldapUser['group']);
					if (!$group) {
						$group = FastGroup::createGroup($user['group']);
						$log[] = 'Created new group ' . $user['group'];
					}
					FastGroup::setUserInGroup($userLdapLogin['id'], $group);
					$log[] = 'Made sure ' . $userLdapLogin['name'] . ' is in ' . $ldapUser['group'];
				} else {
					$log[] = 'USER NOT FOUND IN LDAP - ' . $userLdapLogin['name'];
				}
			} catch (\Exception $ex) {
				$log[] = 'ERROR AT: ' . $ex->getFile() . ':' . $ex->getLine() . ': ' . $ex->getMessage() . '{' . $userLdapLogin['name'] . ",$ldapUser,$group}";
			}
			$log[] = null;
		}

		$log[] = 'DONE.';
		return $log;
	}
}
