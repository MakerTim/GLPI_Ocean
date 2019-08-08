<?php

namespace Ocean;

use PDO;

/**
 * @property string $id
 * @property string|null $name
 * @property string|null $password
 * @property string|null $phone
 * @property string|null $phone2
 * @property string|null $mobile
 * @property string|null $realname
 * @property string|null $firstname
 * @property string $locations_id
 * @property string|null $language
 * @property string $use_mode
 * @property string|null $list_limit
 * @property string $is_active
 * @property string|null $comment
 * @property string $auths_id
 * @property string $authtype
 * @property string|null $last_login
 * @property string|null $date_mod
 * @property string|null $date_sync
 * @property string $is_deleted
 * @property string $profiles_id
 * @property string $entities_id
 * @property string $usertitles_id
 * @property string $usercategories_id
 * @property string|null $date_format
 * @property string|null $number_format
 * @property string|null $names_format
 * @property string|null $csv_delimiter
 * @property string|null $is_ids_visible
 * @property string|null $use_flat_dropdowntree
 * @property string|null $show_jobs_at_login
 * @property string|null $priority_1
 * @property string|null $priority_2
 * @property string|null $priority_3
 * @property string|null $priority_4
 * @property string|null $priority_5
 * @property string|null $priority_6
 * @property string|null $followup_private
 * @property string|null $task_private
 * @property string|null $default_requesttypes_id
 * @property string|null $password_forget_token
 * @property string|null $password_forget_token_date
 * @property string|null $user_dn
 * @property string|null $registration_number
 * @property string|null $show_count_on_tabs
 * @property string|null $refresh_ticket_list
 * @property string|null $set_default_tech
 * @property string|null $personal_token
 * @property string|null $personal_token_date
 * @property string|null $api_token
 * @property string|null $api_token_date
 * @property string|null $display_count_on_home
 * @property string|null $notification_to_myself
 * @property string|null $duedateok_color
 * @property string|null $duedatewarning_color
 * @property string|null $duedatecritical_color
 * @property string|null $duedatewarning_less
 * @property string|null $duedatecritical_less
 * @property string|null $duedatewarning_unit
 * @property string|null $duedatecritical_unit
 * @property string|null $display_options
 * @property string $is_deleted_ldap
 * @property string|null $pdffont
 * @property string|null $picture
 * @property string|null $begin_date
 * @property string|null $end_date
 * @property string|null $keep_devices_when_purging_item
 * @property string|null $privatebookmarkorder
 * @property string|null $backcreated
 * @property string|null $task_state
 * @property string|null $layout
 * @property string|null $palette
 * @property string|null $set_default_requester
 * @property string|null $lock_autolock_mode
 * @property string|null $lock_directunlock_notification
 * @property string|null $date_creation
 * @property string|null $highcontrast_css
 * @property string|null $plannings
 * @property string|null $sync_field
 * @property string $groups_id
 * @property string $users_id_supervisor
 * @property array groups
 */
class FastUser extends FastModel {

	public function __construct(array $DBEntry) {
		parent::__construct($DBEntry);
	}

	/**
	 * @param string $name
	 * @param int $permission
	 * @param array $rights
	 * @return bool if has right
	 */
	public function hasRight($name, $permission, $rights = null) {
		if (!$rights) {
			$rights = $this->getRightsTable();
		}
		return FastProfile::hasRight($rights, $name, $permission);
	}

	/**
	 * @return FastProfile[]
	 */
	public function getProfiles() {
		/** @var PDO $DB */ global $DB;

		$statement = $DB->prepare('SELECT p.* ' .//
			'FROM glpi_profiles p ' .//
			'JOIN glpi_profiles_users pu ' . //
			'ON pu.profiles_id = p.id ' . //
			'WHERE pu.users_id = :userId');
		$statement->bindParam(':userId', $this->id, PDO::PARAM_INT);
		$statement->execute();

		$result = $statement->fetchAll();
		if (!$result) {
			$statement = $DB->prepare('INSERT INTO glpi_profiles_users (users_id, profiles_id, is_dynamic) values (:userId, 9, 1)');
			$statement->bindParam(':userId', $this->id, PDO::PARAM_INT);
			if (!$statement->execute()) {
				sendError('No profiles for user');
			}
			$statement = $DB->prepare('SELECT p.* ' .//
				'FROM glpi_profiles p ' .//
				'JOIN glpi_profiles_users pu ' . //
				'ON pu.profiles_id = p.id ' . //
				'WHERE pu.users_id = :userId');
			$statement->bindParam(':userId', $this->id, PDO::PARAM_INT);
			$statement->execute();
		}
		$profiles = [];
		foreach ($result as $row) {
			$profiles[] = new FastProfile($row);
		}
		return $profiles;
	}

	public function getRightsTable() {
		$rightTable = null;
		foreach ($this->getProfiles() as $profile) {
			if ($rightTable == null) {
				$rightTable = $profile->getRights();
			} else {
				$rightTable = $profile->getRightsCombined($rightTable);
			}
		}
		return $rightTable;
	}

	public function getMail() {
		/** @var PDO $DB */ global $DB;
		$statement = $DB->prepare('SELECT email FROM glpi_useremails WHERE users_id=:id ORDER BY is_default DESC');
		$statement->bindParam(':id', $this->id);
		if (!$statement->execute()) {
			return '';
		}
		return $statement->fetch()['email'];
	}

	// From {GLPI}/inc/auth.class.php:271
	static function checkPassword($pass, $hash) {
		$tmp = password_get_info($hash);
		if (isset($tmp['algo']) && $tmp['algo']) {
			$ok = password_verify($pass, $hash);
		} else if (strlen($hash) == 32) {
			$ok = md5($pass) == $hash;
		} else if (strlen($hash) == 40) {
			$ok = sha1($pass) == $hash;
		} else {
			$salt = substr($hash, 0, 8);
			$ok = ($salt . sha1($salt . $pass) == $hash);
		}

		return $ok;
	}

	public static function generateToken($length, $subset = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789') {
		$token = '';
		$subsetLength = strlen($subset) - 1;
		for ($i = 0; $i < $length; $i++) {
			$token .= $subset[mt_rand(0, $subsetLength)];
		}
		return $token;
	}


	public static function resetToken($token, $userId) {
		/** @var PDO $DB */ global $DB;
		if ($token == null) {
			$token = static::generateToken(40);

			$tokenStatement = $DB->prepare('UPDATE glpi_users SET personal_token=:token WHERE id=:id');
			$tokenStatement->bindParam(':id', $userId);
			$tokenStatement->bindParam(':token', $token);
			if (!$tokenStatement->execute()) {
				sendError('Failed to make a token');
			}
		}

		return ['personal-token' => $token];
	}
}
