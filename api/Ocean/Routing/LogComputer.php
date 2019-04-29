<?php

namespace Ocean\Routing;

use Ocean\FastUser;
use Ocean\RoutingBase;
use PDO;

class LogComputer extends RoutingBase {

	/**
	 * @param FastUser $user
	 * @return mixed
	 */
	public function call($user) {
		/** @var PDO $DB */ global $DB;

		$computerSpecs = json_decode(file_get_contents('php://input'), true);

		$existingComputer = $DB->prepare('SELECT * FROM glpi_computers WHERE name = :pcName');
		$existingComputer->bindParam(':pcName', $computerSpecs['Host Name']);
		if (!$existingComputer->execute()) {
			sendError('Can\t find a computer by name');
		}
		if ($existingComputer->rowCount() > 0) {
			$specsQuery = 'UPDATE glpi_computers SET name=:pcName, contact_num=:OSName, contact=:OSVersion, users_id=:userId, domains_id=:domainId, uuid=:uuid, manufacturers_id=:manufactureId, computermodels_id=:modelId, comment=:comments, date_mod=:dateMod WHERE name=:pcName LIMIT 1;';
		} else {
			$specsQuery = 'INSERT INTO glpi_computers (name, serial, otherserial, contact, contact_num, comment, date_mod, template_name, uuid, date_creation, users_id, domains_id, manufacturers_id, computermodels_id) ' . "VALUES (:pcName, '', '', :OSVersion, :OSName, :comments, :dateMod, null, :uuid, :dateMod, :userId, :domainId, :manufactureId, :modelId)";
		}
		$DB->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

		$computerStatement = $DB->prepare($specsQuery);

		$manufacturerId = $this->findOrCreateManufactureId($computerSpecs['System Manufacturer']);
		$modelId = $this->findOrCreateModelId($computerSpecs['System Model']);
		$domainId = $this->findOrCreateDomainId($computerSpecs['Domain']);

		$commentBox = 'Last boot: ' . $computerSpecs['System Boot Time'] . ' ' . PHP_EOL . //
			'Processor(s): ' . json_encode($computerSpecs['Processor(s)']) . ' ' . PHP_EOL . //
			'Logon Server: ' . $computerSpecs['Logon Server'] . ' ' . PHP_EOL . //
			'Networking: ' . json_encode($computerSpecs['Network Card(s)']) . ' ' . PHP_EOL . //
			'RAM: ' . json_encode($computerSpecs['Virtual Memory']) . ' ' . PHP_EOL . //
			'';

		$date = date("Y-m-d H:i:s");
		$computerStatement->bindParam(':pcName', $computerSpecs['Host Name']);
		$computerStatement->bindParam(':dateMod', $date);
		$computerStatement->bindParam(':OSName', $computerSpecs['OS Name']);
		$computerStatement->bindParam(':OSVersion', $computerSpecs['OS Version']);
		$computerStatement->bindParam(':userId', $user->id);
		$computerStatement->bindParam(':domainId', $domainId);
		$computerStatement->bindParam(':uuid', $computerSpecs['Product ID']);
		$computerStatement->bindParam(':manufactureId', $manufacturerId);
		$computerStatement->bindParam(':modelId', $modelId);
		$computerStatement->bindParam(':comments', $commentBox);

		if (!$computerStatement->execute()) {
			sendError('Error while sending computer stats');
		}

		return true;
	}

	private function findOrCreateDomainId($domainName) {
		/** @var PDO $DB */ global $DB;

		$statement = $DB->prepare('SELECT id FROM glpi_domains WHERE name=:domainName');
		$statement->bindParam(':domainName', $domainName);
		if (!$statement->execute()) {
			sendError('Failed searching for Domain');
		}

		if ($statement->rowCount() === 0) {
			$insert = $DB->prepare("INSERT INTO glpi_domains (name, comment, date_mod, date_creation) VALUES (:name, '', :aDate, :aDate)");
			$date = date("Y-m-d H:i:s");
			$insert->bindParam(':name', $domainName);
			$insert->bindParam(':aDate', $date);
			if (!$insert->execute()) {
				sendError('Failed inserting domain');
			}

			$statement = $DB->query('SELECT LAST_INSERT_ID() AS id;');
			if (!$statement->execute()) {
				sendError('No domain id found');
			}
		}
		return intval($statement->fetchAll()[0]['id']);
	}

	private function findOrCreateModelId($modelName) {
		/** @var PDO $DB */ global $DB;

		$statement = $DB->prepare('SELECT id FROM glpi_computermodels WHERE name=:modelName');
		$statement->bindParam(':modelName', $modelName);
		if (!$statement->execute()) {
			sendError('Failed searching for PCModel');
		}

		if ($statement->rowCount() === 0) {
			$insert = $DB->prepare("INSERT INTO glpi_computermodels (name, comment, product_number, date_mod, date_creation) VALUES (:name, '', '', :aDate, :aDate)");
			$date = date("Y-m-d H:i:s");
			$insert->bindParam(':name', $modelName);
			$insert->bindParam(':aDate', $date);
			if (!$insert->execute()) {
				sendError('Failed inserting model');
			}

			$statement = $DB->query('SELECT LAST_INSERT_ID() AS id;');
			if (!$statement->execute()) {
				sendError('No model id found');
			}
		}
		return intval($statement->fetchAll()[0]['id']);
	}

	private function findOrCreateManufactureId($manufactureName) {
		/** @var PDO $DB */ global $DB;

		$statement = $DB->prepare('SELECT id FROM glpi_manufacturers WHERE name=:manuName');
		$statement->bindParam(':manuName', $manufactureName);
		if (!$statement->execute()) {
			sendError('Failed searching for Manufacture');
		}

		if ($statement->rowCount() === 0) {
			$insert = $DB->prepare("INSERT INTO glpi_manufacturers (name, comment, date_mod, date_creation) VALUES (:name, '', :aDate, :aDate)");
			$date = date("Y-m-d H:i:s");
			$insert->bindParam(':name', $manufactureName);
			$insert->bindParam(':aDate', $date);
			if (!$insert->execute()) {
				sendError('Failed inserting manufacture');
			}

			$statement = $DB->query('SELECT LAST_INSERT_ID() AS id;');
			if (!$statement->execute()) {
				sendError('No manufacture id found');
			}
		}
		return intval($statement->fetchAll()[0]['id']);
	}
}
