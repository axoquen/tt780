<?

class FSubstr extends FColumn {
	static $prefix = "concat_";

	function FSubstr($field, $alias = "") {
		if(!is_array($field)) {
			var_dump($field);
			$this->agrErr("Debe ser lista de columnas");
		}

		parent::FColumn($field, $alias);
	}
}

