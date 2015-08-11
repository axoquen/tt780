<?php

// axoquen@gmail.com - Enero 2011

class uForm extends Workflow {
    protected $functions_array = array();
    protected $variable_mode = "modo";

    protected $context = array();
    protected $extra = null;

    public $flows = array(
        "1" => array(
            "__print",
            "__do",
        ),
    );

    function uForm($arr = null) {
        $this->Workflow($arr);
    }

////////////////////////////////////////////////////////////////////////////////

    function generate($function_print, $function_do, $context = array(), $extra = null) {
        $this->functions_array = array(
            $function_print,
            $function_do
        );

        $this->extra = $extra;
        $this->context = $context;

        $mode = 1;

        // si el formulario se va a actualizar, mantiene la fase en su ultima posicion
        if($this->__h_ep($this->sincro) && $this->__h_gp($this->sincro) == "refresh")
            $this->cursor($mode, $this->cursor($mode) - 1);

        // ejecuta el flujo
        return $this->flow($mode);
    }

////////////////////////////////////////////////////////////////////////////////
// fases formulario

    function __print($params) {
        $contenido = $this->__dispatcher($this->functions_array[0], $this->extra);

        if(!is_string($contenido))
            return $contenido;

        $hiddens = $this->fieldHidden($this->sincro, $params[$this->sincro]);
        $string = '?' . printAssoc($this->context);

        if(is_array($this->context) && count($this->context) > 0)
            foreach($this->context as $nombre => $valor) {
                $hiddens .= $this->fieldHidden($nombre, $valor);
                $string .= "&{$nombre}={$valor}";
            }

        return str_replace(
            array(
                '__HIDDENS__',
                '__STRING__',
            ),
            array(
                $hiddens,
                $string
            )
            ,
            $contenido
        );
    }

    function __do($extra) {
        return $this->__dispatcher($this->functions_array[1], $this->extra, $this);
    }

////////////////////////////////////////////////////////////////////////////////

    function fieldHidden($name, $value = "") {
        return "<input type=\"hidden\" name=\"$name\" value=\"$value\">\n";
    }

}




