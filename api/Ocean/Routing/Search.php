<?php

namespace Ocean\Routing;

use Ocean\FastProfile;
use Ocean\FastTicket;
use Ocean\FastUser;
use Ocean\RoutingBase;
use Ocean\SQLBuilder;
use PDO;

class Search extends RoutingBase {

	/**
	 * @param FastUser $user
	 * @return mixed
	 */
	public function call($user) {
		/** @var PDO $DB */ global $DB;

		if (!$user->hasRight('ticket', FastProfile::READ)) {
			return [];
		}

		$input = json_decode(file_get_contents('php://input'));
		$searchString = explode(' ', $input[0]);
		$debug = '';
		$searchParams = $input[1];
		$dateFilter = $input[2];

		if(empty($searchString) || count($searchParams) === 0){
			return [$_SERVER['HTTP_RNG'], [], []];
		}

		$params = [];
		$externals = [];
		foreach ($searchParams as $key => $value) {
			if (is_string($value)) {
				$params[] = $value;
			} else if ($value[1] === 'self') {
				$params[] = $value[0];
			} else {
				$externals[$value[1]] = $value;
			}
		}

		foreach ($searchString as &$inputSearch) {
			$inputSearch = preg_replace('/_/m', ' ', $inputSearch);
			if (strpos($inputSearch, '#') !== 0) {
				continue;
			}
			$inputSearch = substr($inputSearch, 1);
			$regex = '/^(\d{4}|\d{1,2})?(?:-(\d{1,2}))?(?:-(\d{4}|\d{1,2}))?/m';
			preg_match_all($regex, $inputSearch, $matches, PREG_SET_ORDER);
			if (!empty($matches) && !empty($matches[0]) && !empty($matches[0][0])) {
				$matches = $matches[0];
				if (count($matches) === 4) {
					if (strlen($matches[1]) == 4) {
						$year = $matches[1];
						$y = true;
					} else if (strlen($matches[3]) == 4) {
						$year = $matches[3];
						$y = false;
					} else {
						continue;
					}
					if (intval($matches[2]) <= 12) {
						$month = $matches[2];
						$day = $matches[$y ? 3 : 1];
					} else if (intval($matches[$y ? 1 : 3]) <= 12) {
						$month = $matches[$y ? 1 : 3];
						$day = $matches[$y ? 3 : 1];
					} else {
						continue;
					}
				} else if (count($matches) === 3) {
					if (intval($matches[2]) <= 12) {
						$month = $matches[2];
						$day = $matches[1];
					} else if (intval($matches[1]) <= 12) {
						$month = $matches[1];
						$day = $matches[2];
					} else {
						continue;
					}
					$timestamp = mktime(0, 0, 0, $month, $day);
					if ($timestamp && $timestamp > time()) {
						$year = date('Y') - 1;
					} else {
						$year = date('Y');
					}
				} else {
					continue;
				}
				if (strlen($month) == 1) {
					$month = '0' . $month;
				}
				if (strlen($day) == 1) {
					$day = '0' . $day;
				}
				$inputSearch = "$year-$month-$day";
			} else {
				$tryDate = strtotime($inputSearch);
				if ($tryDate) {
					$inputSearch = date('Y-m-d', $tryDate);
				}
			}
		}

		$sqlBuilder = new SQLBuilder('glpi_tickets');
		$sqlBuilder->select = 'SELECT DISTINCT';
		$sqlBuilder->selector = 't.id';
		if (!empty($params)) {
			$sqlBuilder->search($searchString, $params);
		} else {
			$sqlBuilder->where = ' WHERE 1=0 ';
		}
		$tickets = [];
		foreach ($sqlBuilder->fetchAll() as $ticket) {
			$tickets[] = $ticket['id'];
		}

		foreach ($externals as $type => $external) {
			$ids = [];
			if ($type === 'join') {
				// users#name,realname,firstname
				$joinData = explode('#', $external[2]);
				$fieldData = explode(',', $joinData[1]);
				$joinData = $joinData[0];
				$sqlSubBuilder = new SQLBuilder('glpi_' . $joinData);
				$sqlSubBuilder->selector = 't.id';

				$sqlSubBuilder->search($searchString, $fieldData);

				$ids = [];
				foreach ($sqlSubBuilder->fetchAll() as $data) {
					$ids[] = $data['id'];
				}
			} else if ($type === 'multiple-link') {
				// users#name,realname,firstname;tickets_users;type,1
				$joinData = explode('#', $external[2]);
				$subData = explode(';', $joinData[1]);
				$fields = explode(',', $subData[0]);
				$table = $joinData[0];

				$sqlSubBuilder = new SQLBuilder('glpi_' . $table);
				$sqlSubBuilder->selector = 't.id';
				$sqlSubBuilder->search($searchString, $fields);

				$subIds = [];
				foreach ($sqlSubBuilder->fetchAll() as $data) {
					$subIds[] = $data['id'];
				}
				if (!empty($subIds)) {
					// users_id;tickets_id
					$external[3] = explode(';', $external[3]);
					$findField = $external[3][0];
					$getField = $external[3][1];
					$subTable = $subData[1];
					$subWhere = str_replace(',', '`=', $subData[2]);

					$sqlSubBuilder = new SQLBuilder('glpi_' . $subTable, 'WHERE `' . $subWhere . ' AND `' . $findField . '` IN (' . implode(', ', $subIds) . ')');
					$sqlSubBuilder->selector = 't.' . $getField;
					foreach ($sqlSubBuilder->fetchAll() as $data) {
						$ids[] = $data[$getField];
					}
					$external[0] = 'id';
				}
			} else if ($type === 'multiple-direct') {
				// itilfollowups#content;itemtype,Ticket
				$joinData = explode('#', $external[2]);
				$subData = explode(';', $joinData[1]);
				$fields = explode(',', $subData[0]);
				$where = str_replace(',', "`='", $subData[1]);
				$table = $joinData[0];
				$selector = $external[3];

				$sqlSubBuilder = new SQLBuilder('glpi_' . $table, "WHERE `$where'");
				$sqlSubBuilder->selector = 't.' . $selector;
				$sqlSubBuilder->search($searchString, $fields);
				foreach ($sqlSubBuilder->fetchAll() as $data) {
					$ids[] = $data[$selector];
				}
				$external[0] = 'id';
			}
			if (!empty($ids)) {
				$sqlSubBuilder = new SQLBuilder('glpi_tickets', 'WHERE ' . $external[0] . ' IN (' . implode(', ', $ids) . ')');
				$sqlSubBuilder->selector = 't.id';
				foreach ($sqlSubBuilder->fetchAll() as $ticket) {
					$index = array_search($ticket['id'], $tickets);
					if ($index === false) {
						$tickets[] = $ticket['id'];
					} else {
						array_unshift($tickets, $ticket['id']);
						$tickets = array_unique($tickets);
					}
				}
			}
		}

		foreach ($tickets as $key => &$ticket) {
			$ticket = FastTicket::getTicketWithLeftJoins($ticket);
			FastTicket::bindTicketDetails($ticket);

			foreach ($dateFilter as $filterKey => $filter) {
				if (count($filter) === 2 && $ticket[$filterKey]) {
					$ticketDate = new \DateTime($ticket[$filterKey]);
					$filterStartDate = new \DateTime($filter[0]);
					$filterEndDate = new \DateTime($filter[1]);

					if ($ticketDate->format('Y-m-d') < $filterStartDate->format('Y-m-d') || //
						$ticketDate->format('Y-m-d') > $filterEndDate->format('Y-m-d')) {
						unset($tickets[$key]);
						continue 2;
					}
				}
			}
		}

		return [$_SERVER['HTTP_RNG'], $tickets, $searchString, $debug];
	}

	private function _($array, $key, $default = '') {
		if (key_exists($key, $array)) {
			return $array[$key];
		}
		return $default;
	}

}
