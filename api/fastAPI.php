<?php

use KaceDirect\APIValidation;
use KaceDirect\RoutingBase;

include('include.php');

$user = null;
APIValidation::check();

ini_set('error_reporting', E_ALL ^ E_WARNING);
if (class_exists($routing, true)) {
	$route = new $routing();
	if ($route instanceof RoutingBase) {
		echo json_encode($route->call($user), JSON_PRETTY_PRINT);
		cleanPDOState();
	} else {
		sendError("Route $route is not a real route!");
	}
} else {
	sendError("Route $fullRouting not found in $routing", 404);
}
