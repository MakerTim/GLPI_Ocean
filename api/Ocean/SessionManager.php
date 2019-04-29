<?php

namespace Ocean;

use PDO;

class SessionManager {

	/**
	 * @return FastUser
	 */
	public static function checkSession() {
		if (!key_exists('HTTP_PERSONAL_TOKEN', $_SERVER)) {
			sendError('Missing personal-token header', 403);
		}
		$sessionKey = $_SERVER['HTTP_PERSONAL_TOKEN'];
		$user = null;
		try {
			$user = self::checkToken($sessionKey);
		} catch (\Exception $exception) {
			sendError($exception->getMessage());
		}
		return $user;
	}

	/**
	 * @param string $token
	 * @return FastUser
	 */
	private static function checkToken($token) {
		/** @var PDO $DB */ global $DB;

		$statement = $DB->prepare('SELECT u.id, u.name, u.phone, u.phone2, u.mobile, u.realname, u.firstname, u.locations_id, u.language, u.use_mode, u.list_limit, u.is_active, u.comment, u.auths_id, u.authtype, u.last_login, u.date_mod, u.date_sync, u.is_deleted, u.profiles_id, u.entities_id, u.usertitles_id, u.usercategories_id, u.date_format, u.number_format, u.names_format, u.csv_delimiter, u.is_ids_visible, u.use_flat_dropdowntree, u.show_jobs_at_login, u.priority_1, u.priority_2, u.priority_3, u.priority_4, u.priority_5, u.priority_6, u.followup_private, u.task_private, u.default_requesttypes_id, u.password_forget_token, u.password_forget_token_date, u.user_dn, u.registration_number, u.show_count_on_tabs, u.refresh_ticket_list, u.set_default_tech, u.personal_token, u.personal_token_date, u.api_token, u.api_token_date, u.display_count_on_home, u.notification_to_myself, u.duedateok_color, u.duedatewarning_color, u.duedatecritical_color, u.duedatewarning_less, u.duedatecritical_less, u.duedatewarning_unit, u.duedatecritical_unit, u.display_options, u.is_deleted_ldap, u.pdffont, u.picture, u.begin_date, u.end_date, u.keep_devices_when_purging_item, u.privatebookmarkorder, u.backcreated, u.task_state, u.layout, u.palette, u.set_default_requester, u.lock_autolock_mode, u.lock_directunlock_notification, u.date_creation, u.highcontrast_css, u.plannings, u.sync_field, u.groups_id, u.users_id_supervisor ' . //
			'FROM glpi_users u WHERE u.is_active = 1 ' . //
			'AND (u.begin_date <= CURRENT_DATE OR ISNULL(begin_Date)) ' . //
			'AND u.personal_token = :personalToken');
		$statement->bindParam(':personalToken', $token, PDO::PARAM_STR);
		if (!$statement->execute()) {
			sendError('No user with this token');
		}
		$result = $statement->fetch();
		if (!$result) {
			sendError('No user with this token');
		}
		return new FastUser($result);
	}
}
