<?php

class FCondition extends FOperation {
	private $field;
	private $operation;
	private $value;


	static $operators = array(
		"=", "!=", "like", "not like", "in", "not in", "is", "is not", "<", ">", "<=", ">=", "=<", "=>"
	);

	function FCondition($field, $operation, $value) {
		if(!is_array($field))
			$field = "$field";

		$this->field = $field;
		$this->operation = "$operation";
		$this->value = $value;

		$this->cur = 0;

		if(!in_array($this->operation, FCondition::$operators))
			$this->agrErr("La operacion no es de tipo valido: \"{$field} '{$operation}' {$value}\"");
	}

///////////////////////////////////////////////////////////

	function isEmpty() {
		return false;
	}

	function next() {
		if($this->cur++ == 0)
			return $this;

		return null;
	}

	function reset() {
		$this->cur = 0;
	}

///////////////////////////////////////////////////////////

	function getLeft() {
		return $this->field;
	}
	
	function getOperation() {
		return $this->operation;
	}

	function getRight() {
		return $this->value;
	}

///////////////////////////////////////////////////////////

	function setLeft($value) {
		if(!is_array($value))
			$value = "$value";

		$this->field = $value;
	}

	function setRight($value) {
		if(!is_array($value))
			$value = "$value";

		$this->value = $value;
	}	
}
