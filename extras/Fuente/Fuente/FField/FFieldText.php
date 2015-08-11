<?php

class FFieldText extends FField {

	function FFieldText($name, $options = array(), $default = '') {
		if(!is_array($options))
			$options = array($options);

		parent::FField($name, $options, $default);
	}
}
