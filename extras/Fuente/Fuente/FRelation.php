<?php

class FRelation extends FBase {
	const BASE = 0;
	const JOIN = 1;
	const LEFT_JOIN = 2;
	const RIGHT_JOIN = 3;

	protected $alias;
	protected $ops = array();
	protected $fuentes = array();

	static protected $psc = array('cur' => 0);

	function FRelation($objConector, $alias = null) {
		if($alias == null)
			$alias = "temp_" . ++FRelation::$psc["cur"];

		$this->alias = $alias;
		$this->connector = $objConector;

		if(!preg_match('/^[\w_]+$/', $alias))
			$this->agrErr("El nombre para la relación no es valido: <b>\"{$alias}\"</b>");

		if(! $objConector instanceof Connector)
			$this->agrErr("El segundo argumento debe ser un conector valido y usted dio: \"" . print_r($objConector) . "\"");

		$this->describer = $this->connector->getDescriber();
	}

////////////////////////////////////////////////////////////////////////////////

	function serial() { }

	function unserial() { }

////////////////////////////////////////////////////////////////////////////////

	function getFuente($alias) {
		if(isset($this->fuentes[$alias]))
			return $this->fuentes[$alias];
	}

	function getAlias() {
		return $this->alias;
	}

	function &getConnector() {
		return $this->connector;
	}

	function getFields($flag = null, $onlynames = true) {
		if($flag != null)
			$pre_res = $this->__searchFlag($flag);
		else {
			$pre_res = array();
			foreach($this->fuente_fields as $k => $v) {
				preg_match('/^(\w+)\.(\w+)$/', $v, $aux);
				$fields = $this->fuentes[$aux[1]]->getFields(null, false);
				if(isset($fields[$aux[2]]))
					$pre_res = $fields[$aux[2]];
			}
		}

		if(is_array($pre_res)) {
			if($onlynames) {
				$res = array();

				foreach($pre_res as $objField)
					$res[] = $objField->getName();

				return $res;
			}

			return $pre_res;
		}

		if($onlynames)
			return $pre_res->getName();

		return $pre_res;
	}

////////////////////////////////////////////////////////////////////////////////

	function addStart($alias, $fuente, $where = null) {
		$this->fuentes = array();
		$this->fuente_fields = array();

		$fuente = $this->__addFuente($alias, $fuente);

		$this->ops = array(
			0 => array(
				'alias' => $alias,
				'fuente' => $fuente,
			)
		);

		$this->ops[0]['where'] = $this->__verifyWhere($where, $this->__getTotalFields(), array_keys($this->fuentes));

		return $this;
	}

	function addJoin($alias, $fuente, $where = null) {
		if(!isset($this->ops[0]))
			$this->agrErr('No hay fuente inicial para la relaci&oacute;n');

		$fuente = $this->__addFuente($alias, $fuente);

		$c = count($this->ops);

		$this->ops[$c] = array(
			'type' => FRelation::JOIN,
			'alias' => $alias,
			'fuente' => $fuente
		);
		
		$this->ops[$c]['where'] = $this->__verifyWhere($where, $this->__getTotalFields(), array_keys($this->fuentes));

		return $this;
	}

	function addLeftJoin($alias, $fuente, $where = null) {
		if(!isset($this->ops[0]))
			$this->agrErr('No hay fuente inicial para la relaci&oacute;n');

		$fuente = $this->__addFuente($alias, $fuente);

		$c = count($this->ops);

		$this->ops[$c] = array(
			'type' => FRelation::LEFT_JOIN,
			'alias' => $alias,
			'fuente' => $fuente
		);

		$this->ops[$c]['where'] = $this->__verifyWhere($where, $this->__getTotalFields(), array_keys($this->fuentes));

		return $this;
	}
	
	function addRightJoin($alias, $fuente, $where = null) {
		if(!isset($this->ops[0]))
			$this->agrErr('No hay fuente inicial para la relaci&oacute;n');

		if($alias == null || !preg_match("/^\w+$/", $alias))
			$this->agrErr("El alias no es valido: <b>\"{$alias}\"</b>");

		if(!method_exists($fuente, "Fuente") && !method_exists($fuente, "FRelation"))
			$this->agrErr("Fuente no reconocida: <b>\"{$fuente}\"</b>");

		$fuente = $this->__addFuente($alias, $fuente);


		$c = count($this->ops);

		$this->ops[$c] = array(
			'type' => FRelation::RIGHT_JOIN,
			'alias' => $alias,
			'fuente' => $fuente
		);

		$this->ops[$c]['where'] = $this->__verifyWhere($where, $this->__getTotalFields(), array_keys($this->fuentes));

		return $this;
	}

//////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////

	function select($fields = null, $where = null, $orderby = null, $groupby = null, $start = -1, $limit = -1, $query = false) {
		$fuente_names = array_keys($this->fuentes);

		$fields = $this->__verifyColumns($fields, true, $this->fuente_fields, $fuente_names);
		$where = $this->__verifyWhere($where, $this->fuente_fields, $fuente_names);
		$orderby = $this->__verifyOrderBy($orderby, $this->fuente_fields, $fuente_names);
		$groupby = $this->__verifyGroupBy($groupby, $this->fuente_fields, $fuente_names);

		$res = $this->describer->relations_select($this->ops, $fields, $where, $orderby, $groupby, $start, $limit, $this->fuente_fields);
		if($query)
			return $res;

		return $this->connector->executeQuery($res, true, $this->raw);
	}

////////////////////////////////////////////////////////////////////////////////

	protected function __searchFlag($flag) {
		$res = array();

		foreach($this->fuente_fields as $k => $v) {
			preg_match('/^(\w+)\.(\w+)$/', $v, $aux);
			$fields = $this->fuentes[$aux[1]]->getFields($flag, false);
			if(isset($fields[$aux[2]]))
				$res = $fields[$aux[2]];
		}

		return $res;
	}

////////////////////////////////////////////////////////////////////////////////
// validaciones de estructuras...

	protected function __verifyReferenceToField($name, $fuente_fields = null, $fuente_name = null) {
		switch (true) {
			case preg_match("/^'[^']*'$/", $name) :

			case $name == '*' || $name === "null":

			case preg_match("/^(\w+)\.(\w+)$/", $name, $aux) && in_array($aux[1], $fuente_name) && isset($fuente_fields["{$aux[1]}.{$aux[2]}"]) :
				return $name;

			case preg_match("/^(\w+)$/", $name) :

				if(isset($fuente_fields[$name]))
					return $fuente_fields[$name];

				foreach ($fuente_name as $fn)
					if(isset($fuente_fields["{$fn}.{$name}"]))
						return $fuente_fields["{$fn}.{$name}"];
		}

		return "'" .$name . "'";
	}

    protected function __addFuente($alias_fuente, $referencia_fuente) {
		if(!is_string($alias_fuente)|| !preg_match("/^\w+$/", $alias_fuente))
			$this->agrErr("El alias no es valido: <b>\"{$alias_fuente}\"</b>");

		// $this->fuentes: array FBase
		// $this->fuente_fields: array 'alias' | 'fuente_alias.campo' => 'fuente.campo'

		if(is_array($referencia_fuente) && isset($referencia_fuente['fuente']) && $referencia_fuente['fuente'] instanceof FBase) {
			$fuente_obj = $referencia_fuente['fuente'];

			$fuente_name = $fuente_obj->getName();
			$fields = $fuente_obj->getFields();
			
			if(isset($referencia_fuente['include']) && !is_array($referencia_fuente['include']))
				$referencia_fuente['include'] = array($referencia_fuente['include']);

			if(isset($referencia_fuente['include']) && count($referencia_fuente['include']) > 0) {
				// alias => real
				foreach($referencia_fuente['include'] as $a => $r) {
					if(is_integer($a)) {
						$a = $r;

						if(preg_match('/^\w+\.(\w+)$/', $r, $aux))
							$r = $aux[1];

						if(preg_match('/^([^\s]+)\sas\s([^\s]+)$/', $r, $aux)) {
							$r = $aux[1];
							$a = $aux[2];
						}
					}

					if(!in_array($r, $fields))
						$this->agrErr("El campo '{$r}' no existe en la fuente <b>{$fuente_name}</b>");
// ???? pero... funciona
					$this->fuente_fields[$a] = "{$alias_fuente}.{$r}";
				}
			}
			else
				foreach($fields as $f)
					$this->fuente_fields[$f] =  "{$alias_fuente}.{$f}";

			if(isset($referencia_fuente['exclude']) && is_array($referencia_fuente['exclude']) && count($referencia_fuente['exclude']) > 0) {
				// alias => real
				foreach($referencia_fuente['exclude'] as $a => $r) {
					if(is_integer($a)) {
						$a = $r;
						$r = preg_match('/^\w+\.(\w+)$/', $r, $aux) ? $aux[1] : $r;
					}

					if(!in_array($r, $fields))
						$this->agrErr("El campo '{$r}' no existe en la fuente <b>{$fuente_name}</b>");
// ???? pero... funciona
					unset($this->fuente_fields[$a]);
				}
			}

		}
		else if($referencia_fuente instanceof FBase) {
			$fuente_obj = $referencia_fuente;
			$fuente_name  = $fuente_obj->getName();

			$fields = $referencia_fuente->getFields();
			foreach($fields as $f)
				$this->fuente_fields[$f] = "{$fuente_name}.{$f}";
		}
		else
			$this->agrErr("Fuente no reconocida: <b>'{$referencia_fuente}'</b>");

		$this->fuentes[$alias_fuente] = $fuente_obj;

		return $fuente_obj;
	}

    protected function __getTotalFields() {
		$res = array();
		foreach($this->ops as $ops) {
			$alias = $ops['alias'];
			$fields = $ops['fuente']->getFields();

			foreach($fields as $field)
				$res["{$alias}.{$field}"] = "{$alias}.{$field}";
		}

		return $res;
	}
}
