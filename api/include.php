<?php

use KaceDirect\PDOWrapper;

spl_autoload_register(function ($class_name) {
	if (file_exists("../api/$class_name.php")) {
		/** @noinspection PhpIncludeInspection */
		include "../api/$class_name.php";
	} else {
		throw new Exception('No class named ' . $class_name);
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

$fullRouting = substr(str_replace($_SERVER['SCRIPT_NAME'], '', $_SERVER['REQUEST_URI']), 1);
$routing = preg_replace('/\/$/', '', $fullRouting);
$routing = str_replace('.', '', $fullRouting);
$routing = 'KaceDirect\\Routing\\' . str_replace('/', '\\', $routing);

PDOWrapper::setup();

