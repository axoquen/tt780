<?php

function printSize($size) {
	$i = 0;
	$iec = array('Bytes', 'KB', 'MB', 'GB', 'TB', 'PB', 'EB', 'ZB', 'YB');
	while (($size / 1024) > 1) {
		$size = $size / 1024;
		$i++;
	}

	$size = number_format(floatval($size), 2);

	if(strpos($size, '.00') !== false)
		$size = str_replace('.00', '', $size);

	return "{$size} {$iec[$i]}";
}
