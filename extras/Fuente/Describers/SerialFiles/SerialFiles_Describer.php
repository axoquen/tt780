<?php

//////////////////////////////////////////////////////////////////////////////////////////////////////
// DescriptorPHPFile: interpreta un juego de obejtos y los convierte a sentencias para PHPFile //
//////////////////////////////////////////////////////////////////////////////////////////////////////

class DescriberPHPFile extends Describer {
	private $parameters = null;
	private $host;

	private static $order_relations = array(
		FOrderBy::DESC => ">",
		FOrderBy::ASC => "<"
	);
	
	private static $operations_relations = array(
		"foperation" => "&&",
		"fcand" => "&&",
		"fcor" => "||"
	);


//----------------------------------
	private static $functions_relations = array(
		"ffunction" => '# ffunction #',
		"fcount" => array(
			'if($row_key != "" && !isset($row_parameter["groupby_hash"][$row_key])) {',
			'  $row_parameter["groupby_hash"][$row_key] = $row_parameter["groupby_row_count"]; ',
			'  $row_parameter["groupby_row_count"] += 1;',
			'} ',
			'$res[$row_parameter["groupby_hash"][$row_key]]["__field_alias__"] += 1; '
		),
/*		"fdistinct" => array(
            '$row_key = $row_key . "_" . $row["__field_name__"]; ',
			'if(!isset($row_parameter["groupby_hash"][$row_key])) {',
			'  $row_parameter["groupby_hash"][$row_key] = $row_parameter["groupby_row_count"]; ',
			'  $res[$row_parameter["groupby_hash"][$row_key]]["__field_alias__"] = $row["__field_name__"]; ',
			'} ',
		),
*/
	);

	
	function serial($name, &$fields, &$connector, $apply = false) {
		$this->name = $name;
		$this->fields = $fields;
		$this->host = $connector->getHost();
		

		if($apply) {
			
			// si la carpeta no existe, crea la nueva fuente
			if(!file_exists($this->host . "/" . $name)) {
				mkdir($this->host . "/" . $name);

				// guarda la definicion
				$fp = fopen($this->host . "/" . $name . "/" . $name . ".def", "w+");
				fwrite($fp, serialize($fields));
				fclose($fp);

				// guarda los parametros
				foreach ($fields as $objCampo) {
					if($objCampo->isFlag(FField_AUTO_INCREMENT)) {
						$parameters["file_count"] = 0;
						$parameters["auto_increment_field"] = $objCampo->getName();
						$parameters["auto_increment_count"] = 0;
						break;
					}
				}

				$fp = fopen($this->host . "/" . $name . "/" . $name . ".prm", "w+");
				fwrite($fp, serialize($parameters));
				fclose($fp);

			}
			else {
				// hace las modificaciones

				// ...s
			}
		}

		// recupera los parametros
		$fp = fopen($this->host . "/" . $name . "/" . $name . ".prm", "r+");
		if($fp) {
			$this->parameters = unserialize(fgets($fp));
			fclose($fp);
		}

		return true;
	}

	function unserial($name, &$connector) {
		$this->name = $name;
		$this->fields = null;
		$this->host = $connector->getHost();

		$res = false;
		if(file_exists($this->host . "/" . $name)) {
			
			// recupera la definicion
			$fp = fopen($this->host . "/" . $name . "/" . $name . ".def", "r+");
			if($fp) {
				$this->fields = unserialize(fgets($fp));
				$res = $this->fields;
				fclose($fp);
			}

			// recupera los parametros
			$fp = fopen($this->host . "/" . $name . "/" . $name . ".prm", "r+");
			if($fp) {
				$this->parameters = unserialize(fgets($fp));
				fclose($fp);
			}
		}

		return $res;
	}


/////////////////////// operaciones

	function insert($rows) {
		$res = array();

		if(count($rows) > 0) {
			$heads = array_keys($this->fields);

			foreach($rows as $row) {
				$res[] = '$nrow = array(); ';

				// normaliza los registros (todos deben tener referencias a los mismos campos, se toma como base el primer registro)
				foreach($heads as $field_name) {

					$code = "";
					if(isset($row[$field_name])) {
						if($this->fields[$field_name]->isFlag(FField::AUTO_INCREMENT)) {
							if("{$row[$field_name]}" == "0") {
								$this->parameters["auto_increment_count"] += 1;
								$row[$field_name] = $this->parameters["auto_increment_count"];
							}
						}

						$code = '$nrow["' . $field_name . '"] = "' . $row[$field_name] . '"; ';

						unset($row[$field_name]);
					}
					else
						$code = '$nrow["' . $field_name . '"] = "' . $this->fields[$field_name]->getDefault() . '"; ';

					$res[] = $code;
				}
				
				if(count($row) > 0) {
					$fields = array_keys($row);
					$this->agrErr("El campo \"{$fields[0]}\" no existe");
				}

				$this->parameters["file_count"] += 1;
				$res[] = '$this->array_serial("' . $this->parameters["file_count"] . '", "' . $this->name . '", $nrow); ';
			}
		}
		
		
		// al modificarse uno de los parametros se guarda el nuevo cambio
		$fp = fopen($this->host . "/" . $this->name . "/" . $this->name . ".prm", "w+");
		fwrite($fp, serialize($this->parameters));
		fclose($fp);

		return $res;
	}

	function select($fields, $where = null, $order = null, $start = -1, $limit = -1) {
		$code_before = array();
		$code_in_process = array();
		$code_after = array();

		// genera el codigo para la generacion de una tabla de ordenacion (parametros 'orderby' en el array de $row_parameter)
/*		if($order)
			$res = $this->__impObjOrderBy($order);
*/
		// genera el codigo para la seleccion de los registros
		if($where) {
			list($before, $in_process, $after) = $this->__impObjFCondition($where);
			
			$code_before = array_merge($code_before, $before);
			$code_in_process = array_merge($code_in_process, $in_process);
			$code_after = array_merge($code_after, $after);
		}

		// genera el codigo para formar los registros
		list($before, $in_process, $after) = $this->__impFieldsList($fields); 
		$code_before = array_merge($code_before, $before);
		$code_in_process = array_merge($code_in_process, $in_process);
		$code_after = array_merge($code_after, $after);


/*	
		// genera el codigo para el recorrido
		$code = array(
			"\$code = array(\n" . implode(",\n", $res) . "\n);",
			'$result = $this->around("' . $this->name . '", $code); '
		);
		
		// genera el codigo para el intervalo de resultados
		if($start != -1 || $limit != -1) {
			$res[] = 'if(!isset($row_parameter["res_count"])) $row_parameter["res_count"] = 0; $row_parameter["res_count"] += 1; ';

			if($start != -1)
				$res[] = 'if($row_parameter["res_count"] < ' . $start . ') continue';

			if($limit != -1)
				$res[] = 'if($row_parameter["res_count"] > ' . $limit . ') break; ';
		}
*/
	}


	function update($values, $where = null) {
	}

	function delete($where = null) {
	}

	function join($fuentes, $fields = null, $where = null, $orderby = null, $start = -1, $limit = -1) {
	}

	function drop() {
	}

	function temporal_serial_by_fields($name, $fields, $connector) {
	}

	function temporal_serial_by_query($name, $query, $connector) {
	}


//-------------------------------------
	
	function __impFieldsList($field) {
		$res = array(
			"before" => array(),
			"in_process" => array(),
			"after" => array(),
		);

		if(is_array($field)) {
			$groupby = array();
			$assign = array();
			$ih = array();

			// recorre la lista de campos en busca de funciones especiales y asignaciones simples
			foreach($field as $name) {
				
				// en el caso de una funcion especial
				if(is_object($name) && method_exists($name, "ffunction")) {
					if(isset(DescriberPHPFile::$functions_relations[strtolower(get_class($name))]))
						$pre_res = DescriberPHPFile::$functions_relations[strtolower(get_class($name))];
					else
						$pre_res = DescriberPHPFile::$functions_relations["ffunction"];
						

					$groupby = array_merge($groupby, $name->getGroupBy());

					$pre_res = str_replace("__field_name__", $name->getField(), $pre_res);
					$pre_res = str_replace("__field_alias__", $name->getAlias(), $pre_res);

					$assign[] = $pre_res;
				}
				else if(is_object($name) && "fih" == strtolower(get_class($name)))
					// codigo directo
					$ih[] = $name->getText();
				else {
					// asignacion simple
					$alias = $name;
					if(preg_match("/(\w+) + as + (\w+)/", $name, $aux))
						$alias = $aux[2];


					$assign[] = '$res[$row_count][' . $alias . '] = $row[' . $name . ']; ';
				}
			}

			
			if(count($agrp) > 0) {
				// genera una tabla hash que guardara las claves unicas de cada grupo
				$res["before"][] = '$row_parameter["groupby"] = array("' . implode('","', $agrp) . '"); ';
				$res["before"][] = '$row_parameter["groupby_hash"] = array(); ';
				$res["before"][] = '$row_parameter["groupby_row_count"] = 0; ';


				// genera la clave que corresponde al registro actual
				
				$res["in_process"][] = '$row_key = ""; ';
				$res["in_process"][] = 'foreach($row_parameter["groupby"] as $field) { ';
				$res["in_process"][] = '  $row_key .= $row[$field]; ';
				$res["in_process"][] = '}';
				
				// agrega el registro
				foreach($assign as $code)
					$res["in_process"][] = str_replace('$row_count', '$row_parameter["groupby_hash"][$row_key]', $code);

				foreach($ih as $code)
					$res["in_process"][] = $code;
			}
			else
				foreach($assign as $code)
					$res["in_process"] = $assign;
		}

		return array($res["before"], $res["in_process"], $res["after"]);
	}
	
	function __impObjFCondition($where, $nfields = null, $in_atom = false) {
		$res = array(
			"before" => array(),
			"in_process" => array(),
			"after" => array(),
		);

		if("fcondition" == strtolower(get_class($objFCondition))) {
			$left = $objFCondition->getLeft();
			$right = $objFCondition->getRight();

			$operation = $objFCondition->getOperation();
			switch($operation) {
				case "in": case "not in":

					for($i = 0; $i < count($right); $i++)
						$right[$i] = '"' . $right[$i] . '"';

					$right = implode(", ", $right);

					$left = str_replace(".", "", $left);
					$left = str_replace("-", "", $left);

					$res["before"][] = '$row_parameter["' . $left . '_array"] = array(' . $valor . ');';

					$atom = 'in_array($row["' . $left . '"], $row_parameter["' . $left . '_array"])';
					if(!$in_atom)
						 $atom = 'if(!' . $atom . ')) continue; ';

					$res["in_process"][] = $atom;

					break;

				default:

				 	if($nfields && !in_array($left, $nfields))
			 			$left = '"' . $left . '"';
			 		else
			 			$left = '$row["' . $left . '"]';

				 	if($nfields && !in_array($right, $nfields))
				 		$right = "'" . $right->getRight() . "'";
			 		else
			 			$right = '$row["' . $right . '"]';

					$atom = $left . ' ' . $operacion . ' ' . $right;
					if(!$in_atom)
						$atom = 'if(!(' . $atom . ')) continue; ';

					$res["in_process"][] = $atom;

					break;
			}
		}
		else if(method_exists($objFCondition, "foperation")) {

			$objFCondition->reset();
			while(($objc = $objFCondition->next()) != null) {
				list($before, $in_process, $after) = $this->__impObjFCondition($objc, $nfields, true);

				$res["before"] = array_merge($res["before"], $before);
				$res["in_process"] = array_merge($res["in_process"], $in_process);
				$res["after"] = array_merge($res["after"], $after);
			}

			if(isset(DescriberPHPFileArray::$operations_relations[strtolower(get_class($objFCondition))]))
				$atom = implode(' ' . DescriberPHPFile::$operations_relations[strtolower(get_class($objFCondition))] . ' ', $res["in_process"]);

			if($in_atom)
				$atom = "(" . $atom . ")";

			if(!$in_atom)
				$atom = "if (!(" . $atom . ")) continue; ";

			$res["in_process"][] = $atom;
		}
		else if ("fih" == strtolower(get_class($objFCondition)))
			$res["in_process"][] = $objFCondition->getText();

		return array($res["before"], $res["in_process"], $res["after"]);
	}


	function __impObjOrderBy($order) {
		$res = array(
			"before" => array(),
			"in_process" => array(),
			"after" => array()
		);

		if($objFOrden != null) {
			
			$hash = array();
			$orders = array();
			foreach($order as $objorder)
				if(isset(DescriberPHPFile::$order_relations[$objfo->getType()])) {
					$order[] = '"' . $objorder->getField() . '" => "' . DescriberPHPFile::$order_relations[$objfo->getType()] .'"';
					$hash[] = '"' . $objorder->getField() . '" => array();';
				}

			$res["before"][] = '$row_parameter["orderby"] = array(' . implode(", ", $order) . ')'; 
			$res["before"][] = '$row_parameter["orderby_hash"] = array(' . implode(", ", $hash) . ')'; 

			// crea el codigo para el ordenado posterior
			$res["in_process"][] = 'if(count($row_parameter["orderby"])) > 0) {';


			$res["after"][] = '  $ordered_res = array(); ';
			$res["after"][] = '  for($i = 0; $i < count($res); $i++) {';
			$res["after"][] = '  ';
			$res["after"][] = '  }';
			$res["after"][] = '} ';

			$res = implode(", ", $res);
		}

		return array($res["before"], $res["in_process"], $res["after"]);

	}
}

?>