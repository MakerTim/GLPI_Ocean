<?php

namespace Ocean;

abstract class RoutingBase {

	/**
	 * @param FastUser $user
	 * @return mixed
	 */
	public abstract function call($user);

}
