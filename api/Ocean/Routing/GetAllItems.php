<?php

namespace Ocean\Routing;

use Ocean\FastProfile;
use Ocean\FastUser;
use Ocean\RoutingBase;
use Ocean\SQLBuilder;
use PDO;

class GetAllItems extends RoutingBase {

	/**
	 * @param FastUser $user
	 * @return mixed
	 */
	public function call($user) {
		$requestedType = $this->isValidType();
		$this->checkRight($user, $requestedType);

		return $this->fetch($requestedType);
	}

	private function fetch($requestedType) {
		/** @var PDO $DB */ global $DB;

		$trash = $this->withTrash();
		$limit = $this->limitAmount();
		$rt = strtolower($requestedType);
		$table = 'glpi_' . $rt . 's';

		$sqlBuilder = new SQLBuilder($table, 'WHERE t.is_deleted <= :trash', 'LIMIT :limit');

		$types = [ //
			$this->SQL_entities($rt), //
			$this->SQL_locations($rt), //
			$this->SQL_user_not($rt, 'uit', ['line'], 'users_id_tech'), //
			$this->SQL_user_not($rt, 'git', ['line'], 'groups_id_tech'), //
			$this->SQL_updateSource($rt), //
			$this->SQL_domain($rt), //
			$this->SQL_networks($rt), //
			$this->SQL_linkModels($rt, 'name', ['computer', 'monitor', 'printer', 'peripheral', 'phone', 'enclosure', 'networkequipment', 'pdu', 'rack']), //
			$this->SQL_linkTypes($rt, 'name', ['computer', 'monitor', 'printer', 'peripheral', 'phone', 'networkequipment', 'pdu', 'rack', 'softwarelicense', 'certificate', 'line']), //
			$this->SQL_softwareCategory($rt), //
			$this->SQL_licensesSoftware($rt), //
			$this->SQL_licenseLicense($rt), //
			$this->SQL_licenseVersions($rt, 'softwareversions_id_buy', 'svb'), //
			$this->SQL_licenseVersions($rt, 'softwareversions_id_use', 'svu'), //
			$this->SQL_manufactures($rt, ['line']), //
			$this->SQL_softwareParent($rt), //
			$this->SQL_phonePowerSupply($rt), //
			$this->SQL_lineOperator($rt), //
			$this->SQL_user($rt, 'ui', ['computer', 'monitor', 'peripheral', 'phone', 'printer', 'software', 'networkequipment'], 'users_id'), //
			$this->SQL_group($rt, 'gi', ['computer', 'monitor', 'peripheral', 'phone', 'printer', 'software', 'networkequipment'], 'groups_id'), //
			$this->SQL_states($rt, ['computer', 'printer', 'monitor', 'peripheral', 'phone', 'enclosure', 'networkequipment', 'pdu', 'rack', 'softwarelicense', 'certificate', 'line']), //
		];
		foreach ($types as $type) {
			if ($type) {
				$sqlBuilder->leftJoin($type[0], $type[1], $type[2], $type[3], $type[4], $type[5]);
			}
		}

		return $sqlBuilder->fetchAll(function (\PDOStatement $statement) use ($trash, $limit) {
			$statement->bindParam(':trash', $trash, PDO::PARAM_INT);
			$statement->bindParam(':limit', $limit, PDO::PARAM_INT);
		});
	}

	private function SQL_entities($requestedType) {
		if ($this->isAllowedType($requestedType, true)) {
			return ['glpi_entities', 'entities_id', 'completename', null, null, null];
		}
		return false;
	}

	private function SQL_locations($requestedType) {
		if ($this->isAllowedType($requestedType, true)) {
			return ['glpi_locations', 'locations_id', 'completename', null, null, null];
		}
		return false;
	}

	private function SQL_updateSource($requestedType) {
		if ($this->allowOnly($requestedType, 'computer')) {
			return ['glpi_autoupdatesystems', 'autoupdatesystems_id', 'name', null, null, null];
		}
		return false;
	}

	private function SQL_domain($requestedType) {
		if ($this->isAllowedType($requestedType, ['computer', 'printer', 'networkequipment'])) {
			return ['glpi_domains', 'domains_id', 'name', null, null, null];
		}
		return false;
	}

	private function SQL_networks($requestedType) {
		if ($this->isAllowedType($requestedType, ['computer', 'printer', 'networkequipment'])) {
			return ['glpi_networks', 'networks_id', 'name', null, null, null];
		}
		return false;
	}

	private function SQL_linkModels($requestedType, $wanted, $allowedTypes) {
		if ($this->isAllowedType($requestedType, $allowedTypes)) {
			$tableShort = substr($requestedType, 0, 2);
			return ['glpi_' . $requestedType . 'models', $requestedType . 'models_id', $wanted, $tableShort . 'mod', null, null];
		}
		return false;
	}

	private function SQL_linkTypes($requestedType, $wanted, $allowedTypes) {
		if ($this->isAllowedType($requestedType, $allowedTypes)) {
			$tableShort = substr($requestedType, 0, 2);
			return ['glpi_' . $requestedType . 'types', $requestedType . 'types_id', $wanted, $tableShort . 'typ', null, null];
		}
		return false;
	}

	private function SQL_phonePowerSupply($requestedType) {
		if ($this->allowOnly($requestedType, 'phone')) {
			return ['glpi_phonepowersupplies', 'phonepowersupplies_id', 'name', null, null, null];
		}
		return false;
	}

	private function SQL_lineOperator($requestedType) {
		if ($this->allowOnly($requestedType, 'line')) {
			return ['glpi_lineoperators', 'lineoperators_id', 'name', null, null, null];
		}
		return false;
	}

	private function SQL_softwareCategory($requestedType) {
		if ($this->allowOnly($requestedType, 'software')) {
			return ['glpi_softwarecategories', 'softwarecategories_id', 'name', null, null, null];
		}
		return false;
	}

	private function SQL_licensesSoftware($requestedType) {
		if ($this->allowOnly($requestedType, 'softwarelicense')) {
			return ['glpi_softwares', 'softwares_id', 'name', null, null, null];
		}
		return false;
	}

	private function SQL_licenseLicense($requestedType) {
		if ($this->allowOnly($requestedType, 'softwarelicense')) {
			return ['glpi_softwarelicenses', 'softwarelicenses_id', 'completename', 'sls', null, null];
		}
		return false;
	}

	private function SQL_licenseVersions($requestedType, $property, $tableShort) {
		if ($this->allowOnly($requestedType, 'softwarelicense')) {
			return ['glpi_softwareversions', $property, 'name', $tableShort, null, null];
		}
		return false;
	}

	private function SQL_softwareParent($requestedType) {
		if ($this->allowOnly($requestedType, 'software')) {
			return ['glpi_softwares', 'softwares_id', 'name', 'sos', null, 'softwares_parent'];
		}
		return false;
	}

	private function SQL_manufactures($requestedType, $allowedTypes) {
		if ($this->isNotAllowedType($requestedType, $allowedTypes)) {
			return ['glpi_manufacturers', 'manufacturers_id', 'name', null, null, null];
		}
		return false;
	}

	private function SQL_states($requestedType, $allowedTypes) {
		if ($this->isAllowedType($requestedType, $allowedTypes)) {
			return ['glpi_states', 'states_id', 'completename', null, null, null];
		}
		return false;
	}

	private function SQL_user($requestedType, $tableShort, $allowedTypes = [], $on = 'users_id') {
		if ($this->isAllowedType($requestedType, $allowedTypes)) {
			return ['glpi_users', $on, 'name', $tableShort, null, null];
		}
		return false;
	}

	private function SQL_user_not($requestedType, $tableShort, $allowedTypes = [], $on = 'users_id') {
		if ($this->isNotAllowedType($requestedType, $allowedTypes)) {
			return ['glpi_users', $on, 'name', $tableShort, null, null];
		}
		return false;
	}

	private function SQL_group($requestedType, $tableShort, $allowedTypes = [], $on = 'groups_id') {
		if ($this->isAllowedType($requestedType, $allowedTypes)) {
			return ['glpi_groups', $on, 'name', $tableShort, null, null];
		}
		return false;
	}

	private function allowOnly($requestedType, $allowedType) {
		return $allowedType === true || $requestedType === $allowedType;
	}

	private function isAllowedType($requestedType, $allowedTypes) {
		return $allowedTypes === true || in_array($requestedType, $allowedTypes);
	}

	private function isNotAllowedType($requestedType, $allowedTypes) {
		return $allowedTypes === true || !in_array($requestedType, $allowedTypes);
	}

	/**
	 * @return int amount
	 */
	private function limitAmount() {
		if (key_exists('HTTP_LIMIT', $_SERVER)) {
			$limit = intval($_SERVER['HTTP_LIMIT']);
		} else {
			$limit = 50;
		}

		return $limit;
	}

	/**
	 * @return int includeTrash
	 */
	private function withTrash() {
		if (key_exists('HTTP_INCLUDE_TRASH', $_SERVER)) {
			$withTrash = boolval($_SERVER['HTTP_INCLUDE_TRASH']);
		} else {
			$withTrash = false;
		}

		return $withTrash ? 1 : 0;
	}

	/**
	 * @return string type
	 */
	private function isValidType() {
		if (!key_exists('HTTP_TYPE', $_SERVER)) {
			sendError('Missing type header', 400);
		}
		$requestedType = $_SERVER['HTTP_TYPE'];

		if (!in_array($requestedType, //
			['Computer', 'Monitor', 'NetworkEquipment', 'Peripheral', //
				'Phone', 'Printer', 'Software', 'DCRoom', 'Rack', 'Enclosure', //
				'PDU', 'SoftwareLicense', 'Certificate', 'Line'])) {
			sendError('Invalid type requested ' . $requestedType, 400);
		}

		return $requestedType;
	}

	/**
	 * @param FastUser $user
	 * @param $requestedType
	 */
	private function checkRight(FastUser $user, $requestedType) {
		if (!$user->hasRight($requestedType, FastProfile::READ)) {
			sendError('User has not right to list ' . $requestedType, 403);
		}
	}
}
