<?

class FMin extends FColumn {
	static $prefix = "min_";
	static $all = "minimo";

	function FMin($field = "*", $alias = "") {
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