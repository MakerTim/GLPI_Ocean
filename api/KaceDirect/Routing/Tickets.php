<?php


namespace KaceDirect\Routing;

use KaceDirect\RoutingBase;
use PDO;

class Tickets extends RoutingBase {

	public function call($user) {
		/** @var PDO $DB */ global $DB;
		global $user;

		$type = $this->getType();
		$select = "SELECT t.ID AS 'id', 
				IF(s.STATE='opened', 'assign', IF(s.STATE='stalled', 'waiting', s.STATE)) AS 'status', 
				t.TITLE AS 'name', t.MODIFIED AS 'date_mod'";

		if ($type === 'search') {
			$fields = json_decode(file_get_contents('php://input'), true);
			$statement = "$select
				FROM HD_TICKET t
				JOIN HD_STATUS s
				ON t.HD_STATUS_ID = s.ID ";

			$searchFields = [];
			if (array_key_exists('name', $fields) && $fields['name']) {
				$searchFields = array_merge($searchFields, explode(' ', $fields['name']));
			}
			if (array_key_exists('content', $fields) && $fields['content']) {
				$fields['content'] = str_replace("\n", ' ', $fields['content']);
				$searchFields = array_merge($searchFields, explode(' ', $fields['content']));
			}
			if (count($searchFields) > 0) {
				$statement .= 'WHERE ';
				foreach ($searchFields as $searchField) {
					$searchField = str_replace("'", "''", $searchField);
					$statement .= " t.TITLE LIKE '%$searchField%' OR t.SUMMARY LIKE '%$searchField%' OR";
				}
				$statement = rtrim($statement, 'OR');
			} else {
				return [];
			}

			return $DB->query($statement)->fetchAll();
		} else if ($type === 'open' || $type === 'closed') {
			$statement = $DB->prepare( //
				"$select
				FROM HD_TICKET t
				JOIN HD_STATUS s
				ON t.HD_STATUS_ID = s.ID
				WHERE t.SUBMITTER_ID = ? AND s.STATE " . //
				($type === 'open' ? '!=' : '=') . //
				"'closed' ORDER BY date_mod DESC
				LIMIT 5");
			$statement->execute([$user->userId]);
		} else if ($type === 'around') {
			$statement = $DB->prepare( //
				"$select 
				FROM HD_TICKET t
				JOIN HD_STATUS s
				ON t.HD_STATUS_ID = s.ID
				WHERE t.SUBMITTER_ID IN (
				SELECT u.ID
				FROM USER u
				WHERE u.MANAGER_ID = (
					SELECT u.MANAGER_ID
					FROM USER u
					WHERE u.Id = ?
				)
				AND u.ID != ?
			)
			ORDER BY date_mod DESC
			LIMIT 5");
			$statement->execute([$user->userId, $user->userId]);
		}
		return $statement->fetchAll();
	}
}
