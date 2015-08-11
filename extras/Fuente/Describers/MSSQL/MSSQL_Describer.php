<?

//////////////////////////////////////////////////////////////////////////////////////////////////

class MSSQL_Describer extends Describer {

	private static $fields_relations = array(
		"ffielddate" => array(
			FFieldDate::DATE => "varchar(10)",
			FFieldDate::DATE_TIME => "smalldatetime",
			FFieldDate::TIME => "varchar(8)",
		),
		"ffieldinteger" => "INTEGER",
		"ffieldfloat" => "FLOAT",
		"ffieldtext" => "TEXT",
		"ffieldvarchar" => "VARCHAR",
	);

	private static $fields_flags_relations = array( 
		FField::NOT_NULL => "not null",
		FField::AUTO_INCREMENT => "identity(1,1)",
 		FField::PRIMARY_KEY => "primary key"
	);

	private static $order_relations = array(
		FOrderBy::DESC => "DESC",
		FOrderBy::ASC => "ASC"
	);

	private static $operations_relations = array(
		"foperation" => "AND",
		"fcand" => "AND",
		"fcor" => "OR"
	);

	private static $functions_relations = array(
		"ffunction" => "# function #",
		"fcount" => "COUNT",
		"fdistinct" => "DISTINCT"
	);


	function serial(&$fuente, $apply = false) { 

		if($apply) {
			$name = $fuente->getName();
			$fields = $fuente->getFields(null, false);
			$connector = $fuente->getConnector();

			$res = $connector->executeQuery("exec sp_columns " . $name, false);
/*
array(19) {
    ["TABLE_QUALIFIER"]=> string(4) "mide"
    ["TABLE_OWNER"]=> string(3) "dbo"
    ["TABLE_NAME"]=> string(7) "paginas"
    ["COLUMN_NAME"]=> string(9) "id_pagina"
    ["DATA_TYPE"]=> int(12)
    ["TYPE_NAME"]=> string(7) "varchar"
    ["PRECISION"]=> int(200)
    ["LENGTH"]=> int(200)
    ["SCALE"]=> NULL
    ["RADIX"]=> NULL
    ["NULLABLE"]=> int(0)
    ["REMARKS"]=> NULL
    ["COLUMN_DEF"]=> NULL
    ["SQL_DATA_TYPE"]=> int(12)
    ["SQL_DATETIME_SUB"]=> NULL
    ["CHAR_OCTET_LENGTH"]=> int(200)
    ["ORDINAL_POSITION"]=> int(1)
    ["IS_NULLABLE"]=> string(3) "NO "
    ["SS_DATA_TYPE"]=> int(39)
  }
*/

			// crea la tabla en caso de no existir en la base
			if(!$res) {

				$sinbanderas = array();
				$crea = "";
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

					$post[] = "ALTER TABLE " . $name . " ADD CONSTRAINT pk_" . $name . " PRIMARY KEY (" . implode(", ", $pk) . ")";
				}

				$arr = array();
				foreach($fields as $objfield) {
					if($objfield->isFlag(FField::AUTO_INCREMENT))
						$arr[] = $objfield;
				}

				if(count($arr) > 1) {
					$sinbanderas[] = FField::AUTO_INCREMENT;
				}

				$aimprimir = array();

				foreach($fields as $objfield)
					$aimprimir[] = $this->__impObjFField($objfield, $sinbanderas);

				$crea = "CREATE TABLE " . $name . " (" . implode(", ", $aimprimir) . ") ";

				$connector->executeQuery($crea);
				if(count($post) > 0) {
					foreach ($post as $query)
						$connector->executeQuery($query);
				}

			}
			// iguala las estructuras, la base de datos con la descrita en la fuente
			else {

			}

			$connector->vaciaErrs();
		}

		return true;
	}

	function unserial(&$fuente) {

	}

/////////////////////// operaciones

	function insert(&$fuente, $rows) {
		$name = $fuente->getName();
		$fields = $fuente->getFields(null, false);

		$str_insert = array();

		if(count($rows) > 0) {


			$arr = array();
			foreach($fields as $objfield) {
				if($objfield->isFlag(FField::AUTO_INCREMENT)) {
					$arr[] = $objfield;
					$unset[] = $objfield->getName();
				}
			}
			$auto_increment_counter = 0;
			if(count($arr) > 1)
				$auto_increment_counter++;



			foreach($rows as $row) {
				$extra = "";
				$heads = array_keys($row);

				$str_fields = array();
				foreach($row as $field_name => $field_value) {

					// FField::AUTO_INCREMENT
					if($fields[$field_name]->isFlag(FField::AUTO_INCREMENT)) {
						if($field_value == 0) {

							// en el caso de manejar dos o mas campos de tipo auto_increment y que se le ha asignado el valor '0' se consigue el siguiente valor
							if(count($arr) > 1) {
								$field_value = $fuente->select($field_name, null, $field_name . " DESC", null, 0, 1);
								if($field_value)
									$field_value = intval($field_value[0][$field_name]) + 1;
								else {
									$auto_increment++;
									$field_value = $auto_increment;
								}
							}
							else {
								// quita el campo de la cabecera
								
								$nhead = array();
								foreach($heads as $h)
									if($h != $field_name)
										$nhead[] = $h;

								$heads = $nhead;
								
								continue;
							}
						}
						else if($extra == "")
							$extra = "SET IDENTITY_INSERT " . $name . " ON";
					}

					$str_fields[] = $this->__impObjFieldValue($field_name, $field_value, true);
				}

				$str_insert[] = "INSERT INTO " . $name . "(" . implode(", ", $heads) . ") VALUES (" . implode(", ", $str_fields) . ") " . $extra . " ";
			}
		}

		return implode("\n", $str_insert);
	}

	function select(&$fuente, $columns, $where, $orderby, $groupby, $start, $limit) {
		$name = $fuente->getName();
		$fields = $fuente->getFields();

		$columns = $this->__impObjFColumn($columns, $fields);

		$where = $this->__impObjFCondition($where, $fields);
		if($where != "")
			$where = " WHERE " . $where;

		$orderby = $this->__impObjFOrderBy($orderby, $fields);
		if($orderby != "")
			$orderby = " ORDER BY " . $orderby;

		$groupby = $this->__impObjFGroupBy($groupby, $fields);
		if($groupby)
			$groupby = " GROUP BY " . $groupby;

		$options = "";
		if($start != -1 || $limit != -1) {
			if($start == -1) {
				$start = $limit;
				$limit = -1;
			}

			if($limit == -1)
				$options = "top " . $start;
			else {
				$pk = $fuente->getFields(FField::PRIMARY_KEY);

				if($pk == null || count($pk) != 1)
					$this->agrErr("No se puede obtener el intervalo de la tabla ");

				$options = "top " . $limit;

				$str = $pk[0] . " not in ( select top " . $start . " " . $pk[0] . " FROM " . $name . $where . $groupby . $orderby . " )";
				if($where)
					$where = " WHERE " . $str . " and (" . str_replace(" WHERE ", "", $where) . ")";
				else
					$where = " WHERE " . $str;
			}
		}

		return "SELECT " . $options . " " . $columns . " FROM " . $name . $where . $groupby . $orderby;
	}

	function update(&$fuente, $arrValues, $where) {
		$name = $fuente->getName();
		$fields = $fuente->getFields();

		$sfields = array();
		foreach($arrValues as $cname => $cvalue)
				$sfields[] = $this->__impObjFieldValue($cname, $cvalue);

		$where = $this->__impObjFCondition($where, $fields);
		if($where != "")
			$where = " WHERE " . $where;

		return "UPDATE " . $name . " SET " . implode(",", $sfields) . $where . " ";
	}

	function delete(&$fuente, $where) {
		$name = $fuente->getName();
		$fields = $fuente->getFields();

		$where = $this->__impObjFCondition($where, $fields);
		if($where != null)
			$where = " WHERE " . $where;

		return "DELETE FROM " . $name . $where . " ";
	}

/////////////////////////////////////////////////////////////////////////////////////

	function relations_select($ops, $columns, $where, $orderby, $groupby, $start, $limit, &$fields) {
		$strcolumns = $this->__impObjFColumn($columns, $fields);

		$fuente_cursor = $ops[0]["fuente"]->getName();
		if($ops[0]["alias"] != $fuente_cursor)
			$fuente_cursor .= " AS " . $ops[0]["alias"]; 

		for($i = 1; $i < count($ops); $i++) {
			$fuente = $ops[$i]["fuente"]->getName();

			$stralias = "";
			if($ops[$i]["alias"] != $fuente_cursor)
				$stralias = " AS " . $ops[$i]["alias"];

			switch($ops[$i]["type"]) {
				case FRelation::JOIN:
					$condition = "";
					if($ops[$i]["where"] != null)
							$condition = " ON " . $this->__impObjFCondition($ops[$i]["where"], $fields);

					$fuente_cursor .= " JOIN " . $fuente->getName() . $stralias . $condition;

					break;

				case FRelation::LEFT_JOIN:
					$condition = "";
					if($ops[$i]["where"] != null)
							$condition = " ON " . $this->__impObjFCondition($ops[$i]["where"], $fields);

					$fuente_cursor .= " LEFT JOIN " . $fuente->getName() . $stralias . $condition;

					break;

				case FRelation::RIGHT_JOIN:
					$condition = "";
					if($ops[$i]["where"] != null)
							$condition = " ON " . $this->__impObjFCondition($ops[$i]["where"], $fields);

					$fuente_cursor .= " RIGHT JOIN " . $fuente->getName() . $stralias . $condition;

					break;
					
				case FRelation::UNION: case FRelation::INTERSECT: case FRelation::EXCEPT:
					// serializar el resultado de la operacion, y porner a $fuente_cursor al nombre de la tabla temporal
					return " todavia no";

					break;
			}
		}

		$where = $this->__impObjFCondition($where, $fields);
		if($where != null)
			$where = " WHERE " . $where;

		$orderby = $this->__impObjFOrderBy($orderby, $fields);
		if($orderby != "")
			$orderby = " ORDER BY " . $orderby;

		$groupby = $this->__impObjFGroupBy($groupby, $fields);
		if($groupby)
			$groupby = " GROUP BY " . $strgroupby;

		$options = "";
		if($start != -1 || $limit != -1) {
			if($start == -1) {
				$start = $limit;
				$limit = -1;
			}

			if($limit == -1)
				$options = "top " . $start;
			else {
				$pk = $fuente->getFields(FField::PRIMARY_KEY);

				if($pk == null || count($pk) != 1)
					$this->agrErr("No se puede obtener el intervalo de la tabla ");

				$options = "top " . $limit;

				$str = $pk[0] . " not in ( select top " . $start . " " . $pk[0] . " FROM " . $fuente_cursor . $where . $groupby . $orderby . " )";
				if($where)
					$where = " WHERE " . $str . " and (" . str_replace(" WHERE ", "", $where) . ")";
				else
					$where = " WHERE " . $str;
			}
		}

		return "SELECT " . $options . " " . $strcolumns . " FROM " . $fuente_cursor . $where . $groupby . $orderby;
	}

///////////////////
// operaciones de soprte

	private function __fieldsToFFields($tipo) {
		$res = "text";

		$long = 0;

		$tipo = trim(strtolower($tipo));
		if(preg_match("/(\w+)\((\d+)\)/", $tipo, $aux)) {
			$tipo = $aux[1];
			$long = $aux[2];
		}

		$equivalencia = array(
			"ffieldvarchar" => array(
				"varchar",
			),
			"ffieldfloat" => array(
				"float",
				"double",
			),
			"ffieldinteger" => array(
				"smallint",
				"int",
				"integer"
			),
			"ffieldtext" => array(
				"text"
			),
			"ffielddate" => array(
				"date",
				"datetime",
				"time"
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
		if(!$solovalor)
			return ($name . " = '" . addslashes($value) . "'");

		return "'$value'";
	}

	private function __impObjFColumn($columns, &$fields, $set_alias = true) {
		$res = array();

		if(is_array($columns)) {
			foreach($columns as $name)
				$res = array_merge($res, array($this->__impObjFColumn($name, $fields, $set_alias)));
		}
		else if(method_exists($columns, "fcolumn")) {
			$alias = $columns->getAlias();

			if($alias == "" && $set_alias && method_exists($columns, "ffunction"))
					eval('$alias = " AS " . ' . get_class($columns) . '::$prefix; ');
			else if ($set_alias && $alias != "")
				$alias = " AS " . $alias;
			else
				$alias = "";
			

			switch (strtolower(get_class($columns))) {
				case "fcount":
					$reference = $columns->getReference();
					if(!is_string($reference))
						$reference = $this->__impObjFColumn($reference[0], $fields, false);

					$res[] = "COUNT(" . $reference . ")" . $alias;
					break;
				case "fdistinct":
					$reference = $columns->getReference();
					if(!is_string($reference))
						$reference = $this->__impObjFColumn($reference, $fields, false);

					$res[] = "DISTINCT(" . $reference . ")" . $alias;
					break;

				default:
					$res[] = $columns->getReference() . $alias;
				
			}
		}
		else if("fhc" == strtolower(get_class($columns)))
			$res[] = $columns->getText();
		else if(!$set_alias && !is_object($columns) && !is_array($columns))
			$res[] = $columns;
		else
			$res[] = "# $columns #";

		return implode(", ", $res);
	}

	protected function __impObjFField($objFField, $sinbanderas = array()) {
		$res = $objFField->getName() . " ";

		$nameclase = strtolower(get_class($objFField));
		if(isset(MSSQL_Describer::$fields_relations[$nameclase])) {
			if(is_array(MSSQL_Describer::$fields_relations[$nameclase]))
				$res .= MSSQL_Describer::$fields_relations[$nameclase][$objFField->getFormat()];
			else
				$res .= MSSQL_Describer::$fields_relations[$nameclase];
		}

		if(strtolower(get_class($objFField)) == "ffieldvarchar")
			$res .= "(" . $objFField->length() . ")";

		$opciones = $objFField->getOptions();
		foreach($opciones as $bandera)
			if(isset(MSSQL_Describer::$fields_flags_relations[$bandera]) && !in_array($bandera, $sinbanderas))
				$res .= " " . MSSQL_Describer::$fields_flags_relations[$bandera];

		if(is_string($objFField->getDefault()) && !$objFField->isFlag(FField::AUTO_INCREMENT))
			$res .= " DEFAULT '" . $objFField->getDefault() . "'";

		return $res;
	}

	protected function __impObjFCondition($objFCondition, &$fields) {
		$res = "";

		if("fcondition" == strtolower(get_class($objFCondition))) {
			$left = $objFCondition->getLeft();
			$operation = $objFCondition->getOperation();
			$right = $objFCondition->getRight();

			switch($operation) {
				case "in": case "not in":
					if(is_array($right))
						$right = "(" . implode(", ", $right) . ")";
					else if(!preg_match("/^\((.+)\)$/", $right, $aux))
						$right = " (" . $right . ") ";

					break;

				default:

					break;
			}

			$res = $left . " " . $operation . " " . $right;
		}
		else if(method_exists($objFCondition, "foperation")) {
			$straux = array();

			$objFCondition->reset();
			while(($objc = $objFCondition->next()) != null)
				$straux[] = $this->__impObjFCondition($objc, $fields);

			$tipo = "#";
			if(isset(MSSQL_Describer::$operations_relations[strtolower(get_class($objFCondition))]))
				$tipo = MSSQL_Describer::$operations_relations[strtolower(get_class($objFCondition))];

			$res = "( " . implode(" " . $tipo . " ", $straux) . " )";
		}
		else if ("fhc" == strtolower(get_class($objFCondition)))
			$res = $objFCondition->getText();

		return $res;
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
					if(isset(MSSQL_Describer::$order_relations[$objfo->getType()]))
						$type = MSSQL_Describer::$order_relations[$objfo->getType()];

					$res[] = $objfo->getField() . " " . $type;
				}
			}

			$res = implode(", ", $res);
		}

		return $res;
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

		return $res;
	}

}

?>