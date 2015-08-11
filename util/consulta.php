<?php

function consulta($query, $interrupt = true, $raw = false) {
	$result = null;

	$result = mysql_query($query);
	if(($err = mysql_error()) != "" || $result == false) {
		if($interrupt) {
			trigger_error(
                      defined('TT780_DEBUG') ? 
                          "Error en la consulta: <b>\"" . $query . "\"</b><br>\nMensaje de MySQL: \"<i>" . $err . "</i>\"" :
                          "Error en la BD",
                      E_USER_ERROR
                  );
		}

		if($result != false)
			mysql_free_result($result);
				
		$result = null;
	}

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


function sanitized($text) {
	return str_replace("'", "´", trim($text));
}
