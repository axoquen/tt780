<?php

//////////////////////////////////////////////////////////////////////////////////////////////////
// MySQL_Connector: describe una coneccion a MySQL y guarda una lista que contiene todos los      //
//     registros resultantes, producto de una consulta SQL a MySQL                              //
//////////////////////////////////////////////////////////////////////////////////////////////////

if(!function_exists("mysql_connect"))
	die("<b>Connector:</b> Error en su instalaci�n de MySQL...<br>\n");

//////////////////////////////////////////////////////////////////////////////////////////////////

class MySQL_Connector extends FConnector {
	protected $dbsel;
	protected $link;

	static $desc;
	static $traza = null;

	function MySQL_Connector($host, $name = null, $pass = null) {
		if(($this->link = @mysql_connect($host, $name, $pass)) == 0)
			$this->agrErr("Error al crear la conexion con MySQL: " . $host);
	}

	// selecciona la BD de nombre $name en el DBMS
	function selectDB($name) {
		if(@mysql_select_db($name, $this->link)) {
			$this->dbsel = $name;
			return true;
		}

		$this->agrErr("Error en la seleccion de '" . $name . "'");
		$this->dbsel = null;
		return false;
	}

	function __destruct() {
		if($this->link)
			mysql_close($this->link);

		$this->link = null;
	}

	function close() {
		$this->__destruct();	
	}

	// envia la consulta $query al DBMS y asigna el resultado a un arreglo de registros
	function &executeQuery($query, $interrupt = true, $raw = false) {
		$result = null;

		if($this->link != null && $this->dbsel != null) {

			if(is_array(MySQL_Connector::$traza))
				MySQL_Connector::$traza[] = array(
					$query,
					debug_backtrace(),
					microtime(true)
				);

			$result = mysql_query($query, $this->link);
			if(($err = mysql_error($this->link)) != "" || $result == false) {
				if($interrupt)
					$this->agrErr("Error en la consulta: <b>\"" . $query . "\"</b><br>\nMensaje de MySQL: \"<i>" . $err . "</i>\"", $interrupt);

				if($result != false)
					mysql_free_result($result);
					
				$result = null;
			}
			
			if(is_array(MySQL_Connector::$traza))
				MySQL_Connector::$traza[count(MySQL_Connector::$traza) - 1][] = microtime(true);
		}
		else
			$this->agrErr("No hay conexión valida o BD seleccionada: " . $query);

		if($raw)
			return $result;

		$res = null;
		if($result != null && !is_bool($result) && mysql_num_rows($result) > 0) {
			$aux = array();
			while($unreg = mysql_fetch_assoc($result))
				$aux[] = $unreg;

			$res = $aux;
		}
		else if(is_bool($result))
			$res = $result;

		return $res;
	}

	function &getDescriber() {
		if(MySQL_Connector::$desc == null)
			MySQL_Connector::$desc = new MySQL_Describer();

		return MySQL_Connector::$desc;
	}

	function startTrace() {
		MySQL_Connector::$traza = array();
	}

	function finishTrace() {
		MySQL_Connector::$traza = null;
	}

	static function Trace() {
		if(!is_array(MySQL_Connector::$traza))
			return;
		
		echo <<<PPP
<script type="text/javascript">
	function MySQL_connector_switchDetail(anchor) {
		var table = anchor.parentNode.parentNode.parentNode;
		table.childNodes[1].style.display = table.childNodes[1].style.display == 'none' ? (document.all ? 'block' : 'table-row') : 'none';

		anchor.innerHTML = table.childNodes[1].style.display == 'none' ? '+' : '-';

		return false;
	}
</script>
PPP;

		$c = count(MySQL_Connector::$traza);
		$start = MySQL_Connector::$traza[0][2];
		$finish = MySQL_Connector::$traza[$c - 1][3];
		
		$time = $finish - $start;
	
		echo "<h1 style=\"text-align: left; font-size: 15px; font-weight: 800; padding: 10px; \">{$c} consultas en {$time} seg. ({$finish} - {$start}) </h1>";
		
		$time = 0;
		
		foreach(MySQL_Connector::$traza as $query) {
			$t = ($query[3] - $query[2]);
			$time += $t;
			
			echo "<table style=\"text-align: left; width: 100%; margin: 5px 0px; \">";
			echo "<tr> <td colspan=\"2\" style=\"padding: 5px; font-weight: 800; font-size: 13px; \"><a href=\"#\" style=\"display: block; float: left; margin-right: 10px; line-height: 13px; height: 13px; font-size: 150%; text-decoration: none; \" onclick=\"javasrcipt: return MySQL_connector_switchDetail(this); \">+</a> {$query[0]} :: <span style=\"color: #666; font-weight: 400; \">{$t}  seg.</span></td></tr>";
			echo "<tr style=\"display: none; \"> <td width=\"50\"></td><td style=\"padding: 0px 5px 15px 5px; \">";

			foreach($query[1] as $data) {
				$control = '';
				if(isset($data['class']) && "{$data['class']}{$data['type']}{$data['function']}" == 'Control->execute')
					$control = " :: Control->execute('<i>" . print_r($data['args'][0], true) . "</i>')";

				if(isset($data['file']) && strpos($data['file'], "\\lib\\") !== false && $control == '')
					continue;

				if(isset($data['file']) && preg_match('/^(Tbl[^\.]+)\.php$/', basename($data['file']), $aux)) {
					echo "<p style=\"margin: 0px; padding: 0px; \">{$aux[1]}->{$data['function']} : linea ({$data['line']}) {$control}</p>";
					continue;
				}

				if(isset($data['file']) && $data['file'] != '') {
					echo "<p style=\"margin: 0px; padding: 0px; \">" . basename($data['file']) . " : ({$data['line']}) {$control}" . (isset($data['function']) && $data['function'] == 'include' ? " : include({$data['args'][0]})" : '') . "</p>";
					continue;
				}

				echo "<p style=\"margin: 0px; padding: 0px; \"><span style=\"font-style: oblique; \">{$data['function']} {$control}</span></p>";
			}
			echo "</td></tr></table>";
		}
		
		echo "<h1 style=\"text-align: left; font-size: 15px; font-weight: 800; padding: 10px; \">Total tiempo de consultas: {$time} segs.</h1>";

		return;
	}
}
