<?php

function plugin_init_ocean() {
	/** @var DB $DB */ global $PLUGIN_HOOKS, $DB;

	$overwriteLogin = $DB->query("SELECT setting FROM glpi_plugin_ocean_config WHERE what = 'overwrite_login';");
	if (!strpos($_SERVER['REQUEST_URI'], '.php?redirect=') && //
		!strpos($_SERVER['REQUEST_URI'], 'front') && //
		!strpos($_SERVER['PHP_SELF'], '.js') && //
		!strpos($_SERVER['PHP_SELF'], '/ajax/') //
		&& $overwriteLogin) {
		if ($overwriteLogin->fetch_row()[0]) {
			echo //
				'<script>
					window.location.href = ' . '".' . redirectUrlOcean(true) . '"' . ';
				</script>
				<a style=\'color: white;\' href=' . "'." . redirectUrlOcean(true) . "'" . '>Klik hier om naar GLPI Ocean te gaan</a>';
			exit();
		}
		echo //
			'<script>
				function fillLogin() {
					const id = document.getElementById("text-login");
					if (!id) {
						setTimeout(function() {fillLogin(); }, 1500);
						return;
					}
					id.innerHTML = "<a style=\'color: white;\' href=' . "'." . redirectUrlOcean(true) . "'" . '>Klik hier om naar GLPI Ocean te gaan</a>";
				}
				setTimeout(function() {fillLogin(); }, 1500);
			</script>';
	}

	$PLUGIN_HOOKS['csrf_compliant']['ocean'] = true;

	Plugin::registerClass('PluginOceanConfig', ['addtabon' => 'Entity']);
	$PLUGIN_HOOKS["menu_toadd"]['ocean'] = ['plugins' => 'PluginOceanConfig'];

//	$DB->$PLUGIN_HOOKS['config_page']['ocean'] = redirectUrlOcean();
}

function redirectUrlOcean($withDir = false) {
	$ret = $withDir ? '/plugins/ocean/' : '';

	// Config page
	if (file_exists(__DIR__ . '/index.html')) {
		// production
		$ret .= 'index.html';
	} else if (file_exists(__DIR__ . '/dist/client/index.html')) {
		// dev + production_test
		$ret .= 'dist/client/index.html';
	} else {
		// Dev
		$ret .= 'redirect.php?r=http://' . $_SERVER['HTTP_HOST'] . ':4200/';
	}

	return $ret;
}

function plugin_version_ocean() {
	return [ //
		'name' => __('Ocean', 'ocean'), //
		'version' => '0.0.3', //
		'author' => '<a href="mailto:makertim@outlook.com"> Tim Biesenbeek </b> </a>', //
		'license' => '<a href="https://github.com/MakerTim/GLPI_Ocean/LICENSE">Apache-2.0</a>', //
		'homepage' => 'https://github.com/MakerTim/GLPI_Ocean', //
		'minGlpiVersion' => '9.4' //
	];
}

function plugin_ocean_check_prerequisites() {
	if (GLPI_VERSION >= 9.4) {
		return true;
	} else {
		echo "Need GLPI version >= 9.4";
	}
}

function plugin_ocean_check_config($verbose = false) {
	return true;
}
