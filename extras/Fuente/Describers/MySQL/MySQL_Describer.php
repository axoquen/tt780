<?php

//////////////////////////////////////////////////////////////////////////////////////////////////////
// MySQL 5.x                                                                                        //
//////////////////////////////////////////////////////////////////////////////////////////////////////

class MySQL_Describer extends FDescriber {

	private static $fields_relations = array(
		'ffielddate' => 'DATE',
		'ffielddatetime' => 'DATETIME',
		'ffieldtime' => 'TIME',
		'ffieldinteger' => 'INTEGER',
		'ffieldfloat' => 'FLOAT',
		'ffieldtext' => 'TEXT',
		'ffieldvarchar' => 'VARCHAR',
	);

	private static $fields_flags_relations = array( 
		FField::NOT_NULL => 'NOT NULL',
		FField::AUTO_INCREMENT => 'AUTO_INCREMENT',
 		FField::PRIMARY_KEY => 'PRIMARY KEY'
	);

	private static $order_relations = array(
		FOrderBy::DESC => 'DESC',
		FOrderBy::ASC => 'ASC'
	);
	
	private static $operations_relations = array(
		'foperation' => 'AND',
		'fcand' => 'AND',
		'fcor' => 'OR'
	);

////////////////////////////////////////////////////////////////////////////////

	protected function __apply($result, &$p_columns, &$p_where, &$p_orderby, &$p_groupby) {

		foreach($result as $parameter => $value)
			if(${$parameter} != null) {
				if(!is_array(${$parameter}))
					${$parameter} = array(${$parameter}, $value);
				else
					${$parameter}[] = $value;
			}
			else
				${$parameter} = $value;

	}

////////////////////////////////////////////////////////////////////////////////

	function serial(&$fuente, $apply = false) {

		if($apply) {
			$name = $fuente->getName();
			$fields = $fuente->getFields(null, false);
			$connector = $fuente->getConnector();

			$res = $connector->executeQuery("desc {$name}", false);

			// crea la tabla en caso de no existir en la base
			if(!$res) {

				$sinbanderas = array();
				$crea = '';
				$post = array();

				$arr = array();
				foreach($fields as $objfield) {
					if($objfield->isFlag(FField::PRIMARY_KEY))
						$arr[] = $objfield;
				}

				if(count($arr) > 1) {
					$sinbanderas[] = FField::PRIMARY_KEY;

					$pk = array();
					foreach ($arr as $objfield)
						$pk[] = $objfield->getName();

					$post[] = "ALTER TABLE " . $name . " ADD PRIMARY KEY (" . implode(", ", $pk) . ") ";
				}

				$arr = array();
				foreach($fields as $objfield) {
					if($objfield->isFlag(FField::AUTO_INCREMENT))
						$arr[] = $objfield;
				}

				if(count($arr) > 1 || (isset($arr[0]) && !$arr[0]->isFlag(FField::PRIMARY_KEY)))
					$sinbanderas[] = FField::AUTO_INCREMENT;

				$aimprimir = array();

				foreach($fields as $objfield)
					$aimprimir[] = $this->__impObjFField($objfield, $sinbanderas);

				$crea = "CREATE TABLE {$name} (" . implode(', ', $aimprimir) . ') ';

				$connector->executeQuery($crea);
				if(count($post) > 0) {
					foreach ($post as $query)
						$connector->executeQuery($query);
				}
			}
		}

		return true;
	}

	function unserial(&$fuente) {
		$name = $fuente->getName();
		$connector = $fuente->getConnector();

		$res = false;

		if(($desc = $connector->executeQuery("desc {$name}", false)) != null) {
			$columnas = array(
				'Field',
				'Type',
				'Null',
				'Key',
				'Default',
				'Extra'
			);

			$fields = array();

			foreach($desc as $field) {
				$tipo = $this->__fieldsToFFields($field['Type']);
				$obj = null;

				$options = array();
				if(strtolower($field['Null']) == 'no')
					$options[] = FField::NOT_NULL;

				if(strtolower($field['Key']) == 'pri')
					$options[] = FField::PRIMARY_KEY;

				if(strtolower($field['Extra']) == 'auto_increment')
					$options[] = FField::AUTO_INCREMENT;

				switch($tipo[0]) {
					case 'ffieldvarchar':
						$obj = new FFieldVarchar($field['Field'], $tipo[1], $options);
						break;

					case 'ffieldinteger':
						if(strtolower($field['Extra']) == 'auto_increment')
							$options[] = FField::AUTO_INCREMENT;
						$obj = new FFieldInteger($field['Field'], $options);
						break;

					case 'ffieldtext':
						$obj = new FFieldText($field['Field'], $options);
						break;

					case 'ffielddate':
						$obj = new FFieldDate($field['Field'], $options);
						break;
						
					case 'ffielddatetime':
						$obj = new FFieldDateTime($field['Field'], $options);
						break;

					case 'ffieldtime':
						$obj = new FFieldTime($field['Field'], $options);
						break;

					case 'ffieldfloat':
						$obj = new FFieldFloat($field['Field'], $options);
						break;
				}

				$fields[$field['Field']] = $obj;
			}

			$res = $fields;
		}

		return $res;
	}

/////////////////////////////////////////////////////////////////////////////////////////////////////

	function select(&$fuente, $columns, $where, $orderby, $groupby, $start, $limit) {
		$name = $fuente->getName();
		$fields = $fuente->getFields();
		
		$toapply = array(
			'columns' => '__impObjFColumn',
			'where' => '__impObjFCondition',
			'orderby' => '__impObjFOrderBy',
			'groupby' => '__impObjFGroupBy',
		);
		
		$str_columns = $str_where = $str_orderby = $str_groupby = '';
		
		foreach($toapply as $variable => $validator) {
			$aux = $this->$validator(${$variable}, $fields);
			$this->__apply($aux, $str_columns, $str_where, $str_orderby, $str_groupby);
		}

		if(is_array($str_columns))
			$str_columns = implode(', ', $str_columns);

		if($str_where)
			$str_where = " WHERE " . (is_array($str_where) ? '(' . implode(') and (', $str_where) . ')' : $str_where);

		if($str_orderby)
			$str_orderby = " ORDER BY " . (is_array($str_orderby) ? implode (', ', $str_orderby) : $str_orderby);

		if($str_groupby)
			$str_groupby = " GROUP BY " . (is_array($str_groupby) ? implode (', ', $str_groupby) : $str_groupby);

		$extra = '';
		if($start != -1 || $limit != -1) {
			$extra = ' LIMIT ' . ($start != -1 ?
				$start . ($limit != -1 ?
					    ", {$limit}" :
					    ''
					 ):
				$limit);
		}

		return "SELECT {$str_columns} FROM {$name}{$str_where}{$str_groupby}{$str_orderby}{$extra} ";
	}

	// ... agrega el soporte de varios campos autoincrement
	function insert(&$fuente, $rows) {
		$name = $fuente->getName();
		$fields = $fuente->getFields(null, false);

		$heads = array();
		$str_rows = array();

		if(count($rows) > 0) {
			$heads = array_keys($rows[0]);

			// obtiene las agrupaciones
			$flags = array(
				FField::PRIMARY_KEY,
				FField::NOT_NULL,
				FField::AUTO_INCREMENT
			);

			$psc = array();
			foreach ($fields as $field_name => $objField)
				foreach ($flags as $flag) {
					if(!isset($psc[$flag]))
						$psc[$flag] = array();

					if($objField->isFlag($flag))
						$psc[$flag][$field_name] = $objField;
				}

			$toeval = '';
			if(count($psc[FField::AUTO_INCREMENT]) > 0)
				foreach ($psc[FField::AUTO_INCREMENT] as $field_name => $objField)
					if(!in_array($field_name, $heads))
						$heads[] = $field_name;


			foreach($rows as $row) {
				$str_fields = array();

				foreach($heads as $field_name) {
					$field_value = isset($row[$field_name]) ? $row[$field_name] : '';

					// revisa cada fila y campo para modificar el valor si es necesario antes de enviarlo a MySQL
					switch(true) {
						case $fields[$field_name]->isFlag(FField::AUTO_INCREMENT) :
							// si no es un valor valido
							if((!array_key_exists($field_name, $row) || !is_numeric($field_value)) || intval($field_value) == 0)

								// AUTO_INCREMENT MySQL no soporta mas de un campo autoincrementables o que no sea clave primaria
								if(count($psc[FField::AUTO_INCREMENT]) > 1 || !$fields[$field_name]->isFlag(FField::PRIMARY_KEY)) {

									if(!isset($psc["{$field_name}_auto_increment"])) {
										$psc["{$field_name}_auto_increment"] = $fuente->getConnector()->executeQuery("SELECT max({$field_name}) as m from {$name}", false);
										if($psc["{$field_name}_auto_increment"])
											$psc["{$field_name}_auto_increment"] = intval($psc["{$field_name}_auto_increment"][0]['m']);
										else
											$psc["{$field_name}_auto_increment"] = 0;
									}
									
									$psc["{$field_name}_auto_increment"]++;

									$str_fields[] = "'" . intval($psc["{$field_name}_auto_increment"]) . "'";

									break;
								}
						default:
							$str_fields[] = $this->__impObjFieldValue($field_name, $field_value, true);
					}
				}

				$str_rows[] = "(" . implode(", ", $str_fields) . ")";
			}
		}

		return "INSERT INTO {$name} (" . implode(", ", $heads) . ") VALUES " . implode (", ", $str_rows) . " ";
	}

	function update(&$fuente, $arrValues, $where) {
		$name = $fuente->getName();
		$fields = $fuente->getFields();

		//AUTO_INCREMENT
		$psc_ai = $fuente->getFields(FField::AUTO_INCREMENT, false);
		if(is_array($psc_ai) && (count($psc_ai) > 1 || !$psc_ai[0]->isFlag(FField::PRIMARY_KEY)))
			foreach ($psc_ai as $objField) {
				$field_name = $objField->getName();

				if(!array_key_exists($field_name, $arrValues) || intval($arrValues[$field_name]) != 0)
					continue;

				// .. si es primary key se deja que mysql lo haga
				if($objField->isFlag(FField::PRIMARY_KEY))
					continue;

				$aux = $fuente->getConnector()->executeQuery("SELECT MAX({$field_name}) AS m FROM {$name}", false);

				$arrValues[$field_name] = $aux ? intval($aux[0]['m']) : 0;

				$arrValues[$field_name]++;
			}

		$sfields = array();
		foreach($arrValues as $cname => $cvalue)
				$sfields[] = $this->__impObjFieldValue($cname, $cvalue);

		$where = $this->__impObjFCondition($where, $fields);
		$where = $where['p_where'];
		if($where != "")
			$where = " WHERE {$where}";

		return "UPDATE {$name} SET " . implode(",", $sfields) . $where . " ";
	}

	function delete(&$fuente, $where) {
		$name = $fuente->getName();
		$fields = $fuente->getFields();

		$where = $this->__impObjFCondition($where, $fields);
		$where = $where['p_where'];
		if($where != null)
			$where = " WHERE {$where}";

		return "DELETE FROM {$name}{$where}";
	}

////////////////////////////////////////////////////////////////////////////////////////

	function relations_select($ops, $columns, $where, $orderby, $groupby, $start, $limit, &$fields) {
		$toapply = array(
			'columns' => '__impObjFColumn',
			'where' => '__impObjFCondition',
			'orderby' => '__impObjFOrderBy',
			'groupby' => '__impObjFGroupBy',
		);

		$str_columns = $str_where = $str_orderby = $str_groupby = '';

		foreach($toapply as $variable => $validator) {
			$aux = $this->$validator(${$variable}, $fields);
			$this->__apply($aux, $str_columns, $str_where, $str_orderby, $str_groupby);
		}


		$fuente_cursor = $ops[0]['fuente']->getName();
		if($ops[0]['alias'] != $fuente_cursor)
			$fuente_cursor .= " AS {$ops[0]['alias']}";

		if(is_array($str_columns))
			$str_columns = implode(', ', $str_columns);

		if($str_where)
			$str_where = " WHERE " . (is_array($str_where) ? '(' . implode(') and (', $str_where) . ')' : $str_where);

		if($str_orderby)
			$str_orderby = " ORDER BY " . (is_array($str_orderby) ? implode (', ', $str_orderby) : $str_orderby);

		if($str_groupby)
			$str_groupby = " GROUP BY " . (is_array($str_groupby) ? implode (', ', $str_groupby) : $str_groupby);

		$extra = '';
		if($start != -1 || $limit != -1) {
			$extra = ' LIMIT ' . ($start != -1 ?
				$start . ($limit != -1 ?
					    ", {$limit}" :
					    ''
					 ):
				$limit);
		}

		for($i = 1; $i < count($ops); $i++) {
			$fuente = $ops[$i]['fuente']->getName();

			$stralias = '';
			if($ops[$i]['alias'] != $fuente)
				$stralias = " AS {$ops[$i]['alias']}";

			if($ops[$i]['where'] != null) {
				$condition = $this->__impObjFCondition($ops[$i]['where'], $fields);
				$condition = ' ON ' . $condition['p_where'];
			}

			switch($ops[$i]['type']) {
				case FRelation::JOIN:
					$fuente_cursor .= ' JOIN ' . $fuente . $stralias . $condition;

					break;

				case FRelation::LEFT_JOIN:
					$fuente_cursor .= ' LEFT JOIN ' . $fuente . $stralias . $condition;

					break;

				case FRelation::RIGHT_JOIN:
					$fuente_cursor .= ' RIGHT JOIN ' . $fuente . $stralias . $condition;

					break;
			}
		}

		return "SELECT " . $str_columns . " FROM ". $fuente_cursor . $str_where . $str_groupby . $str_orderby . $extra;
	}

/////////////////////////////////////////////////////////////////////////////////////////

	private function __fieldsToFFields($tipo) {
		$res = 'text';

		$long = 0;

		$tipo = trim(strtolower($tipo));
		if(preg_match("/(\w+)\((\d+)\)/", $tipo, $aux)) {
			$tipo = $aux[1];
			$long = intval($aux[2]);
		}

		$equivalencia = array(
			'ffieldvarchar' => array(
				'varchar',
			),
			'ffieldfloat' => array(
				'float',
				'double',
			),
			'ffieldinteger' => array(
				'smallint',
				'int',
				'integer'
			),
			'ffieldtext' => array(
				'text'
			),
			'ffielddate' => array(
				'date',
			),
			'ffielddatetime' => array(
				'datetime',
			),
			'ffieldtime' => array(
				'time',
			)
		);

		foreach($equivalencia as $equiv => $tipos) {
			if(in_array($tipo, $tipos)) {
				$res = $equiv;
				break;
			}
		}
		
		return array($res, $long);
	}

	
	private function __impObjFieldValue($name, $value, $solovalor = false) {
		$value = addslashes(str_replace("'", '"', $value));

		if(!$solovalor)
			return "{$name} = '{$value}'";

		if($value == 'null')
			return 'null';

		return "'$value'";
	}
	
	protected function __impObjFField($objFField, $sinbanderas = array()) {
		$res = $objFField->getName() . " ";

		$nameclase = strtolower(get_class($objFField));
		if(isset(MySQL_Describer::$fields_relations[$nameclase])) {
			if(is_array(MySQL_Describer::$fields_relations[$nameclase]))
				$res .= MySQL_Describer::$fields_relations[$nameclase][$objFField->getFormat()];
			else
				$res .= MySQL_Describer::$fields_relations[$nameclase];
		}

		if(method_exists($objFField, "length"))
			$res .= "(" . $objFField->length() . ")";

		$opciones = $objFField->getOptions(); 
		foreach($opciones as $bandera)
			if(isset(MySQL_Describer::$fields_flags_relations[$bandera]) && !in_array($bandera, $sinbanderas))
				$res .= " " . MySQL_Describer::$fields_flags_relations[$bandera];

		return $res;
	}

	private function __impObjFColumn($columns, $fields, $set_alias = true) {
		$res = array();
		$extra = array();

		if(is_array($columns)) {
			foreach($columns as $name) {
				$aux = $this->__impObjFColumn($name, $fields, $set_alias);
				$res[] = $aux['p_columns'];
			}
		}
		else if($columns instanceof FColumn) {
			$alias = $columns->getAlias();

			if($alias == "" && $set_alias && method_exists($columns, "ffunction"))
				eval('$alias = " AS " . ' . get_class($columns) . '::$prefix; ');
			else if ($set_alias && $alias != "" && $columns->getReference() != $alias)
				$alias = " AS {$alias}";
			else
				$alias = '';

			$mono = array(
				'fconcat' => 'CONCAT',
				'fcount' => 'COUNT',
				'fdistinct' => 'DISTINCT',
				'fsum' => 'SUM',
				'fmax' => 'MAX',
				'fmin' => 'MIN',
			);

			// funciones soportadas
			if(isset($mono[strtolower(get_class($columns))])) {
				$reference = $columns->getReference();

				if(!is_string($reference)) {
					$reference = $this->__impObjFColumn($reference, $fields, false);

					if($columns instanceof FCount) {
						$extra = array('p_groupby' =>  implode(',', $reference));
						$reference = '*';

					}
					else
						$reference = $reference['p_columns'];
				}

				$res[] = "{$mono[strtolower(get_class($columns))]}({$reference}){$alias}";
			}
			else
				$res[] = $columns->getReference() . $alias;
		}
		else if("fhc" == strtolower(get_class($columns)))
			$res[] = $columns->getText();
		else if(!$set_alias && !is_object($columns) && !is_array($columns))
			$res[] = $columns;
		else
			$res[] = "# $columns #";

		return array('p_columns' => implode(', ', $res)) + $extra;
	}

	protected function __impObjFCondition($objFCondition, &$fields) {
		$res = "";

		if($objFCondition instanceof FCondition) {
			$left = $objFCondition->getLeft();
			$operation = $objFCondition->getOperation();
			$right = $objFCondition->getRight();

			if($left instanceof FColumn) {
				$left = $this->__impObjFColumn($left, $fields, false);
				$left = $left['p_columns'];
			}

			if($right instanceof FColumn) {
				$right = $this->__impObjFColumn($right, $fields, false);
				$right = $right['p_columns'];
			}

			switch($operation) {
				case "in": case "not in":
					if(is_array($right))
						$right = "(" . implode(", ", $right) . ")";
					else if(!preg_match("/^\((.+)\)$/", $right, $aux))
						$right = " (" . $right . ") ";

					break;
				
				case "is": case "is not":
					if($right != 'null')
						$right = "# {$right} #";

					break;

				default:

					break;
			}

			$res = $left . " " . $operation . " " . $right;
		}
		else if($objFCondition instanceof FOperation) {
			$straux = array();

			$objFCondition->reset();
			while(($objc = $objFCondition->next()) != null) {
				$aux = $this->__impObjFCondition($objc, $fields);
				$straux[] = $aux['p_where'];
			}

			$tipo = "#";
			if(isset(MySQL_Describer::$operations_relations[strtolower(get_class($objFCondition))]))
				$tipo = MySQL_Describer::$operations_relations[strtolower(get_class($objFCondition))];

			if(count($straux) > 1)
				$res = "( " . implode(" " . $tipo . " ", $straux) . " )";
			else
				$res = $straux;
		}
		else if ($objFCondition instanceof FHC)
			$res = $objFCondition->getText();



		return array('p_where' => $res);
	}

	protected function __impObjFOrderBy($objFOrden, &$fields) {
		$res = "";

		if($objFOrden != null) {
			$res = array();

			foreach($objFOrden as $objfo) {
				if("fhc" == strtolower(get_class($objfo)))
					$res[] = $objfo->getText();
				else {
					$type = "#";
					if(isset(MySQL_Describer::$order_relations[$objfo->getType()]))
						$type = MySQL_Describer::$order_relations[$objfo->getType()];

					$res[] = $objfo->getField() . " " . $type;
				}
			}

			$res = implode(", ", $res);
		}

		return array('p_orderby' => $res);
	}
	
	protected function __impObjFGroupBy($objFOrden, &$fields) {
		$res = "";

		if($objFOrden != null) {
			$res = array();

			foreach($objFOrden as $objfo) {
				if("fhc" == strtolower(get_class($objfo)))
					$res[] = $objfo->getText();
				else
					$res[] = $objfo->getField();
			}

			$res = implode(", ", $res);
		}

		return array('p_groupby' => $res);
	}

}
