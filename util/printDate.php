<?php

function printDate($date, $form = 0, $format = 1, $default = "&nbsp;") {
	// $fecha viene en 'AAAA-MM-DD'
	$res = $default;

	$meses = array();

	switch($format) {
		case 3:
			$meses = array("01" => "01", "02" => "02", "03" => "03", "04" => "04", "05" => "05", "06" => "06", "07" => "07", "08" => "08", "09" => "09", "10" => "10", "11" => "11", "12" => "12");
			break;

		case 2:
			$meses = array("01" => "Ene", "02" => "Feb", "03" => "Mar", "04" => "Abr", "05" => "May", "06" => "Jun", "07" => "Jul", "08" => "Ago", "09" => "Sep", "10" => "Oct", "11" => "Nov",	"12" => "Dic");
			break;

		case 1: default:
			$meses = array("01" => "Enero", "02" => "Febrero", "03" => "Marzo", "04" => "Abril", "05" => "Mayo", "06" => "Junio", "07" => "Julio", "08" => "Agosto", "09" => "Septiembre", "10" => "Octubre", "11" => "Noviembre", "12" => "Diciembre");
			break;
	}

	if(preg_match('/(\d{4})-(\d{2})-(\d{2})( \d{2}:\d{2}:\d{2})?/', $date, $aux)) {
		if($aux[1] != "0000") {
			if($format != 3)
				$aux[3] = (int)$aux[3];

			switch($form) {
				case 1: case 2:
					$res = ($aux[3] != '00' ? $aux[3] . "/" : '') . ($aux[2] != '00' ? $meses[$aux[2]] . "/" : '') . $aux[1] . " " . (isset($aux[4]) ? "<br>" . $aux[4] : '');
					break;
				case 3:
					$res = ($aux[3] != '00' ? $aux[3] . "/" : '') . ($aux[2] != '00' ? $meses[$aux[2]] . "/" : '') . $aux[1] . " " . (isset($aux[4]) ? " " . $aux[4] : '');
					break;
				case 4: 
					$res = $aux[3] . "-" . $meses[$aux[2]] . "-" . $aux[1];
					break;
				case 5: 
					$res = "{$aux[3]} de {$meses[$aux[2]]}, {$aux[1]}";
					break;
				case 6: 
					$dia = array(0 => 'Domingo', 1 => 'Lunes', 2 => 'Martes', 3 => 'Miercoles', 4 => 'Jueves', 5 => 'Viernes', 6 => 'Sabado');
					$i = getdate(mktime(1, 1 , 1, $aux[2], $aux[3], $aux[1]));
					$res = "{$dia[$i['wday']]} {$aux[3]} de {$meses[$aux[2]]}, {$aux[1]}";
					berak;
				case 0: default:
					$res = $meses[$aux[2]] . " " . $aux[3] . ", " . $aux[1];
			}
		}
	}

	return $res;
}
