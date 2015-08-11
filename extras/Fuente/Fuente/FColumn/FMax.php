<?

class FMax extends FColumn {
	static $prefix = "max_";
	static $all = "maximo";

	function FMax($field = "*", $alias = "") {
		if((is_array($field) && count($field) > 1) || (is_string($field) && count(explode(",", $field)) > 1)) {
			var_dump($field);
			$this->agrErr("No se puede manejar listados de columnas");
		}
		else if (is_array($field))
			$field = $field[0];

		parent::FColumn($field, $alias);
	}
}

?>