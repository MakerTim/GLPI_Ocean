<?php

namespace Ocean\Routing;

use Ocean\FastProfile;
use Ocean\FastTicket;
use Ocean\FastUser;
use Ocean\RoutingBase;
use Ocean\SQLBuilder;
use PDO;

class Tickets extends RoutingBase {

	/**
	 * @param FastUser $user
	 * @return mixed
	 */
	public function call($user) {
		/** @var PDO $DB */ global $DB;

		if (!$user->hasRight('ticket', FastProfile::READ)) {
			return [];
		}

		$type = $this->getType();
		if ($type === 'search') {
			return FastTicket::search(json_decode(file_get_contents('php://input')));
		}

		$where = '';
		if ($type === 'open' || $type === 'closed') {
			$sqlBuilderTickets = new SQLBuilder('glpi_tickets_users', 'WHERE users_id = :usrId');
			$sqlBuilderTickets->selector = 'DISTINCT tickets_id';
			$affectedIds = $sqlBuilderTickets->fetchAll(function (\PDOStatement $statement) use ($user) {
				$statement->bindParam(':usrId', $user->id, PDO::PARAM_INT);
			});
			$ids = '(';
			foreach ($affectedIds as $row) {
				$ids .= $row['tickets_id'] . ',';
			}
			if (strlen($ids) > 1) {
				$where = 't.id IN ' . str_replace(',)', ')', $ids . ')');
			} else {
				$where = '0=1 ';
			}

			if ($type === 'open') {
				$where .= ' AND t.status < ' . FastTicket::SOLVED;
			} else {
				$where .= ' AND t.status >= ' . FastTicket::SOLVED;
			}
		} else if ($type === 'around') {
			$statement = $DB->prepare(//
				'SELECT tu.tickets_id ' . //
				'FROM glpi_tickets_users tu ' . //
				'WHERE tu.users_id IN ( ' . //
				'	SELECT DISTINCT gu.users_id ' . //
				'	FROM glpi_groups_users gu ' .//
				'	WHERE gu.users_id <> :uid AND gu.groups_id IN ( ' . //
				'		SELECT gu.groups_id ' . //
				'		FROM glpi_groups_users gu ' . //
				'		WHERE gu.users_id = :uid ' . //
				'	) ' . //
				')' //
			);
			$statement->bindParam(':uid', $user->id, PDO::PARAM_INT);
			if (!$statement->execute()) {
				//				$statement->debugDumpParams();
				sendError('Getting around tickets went wrong');
			}
			$ids = '(';
			foreach ($statement->fetchAll() as $row) {
				$ids .= $row['tickets_id'] . ',';
			}
			if (strlen($ids) > 1) {
				$where = 't.id IN ' . str_replace(',)', ')', $ids . ')');
			} else {
				$where = '0=1 ';
			}
		}
		$where .= ' AND t.is_deleted=0 ';

		$sqlBuilderTickets = new SQLBuilder('glpi_tickets', "WHERE $where", 'ORDER BY date_mod DESC LIMIT 5');
		$sqlBuilderTickets->leftJoin('glpi_entities', 'entities_id', 'name');
		$sqlBuilderTickets->leftJoin('glpi_users', 'users_id_lastupdater', 'name', 'usu');
		$sqlBuilderTickets->leftJoin('glpi_users', 'users_id_recipient', 'name', 'usr');
		$sqlBuilderTickets->leftJoin('glpi_requesttypes', 'requesttypes_id', 'name');
		$sqlBuilderTickets->leftJoin('glpi_itilcategories', 'itilcategories_id', 'completename');
		$sqlBuilderTickets->leftJoin('glpi_locations', 'locations_id', 'completename');
		$results = $sqlBuilderTickets->fetchAll();

		foreach ($results as &$result) {
			$result['type'] = nameOfType(FastTicket::TYPES, $result['type']);
			$result['status'] = nameOfType(FastTicket::STATUS, $result['status']);
			$result['urgency'] = nameOfType(FastTicket::PRIORITIES, $result['urgency']);
			$result['impact'] = nameOfType(FastTicket::PRIORITIES, $result['impact']);
			$result['priority'] = nameOfType(FastTicket::PRIORITIES, $result['priority']);
			$result['global_validation'] = nameOfType(FastTicket::VALIDATION, $result['global_validation']);
			$result['content'] = html_entity_decode($result['content']);
		}

		return $results;
	}

	private function getType() {
		if (!key_exists('HTTP_TYPE', $_SERVER)) {
			sendError('Missing type header', 400);
		}
		$requestedType = $_SERVER['HTTP_TYPE'];

		if (!in_array($requestedType, //
			['open', 'closed', 'around', 'search'])) {
			sendError('Invalid type requested ' . $requestedType, 400);
		}

		return $requestedType;
	}
}
