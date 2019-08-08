<?php

use Ocean\PDOWrapper;

spl_autoload_register(function ($class_name) {
	$class_name = str_replace('\\', '/', $class_name);
	if (file_exists("../api/$class_name.php")) {
		/** @noinspection PhpIncludeInspection */
		include "../api/$class_name.php";
	} else if (file_exists(GLPI_ROOT . "inc/$class_name.php")) {
		/** @noinspection PhpIncludeInspection */
		include GLPI_ROOT . "inc/$class_name.php";
	} else {
		include GLPI_ROOT . 'vendor/autoload.php';
	}
});

function oceanSetupCORS() {
	$origin = array_key_exists('HTTP_ORIGIN', $_SERVER) ? $_SERVER['HTTP_ORIGIN'] : '*';
	if (!$origin) {
		$origin = '*';
	}

	$headerAllow = array_key_exists('HTTP_ACCESS_CONTROL_REQUEST_HEADERS', $_SERVER) ? $_SERVER['HTTP_ACCESS_CONTROL_REQUEST_HEADERS'] : '*';
	if (!$headerAllow) {
		$headerAllow = '*';
	}

	$headers = [ //
		'Access-Control-Allow-Origin' => $origin, //
		'Access-Control-Allow-Methods' => 'GET, POST, PUT, DELETE, OPTIONS', //
		'Access-Control-Allow-Credentials' => 'true', //
		'Access-Control-Max-Age' => '3600', //
		'Access-Control-Allow-Headers' => $headerAllow //
	];

	foreach ($headers as $header => $value) {
		header("$header: $value");
	}

	if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
		exit();
	}
}

function nameOfType($dictionary, $lookup) {
	foreach ($dictionary as $key => $value) {
		if ($value == $lookup) {
			return $key;
		}
	}
	return $lookup; // failed lookup
}

function valueOfType($dictionary, $lookup) {
	foreach ($dictionary as $key => $value) {
		if ($key == $lookup) {
			return $value;
		}
	}
	return $lookup; // failed lookup
}

function getBaseGLPIOceanURL() {
	$url = $_SERVER['REQUEST_SCHEME'] . '://' . $_SERVER['SERVER_NAME'] . '/plugins/ocean/';
	if (file_exists(__DIR__ . '/index.html')) {
	} else if (file_exists(__DIR__ . '/dist/client/index.html')) {
		$url .= 'dist/client/';
	} else {
		$url = $_SERVER['REQUEST_SCHEME'] . '://' . $_SERVER['SERVER_NAME'] . ':4200/';
	}

	return $url;
}

function cleanPDOState() {
	/** @var PDO $DB */ global $DB;

	$DB->query('SELECT 1;')->fetchAll();
}

function sendError($message, $code = 500) {
	http_response_code($code);
	echo json_encode($message);
	cleanPDOState();
	exit();
}

try {
	oceanSetupCORS();
	PDOWrapper::setup();
} catch (\Exception $exception) {
	sendError($exception->getMessage());
}

$fullRouting = substr(str_replace($_SERVER['SCRIPT_NAME'], '', $_SERVER['REQUEST_URI']), 1);
$routing = preg_replace('/\/$/', '', $fullRouting);
$routing = str_replace('.', '', $fullRouting);
$routing = 'Ocean\\Routing\\' . str_replace('/', '\\', $routing);
