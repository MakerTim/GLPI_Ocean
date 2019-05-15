<?php

namespace Ocean\Routing;

use Ocean\FastMail;
use Ocean\FastUser;
use Ocean\RoutingBase;
use PDO;

class Test extends RoutingBase {

	/**
	 * @param FastUser $user
	 * @return mixed
	 */
	public function call($user) {
		/** @var PDO $DB */ global $DB;

		$user = new FastUser($DB->query('SELECT * FROM glpi_users WHERE id=6')->fetch());

//		return FastMail::mailTicket($user, 1);
		return '';
	}
}
