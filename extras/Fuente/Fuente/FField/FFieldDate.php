<?php

class FFieldDate extends FField {

	function FFieldDate($name, $options = array(), $default = '0000-00-00') {
		if(!is_array($options))
			$options = array($options);

		if(in_array(FField::AUTO_INCREMENT, $options))
			$this->agrErr("El modificador {FField::AUTO_INCREMENT} no es valido para este campo: {$name}");

		parent::FField($name, $options, $default);
	}

}
