<?php

class FFieldInteger extends FField {
	function FFieldInteger($name, $options = array(), $default = '0') {
		if(!is_array($options))
			$options = array($options);

		parent::FField($name, $options, $default);
	}
}
