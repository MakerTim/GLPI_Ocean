<?php

namespace Ocean\Routing;

use Ocean\FastUser;
use Ocean\RoutingBase;
use PDO;

class Login extends RoutingBase {

	/**
	 * @param null $user
	 * @return mixed
	 */
	public function call($user) {
		if (key_exists('PHP_AUTH_USER', $_SERVER) && key_exists('PHP_AUTH_PW', $_SERVER)) {
			return $this->withCredentials($_SERVER['PHP_AUTH_USER'], $_SERVER['PHP_AUTH_PW']);
		} else if (key_exists('HTTP_API_TOKEN', $_SERVER)) {
			return $this->withAPIToken($_SERVER['HTTP_API_TOKEN']);
		} else if (key_exists('HTTP_AD_SID', $_SERVER)) {
			return $this->withAD($_SERVER['HTTP_AD_SID']);
		}
		return sendError('No correct way for logging in, Username/Password, API-Token or AD-SID expected', 400);
	}


	/**
	 * @param string $username
	 * @param string $password
	 * @return mixed
	 */
	private function withCredentials($username, $password) {
		/** @var PDO $DB */ global $DB;

		$statement = $DB->prepare('SELECT u.id, u.personal_token, u.password FROM glpi_users u WHERE u.name = :username');
		$statement->bindParam(':username', $username);
		if (!$statement->execute()) {
			sendError('Can\'t find user');
		}
		$result = $statement->fetch();
		if (!$result) {
			sendError('No username found');
		}
		$passwordHash = $result['password'];
		if (!FastUser::checkPassword($password, $passwordHash)) {
			sendError('Password doesnt match', 401);
		}

		return FastUser::resetToken($result['personal_token'], $result['id']);
	}

	private function withAPIToken($token) {
		/** @var PDO $DB */ global $DB;

		$statement = $DB->prepare('SELECT u.id, u.personal_token FROM glpi_users u WHERE u.api_token = :api');
		$statement->bindParam(':api', $token);
		if (!$statement->execute()) {
			sendError('Can\'t find user');
		}
		$result = $statement->fetch();
		if (!$result) {
			sendError('Token doesnt match');
		}

		return FastUser::resetToken($result['personal_token'], $result['id']);
	}

	private function withAD($SID) {
		/** @var PDO $DB */ global $DB;

		set_time_limit(0);

		$SID = base64_decode($SID);
		if (!preg_match('/^S-\d-\d+-(?:\d+-){1,14}\d+$/', $SID)) {
			sendError('Not a valid SID');
		}
		exec('wmic useraccount where sid=\'' . $SID . '\' get name', $output);
		if (count($output) <= 1) {
			sendError('No account found with this SID');
		}
		$usernameSID = $output[1];

		$statement = $DB->prepare('SELECT u.id, u.personal_token FROM glpi_users u WHERE u.name = :name');
		$statement->bindParam(':name', $usernameSID);
		if (!$statement->execute()) {
			sendError('Can\'t find user');
		}
		$result = $statement->fetch();
		if (!$result) {
			sendError('Can\'t find corresponding user');
		}

		return FastUser::resetToken($result['personal_token'], $result['id']);
	}
}
