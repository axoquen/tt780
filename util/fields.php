<?php

function fieldHidden($name, $value = '') {
	return "<input type=\"hidden\" name=\"$name\" value=\"$value\">\n";
}

function fieldSelect($name, $catalogo, $seleccionados = array(), $atributos = array()) {
	$res = "\n<select name=\"" . $name . "\"";

	// se imprimen los atributos
	foreach($atributos as $atributo => $valor)
		$res .= " " . $atributo . "=\"" . $valor ."\"";

	$res .= ">\n";

	if($catalogo == null || !is_array($catalogo))
		$catalogo = array();


	if(is_array($seleccionados))
		for($i = 0; $i < count($seleccionados); $i++) {
			if(!is_string($seleccionados[$i]))
				$seleccionados[$i] = "{$seleccionados[$i]}";
		}
	else
		$seleccionados = array($seleccionados);


	foreach($catalogo as $clave => $valor) {
		$res .= "    <option value=\"" . $clave . "\"";

		if(in_array("{$clave}", $seleccionados))
			$res .= " selected";

		$res .= ">" . $valor . "</option>\n";
	}

	return $res . "</select>\n";
}

function fieldCheckBox($name, $value = "on", $bolean = false, $atributos = array()) {
	$res = "\n<input name=\"" . $name . "\" type=\"checkbox\" value=\"" . $value . "\"";

	// se imprimen los atributos
	foreach($atributos as $atributo => $valor)
		$res .= " " . $atributo . "=\"" . $valor ."\"";

	if($bolean == true)
		$res .= " checked"; 

	return $res . ">\n";
}

function fieldRadio($name, $value, $bolean, $atributos = array()) {
	$res = "<input name=\"" . $name . "\" type=\"radio\" value=\"" . $value . "\"";

	// se imprimen los atributos
	foreach($atributos as $atributo => $valor)
		$res .= " " . $atributo . "=\"" . $valor ."\"";

	if($bolean == true)
		$res .= " checked";

	return $res . ">";
}

function valueDate($fecha) {
	if($fecha == '0000-00-00')
		return '';

	if(preg_match('/^(\d{4})-(\d{2})-(\d{2})$/', $fecha, $aux)) {
		if($aux[2] == '00')
			$fecha = "{$aux[1]}";
		else if($aux[3] == '00')
			$fecha = "{$aux[2]}-{$aux[1]}";
		else
			$fecha = "{$aux[3]}-{$aux[2]}-{$aux[1]}";
	}
	else if(!preg_match('/^(\d{2})-(\d{2})-(\d{4})$/', $fecha))
		$fecha = '';

	return $fecha;
}

function formBlock($title, $content) {
	$description = '';
	if(is_array($title)) {
		$description = $title[1];
		$title = $title[0];
	}

	$id = 'formblock_' . preg_replace('/[^\w]+/', '', $title);

	return <<<PPP
  <div class="separador" id="{$id}_pestanna">
    <a href="#" onclick="javascript: var v = $('#{$id}_contenido').css('display') != 'block'; eval('$(\'#{$id}_pestanna\').' + (v ? 'addClass' : 'removeClass') + '(\'separador_oculto\')'); $('#{$id}_contenido').css('display', v ? 'block' : 'none'); $('#{$id}_signo').html(v ? '-' : '+'); return false; ">
      <span id="{$id}_signo">+</span> {$title}
      <span class="resumen">{$description}</span>
    </a>
  </div>
  <div class="separados" id="{$id}_contenido" style="display: none; ">
{$content}
  </div>
PPP;

}

function fieldDateInterval($start_name, $end_name, $start_value = '', $end_value = '') {

	$start_value = valueDate($start_value);
	$end_value = valueDate($end_value);

	return <<<PPP
      <input type="text" name="{$start_name}" value="{$start_value}">
      al
      <input type="text" name="{$end_name}" value="{$end_value}">
      <span>(dd-mm-aaaa)</span>

PPP;
}

