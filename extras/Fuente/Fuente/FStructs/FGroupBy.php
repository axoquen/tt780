<?php

class FGroupBy extends PuntoCritico {
	private $field;

	function FGroupBy($field) {
		if(!is_string($field))
			$field = "#{$field}#";

		$this->field = $field;
	}

	function getField() {
		return $this->field;
	}

	function setField($field) {
		$this->field = $field;
	}
}
