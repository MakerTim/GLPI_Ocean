<?php

namespace Ocean;

use PDO;

class SQLBuilder {

	public $select = 'SELECT';
	public $selector = 't.*';
	public $mainTableName = 't';
	private $table;
	public $where;
	private $group;
	private $order;
	private $limit;
	private $internalParams = [];

	private $selectsOverwrites = '';
	private $joinOtherTables = '';

	/** @var \PDOStatement */
	private $statement;

	public function __construct($table, $where = '', $limit = '', $group = '', $order = '') {
		$this->table = $table;
		$this->where = $where;
		$this->limit = $limit;
		$this->group = $group;
		$this->order = $order;
	}

	public function buildSQL() {
		return "$this->select $this->selector " . $this->selectsOverwrites . //
			" FROM $this->table $this->mainTableName " . //
			$this->joinOtherTables . ' ' . //
			$this->where . ' ' . //
			$this->group . ' ' . //
			$this->order . ' ' . //
			$this->limit;
	}

	private function prepare() {
		/** @var PDO $DB */ global $DB;

		$this->statement = $DB->prepare($this->buildSQL());
	}

	private function execute() {
		try {
			if (!$this->statement->execute()) {
				sendError('Can\'t find in ' . $this->table);
			}
		} catch (\Exception $e) {
			$this->statement->debugDumpParams();
		}
	}

	private function bindParams($bindParams) {
		foreach ($this->internalParams as $param) {
			$this->statement->bindParam($param[0], $param[1]);
		}
		if ($bindParams === null) {
			return;
		}
		$bindParams($this->statement);
	}

	public function search($searchingFor = [], $inFields = [], $tablePK = 'id', $tableShort = 'x') {
		if (!$this->group) {
			$this->group = ' GROUP BY';
		}
		$this->group = $this->group . ' t.id ';
		if (!$this->order) {
			$this->order = ' ORDER BY';
		}
		$this->order = $this->order . ' SUM(x.occ) DESC ';
		if (count($searchingFor) === 0 //
			|| (count($searchingFor) === 1 && empty($searchingFor[0])) ////
			|| (count($searchingFor) === 2 && empty($searchingFor[0]) && empty($searchingFor[1]))) {
			$this->joinOtherTables .= " INNER JOIN (SELECT '_' AS `$tablePK`, 0 as `occ`) as `$tableShort` ON $this->mainTableName.$tablePK=x.$tablePK ";
			return;
		}
		$joinString = 'INNER JOIN (' . PHP_EOL;
		$searchIndex = 0;
		foreach ($searchingFor as $searching) {
			if (empty($searching)) {
				continue;
			}
			$searchKey = ':S' . chr(65 + $searchIndex);
			$searchWildKey = ':SW' . chr(65 + $searchIndex);
			foreach ($inFields as $field) {
				$joinString .= "\tSELECT $tablePK, CONVERT((LENGTH($field) - LENGTH(REPLACE(LOWER($field), $searchKey, ''))) / LENGTH($searchKey), INTEGER) AS `occ` " . PHP_EOL;
				$joinString .= "\tFROM $this->table " . PHP_EOL;
				$joinString .= "\tWHERE $field LIKE $searchWildKey " . PHP_EOL;
				$joinString .= PHP_EOL . "\tUNION ALL " . PHP_EOL;
			}
			$this->internalParams[] = [$searchKey, strtolower($searching)];
			$this->internalParams[] = [$searchWildKey, '%' . $searching . '%'];
			$searchIndex++;
		}
		$joinString = substr($joinString, 0, -12);

		$joinString .= ") as  `$tableShort` ON t.$tablePK=x.$tablePK ";

		$this->joinOtherTables .= " $joinString ";
	}

	public function leftJoin($otherTable, $tableFK, $wanted, $tableShort = null, $tablePK = 'id', $as = null) {
		return $this->join($otherTable, $tableFK, $wanted, $tableShort, $tablePK, $as, 'LEFT JOIN');
	}

	public function innerJoin($otherTable, $tableFK, $wanted, $tableShort = null, $tablePK = 'id', $as = null) {
		return $this->join($otherTable, $tableFK, $wanted, $tableShort, $tablePK, $as, 'JOIN');
	}

	public function rightJoin($otherTable, $tableFK, $wanted, $tableShort = null, $tablePK = 'id', $as = null) {
		return $this->join($otherTable, $tableFK, $wanted, $tableShort, $tablePK, $as, 'RIGHT JOIN');
	}

	private function join($otherTable, $tableFK, $wanted, $tableShort = null, $tablePK = 'id', $as = null, $joinType = 'JOIN') {
		if ($tableShort === null) {
			$tableShort = substr($otherTable, 5, 2);
		}
		if ($tablePK === null) {
			$tablePK = 'id';
		}
		if ($as === null) {
			$as = $tableFK;
		}
		if (is_array($wanted)) {
			$wanteds = $wanted;
			$wanted = 'CONCAT(';

			$separator = '," ",';
			foreach ($wanteds as $concatWanted) {
				$wanted .= "$tableShort.$concatWanted$separator";
			}
			$wanted = rtrim($wanted, $separator) . ')';

			$this->selectsOverwrites .= ",$wanted as '$as' ";
		} else {
			$this->selectsOverwrites .= ",$tableShort.$wanted as '$as' ";
		}
		$this->joinOtherTables .= "$joinType $otherTable $tableShort ON $this->mainTableName.$tableFK = $tableShort.$tablePK ";
		return $this;
	}

	public function fetch($bindParams = null) {
		$this->prepare();
		$this->bindParams($bindParams);
		$this->execute();
		return $this->statement->fetch();
	}

	public function fetchAll($bindParams = null) {
		$this->prepare();
		$this->bindParams($bindParams);
		$this->execute();
		return $this->statement->fetchAll();
	}
}
