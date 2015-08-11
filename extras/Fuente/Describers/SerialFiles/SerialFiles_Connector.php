<?php
//////////////////////////////////////////////////////////////////////////////////////////////////
// ConectorPHPFile: describe una coneccion a PHPFile y guarda una lista que contiene todos los      //
//     registros resultantes, producto de una consulta SQL a PHPFile                              //
//////////////////////////////////////////////////////////////////////////////////////////////////

class SerialFiles_Connector extends Conector {
	protected $queue = array();
	protected $path;
	static protected $psc = array(); 

	function SerialFiles_Connector($path) {
		if(!is_dir($path))
			$this->agrErr("Error al crear el manejador de SerialFiles: " . $path . " no es un directorio valido");

		// limpiar el path
		$this->path = $path . "/";
	}

	function __destruct() {
		if($this->link)
			mysql_close($this->link);

		$this->link = null;
	}

	function close() {
		$this->__destruct();	
	}

	function &executeQuery($query, $interrupt = true, $raw = true) {
		if(is_array(Connector::$traza))
			Connector::$traza[] = addslashes(var_export($query, true));

		$result = null;
		var_dump($query);
		if(is_array($query)) {
			foreach ($query as $code)
				eval($code);
		}
		else
			eval($query);

		if(isset($result["error"])) {
				$this->agrErr("<b>Error en la consulta:</b><br><tt>" . addslashes($result["error"]) . "</tt><br>\n<b>Mensaje de PHPFile</b>:<br> <tt>" . $query["code"] . "</tt>", $interrupt);

			return false;
		}

		return $result;
	}

	function &getDescriber() {
		return new SerialFiles_Describer();
	}
	
///////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////

	const GROUP_START = "start";
	const GROUP_END = "end";
	const GROUP_ACTUAL = "actual";

	function openGroup($id_group) {
		if(!is_readable($this->path. $id_group) || is_writable($this->path . $id_group))
			$this->agrErr("La ruta de la tabla no es valida");

		if(!file_exists($this->path . $id_group . "/temporal"))
			mkdir($this->path . $id_group . "/temporal");

		SerialFiles_Connector::$psc[$id_group] = opendir($hd);
	}

	function cursorGroup($id_group, $position = null, $reference = null) {
		if(!isset(SerialFiles_Connector::$psc[$id_group]))
			return false;

		if($position === null)
			$position = 0;

		switch($position) {
			case SerialFiles_Connector::GROUP_START:
				if($position < 0)
					$position = 0;

				rewinddir($hd);
				while(($file = readdir($hd)) !== false)
					$position--;

				break;

			case SerialFiles_Connector::GROUP_END:
				if($position > 0)
					$position = 0;
				
						
				break;

			case SerialFiles_Connector::GROUP_ACTUAL: default:
				if($cursor = 0) {
				
				}
		}
	}

	function serialCursor($id_group, $row) {
	}

	function unserialCursor($id_group) {
	}

	function dropCursor($id_group) {
	}

///////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////

	protected function around($source, $before = null, $in_process = null, $after = null, $bear = true) {
		$res = null;

		if(file_exists($this->host . "/" . $source)) {
			$dir = opendir($this->host . "/" . $source);

			$row_parameter = array();
			$row_count = 0;
			while($row_file = readdir($dir)) {
				
				// verifica que sea uno de los archivos de la tabla
				if(preg_math("/row_(\d+)\.php/", $row_file)) {
	
					if(is_writable($row_file) && is_readable($row_file)) {
						$row = $this->array_unserial($row_file);

						if($in_process != null)
							eval($in_process);
					}
					else if($bear) {
						// en caso que el archivo no se encuentre accesible lo manda a la cola
						$this->queue[$row_count] = $row_file;
					}
	
					$row_count++;
				}
			}
			
			closedir($dir);


			if(count($this->queue) > 0) {
				$time_bear = 1;	// segundos
				
				// tiempo inicial
				list($usec, $sec) = explode(" ", microtime());
				$time_start = (float)$usec + (float)$sec;
				
				while(1) {
					
					$processed = array();
					foreach ($this->queue as $row_count => $row_file) {
						if(is_writable($row_file) && is_readable($row_file)) {
							$row = $this->array_unserial($row_file);
							
							if($in_process != null)
								eval($in_process);
								
							$processed[$row_count] = $row_count;
						}
					}


					list($usec, $sec) = explode(" ", microtime());
    				$time_end = (float)$usec + (float)$sec;
    				$time = $time_end - $time_start;

    				if($time >= $time_bear)
    					break;
				}

				if(count($processed) != count($this->queue)) {

					foreach ($processed as $row_count)
						unset($this->queue[$row_count]);

					$this->agrErr("No se completo la operaci�n en:" . implode("<br>\n", $this->queue));
				}
				
				
				// operaciones 'despues de ejecucion'
				
				
				
			}
		}
		else 
			$this->agrErr("La fuente \"$source\" no existe");
		
		return $res;
	}


	protected function array_serial($count, $name, $array) {
		$res = false;

		if(is_array($array)) {
			$res = $this->array_serial_generate($array);

			$fp = fopen($this->host . "/" . $name . "/row_" . $count . ".php", "w+");
			if($fp) {
				$res = <<<PPP
<?
\$row = {$res};
?>
PPP;
				fwrite($fp, $res);
				fclose($fp);
				$res = true;
			}
		}

		return $res;
	}

	protected function array_unserial($file_name) {
		$row = null;
		if(file_exists($file_name))
			include ($file_name);

		return $row;
	}
	
	
	private function array_serial_generate($array, $sangria = 1) {
		$res = "";
	
		if(is_array($array)) {
			$san = "";
			for($i = 0; $i < $sangria; $i++)
				$san .= "\t";
	
			$i = 0;
			$fin = "";
			foreach($array as $arr => $contenido) {
				$i++;
				
				if($i < count($array))
					$fin = ",";
				else
					$fin = "";
				
				$res .= $san . "\"$arr\" => " . $this->array_serial_generate($contenido, $sangria + 1) . (is_array($contenido)? $san . ")" . $fin . "\n" : $fin . "\n");
			}
	
			$res = "array (\n" . $res . ")\n";
			
		}
		else if(is_string($array) || is_float($array))
			$res = "\"$array\"";
			

		return $res;
	}
}

?>