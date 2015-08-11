<?php

function generateRandomKey($table, $field, $length = null) {
	// 62 caracteres
	$availables = array(
		'A', 'B', 'C', 'D', 'E', 'F', 'G', 'H', 'I', 'J', 'K', 'L', 'M', 'N', 'O', 'P', 
		'Q', 'R', 'S', 'T', 'U', 'V', 'W', 'X', 'Y', 'Z', 'a', 'b', 'c', 'd', 'e', 'f',
		'g', 'h', 'i', 'j', 'k', 'l', 'm', 'n', 'o', 'p', 'q', 'r', 's', 't', 'u', 'v',
		'w', 'x', 'y', 'z'
	);

	// para generar las semillas aplica
	for($i = 0; $i < 2; $i++)
		rand(0, count($availables) - 1);

	$repeat = true;

	$key = '';
	while($repeat) {
		$key = '';
		for($i = 0; $i < $length; $i++)
			$key .= $availables[rand(0, count($availables) - 1)];

		// comprueba que no exista en la tabla
		if(!consulta("SELECT {$field} FROM {$table} WHERE {$field} = '{$key}'"))
			$repeat = false;
	}

	return $key;
}
