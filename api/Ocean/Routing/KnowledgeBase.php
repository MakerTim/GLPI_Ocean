<?php

namespace Ocean\Routing;

use Ocean\FastProfile;
use Ocean\FastUser;
use Ocean\RoutingBase;
use PDO;

class KnowledgeBase extends RoutingBase {

	/**
	 * @param FastUser $user
	 * @return mixed
	 */
	public function call($user) {
		if (!$user->hasRight('ticket', FastProfile::UPDATE)) {
			return [];
		}

		if (array_key_exists('HTTP_TYPE', $_SERVER)) {
			if ($_SERVER['HTTP_TYPE'] === 'categories') {
				return $this->categories();
			} else if ($_SERVER['HTTP_TYPE'] === 'solutions') {
				return $this->solutions();
			} else if ($_SERVER['HTTP_TYPE'] === 'solution') {
				return $this->solution();
			} else if ($_SERVER['HTTP_TYPE'] === 'postSolution') {
				return $this->postSolution($user);
			}
		}

		return '';
	}

	private function categories() {
		/** @var PDO $DB */ global $DB;

		return $DB->query('SELECT * FROM glpi_knowbaseitemcategories ORDER BY completename')->fetchAll();
	}

	private function solutions() {
		/** @var PDO $DB */ global $DB;

		$statement = $DB->prepare('SELECT * FROM glpi_knowbaseitems WHERE knowbaseitemcategories_id=:category ORDER BY name');
		$statement->bindParam(':category', $_SERVER['HTTP_CATEGORY']);
		if (!$statement->execute()) {
			sendError('Failed to get solutions');
		}
		return $statement->fetchAll();
	}

	private function solution() {
		/** @var PDO $DB */ global $DB;

		$statement = $DB->prepare('SELECT * FROM glpi_knowbaseitems WHERE id=:id');
		$statement->bindParam(':id', $_SERVER['HTTP_ID']);
		if (!$statement->execute()) {
			sendError('Failed to get solution');
		}
		$solution = $statement->fetch();
		$solution['answer'] = html_entity_decode($solution['answer']);
		return $solution;
	}

	/**
	 * @param FastUser $user
	 * @return mixed
	 */
	private function postSolution($user) {
		/** @var PDO $DB */ global $DB;

		$content = file_get_contents('php://input');

		if (array_key_exists('HTTP_ID', $_SERVER)) {
			$statement = $DB->prepare("UPDATE glpi_knowbaseitems SET knowbaseitemcategories_id=:category, answer=:content, date_mod=:date, view=2 WHERE id=:id");
			$statement->bindParam(':id', $_SERVER['HTTP_ID']);
		} else {
			$statement = $DB->prepare("INSERT INTO glpi_knowbaseitems (`knowbaseitemcategories_id`, `name`, `answer`, `users_id`, `date`, `date_mod`, `begin_date`, `view`) VALUES (:category, :name, :content, :userId, :date, :date, :date, 2)");
			$name = $_SERVER['HTTP_NAME'];
			$statement->bindParam(':name', $name);
			$statement->bindParam(':userId', $user->id);
		}
		$date = date("Y-m-d H:i:s");
		$statement->bindParam(':category', $_SERVER['HTTP_CATEGORYID']);
		$statement->bindParam(':content', $content);
		$statement->bindParam(':date', $date);
		if (!$statement->execute()) {
			sendError('Failed knowbaseitem update/insert');
		}

		if (!array_key_exists('HTTP_ID', $_SERVER)) {
			$id = $DB->query('SELECT LAST_INSERT_ID() AS id;');
			if (!$id->execute()) {
				sendError('No ticket id found');
			}
			$_SERVER['HTTP_ID'] = intval($id->fetchAll()[0]['id']);
		}

		$linkedStatement = $DB->prepare("INSERT INTO glpi_knowbaseitems_items (`knowbaseitems_id`, `itemtype`, `items_id`, `date_creation`, `date_mod`) VALUES (:id, 'Ticket', :ticketId, :date, :date)");
		$linkedStatement->bindParam(':id', $_SERVER['HTTP_ID']);
		$linkedStatement->bindParam(':ticketId', $_SERVER['HTTP_TICKETID']);
		$linkedStatement->bindParam(':date', $date);
		if (!$linkedStatement->execute()) {
			$linkedStatement->debugDumpParams();
			sendError('Failed linking knowbaseitem');
		}
		return $this->solution();
	}
}
