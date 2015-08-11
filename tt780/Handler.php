<?php

abstract class Handler extends LibBase {
    protected $control = null;

    function __construct($control) {
        $this->control = $control;
    }
    
    static function __from() {
        $top = debug_backtrace();
        $top = $top[1];
        
        return "{$top['function']} from {$top['file']} ({$top['line']})";
    }





    // Adjust local path ($uri) in relation to working directory on run-time - pre inicialization
    //   array(
    //     uri,
    //     type
    //  )
    static function __tt780_include($query, $uri, $search_path = null, $in_configuration = null, $pointer = null) {
        // $query: la cadena solicitada
        // $uri: cadena de identificacion de tipo de handler que incluye parametros  de inicializacion
        // $search_path: ruta desde la que se hace referencia el $uri (en caso que se requiera)
        // $in_configuration: si el handler fue definido explicitamente se incluye el array de parametros
        // $pointer: si el handler fue definido explicitamente se incluye la clave que se utilizo para referenciarlo dentro del array de configuracion
        trigger_error('Implement this function', E_USER_ERROR);
    }

    // inicializa/reconfigura el servicio con el uri obtenido por la llamada al include
    abstract function __tt780_start($query, $uri, $in_configuration);
    
    abstract function __tt780_is_executable($query);

    // ejecuta el servicio con los $parameters
    abstract function __tt780_execute($query, $parameters);

}

