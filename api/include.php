<?php

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
