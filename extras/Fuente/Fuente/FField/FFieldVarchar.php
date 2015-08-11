<?php

class FFieldVarchar extends FField {
	private $length;

	function FFieldVarchar($name, $length = 255, $options = array(), $default = null) {
		if(!is_array($options))
			$options = array($options);

		if(intval($length) != 0)
			$this->length = $length;
		else
			$this->agrErr('FFieldVarchar: <b>' . $name. '(' . $length . ')</b>');

		parent::FField($name, $options, $default);
	}

	function length() {
		return $this->length;
	}
}
