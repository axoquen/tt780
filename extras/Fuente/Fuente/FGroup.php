<?

class FGroup extends FBase {
	const UNION = 4;
	const INTERSECT = 5;
	const EXCEPT = 6;

	protected $alias;
	protected $connector;

	protected $ops = array();
	protected $fuentes = array();

	public $raw = false;

	static protected $psc = array('cur' => 0);

	function FGroup($connector, $alias = null) {
		if($alias == null)
			$alias = "temp_" . ++FGroup::$psc["cur"];

		$this->alias = $alias;
		$this->connector = $connector;

		if(!preg_match('/^\w+$/', $alias))
			$this->agrErr("El nombre para la relación no es valido: <b>\"{$alias}\"</b>");

		if(!is_object($connector) || !method_exists($connector, "executeQuery"))
			$this->agrErr("{$alias} : El conector proporcionado no es valido");

		$this->describer = $this->connector->getDescriber();
	}

	function getFuente($alias) {
		if(isset($this->fuentes[$alias]))
			return $this->fuentes[$alias];
	}
	
	function serial() {
	}
	
	function &getConnector() {
		return $this->connector;
	}

	function getAlias() {
		return $this->alias;
	}

	function setAlias($alias) {
		if(preg_match("/^\w+$/", $alias))
			$this->alias = $alias;
		else	
			$this->agrErr("No es un alias valido: \"$alias\" ");
	}

	function getFields($flag = null, $onlynames = true) {
		$res = array();

		foreach ($this->fuentes as $alias => $objFReference) {
			$aux = $objFReference->getFuente()->getFields($flag, false);

			foreach ($aux as $name => $objFField)
				if($onlynames) {
					if(!in_array($name, $res))
						$res[] = $name;
				}
				else {
					if(!isset($res[$name]))
						$res[$name] = $objFField;
				}
		}

		return $res;
	}
	
////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////

	function addStart($alias, $fuente, $where = null) {
		if($alias == null || !preg_match("/^\w+$/", $alias))
			$this->agrErr("El alias no es valido: <b>\"{$alias}\"</b>");

		if(!method_exists($fuente, "Fuente") && !method_exists($fuente, "FGroup"))
			$this->agrErr("Fuente no reconocida: <b>'{$fuente}'</b>");

		$this->fuentes = array();
		$this->fuente_fields = array();

		$this->fuentes[$alias] = $fuente;
		$fields = $fuente->getFields();
		foreach($fields as $n => $o) {
			$n = $alias . "." . $o;
			$this->fuente_fields[$n] = $o;
		}

		$where = $this->__verifyWhere($where, array_keys($this->fuentes), $this->fuente_fields);

		$this->ops[0] = array(
			"alias" => $alias,
			"fuente" => $fuente,
			"where" => $where
		);

		return $this;
	}

//////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////

	function addUnion($fuente, $where = null, $manifest = null) {
		if(!isset($this->ops[0]))
			$this->agrErr('No hay fuente inicial para la relaci&oacute;n');

		if(!method_exists($fuente, "Fuente") && !method_exists($fuente, "FGroup"))
			$this->agrErr("Fuente no reconocida: <b>\"{$fuente}\"</b>");

		if(!is_array($manifest) || count($manifest) > 0) {
			$aux_1 = $this->getFields();
			$aux_2 = $fuente->getFields();

			if(count($aux_1) > count($aux_2))
				$referencia = count($aux_1);
			else
				$referencia = count($aux_2);

			$manifest = array();
			foreach ($aux as $name)
				$manifest[$name] = $name;
		}

		$this->ops[] = array(
			'type' => FGroup::UNION,
			'fuente' => $fuente,
			'where' => $where,
			'manifest' => $manifest
		);

		return $this;
	}

////////////////////////////////////////////////////
// validaciones de estructuras...

	protected function __verifyRow($register, $fuente_name, $fuente_fields) {
		$new_register = null;
		
		// siempre debe especificar el nombre del campo
		if(is_array($register) && count($register) > 0) {
			foreach($register as $field_name => $field_value) {
				$field_name = $this->__verifyReferenceToField($field_name, $fuente_name, $fuente_fields);

				if(isset($fuente_fields[$field_name]))
					$new_register[$field_name] = $field_value;
				else
					$new_register["# " . $field_name . " #"] = $field_value;
			}
		}
		else
			$new_register["# error #"] = "# " . $register . " #";

		return $new_register;
	}

	// busca si el valor es una referencia a campo o es un valor
	protected function __verifyReferenceToField($name, $fuente_name = "", $fuente_fields = null) {
		if(preg_match("/^'[^']*'$/", $name))
			return $name;

		if(preg_match("/^(\w+)\.(\w+)$/", $name, $aux) && in_array($aux[1], $fuente_name) && isset($fuente_fields[$aux[1] . '.' . $aux[2]]))
			return $name;
		else if(preg_match("/^(\w+)$/", $name)) {
			foreach ($fuente_name as $fn)
				if(isset($fuente_fields[$fn . "." . $name]))
					return $fn . "." . $name;
		}
		else if($name == "*")
			return $name;

		return "'" .$name . "'";
	}

}

?>