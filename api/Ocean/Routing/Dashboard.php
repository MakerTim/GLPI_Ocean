<?php

namespace Ocean\Routing;

use Ocean\FastProfile;
use Ocean\FastTicket;
use Ocean\FastUser;
use Ocean\RoutingBase;
use PDO;

class Dashboard extends RoutingBase {

	/**
	 * @param FastUser $user
	 * @return mixed
	 */
	public function call($user) {
		/** @var PDO $DB */ global $DB;

		if (!$user->hasRight('ticket', FastProfile::UPDATE)) {
			return [];
		}

		$type1 = $this->getType();
		if ($type1 === 'user') {
			$type = 'tickets_users';
			$field = 'users_id';
		} else {
			$type = 'groups_tickets';
			$field = 'groups_id';
		}

		$statement = $DB->prepare('SELECT * FROM glpi_tickets WHERE id IN (SELECT tickets_id FROM glpi_' . //
			$type . " WHERE $field=:field) ORDER BY status ASC, date DESC LIMIT 250");
		$statement->bindParam(':field', $_SERVER['HTTP_ID']);
		if(!$statement->execute()){
			sendError('Failed to get dashboard');
		}

		$tickets = $statement->fetchAll();
		foreach ($tickets as &$ticket){
			FastTicket::bindTicketDetails($ticket, -1, true);
		}
		return $tickets;
	}

	private function getType() {
		if (!key_exists('HTTP_TYPE', $_SERVER)) {
			sendError('Missing type header', 400);
		}
		$requestedType = $_SERVER['HTTP_TYPE'];

		if (!in_array($requestedType, //
			['group', 'user'])) {
			sendError('Invalid type requested ' . $requestedType, 400);
		}

		return $requestedType;
	}
}
