<?php

//////////////////////////////////////////////////////////////////////////////////////////////////

if(!function_exists("mssql_connect"))
	die("<b>Connector:</b> Error en su instalación de MSSQL...<br>\n");

//////////////////////////////////////////////////////////////////////////////////////////////////

class MSSQL_Connector extends Connector {
	protected $host;
	protected $name;
	protected $dbsel;

	protected $link;

	static $traza = null;

	function MSSQL_Connector($servidor, $name = null, $pass = null) {
		$this->host = $host;
		$this->name = $name;

		if(($this->link = @mssql_connect($servidor, $name, $pass)) == 0)
			$this->agrErr("Error al crear la conexion con MSSQL: " . $servidor);
	}

	// selecciona la BD de nombre $name en el DBMS
	function selectDB($name) {
		if(@mssql_select_db($name, $this->link)) {
			$this->dbsel = $name;
			return true;
		}

		$this->agrErr("Error en la seleccion de '" . $name . "'");
		$this->dbsel = null;
		return false;
	}

	function __destruct() {
		if($this->link)
			mssql_close($this->link);

		$this->link = null;
	}

	function close() {
		$this->__destruct();
	}

	// envia la consulta $query al DBMS y asigna el resultado a un arreglo de registros
	function &executeQuery($query, $interrupt = true, $raw = false) {
		$result = null;

		if($this->link != null && $this->dbsel != null) {
			if(is_array(MSSQL_Connector::$traza))
				MSSQL_Connector::$traza[] = $query;
				
			$result = @mssql_query($query, $this->link);
			if($result == false) {
				$err = mssql_get_last_message();
				if($interrupt)
					$this->agrErr("Error en la consulta: <b>\"" . $query . "\"</b><br>\nMensaje de SQL SERVER: \"<i>" . $err . "</i>\"", $interrupt);

				if($result != false)
					mssql_free_result($result);

				return false;
			}
		}
		else
			$this->agrErr("No hay conexión valida o BD seleccionada: " . $query);

		if($raw)
			return $result;

		$res = null;
		if($result != null && !is_bool($result) && mssql_num_rows($result) > 0) {

			$aux = array();
			while($unreg = mssql_fetch_assoc($result))
				$aux[] = $unreg;

			$res = $aux;
		}
		else if(is_bool($result))
			$res = $result;

		return $res;
	}

	function &getDescriber() {
		return new MSSQL_Describer();
	}

	function startTrace() {
		MSSQL_Connector::$traza = array();
	}

	function finishTrace() {
		MSSQL_Connector::$traza = null;
	}

	function Trace() {
		return MSSQL_Connector::$traza;
	}
}

?>