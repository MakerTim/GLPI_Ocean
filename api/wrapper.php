<?php

function setupCORS() {
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

setupCORS();

$curl = curl_init();

$headers = [];
foreach (getallheaders() as $headerKey => $headerValue) {
	if (in_array($headerKey, ['Referer', 'Host', 'Origin', 'Sec-Fetch-Mode', 'Sec-Fetch-Site'])) {
		if ($headerKey == 'Host') {
			$headerValue = 'localhost';
		} else {
			continue;
		}
	}
	$headers[] = "$headerKey: $headerValue";
}

curl_setopt_array($curl, [ //
	CURLOPT_URL => str_replace(' ', '%20', $_GET['url']), //
	CURLOPT_RETURNTRANSFER => true, //
	CURLOPT_ENCODING => "", //
	CURLOPT_MAXREDIRS => 10, //
	CURLOPT_TIMEOUT => 3000, //
	CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1, //
	CURLOPT_HEADER => 1, //
	CURLOPT_CUSTOMREQUEST => $_SERVER['REQUEST_METHOD'], //
	CURLOPT_POSTFIELDS => file_get_contents('php://input'), //
	CURLOPT_HTTPHEADER => $headers, //
]); //

$response = curl_exec($curl);
$header_size = curl_getinfo($curl, CURLINFO_HEADER_SIZE);
$response_headers = substr($response, 0, $header_size);
$response = substr($response, $header_size);
if (empty($response) && $errno = curl_errno($curl)) {
	$response = json_encode(curl_strerror($errno));
}

$i = 0;

$response_headerNames = [];
foreach (preg_split("/((\r?\n)|(\r\n?))/", $response_headers) as $header) {
	if (count(explode(':', $header, 2)) < 2) {
		continue;
	}
	if (in_array($header, ['Transfer-Encoding: chunked'])) {
		continue;
	}
	header($header, false);
	$response_headerNames[] = explode(':', $header, 2)[0];
}
header('Access-Control-Expose-Headers: ' . implode(',', $response_headerNames));

echo ($response) . PHP_EOL;

curl_close($curl);

