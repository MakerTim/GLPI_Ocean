<?php

use Ocean\APIValidation;
use Ocean\SessionManager;

define('GLPI_ROOT', '../../../');
include('include.php');

APIValidation::check();
$user = null;
if ($routing !== 'Ocean\\Routing\\Login') {
	$user = SessionManager::checkSession();
}

ini_set('error_reporting', E_ALL ^ E_WARNING);

if (class_exists($routing, true)) {
	$route = new $routing();
	if ($route instanceof \Ocean\RoutingBase) {
		echo json_encode($route->call($user), JSON_PRETTY_PRINT);
		cleanPDOState();
	} else {
		sendError("Route $route is not a real route!");
	}
} else {
	sendError("Route $fullRouting not found in $routing", 404);
}
