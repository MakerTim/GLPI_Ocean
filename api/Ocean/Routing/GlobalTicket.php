<?php

namespace Ocean\Routing;

use Ocean\FastProfile;
use Ocean\FastTicket;
use Ocean\FastUser;
use Ocean\RoutingBase;
use PDO;

class GlobalTicket extends RoutingBase {

	/**
	 * @param FastUser $user
	 * @return mixed
	 */
	public function call($user) {
		if (!$user->hasRight('ticket', FastProfile::UPDATE)) {
			return [];
		}

		if (array_key_exists('HTTP_TYPE', $_SERVER)) {
			if ($_SERVER['HTTP_TYPE'] === 'assignTo') {
				return $this->assignTo($user);
			}
		}

		return $this->getOverview($user);
	}


	/**
	 * @param FastUser $user
	 * @return mixed
	 */
	public function assignTo($user) {
		/** @var PDO $DB */ global $DB;

		$content = json_decode(file_get_contents('php://input'));
		//oldContainer, newContainer, key, id
		$old = $this->parseContainer($content->oldContainer);
		$new = $this->parseContainer($content->newContainer);
		$ticketId = $content->id;

		if ($old === null && $new === null) {
			sendError('Move something from nothing to nothing');
		} else if ($old === null) {
			$type = $new[0];
			$id = $new[1];
			$toTable = $new[2];

			$statement = $DB->prepare("INSERT IGNORE INTO $toTable (tickets_id, " . $type . "s_id, type) VALUES (:ticketId, :assigned, 2)");
			$statement->bindParam(':ticketId', $ticketId);
			$statement->bindParam(':assigned', $id);

			if ($type === 'user') {
				FastTicket::setTicketStatus($ticketId, FastTicket::ASSIGNED, FastTicket::INCOMING);
			}
		} else if ($new === null) {
			$type = $old[0];
			$id = $old[1];
			$toTable = $old[2];

			$statement = $DB->prepare("DELETE FROM $toTable WHERE tickets_id=:id AND " . $type . "s_id=:assigned AND type=2 LIMIT 1");
			$statement->bindParam(':id', $ticketId);
			$statement->bindParam(':assigned', $id);

			if ($type === 'user') {
				FastTicket::setTicketStatus($ticketId, FastTicket::INCOMING, FastTicket::ASSIGNED);
			}
		} else if ($old[0] === $new[0]) {
			$type = $old[0];
			$id = $old[1];
			$newId = $new[1];
			$toTable = $old[2];

			$statement = $DB->prepare("UPDATE $toTable SET " . $type . "s_id=:newAssigned WHERE tickets_id=:id AND " . $type . "s_id=:assigned AND type=2 LIMIT 1");
			$statement->bindParam(':id', $ticketId);
			$statement->bindParam(':assigned', $id);
			$statement->bindParam(':newAssigned', $newId);
			FastTicket::setTicketStatus($ticketId, FastTicket::ASSIGNED, FastTicket::INCOMING);
		} else {
			$type = $old[0];
			$id = $old[1];
			$toTable = $old[2];

			$statement = $DB->prepare("DELETE FROM $toTable WHERE tickets_id=:id AND " . $type . "s_id=:assigned AND type=2 LIMIT 1");
			$statement->bindParam(':id', $ticketId);
			$statement->bindParam(':assigned', $id);
			if (!$statement->execute()) {
//				$statement->debugDumpParams();
				sendError('Error moving ticket ' . json_encode($content));
			}

			$type = $new[0];
			$id = $new[1];
			$toTable = $new[2];

			$statement = $DB->prepare("INSERT IGNORE INTO $toTable (tickets_id, " . $type . "s_id, type) VALUES (:ticketId, :assigned, 2)");
			$statement->bindParam(':ticketId', $ticketId);
			$statement->bindParam(':assigned', $id);
			if ($type === 'user') {
				FastTicket::setTicketStatus($ticketId, FastTicket::ASSIGNED, FastTicket::INCOMING);
			}
		}

		if (!$statement->execute()) {
//			$statement->debugDumpParams();
			sendError('Error moving ticket ' . json_encode($content));
		}

		return true;
	}

	private function parseContainer($containerName) {
		if ($containerName === 'unassigned') {
			return null;
		}
		$values = explode('_', $containerName);
		if ($values[0] === 'user') {
			$values[2] = 'glpi_tickets_users';
		} else if ($values[0] === 'group') {
			$values[2] = 'glpi_groups_tickets';
		} else {
			sendError('New container type not supported');
		}
		return $values;
	}

	/**
	 * @param FastUser $user
	 * @return mixed
	 */
	public function getOverview($user) {
		/** @var PDO $DB */ global $DB;
		$status = FastTicket::WAITING;

		$groupStatement = $DB->query('SELECT id, completename FROM glpi_groups WHERE is_assign=1');
		if (!$groupStatement->execute()) {
			sendError('Failed to find groups');
		}
		$columns = [];
		$columns['user_' . $user->id] = ['id' => $user->id, 'name' => $user->firstname . ' ' . $user->realname, 'tickets' => []];
		$columns['none'] = ['name' => 'unassigned', 'tickets' => []];
		foreach ($groupStatement->fetchAll() as $group) {
			$columns['group_' . $group['id']] = ['id' => $group['id'], 'name' => $group['completename'], 'tickets' => []];
		}

		$groupedTickets = $DB->prepare('SELECT gt.id AS `the_id`, gt.groups_id as `key`, t.* FROM glpi_groups_tickets gt INNER JOIN glpi_tickets t ON gt.tickets_id = t.id WHERE t.status < :status AND gt.type=2 ORDER BY id DESC');
		$groupedTickets->bindParam(':status', $status);
		if (!$groupedTickets->execute()) {
			sendError('Failed to find grouped tickets');
		}
		foreach ($groupedTickets->fetchAll() as $gTicket) {
			$ticket = FastTicket::getTicketWithLeftJoins($gTicket['id']);
			FastTicket::bindTicketDetails($ticket);
			$ticket['the_id'] = $gTicket['the_id'];
			$ticket['key'] = $gTicket['key'];
			$columns['group_' . $gTicket['key']]['tickets'][] = $ticket;
		}

		$userTickets = $DB->prepare('SELECT ut.id AS `the_id`, ut.users_id as `key`, t.* FROM glpi_tickets_users ut INNER JOIN glpi_tickets t ON ut.tickets_id = t.id WHERE t.status < :status AND ut.type=2 AND ut.users_id = :uid ORDER BY id DESC');
		$userId = $user->id;
		$userTickets->bindParam(':status', $status);
		$userTickets->bindParam(':uid', $userId);
		if (!$userTickets->execute()) {
			sendError('Failed to find users ticket');
		}
		foreach ($userTickets->fetchAll() as $uTicket) {
			$ticket = FastTicket::getTicketWithLeftJoins($uTicket['id']);
			FastTicket::bindTicketDetails($ticket);
			$ticket['the_id'] = $uTicket['the_id'];
			$ticket['key'] = $uTicket['key'];
			$columns['user_' . $uTicket['key']]['tickets'][] = $ticket;
		}

		$ticketsFloating = $DB->prepare('SELECT * FROM glpi_tickets WHERE status < :status AND id NOT IN ( SELECT tickets_id FROM glpi_tickets_users WHERE type=2 UNION SELECT tickets_id FROM glpi_groups_tickets WHERE type=2) ORDER BY id ASC');
		$ticketsFloating->bindParam(':status', $status);
		if (!$ticketsFloating->execute()) {
			sendError('Failed to find floating ticket');
		}
		foreach ($ticketsFloating->fetchAll() as $fTicket) {
			$ticket = FastTicket::getTicketWithLeftJoins($fTicket['id']);
			FastTicket::bindTicketDetails($ticket);
			$columns['none']['tickets'][] = $ticket;
		}

		return $columns;
	}
}
