<?php

class PluginOceanConfig extends CommonDBTM {

	static protected $notable = true;

	static function getMenuName() {
		return __('Ocean');
	}

	static function getMenuContent() {
		return [ //
			'title' => __('Ocean', 'ocean'), //
			'page' => redirectUrlOcean(true) //
		];
	}

	function getTabNameForItem(CommonGLPI $item, $withTemplate = 0) {
		switch (get_class($item)) {
			case 'Entity':
				return [1 => __('Ocean', 'ocean')];
			default:
				return '';
		}
	}

	static function displayTabContentForItem(CommonGLPI $item, $tabNumber = 1, $withTemplate = 0) {
		switch (get_class($item)) {
			case 'Entity':
				$config = new self();
				return $config->showFormDisplay();
		}
		return true;
	}

	function showFormDisplay() {
		return false;
	}
}
