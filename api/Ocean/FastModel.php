<?php

namespace Ocean;

class FastModel {

	public function __construct(array $DBEntry) {
		foreach ($DBEntry as $key => $value) {
			$this->$key = $value;
		}
	}
}
