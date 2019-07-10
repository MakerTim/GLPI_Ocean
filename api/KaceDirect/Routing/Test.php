<?php


namespace KaceDirect\Routing;

use KaceDirect\RoutingBase;
use PDO;

class Test extends RoutingBase {

	public function call($user) {
		/** @var PDO $DB */ global $DB;

		return $DB->query('SELECT 1;')->fetchAll();
	}
}
