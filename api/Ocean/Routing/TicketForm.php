<?php

namespace Ocean\Routing;

use Ocean\FastProfile;
use Ocean\FastTicket;
use Ocean\FastUser;
use Ocean\RoutingBase;
use PDO;

class TicketForm extends RoutingBase {

	/**
	 * @param FastUser $user
	 * @return mixed
	 */
	public function call($user) {
		/** @var PDO $DB */ global $DB;

		if (!$user->hasRight('ticket', FastProfile::READ)) {
			sendError('Needs ticket read rights', 403);
		}

		if (array_key_exists('HTTP_TYPE', $_SERVER)) {
			if ($_SERVER['HTTP_TYPE'] === 'database') {
				return $this->getTableStructure($user);
			}
			if ($_SERVER['HTTP_TYPE'] === 'options') {
				return $this->getOptions($user);
			}
			if ($_SERVER['HTTP_TYPE'] === 'save') {
				return $this->save($user);
			}
			if ($_SERVER['HTTP_TYPE'] === 'create') {
				return $this->create($user);
			}
			if ($_SERVER['HTTP_TYPE'] === 'mainpost') {
				return $this->mainPost($user);
			}
			if ($_SERVER['HTTP_TYPE'] === 'attachment') {
				return $this->attachment($user);
			}
			if ($_SERVER['HTTP_TYPE'] === 'switch') {
				return $this->switchOrder($user);
			}
		}

		$statement = $DB->query("SELECT * FROM glpi_plugin_ocean_category ORDER BY id DESC");
		if (!$statement->execute()) {
			sendError('Can\'t get category');
		}

		return $statement->fetchAll();
	}


	/**
	 * @param FastUser $user
	 * @return mixed
	 */
	private function switchOrder($user) {
		/** @var PDO $DB */ global $DB;

		if (!array_key_exists('HTTP_ID1', $_SERVER)) {
			sendError('missing id1');
		}
		if (!array_key_exists('HTTP_ID2', $_SERVER)) {
			sendError('missing id2');
		}

		$id1 = intval($_SERVER['HTTP_ID1']);
		$id2 = intval($_SERVER['HTTP_ID2']);

		$switcheroos = [  //
			[$id1, 999], //
			[$id2, $id1], //
			[999, $id2]  //
		];

		$switchStatement = $DB->prepare("UPDATE glpi_plugin_ocean_category SET id=:newId WHERE id=:oldId");

		foreach ($switcheroos as $switcheroo) {
			$switchStatement->bindParam(':oldId', $switcheroo[0]);
			$switchStatement->bindParam(':newId', $switcheroo[1]);
			if (!$switchStatement->execute()) {
				//				$switchStatement->debugDumpParams();
				sendError('Failed switching at: ' . json_encode($switcheroo) . PHP_EOL . json_encode($switcheroos));
			}
		}

		return true;
	}

	/**
	 * @param FastUser $user
	 * @return mixed
	 */
	private function getOptions($user) {
		/** @var PDO $DB */ global $DB;

		if (!$user->hasRight('ticket', FastProfile::CREATE)) {
			return [];
		}

		$requestedTable = array_key_exists('HTTP_TABLE', $_SERVER) ? $_SERVER['HTTP_TABLE'] : '';

		$statement = $DB->prepare("SELECT table_name FROM information_schema.tables WHERE table_type = 'base table' AND table_name = :tableName");
		$statement->bindParam(':tableName', $requestedTable);
		if (!$statement->execute()) {
			sendError("Can't get database table $requestedTable");
		}
		if (!$statement->fetch()) {
			sendError("Can't find database table $requestedTable");
		}

		$requestedField = array_key_exists('HTTP_FIELD', $_SERVER) ? $_SERVER['HTTP_FIELD'] : '';

		$columnStatement = $DB->query("SHOW COLUMNS FROM $requestedTable");
		if (!$columnStatement->execute()) {
			sendError("Can't get columns of $requestedTable");
		}
		$fieldFound = false;
		foreach ($columnStatement->fetchAll() as $row) {
			if ($row['Field'] === $requestedField) {
				$fieldFound = true;
			}
		}
		if (!$fieldFound) {
			sendError("Can't get field $requestedField in $requestedTable");
		}

		$where = '';
		$subType = array_key_exists('HTTP_SUBTYPE', $_SERVER) ? $_SERVER['HTTP_SUBTYPE'] : '';
		if ($subType) {
			$field = substr($requestedTable, 5, -1) . 'types_id';
			$where .= "WHERE $field='" . str_replace("'",'"', $subType) . "'";
		}

		/** @noinspection SqlResolve */
		$realStatement = $DB->query("SELECT id, $requestedField as `value` FROM $requestedTable $where ORDER BY id");
		if (!$realStatement->execute()) {
			sendError('Error that never should have happend');
		}

		return $realStatement->fetchAll();
	}

	/**
	 * @param FastUser $user
	 * @return mixed
	 */
	private function attachment($user) {
		/** @var PDO $DB */ global $DB;

		// Get file from request
		$attachment = json_decode(file_get_contents('php://input'));
		$fileName = $attachment->name;
		if ($fileName === 'img') {
			$fileName = 'screenshot.png';
		}
		$fileContent = $attachment->value;
		$extension = explode('.', $fileName);
		$extension = end($extension);
		if (array_key_exists('HTTP_ATTACHED_TO', $_SERVER)) {
			$ticketId = intval($_SERVER['HTTP_ATTACHED_TO']);
		}


		// Check if extension is allowed
		$extensionStatement = $DB->prepare('SELECT id FROM glpi_documenttypes WHERE ext LIKE :ext');
		$extensionStatement->bindParam(':ext', $extension);
		if (!$extensionStatement->execute()) {
			sendError('Extension not found');
		}
		if ($extensionStatement->rowCount() === 0) {
			sendError('File type not supported', 400);
		}


		// Put file in ocean folder & calculate SHA1
		$rootFiles = GLPI_ROOT . 'files/';
		if (!is_dir($rootFiles)) {
			mkdir($rootFiles, 0777, true);
		}
		if (strpos($fileContent, ';base64,') === false) {
			sendError('File encoding not allowed');
		}
		$fileContent = explode(';base64,', $fileContent);
		$fileMime = $fileContent[0]; //data:image/png
		$fileMime = substr($fileMime, 5);
		$fileContent = $fileContent[1]; //base64 string
		$fileContent = base64_decode($fileContent);
		$sha1 = sha1($fileContent);
		$newFilename = 'ocean/' . $sha1 . '.' . $extension;
		if (file_put_contents($rootFiles . $newFilename, $fileContent) === false) {
			sendError('File not saved');
		}


		// Calculate TAG
		$serverSubSha1 = substr(sha1(php_uname('a')), 0, 8);
		$dirSubSha1 = substr(sha1(__FILE__), 0, 8);
		$tag = uniqid("$serverSubSha1-$dirSubSha1-", true);


		// Put document into the DB
		$documentStatement = $DB->prepare('INSERT INTO glpi_documents (name, filename, filepath, documentcategories_id, mime, date_mod, users_id, tickets_id, sha1sum, tag, date_creation) VALUES ' . //
			'(:name, :name, :filepath, 0, :mime, :date_mod, :users_id, :tickets_id, :sha1sum, :tag, :date_mod);');
		$date = date("Y-m-d H:i:s");
		$documentStatement->bindParam(':name', $fileName);
		$documentStatement->bindParam(':filepath', $newFilename);
		$documentStatement->bindParam(':mime', $fileMime);
		$documentStatement->bindParam(':date_mod', $date);
		$documentStatement->bindParam(':users_id', $user->id);
		$documentStatement->bindParam(':tickets_id', $ticketId);
		$documentStatement->bindParam(':sha1sum', $sha1);
		$documentStatement->bindParam(':tag', $tag);
		if (!$documentStatement->execute()) {
			sendError('Failed to save file into the db');
		}
		$id = $DB->query('SELECT LAST_INSERT_ID() AS id;');
		if (!$id->execute()) {
			sendError('No document id found');
		}
		$id = intval($id->fetchAll()[0]['id']);


		// Put document into Ticket
		$type = 'Ticket';
		$ticketDocumentStatement = $DB->prepare('INSERT INTO glpi_documents_items (documents_id, items_id, itemtype, date_mod, users_id) VALUES ' . //
			'(:documents_id, :items_id, :itemtype, :date_mod, :users_id)');
		$ticketDocumentStatement->bindParam(':documents_id', $id);
		$ticketDocumentStatement->bindParam(':items_id', $ticketId);
		$ticketDocumentStatement->bindParam(':itemtype', $type);
		$ticketDocumentStatement->bindParam(':date_mod', $date);
		$ticketDocumentStatement->bindParam(':users_id', $user->id);
		if (!$ticketDocumentStatement->execute()) {
			sendError('Failed to save file-ticket-link into the db');
		}
		FastTicket::updateDate($ticketId);

		return true;
	}

	/**
	 * @param FastUser $user
	 * @return mixed
	 */
	private function mainPost($user) {
		/** @var PDO $DB */ global $DB;

		$ticketData = json_decode(file_get_contents('php://input'));

		if ($this->getFromObject($ticketData, 'title') === null //
			|| empty($this->getFromObject($ticketData, 'title'))) {
			sendError('Title empty');
		}
		if ($this->getFromObject($ticketData, 'description') === null //
			|| empty($this->getFromObject($ticketData, 'description'))) {
			sendError('Description empty');
		}
		foreach (get_object_vars($ticketData) as $property => $value) {
			if (strpos($property, 'custom.') === 0) {
				$ticketData->description .= PHP_EOL . str_replace('custom.', '', $property) . ': ' . $ticketData->$property;
			}
		}

		$newTicket = FastTicket::createNewTicket($user, //
			$this->getFromObject($ticketData, 'type'), //
			$this->getFromObject($ticketData, 'category'), //
			$this->getFromObject($ticketData, 'status'), //
			$this->getFromObject($ticketData, 'urgency'), //
			$this->getFromObject($ticketData, 'impact'), //
			$this->getFromObject($ticketData, 'priority'), //
			$this->getFromObject($ticketData, 'source'), //
			$this->getFromObject($ticketData, 'SLA-max-time'), //
			$this->getFromObject($ticketData, 'title'), //
			$this->getFromObject($ticketData, 'description'), //
			$this->getFromObject($ticketData, 'other-ticket'), //
			$this->getFromObject($ticketData, 'assignedToUser'), //
			$this->getFromObject($ticketData, 'assignedToGroup') //
		);

		return $newTicket;
	}

	private function getFromObject(\stdClass $class, $property, $default = null) {
		if (property_exists($class, $property)) {
			return $class->$property;
		} else {
			return $default;
		}
	}

	/**
	 * @param FastUser $user
	 * @return mixed
	 */
	private function create($user) {
		/** @var PDO $DB */ global $DB;

		$categoryName = file_get_contents('php://input');

		$insert = $DB->prepare("INSERT INTO glpi_plugin_ocean_category (category_i18n, data) VALUES (:catName, '[]');");
		$insert->bindParam(':catName', $categoryName);
		if (!$insert->execute()) {
			sendError('Failed creating');
		}

		$id = $DB->query('SELECT LAST_INSERT_ID() AS id;');
		if (!$id->execute()) {
			sendError('No id found');
		}

		return intval($id->fetchAll()[0]['id']);
	}

	/**
	 * @param FastUser $user
	 * @return mixed
	 */
	private function save($user) {
		/** @var PDO $DB */ global $DB;

		if (!$user->hasRight('backup', FastProfile::CREATE)) {
			sendError('Needs backup rights', 403);
		}

		$categoryObject = json_decode(file_get_contents('php://input'));

		$id = $categoryObject->id;
		$name = $categoryObject->category_i18n;
		$data = json_encode($categoryObject->data);

		$update = $DB->prepare("UPDATE glpi_plugin_ocean_category SET data=?, category_i18n=? WHERE id=?;");
		if (!$update->execute([$data, $name, $id])) {
			sendError('Failed updating');
		}

		return true;
	}

	/**
	 * @param FastUser $user
	 * @return mixed
	 */
	private function getTableStructure($user) {
		/** @var PDO $DB */ global $DB;

		if (!$user->hasRight('backup', FastProfile::READ)) {
			sendError('Needs backup rights', 403);
		}

		$statement = $DB->query("SELECT table_name FROM information_schema.tables WHERE table_type = 'base table' AND table_name LIKE 'GLPI_%' ORDER BY table_name asc");
		if (!$statement->execute()) {
			sendError('Can\'t get database structure');
		}

		$structure = [];
		$tables = $statement->fetchAll();

		foreach ($tables as $table) {
			$subStatement = $DB->query("SHOW COLUMNS FROM $table[table_name]");
			if (!$subStatement->execute()) {
				sendError('Can\'t get sub structure ' . $table['table_name']);
			}
			$structure[$table['table_name']] = [];
			foreach ($subStatement->fetchAll() as $column) {
				$structure[$table['table_name']][] = $column['Field'];
			}
		}
		return $structure;
	}
}
