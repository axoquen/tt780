<?php

// ej: pclose(startFork($_SERVER['SCRIPT_FILENAME'] . "?accion=procesar&id_repositorio=" . $id_repositorio . "&id_usuario=" . $id_usuario . "&id_paquete=" . $id_paquete . "&material_tipo=" . $material_tipo));


function startFork($phpcommand, $args = '', $log = '', $session = null, $output = null) {
	$script = "";
	$params = "";

	$pos = strpos($phpcommand, "?");
	if($pos !== false) {
		$script = substr($phpcommand, 0, $pos);
		$params = str_replace('&', ' ', substr($phpcommand, $pos + 1));
	}
	else
		$script = $phpcommand;

	$actual = getcwd();

	$dir = dirname($script);
	$script = basename($script);

	if (substr(PHP_OS, 0, 3) == 'WIN')
		$command = "start /b php -q {$script} {$params}" . ($output ? " > {$output}" : '');
	else {
		$output = $output ? "&>> {$output}" : '';
		$command = "/var/www/bin/php -q {$script} {$params} {$output} &";
	}

	chdir($dir);
	
	if($log)
		file_put_contents($log, '[' . date('Y-m-d H:i:s') . "][{$session}] {$command} \r\n", FILE_APPEND | LOCK_EX);

	$proc = popen($command, 'r');

	sleep(2);

	//if($log) {
	//	file_put_contents($log, "\r\n", FILE_APPEND | LOCK_EX);
	//	file_put_contents($log, '[' . date('Y-m-d H:i:s') . "][{$session}] " . print_r($proc, true) . "\r\n", FILE_APPEND | LOCK_EX);
	//}

	chdir($actual);

	return $proc;
}

