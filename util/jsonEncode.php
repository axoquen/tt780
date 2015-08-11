<?php

function jsonEncode($obj) {
	switch(true) {
		case is_array($obj):
			if (is_associative($obj)) {
				$arr_out = array();
				foreach ($obj as $key=>$val)
					$arr_out[] = '"' . $key . '" : ' . jsonEncode($val);

				return "{\n  " . implode(",\n  ", $arr_out) . "\n}";
			}

			$arr_out = array();
			$ct = count($obj);
			for ($j = 0; $j < $ct; $j++)
				$arr_out[] = jsonEncode($obj[$j]);

			return '[' . implode(',', $arr_out) . ']';

		case is_int($obj):
			return $obj;

		case is_bool($obj):
			return $obj ? 'true' : 'false';

		default: 
			$str_out = stripslashes(trim($obj));
			$str_out = str_replace(array('"', '', '/', "\r", "\n"), array('\"', "\\", '/', '', '\\n'), $str_out);
			return '"' . $str_out . '"';
	}
}


function jsonEncodeUTF8($obj) {
	switch(true) {
		case is_array($obj):
			if (is_associative($obj)) {
				$arr_out = array();
				foreach ($obj as $key=>$val)
					$arr_out[$key] = jsonEncodeUTF8($val);

				return $arr_out;
			}

			$arr_out = array();
			$ct = count($obj);
			for ($j = 0; $j < $ct; $j++)
				$arr_out[] = jsonEncodeUTF8($obj[$j]);

			return $arr_out;

		case is_int($obj) || is_bool($obj):
			return $obj;

		default: 
			return utf8_encode($obj);
	}
}


