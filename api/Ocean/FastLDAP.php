<?php

namespace Ocean;

use PDO;

class FastLDAP {

	static function decrypt($glpiToken) {
		$string = base64_decode($glpiToken);
		$result = '';
		$key = "GLPI£i'snarss'ç";

		for ($i = 0; $i < strlen($string); $i++) {
			$keyChar = substr($key, ($i % strlen($key)) - 1, 1);
			$char = substr($string, $i, 1);
			$char = chr(ord($char) - ord($keyChar));
			$result .= $char;
		}
		return $result;
	}

	static function login($login, $password, &$log = []) {
		/** @var PDO $DB */ global $DB;

		$ldaps = $DB->query('SELECT * FROM glpi_authldaps WHERE is_active=1')->fetchAll();
		$user = null;
		foreach ($ldaps as $ldap) {
			# AuthLdap::tryToConnectToServer
			{
				$ds = @ldap_connect($ldap['host'], intval($ldap['port']));
				if ($ds) {
					@ldap_set_option($ds, LDAP_OPT_PROTOCOL_VERSION, 3);
					@ldap_set_option($ds, LDAP_OPT_REFERRALS, 0);
					@ldap_set_option($ds, LDAP_OPT_DEREF, $ldap['deref_option']);
					if ($ldap['use_tls']) {
						if (!@ldap_start_tls($ds)) {
							$log[] = 'TLS ERROR' . PHP_EOL;
							exit();
						}
					}
					if ($ldap['rootdn'] != '') {
						$b = @ldap_bind($ds, $ldap['rootdn'], static::decrypt($ldap['rootdn_passwd']));
					} else {
						$b = @ldap_bind($ds);
					}
					if (!$b) {
						continue;
					}
					$log[] = 'DATABASE LDAP ' . $ldap['host'] . ' FOUND, ACCEPTED, AND LOVED';
				}
			}

			{
				$log[] = 'ASKING THE REAL QUESTION NOW';

				# AuthLdap::searchUserDn
				{
					$user = @ldap_search($ds, $ldap['basedn'], "(" . $ldap['login_field'] . "=" . $login . ")");
					if (!$user) {
						$log[] = '    Umh user not found here... NEXT';
						continue;
					}
					$userEntry = ldap_get_entries($ds, $user);
					if (is_array($userEntry) && $userEntry['count'] === 1) {
						if ($password !== null && !@ldap_bind($ds, $userEntry[0]['dn'], $password)) {
							$log[] = 'WRONG PASSWORD! WOMP WOMP!' . PHP_EOL;
						} else {
							$log[] = 'WELCOME ' . $userEntry[0]['cn'][0];
							preg_match_all('/OU=((?:(?:\\\\,)|(?:[^,]))+)/', $userEntry[0]['dn'], $group, PREG_SET_ORDER, 0);
							$group = str_replace('\,', ',', $group[0][1]);
							$log[] = 'GROUP: ' . $group;
							$user = [ //
								'_loginfield' => $ldap['login_field'], //
								'dn' => $userEntry[0]['dn'], //
								$ldap['login_field'] => $userEntry[0][$ldap['login_field']][0], //
								'sn' => $userEntry[0]['sn'][0], //
								'givenname' => $userEntry[0]['givenname'][0], //
								'whencreated' => date('Y-m-d H:i:s', strtotime(str_replace('.0', '', $userEntry[0]['whencreated'][0]))), //
								'whenchanged' => date('Y-m-d H:i:s', strtotime(str_replace('.0', '', $userEntry[0]['whenchanged'][0]))), //
								'mail' => $userEntry[0]['mail'][0], //
								'group' => $group, //
								'auth_id' => $ldap['id'], //
							];
						}
						break;
					} else {
						$log[] = 'USER NOT FOUND: '  . $login;
						$log[] = $userEntry;
					}
				}
			}
			$log[] = 'THANKYOU -> NEXT';
		}
		return $user;
	}

	static function createLoginIfNotExist($login, $password, &$out = []) {
		/** @var PDO $DB */ global $DB;

		$user = FastLDAP::login($login, $password, $out);
		if ($user) {
			$statement = $DB->prepare('SELECT id FROM glpi_users WHERE name=:name LIMIT 1');
			$statement->bindParam(':name', $user[$user['_loginfield']]);
			$statement->execute();
			if ($statement->rowCount() !== 1) {
				$out[] = 'NO GLPI-USER FOUND';

				$user['token'] = FastUser::generateToken(40);

				$statement = $DB->prepare("INSERT INTO `glpi_users` (`name`, `password`, `realname`, `firstname`, `date_creation`, `date_mod`, `user_dn`, `auths_id`, `authtype`, `personal_token`, `personal_token_date`) " . //
					"VALUES (:login, '', :sn, :givenname, :whencreated, :whenchanged, :dn, :auth_id, '3', :token, now())");
				$statement->bindParam(':login', $user[$user['_loginfield']]);
				$statement->bindParam(':sn', $user['sn']);
				$statement->bindParam(':givenname', $user['givenname']);
				$statement->bindParam(':whencreated', $user['whencreated']);
				$statement->bindParam(':whenchanged', $user['whenchanged']);
				$statement->bindParam(':dn', $user['dn']);
				$statement->bindParam(':auth_id', $user['auth_id']);
				$statement->bindParam(':token', $user['token']);
				if (!$statement->execute()) {
					$out[] = 'NOT CREATED';
				}
			}

			// Check if group exists
			$group = FastGroup::exists($user['group']);
			if (!$group) {
				$group = FastGroup::createGroup($user['group']);
			}

			$statement = $DB->prepare('SELECT id, personal_token FROM glpi_users WHERE name=:name LIMIT 1');
			$statement->bindParam(':name', $user[$user['_loginfield']]);
			$statement->execute();
			$user = $statement->fetch();
			FastGroup::setUserInGroup($user['id'], $group);
		}
		return $user;
	}
}
