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
			if (!preg_match('/\d+/', $_SERVER['HTTP_ID'])) {
				$_SERVER['HTTP_ID'] = $user->id;
			}
			$statement = $DB->prepare("SELECT * FROM glpi_tickets WHERE (users_id_recipient=:field OR id IN (SELECT tickets_id FROM glpi_tickets_users WHERE users_id=:field)) AND date_mod>:lstMnd ORDER BY status ASC, date DESC LIMIT 250");
		} else {
			$statement = $DB->prepare("SELECT * FROM glpi_tickets WHERE (id IN (SELECT tickets_id FROM glpi_groups_tickets WHERE groups_id=:field)) AND date_mod>:lstMnd ORDER BY status ASC, date DESC LIMIT 250");
		}

		$lastMonth = date('Y-m-d', strtotime('last day of -3 months'));
		$statement->bindParam(':lstMnd', $lastMonth);
		$statement->bindParam(':field', $_SERVER['HTTP_ID']);
		if (!$statement->execute()) {
			sendError('Failed to get dashboard');
		}

		$tickets = $statement->fetchAll();
		foreach ($tickets as &$ticket) {
			$ticket = FastTicket::getTicketWithLeftJoins($ticket['id']);
			FastTicket::bindTicketDetails($ticket);
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
