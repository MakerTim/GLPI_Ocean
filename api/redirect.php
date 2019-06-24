<?php

include('include.php');

if (!array_key_exists('redirect', $_GET)) {
	exit();
}

$redirect = $_GET['redirect'];
$requestedHeaders = [];
foreach (getallheaders() as $headerName => $headerValue) {
	$requestedHeaders[] = "$headerName: $headerValue";
}

$curl = curl_init();
curl_setopt_array($curl, array( //
	CURLOPT_URL => $redirect, //
	CURLOPT_HEADER => true, //
	CURLOPT_RETURNTRANSFER => true, //
	CURLOPT_ENCODING => "", //
	CURLOPT_MAXREDIRS => 10, //
	CURLOPT_TIMEOUT => 30, //
	CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_2_0, //
	CURLOPT_CUSTOMREQUEST => $_SERVER['REQUEST_METHOD'], //
	CURLOPT_POSTFIELDS => file_get_contents('php://input'), //
	CURLOPT_HTTPHEADER => $requestedHeaders, //
));

$response = curl_exec($curl);
$err = curl_error($curl);
curl_close($curl);

$headers = [];
$headerString = substr($response, 0, strpos($response, "\r\n\r\n"));
$response = substr($response, strpos($response, "\r\n\r\n") + 4);

$exposedHeaders = [];
foreach (preg_split("/((\r?\n)|(\r\n?))/", $headerString) as $line) {
	$parsedLine = explode(': ', $line);
	if (count($parsedLine) <= 1) {
		continue;
	}
	if (strpos($line, 'Transfer-Encoding') === 0) {
		continue;
	}
	header($line, false);
	$exposedHeaders[] = $parsedLine[0];
}

oceanSetupCORS();
header('Access-control-expose-headers: ' . join(', ', $exposedHeaders));

if ($err) {
	echo json_encode($err);
} else {
	echo json_encode(json_decode($response), JSON_PRETTY_PRINT);
}
