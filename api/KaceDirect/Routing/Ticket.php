<?php

namespace KaceDirect\Routing;

use KaceDirect\RoutingBase;
use PDO;

class Ticket extends RoutingBase {

	/**
	 * @param $user
	 * @return mixed
	 */
	public function call($user) {
		if (array_key_exists('HTTP_TYPE', $_SERVER)) {
			if ($_SERVER['HTTP_TYPE'] === 'attachments') {
				sendError(null, 500);
				return $this->attachment($user);
			}
			sendError(null, 500);
			if ($_SERVER['HTTP_TYPE'] === 'actions') {
				return $this->action($user);
			}
			if ($_SERVER['HTTP_TYPE'] === 'followup') {
				return $this->followup($user);
			}
			if ($_SERVER['HTTP_TYPE'] === 'solution') {
				return $this->solution($user);
			}
			if ($_SERVER['HTTP_TYPE'] === 'mark') {
				return $this->markSolution($user);
			}
			if ($_SERVER['HTTP_TYPE'] === 'delete') {
				return $this->close($user);
			}
			if ($_SERVER['HTTP_TYPE'] === 'newCategory') {
				return $this->newCategory();
			}
		}

		return $this->getTicket($this->getId());
	}

	private function newCategory() {
		/** @var PDO $DB */ global $DB;
		$categoryName = $_SERVER['HTTP_CATEGORY'];
		$date = date("Y-m-d H:i:s");

		$statement = $DB->prepare("INSERT INTO glpi_knowbaseitemcategories (`name`, `completename`, `level`, `ancestors_cache`, `date_mod`, `date_creation`) VALUES (:cat, :cat, '1', '[]', :date, :date);");
		$statement->bindParam(':cat', $categoryName);
		$statement->bindParam(':date', $date);
		if (!$statement->execute()) {
			sendError('No new category made');
		}
		return true;
	}

	/**
	 * @param FastUser $user
	 * @param bool $withFollowup
	 * @param int $status
	 * @return mixed
	 */
	public function close($user, $withFollowup = true, $status = FastTicket::CLOSED) {
		/** @var PDO $DB */ global $DB;

		if ($withFollowup) {
			$this->followup($user, 'Closed by me');
		}

		$date = date("Y-m-d H:i:s");
		$id = $this->getId();
		$userId = $user->id;
		$setClosedStatement = $DB->prepare("UPDATE glpi_tickets SET closedate=:date, solvedate=:date, date_mod=:date, users_id_lastupdater=:user, status=:status WHERE id=:id;");
		$setClosedStatement->bindParam(':date', $date);
		$setClosedStatement->bindParam(':user', $userId);
		$setClosedStatement->bindParam(':id', $id);
		$setClosedStatement->bindParam(':status', $status);
		if (!$setClosedStatement->execute()) {
			sendError('Failed to close ticket');
		}

		return true;
	}


	/**
	 * @param FastUser $user
	 * @return mixed
	 */
	public function markSolution($user) {
		/** @var PDO $DB */ global $DB;

		if (!key_exists('HTTP_APPROVED', $_SERVER)) {
			sendError('Missing approved header', 400);
		}
		$content = trim(file_get_contents('php://input'));
		$isApproved = intval($_SERVER['HTTP_APPROVED']) === 1;
		$id = $this->getId();
		$date = date("Y-m-d H:i:s");
		$status = $isApproved ? 3 : 4;
		$userId = $user->id;
		$updateInsert = $DB->prepare("UPDATE glpi_itilsolutions SET users_id_approval=:userId, status=:status, date_mod=:date, date_approval=:date WHERE itemtype='Ticket' AND items_id=:id AND status=2");
		$updateInsert->bindParam(':id', $id);
		$updateInsert->bindParam(':status', $status);
		$updateInsert->bindParam(':date', $date);
		$updateInsert->bindParam(':userId', $userId);
		if (!$updateInsert->execute()) {
			sendError('Failed to post solution');
		}

		$this->followup($user, ($isApproved ? '<i class="fas fa-thumbs-up"></i>' : '<i class="fas fa-thumbs-down"></i>') . '<br>' . $content);
		if ($isApproved) {
			$this->close($user, false, FastTicket::APPROVAL);
		}

		return true;
	}

	/**
	 * @param FastUser $user
	 * @return mixed
	 */
	public function solution($user) {
		/** @var PDO $DB */ global $DB;

		$id = $this->getId();
		$content = trim(file_get_contents('php://input'));
		if (empty($content)) {
			sendError('Missing Content');
		}
		$date = date("Y-m-d H:i:s");
		$userId = $user->id;
		$statementInsert = $DB->prepare("INSERT INTO glpi_itilsolutions (itemtype, items_id, date_creation, date_mod, content, users_id, users_id_approval, status) VALUES ('Ticket', :id, :date, :date, :content, :userId, '0', '2');");
		$statementInsert->bindParam(':id', $id);
		$statementInsert->bindParam(':date', $date);
		$statementInsert->bindParam(':userId', $userId);
		$statementInsert->bindParam(':content', $content);
		if (!$statementInsert->execute()) {
			sendError('Failed to post solution');
		}

		FastTicket::setTicketStatus($id, FastTicket::WAITING);

		return true;
	}

	/**
	 * @param FastUser $user
	 * @param null|string $message
	 * @return mixed
	 */
	public function followup($user, $message = null) {
		/** @var PDO $DB */ global $DB;

		$id = $this->getId();
		if ($message === null) {
			$content = trim(file_get_contents('php://input'));
		} else {
			$content = $message;
		}

		if (key_exists('HTTP_REOPEN', $_SERVER)) {
			$statementReopen = $DB->prepare("UPDATE glpi_tickets SET closedate=NULL, solvedate=NULL, date_mod=:date, users_id_lastupdater=:user, status='2' WHERE id=:id;");
			$date = date("Y-m-d H:i:s");
			$statementReopen->bindParam(':date', $date);
			$statementReopen->bindParam(':user', $userId);
			$statementReopen->bindParam(':id', $id);
			if (!$statementReopen->execute()) {
				sendError('Failed to reopen ticket');
			}
		}

		if (empty($content)) {
			sendError('Missing Content');
		}
		$date = date("Y-m-d H:i:s");
		$userId = $user->id;
		$statementInsert = $DB->prepare("INSERT INTO glpi_itilfollowups (itemtype, items_id, date, users_id, content, date_mod, date_creation, timeline_position) VALUES ('Ticket', :id, :date, :userId, :content, :date, :date, '1');");
		$statementInsert->bindParam(':id', $id);
		$statementInsert->bindParam(':date', $date);
		$statementInsert->bindParam(':userId', $userId);
		$statementInsert->bindParam(':content', $content);
		if (!$statementInsert->execute()) {
			sendError('Failed to post followup');
		}

		FastTicket::setTicketStatus($id, FastTicket::ASSIGNED, FastTicket::WAITING);
		FastTicket::setTicketStatus($id, FastTicket::ASSIGNED, FastTicket::SOLVED);
		FastTicket::setTicketStatus($id, FastTicket::ASSIGNED, FastTicket::CLOSED);
		FastTicket::setTicketStatus($id, FastTicket::ASSIGNED, FastTicket::ACCEPTED);
		FastTicket::updateDate($id);

		return true;
	}

	/**
	 * @param FastUser $user
	 * @return mixed
	 */
	public function action($user) {
		/** @var PDO $DB */ global $DB;

		$id = $this->getId();
		$actionStatement = $DB->prepare("SELECT itil.* FROM ( SELECT 'followup' AS `type`, itilf.content, itilf.date_mod, itilf.users_id, 0 AS `status` FROM glpi_itilfollowups itilf WHERE itilf.itemtype = 'Ticket' AND itilf.items_id=:id UNION SELECT 'solution' AS `type`, itils.content, itils.date_mod, itils.users_id, itils.status FROM glpi_itilsolutions itils WHERE itils.itemtype='Ticket' AND itils.items_id=:id ) AS `itil` ORDER BY date_mod DESC");
		$actionStatement->bindParam(':id', $id);
		if (!$actionStatement->execute()) {
			sendError('Failed to get Actions');
		}

		$actions = $actionStatement->fetchAll();

		foreach ($actions as &$action) {
			$action['status'] = nameOfType(FastTicket::VALIDATION, $action['status']);
			$action['content'] = html_entity_decode($action['content']);

			$userStatement = $DB->prepare('SELECT id, firstname, realname, picture FROM glpi_users WHERE id=:id');
			$userStatement->bindParam(':id', $action['users_id']);
			if (!$userStatement->execute()) {
				sendError('Failed to get action-user');
			}
			$action['users_id'] = $userStatement->fetch();
		}

		return $actions;
	}

	/**
	 * @param $user
	 * @return mixed
	 */
	public function attachment($user) {
		/** @var PDO $DB */ global $DB;

		echo 'A';

		$id = $this->getId();
		$filesStatement = $DB->prepare("SELECT id,filename,mime,filepath,date_creation FROM glpi_documents WHERE id IN ( SELECT documents_id FROM glpi_documents_items s WHERE items_id = :id AND itemtype='Ticket' );");
		$filesStatement->bindParam(':id', $id);
		if (!$filesStatement->execute()) {
			sendError('Attachment not found');
		}

		$files = [];
		foreach ($filesStatement->fetchAll() as $fileEntry) {
			$entry = [ //
				$fileEntry['filename'], //
				'/front/document.send.php?docid=' . $fileEntry['id'] . "&tickets_id=$id", //
				$fileEntry['date_creation']];
			if (strpos($fileEntry['mime'], 'image') === 0) {
				$entry[] = base64_encode(file_get_contents(GLPI_ROOT . 'files/' . $fileEntry['filepath']));
			}
			$files[] = $entry;
		}
		return $files;
	}

	/**
	 * @param string|integer $id
	 * @return mixed
	 */
	public function getTicket($id) {
		/** @var PDO $DB */ global $DB;
		$statement = $DB->prepare("SELECT 
									t.ID AS id,
									t.TITLE AS name,
									t.CREATED AS `date`,
									t.CREATED AS `date_creation`,
									IF(t.TIME_CLOSED='0000-00-00 00:00:00', NULL, t.TIME_CLOSED) AS `closedate`,
									IF(t.TIME_CLOSED='0000-00-00 00:00:00', NULL, t.TIME_CLOSED) AS `solvedate`,
									t.MODIFIED AS `date_mod`,
									uO.FULL_NAME AS `users_id_recipient`,
									uO.FULL_NAME AS `users_id_lastupdater`,
									IF(s.STATE='opened', 'assign', IF(s.STATE='stalled', 'waiting', s.STATE)) AS `status`,
									t.SUMMARY AS `content`,
									p.NAME AS `urgency`,
									i.NAME AS `impact`,
									p.NAME AS `priority`,
									q.NAME AS `itilcategories_id`,
									t.CUSTOM_FIELD_VALUE0 AS `locations_id`,
									t.*
								FROM HD_TICKET t
								LEFT JOIN USER uO
								ON t.OWNER_ID = uO.ID
								LEFT JOIN USER uS
								ON t.OWNER_ID = uS.ID
								JOIN HD_STATUS s
								ON t.HD_STATUS_ID = s.ID
								JOIN HD_PRIORITY p
								ON t.HD_PRIORITY_ID = p.ID
								JOIN HD_IMPACT i
								ON t.HD_IMPACT_ID = i.ID
								JOIN HD_QUEUE q
								ON t.HD_QUEUE_ID = q.ID
								WHERE t.ID = :id");
		$statement->bindParam(':id', $id);
		$statement->execute();

		$ticket = $statement->fetch();
		$ticket['requested_users'] = $DB->query("SELECT * FROM USER WHERE ID = " . $ticket['SUBMITTER_ID'])->fetchAll();
		$ticket['assigned_users'] = $DB->query("SELECT * FROM USER WHERE ID = " . $ticket['OWNER_ID'])->fetchAll();
		$ticket['solutions'] = [];
		$ticket['possibleSolutions'] = [];
		$ticket['similar'] = [];

		return $ticket;
	}

	private function getId() {
		if (!key_exists('HTTP_ID', $_SERVER)) {
			sendError('Missing id header', 400);
		}
		return intval($_SERVER['HTTP_ID']);
	}
}
