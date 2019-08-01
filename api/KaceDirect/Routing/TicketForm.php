<?php

namespace KaceDirect\Routing;

use KaceDirect\RoutingBase;
use PDO;

class TicketForm extends RoutingBase {

	/**
	 * @return mixed
	 */
	public function call($user) {
		/** @var PDO $DB */ global $DB;

		if (array_key_exists('HTTP_TYPE', $_SERVER)) {
			if ($_SERVER['HTTP_TYPE'] === 'database') {
				return $this->getTableStructure();
			}
			if ($_SERVER['HTTP_TYPE'] === 'options') {
				return $this->getOptions();
			}
			if ($_SERVER['HTTP_TYPE'] === 'save') {
				return $this->save();
			}
			if ($_SERVER['HTTP_TYPE'] === 'create') {
				return $this->create();
			}
			if ($_SERVER['HTTP_TYPE'] === 'mainpost') {
				return $this->mainPost();
			}
			if ($_SERVER['HTTP_TYPE'] === 'attachment') {
				return $this->attachment();
			}
			if ($_SERVER['HTTP_TYPE'] === 'switch') {
				return $this->switchOrder();
			}
		}

		$statement = $DB->query("SELECT * FROM test.glpi_plugin_ocean_category ORDER BY id DESC");
		if (!$statement->execute()) {
			sendError('Can\'t get category');
		}

		return $statement->fetchAll();
	}

	/**
	 * @return mixed
	 */
	private function switchOrder() {
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

		$switchStatement = $DB->prepare("UPDATE test.glpi_plugin_ocean_category SET id=:newId WHERE id=:oldId");

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
	 * @return mixed
	 */
	private function getOptions() {
		/** @var PDO $DB */ global $DB;

		$assetClass = array_key_exists('HTTP_FIELD', $_SERVER) ? $_SERVER['HTTP_FIELD'] : '';
		$assetTable = array_key_exists('HTTP_TABLE', $_SERVER) ? $_SERVER['HTTP_TABLE'] : '';
		if ($assetTable === 'User') {
			$statement = $DB->prepare("SELECT ID, " . $assetClass . " AS NAME
				FROM USER u");
			$statement->execute();
		} else if ($assetClass === 'NAME') {
			$assetType = array_key_exists('HTTP_TABLE', $_SERVER) ? $_SERVER['HTTP_TABLE'] : '';
			$statement = $DB->prepare("SELECT ID, NAME
				FROM ASSET A
				WHERE A.ASSET_TYPE_ID IN (
					SELECT ID
					FROM ASSET_TYPE
					WHERE NAME = ?
				)");
			$statement->execute([$assetType]);
		} else {
			$assetType = array_key_exists('HTTP_TABLE', $_SERVER) ? $_SERVER['HTTP_TABLE'] : '';
			$statement = $DB->prepare("SELECT ID, NAME
				FROM ASSET A
				WHERE A.ASSET_CLASS_ID IN (
					SELECT ID
					FROM ASSET_CLASS
					WHERE NAME = ?
				) AND A.ASSET_TYPE_ID IN (
					SELECT ID
					FROM ASSET_TYPE
					WHERE NAME = ?
				)");
			$statement->execute([$assetClass, $assetType]);
		}

		$returnArray = [];

		foreach ($statement->fetchAll() as $row) {
			$returnArray[] = [ //
				'id' => $row['ID'], //
				'value' => $row['NAME'], //
			];
		}

		return $returnArray;
	}

	/**
	 * @return mixed
	 */
	private function attachment() {
		sendError('ASDF Not supported yet TicketForm->attachment', 500);
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
		//		$extensionStatement = $DB->prepare('SELECT id FROM glpi_documenttypes WHERE ext LIKE :ext');
		//		$extensionStatement->bindParam(':ext', $extension);
		//		if (!$extensionStatement->execute()) {
		//			sendError('Extension not found');
		//		}
		//		if ($extensionStatement->rowCount() === 0) {
		//			sendError('File type not supported', 400);
		//		}


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
	 * @return mixed
	 */
	private function mainPost() {
		sendError('ASDF => Not implemented TicketForm->mainPost');
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

		//		$newTicket = FastTicket::createNewTicket($user, //
		//			$this->getFromObject($ticketData, 'type'), //
		//			$this->getFromObject($ticketData, 'category'), //
		//			$this->getFromObject($ticketData, 'status'), //
		//			$this->getFromObject($ticketData, 'urgency'), //
		//			$this->getFromObject($ticketData, 'impact'), //
		//			$this->getFromObject($ticketData, 'priority'), //
		//			$this->getFromObject($ticketData, 'source'), //
		//			$this->getFromObject($ticketData, 'SLA-max-time'), //
		//			$this->getFromObject($ticketData, 'title'), //
		//			$this->getFromObject($ticketData, 'description'), //
		//			$this->getFromObject($ticketData, 'other-ticket'), //
		//			$this->getFromObject($ticketData, 'assignedToUser'), //
		//			$this->getFromObject($ticketData, 'assignedToGroup') //
		//		);

		//		return $newTicket;
	}

	private function getFromObject(\stdClass $class, $property, $default = null) {
		if (property_exists($class, $property)) {
			return $class->$property;
		} else {
			return $default;
		}
	}

	/**
	 * @return mixed
	 */
	private function create() {
		/** @var PDO $DB */ global $DB;

		$categoryName = file_get_contents('php://input');

		$insert = $DB->prepare("INSERT INTO test.glpi_plugin_ocean_category (category_i18n, data) VALUES (:catName, '[]');");
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
	 * @param $user
	 * @return mixed
	 */
	private function save() {
		/** @var PDO $DB */ global $DB;

		$categoryObject = json_decode(file_get_contents('php://input'));

		$id = $categoryObject->id;
		$name = $categoryObject->category_i18n;
		$data = json_encode($categoryObject->data);

		$update = $DB->prepare("UPDATE test.glpi_plugin_ocean_category SET data=?, category_i18n=? WHERE id=?;");
		if (!$update->execute([$data, $name, $id])) {
			sendError('Failed updating');
		}

		return true;
	}

	/**
	 * @return mixed
	 */
	private function getTableStructure() {
		/** @var PDO $DB */ global $DB;

		$returnArray = ['User' => ['USER_NAME', 'EMAIL', 'FULL_NAME', 'ID']];

		foreach ($DB->query('SELECT `ID`, `NAME` FROM ASSET_TYPE')->fetchAll() as $type) {
			$returnArray[$type['NAME']] = ['NAME'];
			foreach ($DB->query('SELECT `NAME` FROM ASSET_CLASS WHERE ASSET_TYPE_ID=' . $type['ID'])->fetchAll() as $class) {
				$returnArray[$type['NAME']][] = $class['NAME'];
			}
		}

		return $returnArray;
	}
}
