<?php

class FFieldFloat extends FField {
  
	function FFieldFloat($name, $options = array(), $default = null) {
		if(!is_array($options))
			$options = array($options);

		parent::FField($name, $options, $default);
	}
}
