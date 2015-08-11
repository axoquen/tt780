<?

class FCount extends FColumn {
	static $prefix = "count_";
	static $all = "numero";

	function FCount($field, $alias = "") {
		parent::FColumn($field, $alias);
	}
}

?>