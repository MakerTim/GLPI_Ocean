<?php

namespace Ocean\Routing;

use Ocean\FastProfile;
use Ocean\FastTicket;
use Ocean\FastUser;
use Ocean\RoutingBase;
use PDO;

class Group extends RoutingBase {

	/**
	 * @param FastUser $user
	 * @return mixed
	 */
	public function call($user) {
		if (!$user->hasRight('ticket', FastProfile::UPDATE)) {
			return [];
		}

		return $this->getGroup($user);
	}


	/**
	 * @param FastUser $user
	 * @return mixed
	 */
	public function getGroup($user) {
		/** @var PDO $DB */ global $DB;
		$status = FastTicket::WAITING;
		$id = $this->getId();

		$groupStatement = $DB->prepare('SELECT id, name, completename FROM glpi_groups WHERE is_assign=1 AND id=:id');
		$groupStatement->bindParam(':id', $id);
		if (!$groupStatement->execute()) {
			sendError('Listing groups went wrong');
		}
		$group = $groupStatement->fetchAll()[0];
		$userStatement = $DB->prepare("SELECT id, CONCAT(firstname, ' ', realname) AS `name` FROM glpi_users WHERE id IN (SELECT users_id FROM glpi_groups_users WHERE groups_id=:groupId) OR id=:userId ORDER BY FIELD(id, :userId) DESC");
		$userStatement->bindParam(':userId', $user->id);
		$userStatement->bindParam(':groupId', $group['id']);
		if (!$userStatement->execute()) {
			sendError('Failed getting users from group');
		}
		$group['users'] = $userStatement->fetchAll();
		if (key_exists('HTTP_WITHTICKET', $_SERVER)) {
			$groupedTickets = $DB->prepare('SELECT gt.id AS `the_id`, gt.groups_id as `key`, t.* FROM glpi_groups_tickets gt INNER JOIN glpi_tickets t ON gt.tickets_id = t.id WHERE gt.groups_id=:groupId AND t.status < :status AND gt.type=2 ORDER BY id DESC');
			$groupId = $group['id'];
			$groupedTickets->bindParam(':status', $status);
			$groupedTickets->bindParam(':groupId', $groupId);
			if (!$groupedTickets->execute()) {
//				$groupedTickets->debugDumpParams();
				sendError('Failed to find grouped tickets');
			}
			$group['tickets'] = $groupedTickets->fetchAll();
			foreach ($group['tickets'] as &$gTicket) {
				$ticket = FastTicket::getTicketWithLeftJoins($gTicket['id']);
				FastTicket::bindTicketDetails($ticket);
				$ticket['the_id'] = $gTicket['the_id'];
				$ticket['key'] = $gTicket['key'];
				$gTicket = $ticket;
			}

			foreach ($group['users'] as &$user) {
				$userTickets = $DB->prepare('SELECT ut.id AS `the_id`, ut.users_id as `key`, t.* FROM glpi_tickets_users ut INNER JOIN glpi_tickets t ON ut.tickets_id = t.id WHERE t.status < :status AND ut.type=2 AND ut.users_id = :uid ORDER BY id DESC');
				$userId = $user['id'];
				$userTickets->bindParam(':status', $status);
				$userTickets->bindParam(':uid', $userId);
				if (!$userTickets->execute()) {
					sendError('Failed to find users ticket');
				}
				$user['tickets'] = $userTickets->fetchAll();
				foreach ($user['tickets'] as &$uTicket) {
					$ticket = FastTicket::getTicketWithLeftJoins($uTicket['id']);
					FastTicket::bindTicketDetails($ticket);
					$ticket['the_id'] = $uTicket['the_id'];
					$ticket['key'] = $uTicket['key'];
					$uTicket = $ticket;
				}
			}
		}

		return $group;
	}

	private function getId() {
		if (!key_exists('HTTP_ID', $_SERVER)) {
			sendError('Missing group id header', 400);
		}
		return intval($_SERVER['HTTP_ID']);
	}
}
