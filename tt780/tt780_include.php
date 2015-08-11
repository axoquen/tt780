<?php

$tt780_include = array(
    'Control' => '/Control.php',
    'Handler' => '/Handler.php',
    'Parameters' => '/Parameters.php',
    'Paths' => '/Paths.php',
);


function tt780_loader($class) {
    global $lib_path;

    $path = "{$lib_path}/tt780/";

    if(class_exists($class))
        return true;

    switch(true) {
        case strpos($class, 'parameter_') === 0 :

            if(!file_exists("{$path}parameters/" . str_replace('_' , '.', $class) . ".php"))
                return false;

            if(!class_exists('parameter'))
                include_once("{$path}parameters/parameter.php");

            include_once("{$path}parameters/" . str_replace('_' , '.', $class) . ".php");

            break;
        case strpos($class, 'handler_') === 0 :
            if(!file_exists("{$path}handlers/{$class}.php"))
                return false;

            include_once("{$path}handlers/{$class}.php");

            break;
    }

    return true;
}

function tt780_error($errno, $errstr, $errfile, $errline, $errcontext) {
    if(!isset($errcontext)
      || !isset($errcontext['this'])
      || get_class($errcontext['this']) != 'Control'
      || !isset($errcontext['query'])
      || !isset($errcontext['type']))
        return false;

    echo "<b>Fatal error</b>: While trying parse the type \"<b>{$errcontext['type']}</b>\": {$errcontext['query']}<br>";
}


set_error_handler('tt780_error');










