<?php

namespace Ocean;

use PDO;

class APIValidation {

	public static function check() {
		$referer = key_exists('HTTP_REFERER', $_SERVER) ? $_SERVER['HTTP_REFERER'] : '//' . $_SERVER['HTTP_HOST'];
		$ip = self::toIP($referer);
		if (!self::checkIfIpIsAllowed($ip)) {
			sendError('API ip is not allowed, change in setup>general>api', 400);
		}
	}

	protected static function toIP($referer) {
		if (preg_match('/\/\/(.+?)(?:[:|\/]|$)/', $referer, $matches)) {
			$hostname = gethostbyname($matches[1]);
			if (!$hostname) {
				sendError('API ip supported ' . $hostname);
			}
			$ipLong = ip2long($hostname);
			if (!$ipLong) {
				sendError('API ip not able to convert to int ' . $hostname);
			}
			return $ipLong;
		}
		sendError('API not supported ' . $referer);
	}

	protected static function checkIfIpIsAllowed($ipLong) {
		/** @var PDO $DB */ global $DB;

		$apiIpAllowances = $DB->query('SELECT app_token, ipv4_range_start, ipv4_range_end FROM glpi_apiclients WHERE is_active = 1');
		$allowed = false;
		foreach ($apiIpAllowances as $apiIpAllowance) {
			if ($apiIpAllowance['ipv4_range_start'] >= $ipLong && $apiIpAllowance['ipv4_range_end'] <= $ipLong) {
				if (!$apiIpAllowance['app_token']) {
					$allowed = true;
					break;
				}
				$tokenFromHeader = key_exists('HTTP_APP_TOKEN', $_SERVER) ? $_SERVER['HTTP_APP_TOKEN'] : '';
				if ($tokenFromHeader == $apiIpAllowance['app_token']) {
					$allowed = true;
					break;
				}
			}
		}
		return $allowed;
	}
}
