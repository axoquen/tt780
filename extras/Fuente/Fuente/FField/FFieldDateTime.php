<?php

class FFieldDateTime extends FField {

	function FFieldDateTime($name, $options = array(), $default = '0000-00-00 00:00:00') {
		if(!preg_match('/^\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2}$/', $default))
			$default = '0000-00-00 00:00:00';

		if(!is_array($options))
			$options = array($options);

		if(in_array(FField::AUTO_INCREMENT, $options))
			$this->agrErr("el modificador {FField::AUTO_INCREMENT} no es valido para este campo: {$name}");

		parent::FField($name, $options, $default);
	}

}
