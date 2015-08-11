<?php

class FDistinct extends FColumn {
	static $prefix = "distinct_";
	static $all = "total";

	function FDistinct($field, $alias = "") {
		if((is_array($field) && count($field) > 1) || (is_string($field) && count(explode(",", $field)) > 1)) {
			var_dump($field);
			$this->agrErr("No se puede manejar listados de columnas");
		}
		else if (is_array($field))
			$field = $field[0];

		parent::FColumn($field, $alias);
	}
}

