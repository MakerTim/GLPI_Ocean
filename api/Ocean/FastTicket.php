<?php

namespace Ocean;

use PDO;
use stdClass;

/**
 * @property string $id
 * @property string $entities_id
 * @property string|null $name
 * @property string|null $date
 * @property string|null $closedate
 * @property string|null $solvedate
 * @property string|null $date_mod
 * @property string $users_id_lastupdater
 * @property string $status
 * @property string $users_id_recipient
 * @property string $requesttypes_id
 * @property string|null $content
 * @property string $urgency
 * @property string $impact
 * @property string $priority
 * @property string $itilcategories_id
 * @property string $type
 * @property string $global_validation
 * @property string $slas_id_ttr
 * @property string $slas_id_tto
 * @property string $slalevels_id_ttr
 * @property string|null $time_to_resolve
 * @property string|null $time_to_own
 * @property string|null $begin_waiting
 * @property string $sla_waiting_duration
 * @property string $ola_waiting_duration
 * @property string $olas_id_tto
 * @property string $olas_id_ttr
 * @property string $olalevels_id_ttr
 * @property string|null $internal_time_to_resolve
 * @property string|null $internal_time_to_own
 * @property string $waiting_duration
 * @property string $close_delay_stat
 * @property string $solve_delay_stat
 * @property string $takeintoaccount_delay_stat
 * @property string $actiontime
 * @property string $is_deleted
 * @property string $locations_id
 * @property string $validation_percent
 * @property string|null $date_creation
 */
class FastTicket extends FastModel {

	// status
	const INCOMING = 1;
	const ASSIGNED = 2;
	const PLANNED = 3;
	const WAITING = 4;
	const SOLVED = 5;
	const CLOSED = 6;
	const ACCEPTED = 7;
	const OBSERVED = 8;
	const EVALUATION = 9;
	const APPROVAL = 10;
	const TEST = 11;
	const QUALIFICATION = 12;
	const STATUS = [ //
		'new' => self::INCOMING, //
		'assign' => self::ASSIGNED, //
		'plan' => self::PLANNED, //
		'waiting' => self::WAITING, //
		'solved' => self::SOLVED, //
		'closed' => self::CLOSED, //
		'accepted' => self::ACCEPTED, //
		'observe' => self::OBSERVED, //
		'evaluation' => self::EVALUATION, //
		'approbation' => self::APPROVAL, //
		'test' => self::TEST, //
		'qualification' => self::QUALIFICATION, //
	];

	// types
	const INCIDENT = 1;
	const REQUEST = 2;
	const TYPES = ['incident' => self::INCIDENT, 'request' => self::REQUEST];

	const PRIORITIES = [ //
		'lowest' => 1, //
		'low' => 2, //
		'medium' => 3, //
		'high' => 4, //
		'highest' => 5, //
	];

	const VALIDATION = [ // validation & itilsolutions status
		'none' => 1, //
		'waiting' => 2, //
		'accepted' => 3, //
		'rejected' => 4, //
		'N/A' => 0, //
	];

	// rights
	const READMY = 1;
	const READALL = 1024;
	const READGROUP = 2048;
	const READASSIGN = 4096;
	const ASSIGN = 8192;
	const STEAL = 16384;
	const OWN = 32768;
	const CHANGEPRIORITY = 65536;
	const SURVEY = 131072;

	public function __construct(array $DBEntry) {
		parent::__construct($DBEntry);
	}

	public static function createNewTicket(FastUser $user, $type, $category, $status, $urgency, $impact, $priority, $source, $slaTime, $title, $description, $linkedTicket, $assignedUser, $assignedGroup) {
		/** @var PDO $DB */ global $DB;

		$ticketStatement = $DB->prepare( //
			'INSERT INTO glpi_tickets ' . //
			'(date, date_mod, date_creation, entities_id, users_id_lastupdater, users_id_recipient, requesttypes_id, actiontime, slas_id_ttr, slas_id_tto, locations_id, type, status,' .//
			'urgency, impact, priority, name, content) ' . //
			'VALUES ' . //
			'(:ddate, :mdate, :cdate, :eid, :uil, :uir, :rti, :act, :sitr, :sito, :li, :type, :stat, :urg, :imp, :prio, :title, :desc);');
		self::bindValue($ticketStatement, ':ddate', date("Y-m-d H:i:s"));
		self::bindValue($ticketStatement, ':mdate', date("Y-m-d H:i:s"));
		self::bindValue($ticketStatement, ':cdate', date("Y-m-d H:i:s"));
		self::bindValue($ticketStatement, ':eid', $category);
		self::bindValue($ticketStatement, ':uil', $user->id);
		self::bindValue($ticketStatement, ':uir', $user->id);
		self::bindValue($ticketStatement, ':rti', $source);
		self::bindValue($ticketStatement, ':act', $slaTime);
		self::bindValue($ticketStatement, ':sitr', $slaTime);
		self::bindValue($ticketStatement, ':sito', $slaTime);
		self::bindValue($ticketStatement, ':li', $user->locations_id);
		self::bindValue($ticketStatement, ':type', $type, 1);
		self::bindValue($ticketStatement, ':stat', $status, 1);
		self::bindValue($ticketStatement, ':urg', $urgency, 1);
		self::bindValue($ticketStatement, ':imp', $impact, 1);
		self::bindValue($ticketStatement, ':prio', $priority, 1);
		self::bindValue($ticketStatement, ':title', $title, 'No title');
		self::bindValue($ticketStatement, ':desc', $description, '');

		if (!$ticketStatement->execute()) {
			sendError('Ticket failed to create');
		}

		$id = $DB->query('SELECT LAST_INSERT_ID() AS id;');
		if (!$id->execute()) {
			sendError('No ticket id found');
		}
		$id = intval($id->fetchAll()[0]['id']);

		$ticketStatement = $DB->prepare('SELECT * FROM glpi_tickets WHERE id=:id;');
		$ticketStatement->bindParam(':id', $id);
		if (!$ticketStatement->execute()) {
			sendError('New ticket not found');
		}
		$newTicket = $ticketStatement->fetchAll()[0];
		$newTicket = new FastTicket($newTicket);

		$ticketUserStatement = $DB->prepare('INSERT INTO glpi_tickets_users ' . //
			'(tickets_id, users_id, type, use_notification, alternative_email) VALUES ' . //
			"(:ticketId, :userId, 1, 1, '')");
		$ticketUserStatement->bindParam(':ticketId', $newTicket->id);
		$ticketUserStatement->bindParam(':userId', $user->id);
		if (!$ticketUserStatement->execute()) {
			sendError('Can\'t link user to ticket');
		}

		if ($linkedTicket !== null) {
			$ticketTicketStatement = $DB->prepare('INSERT INTO glpi_tickets_tickets (tickets_id_1, tickets_id_2) VALUES (:idFrom, :idTo)');
			$ticketTicketStatement->bindParam(':idFrom', $id);
			$ticketTicketStatement->bindParam(':idTo', $linkedTicket);
			if (!$ticketTicketStatement->execute()) {
				sendError('Can\'t link ticket to ticket');
			}
		}

		if ($assignedGroup !== null) {
			$ticketGroupStatement = $DB->prepare('INSERT INTO glpi_groups_tickets (tickets_id, groups_id, type) VALUES (:idTicket, :idGroup, 2)');
			$ticketGroupStatement->bindParam(':idTicket', $id);
			$ticketGroupStatement->bindParam(':idGroup', $assignedGroup);
			if (!$ticketGroupStatement->execute()) {
				sendError('Can\'t link group to ticket');
			}
			self::setTicketStatus($id, self::ASSIGNED);
		}

		if ($assignedUser !== null) {
			$ticketUserStatement = $DB->prepare("INSERT INTO glpi_tickets_users (tickets_id, users_id, type, alternative_email) VALUES (:idTicket, :idUser, '2', '')");
			$ticketUserStatement->bindParam(':idTicket', $id);
			$ticketUserStatement->bindParam(':idUser', $assignedUser);
			if (!$ticketUserStatement->execute()) {
				sendError('Can\'t link user to ticket');
			}
			self::setTicketStatus($id, self::ASSIGNED);
		}

		FastMail::mailTicket($user, $id);

		return $newTicket;
	}

	private static function bindValue(\PDOStatement $statement, $key, $value, $default = 0) {
		if ($value === null) {
			$value = $default;
		}
		$statement->bindParam($key, $value);
	}

	public static function getTicketWithLeftJoins($id) {
		$sqlBuilderTickets = new SQLBuilder('glpi_tickets', "WHERE t.id=:id", 'ORDER BY date_mod DESC LIMIT 5');
		$sqlBuilderTickets->leftJoin('glpi_entities', 'entities_id', 'name');
		$sqlBuilderTickets->leftJoin('glpi_users', 'users_id_lastupdater', 'firstname', 'usu');
		$sqlBuilderTickets->leftJoin('glpi_users', 'users_id_lastupdater', 'realname', 'usu2', 'id', 'users_id_lastupdater2');
		$sqlBuilderTickets->leftJoin('glpi_users', 'users_id_lastupdater', 'id', 'usu_id', 'id', 'users_id_lastupdater_id');
		$sqlBuilderTickets->leftJoin('glpi_users', 'users_id_recipient', 'firstname', 'usr');
		$sqlBuilderTickets->leftJoin('glpi_users', 'users_id_recipient', 'realname', 'usr2', 'id', 'users_id_recipient2');
		$sqlBuilderTickets->leftJoin('glpi_users', 'users_id_recipient', 'id', 'usr_id', 'id', 'users_id_recipient_id');
		$sqlBuilderTickets->leftJoin('glpi_requesttypes', 'requesttypes_id', 'name');
		$sqlBuilderTickets->leftJoin('glpi_itilcategories', 'itilcategories_id', 'completename');
		$sqlBuilderTickets->leftJoin('glpi_locations', 'locations_id', 'completename');
		$result = $sqlBuilderTickets->fetchAll(function (\PDOStatement $statement) use ($id) {
			$statement->bindParam(':id', $id);
		});
		if (count($result) === 0) {
			return null;
		}

		return $result[0];
	}

	public static function bindTicketSolution(&$ticket, $id = -1) {
		/** @var PDO $DB */ global $DB;
		if ($id === -1) {
			$id = $ticket['id'];
		}

		$statement = $DB->prepare('SELECT * FROM glpi_knowbaseitems WHERE id IN ( SELECT knowbaseitems_id FROM glpi_knowbaseitems_items WHERE items_id=:id )');
		$statement->bindParam(':id', $id);
		if (!$statement->execute()) {
			sendError('Failed to get the solutions');
		}
		$ticket['solutions'] = $statement->fetchAll();

		if (!empty($ticket['name'])) {
			$fields = new stdClass();
			$fields->name = $ticket['name'];
			$ticket['similar'] = self::search($fields);

			$ids = [];
			foreach ($ticket['similar'] as $k => &$simTicket) {
				if ($simTicket['id'] == $id) {
					unset($ticket['similar'][$k]);
					continue;
				}
				$ids[] = $simTicket['id'];
			}
			if (count($ids)) {
				$similarTickets = $DB->prepare('SELECT * FROM glpi_knowbaseitems WHERE id IN ( SELECT knowbaseitems_id FROM glpi_knowbaseitems_items WHERE items_id IN (' . //
					implode(',', $ids) . //
					') AND NOT items_id=:id )');
				$similarTickets->bindParam(':id', $id);
				if (!$similarTickets->execute()) {
					sendError('Failed to get the solutions');
				}
				$ticket['possibleSolutions'] = $similarTickets->fetchAll();
			} else {
				$ticket['possibleSolutions'] = [];
			}
		}
	}

	public static function bindTicketDetails(&$ticket, $id = -1, $offline = false) {
		/** @var PDO $DB */ global $DB;
		if ($id === -1) {
			$id = $ticket['id'];
		}

		$ticket['type'] = nameOfType(FastTicket::TYPES, $ticket['type']);
		$ticket['status'] = nameOfType(FastTicket::STATUS, $ticket['status']);
		$ticket['urgency'] = nameOfType(FastTicket::PRIORITIES, $ticket['urgency']);
		$ticket['impact'] = nameOfType(FastTicket::PRIORITIES, $ticket['impact']);
		$ticket['priority'] = nameOfType(FastTicket::PRIORITIES, $ticket['priority']);
		$ticket['global_validation'] = nameOfType(FastTicket::VALIDATION, $ticket['global_validation']);
		$ticket['content'] = html_entity_decode($ticket['content']);

		if ($offline) {
			return $ticket;
		}

		$assignedUsers = $DB->prepare('SELECT id, CONCAT(firstname, \' \', realname) AS `assigned_users` FROM glpi_users WHERE id IN (SELECT DISTINCT users_id FROM glpi_tickets_users WHERE tickets_id=:id AND type=2)');
		$assignedUsers->bindParam(':id', $id);
		if (!$assignedUsers->execute()) {
			sendError('Failed finding ticket assigned users');
		}
		$ticket['assigned_users'] = $assignedUsers->fetchAll();
		$requesterUsers = $DB->prepare('SELECT id, CONCAT(firstname, \' \', realname) AS `requested_users` FROM glpi_users WHERE id IN (SELECT DISTINCT users_id FROM glpi_tickets_users WHERE tickets_id=:id AND type=1)');
		$requesterUsers->bindParam(':id', $id);
		if (!$requesterUsers->execute()) {
			sendError('Failed finding ticket requester users');
		}
		$ticket['requested_users'] = $requesterUsers->fetchAll();

		$followedGroups = $DB->prepare('SELECT id, name AS `followed_groups` FROM glpi_groups WHERE id IN (SELECT DISTINCT groups_id FROM glpi_groups_tickets WHERE tickets_id=:id AND type=3)');
		$followedGroups->bindParam(':id', $id);
		if (!$followedGroups->execute()) {
			sendError('Failed finding ticket followed groups');
		}
		$ticket['followed_groups'] = $followedGroups->fetchAll();
		$assignedGroups = $DB->prepare('SELECT id, name AS `assigned_groups` FROM glpi_groups WHERE id IN (SELECT DISTINCT groups_id FROM glpi_groups_tickets WHERE tickets_id=:id AND type=2)');
		$assignedGroups->bindParam(':id', $id);
		if (!$assignedGroups->execute()) {
			sendError('Failed finding ticket assigned groups');
		}
		$ticket['assigned_groups'] = $assignedGroups->fetchAll();
		$requestedGroups = $DB->prepare('SELECT id, name AS `requested_groups` FROM glpi_groups WHERE id IN (SELECT DISTINCT groups_id FROM glpi_groups_tickets WHERE tickets_id=:id AND type=1)');
		$requestedGroups->bindParam(':id', $id);
		if (!$requestedGroups->execute()) {
			sendError('Failed finding ticket requested groups');
		}
		$ticket['requested_groups'] = $requestedGroups->fetchAll();

		return $ticket;
	}

	public static function search($fields) {
		$builder = new SQLBuilder('glpi_tickets', '', ', t.status DESC, t.date_mod DESC LIMIT 10');
		$builder->selector = 't.id';

		if (empty($fields->content)) {
			$descExplode = [];
		} else {
			$descExplode = explode(' ', $fields->content);
		}
		if (empty($fields->name)) {
			$titleExplode = [];
		} else {
			$titleExplode = explode(' ', $fields->name);
		}
		$searchFields = array_merge($titleExplode, $descExplode);
		$builder->search($searchFields, ['name', 'content']);

		$tickets = $builder->fetchAll();
		foreach ($tickets as &$ticket) {
			$ticket = self::getTicketWithLeftJoins($ticket['id']);
			self::bindTicketDetails($ticket, -1, true);
		}
		return $tickets;
	}

	/**
	 * @param $ticket array|integer|string
	 */
	public static function updateDate($ticket) {
		/** @var PDO $DB */ global $DB;

		if (!is_numeric($ticket) && !is_string($ticket)) {
			$ticket = $ticket['id'];
		}
		$date = date("Y-m-d H:i:s");
		$updateStatement = $DB->prepare("UPDATE glpi_tickets SET date_mod=:date WHERE id=:id");
		$updateStatement->bindParam(':id', $ticket);
		$updateStatement->bindParam(':date', $date);
		if (!$updateStatement->execute()) {
			sendError('Failed to change the last update date');
		}
	}

	public static function setTicketStatus($id, $status, $oldStatus = null) {
		/** @var PDO $DB */ global $DB;

		if ($oldStatus != null) {
			$updateStatusStatement = $DB->prepare("UPDATE glpi_tickets SET status=:status, date_mod=:date WHERE id=:id AND status=:oldStatus;");
			$updateStatusStatement->bindParam(':oldStatus', $oldStatus);
		} else {
			$updateStatusStatement = $DB->prepare("UPDATE glpi_tickets SET status=:status, date_mod=:date WHERE id=:id;");
		}
		$date = date("Y-m-d H:i:s");
		$updateStatusStatement->bindParam(':status', $status);
		$updateStatusStatement->bindParam(':id', $id);
		$updateStatusStatement->bindParam(':date', $date);
		if (!$updateStatusStatement->execute()) {
			sendError('Failed to set status ticket');
		}
	}

}
