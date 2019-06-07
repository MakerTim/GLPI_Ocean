<?php

namespace Ocean\Routing;

use Ocean\FastProfile;
use Ocean\FastUser;
use Ocean\RoutingBase;

class Navigation extends RoutingBase {

	/**
	 * @param FastUser $user
	 * @return mixed
	 */
	public function call($user) {
		$menuItems = [];

		$this->assets($user, $menuItems);
		$this->adminMenu($user, $menuItems);

		return $menuItems;
	}

	/**
	 * @param $menuItems array
	 * @param FastUser $user
	 */
	private function adminMenu($user, &$menuItems) {
		if (!$user->hasRight('backup', FastProfile::READ)) {
			return;
		}
		$adminItems = [ //
			'settings', //
			'ticket-form', //
			'logs', //
			'users', //
		];

		foreach ($adminItems as $adminItem) {
			$menuItems['admin'][$adminItem] = "admin/$adminItem";
		}

		$menuItems['ticket']['globalTicket'] = 'global/Ticket/';
		$menuItems['ticket']['dashboard'] = 'dashboard/Ticket/';
		$menuItems['ticket']['search'] = 'search/Ticket/';
	}

	/**
	 * @param $menuItems array
	 * @param FastUser $user
	 */
	private function assets($user, &$menuItems) {
		$assets = [];
		foreach ($user->getProfiles() as $profile) {
			$assets = array_merge($assets, json_decode($profile->helpdesk_item_type));
		}
		if (in_array('DCRoom', $assets)) { // supposed to be in management
			$assets[array_search('DCRoom', $assets)] = 'PDU';
		}

		$menuItems['assets'] = [];
		foreach ($assets as $asset) {
			$menuItems['assets'][$asset] = "assets/$asset";
		}

		$menuItems['ticket']['TicketCreate'] = 'concrete/Ticket';
	}
}
