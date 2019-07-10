<?php

namespace KaceDirect;

abstract class RoutingBase {
	public abstract function call($user);

	protected function getType() {
		if (!key_exists('HTTP_TYPE', $_SERVER)) {
			sendError('Missing type header', 400);
		}
		$requestedType = $_SERVER['HTTP_TYPE'];

		return $requestedType;
	}
}
