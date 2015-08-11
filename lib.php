<?php

if(!isset($lib_path))
    $lib_path = dirname(__FILE__) . '/';

$lib_structure = array(
    'Session' => '/Session.php',
    'HTTPRequest' => '/HTTPRequest.php',
    'LibBase' => '/LibBase.php',
);

include  "{$lib_path}/tt780/tt780_include.php";
$lib_structure['/tt780'] = $tt780_include;

////////////////////////////////////////////////////////////////////////////////

function __autoload($classname) {
    global $lib_path;
    global $lib_structure;

    $pathResolve = create_function('$value, $array, $pathResolve', '
        $path = null;

        foreach($array as $key => $target) {
            //echo $key . $target . "<br>";
            if(is_array($target) && ($aux = $pathResolve($value, $target, $pathResolve)) != null)
                return $key . $aux;
            else if($value == $key)
                return $target;
        }
    ');

    $ruta = $pathResolve($classname, $lib_structure, $pathResolve);

    if($ruta != '') {
        $path = file_exists($ruta) ? $ruta : (file_exists("{$lib_path}{$ruta}") ? "{$lib_path}{$ruta}" : false);
        
        if(!$path)
            echo "<b>__autoload:</b> El archivo '<b>{$lib_path}/{$ruta}</b>' no existe";

        require_once $path;

        if(!class_exists($classname))
            echo "<b>__autoload:</b> La clase '<b>{$classname}</b>' no esta definida en '<b>{$path}</b>'";
    }
}

////////////////////////////////////////////////////////////////////////////////
// utilerias

call_user_func(create_function('', '
    global $lib_path;

    $util = array(
        "consulta" => "util/consulta.php",
        "jsonEncode" => "util/jsonEncode.php",
        "generateRandomKey" => "util/generateRandomKey.php",
        "Workflow" => "/util/Workflow.php",
        "uList" => "/util/uList.php",
        "uForm" => "/util/uForm.php",
        "printAssoc" => "/util/printAssoc.php",
        "fields" => "/util/fields.php",
        "printDate" => "/util/printDate.php",
        "printSize" => "/util/printSize.php",
        "startFork" => "/util/startFork.php",
        
        // "l" => "/util/l.php",
        "print_r_reverse" => "/util/print_r_reverse.php",
        "printDump" => "/util/printDump.php",
    );

    foreach($util as $path)
        include_once "{$lib_path}{$path}";
'));

////////////////////////////////////////////////////////////////////////////////
// deberias

function is_date($date) {
    if(preg_match('/^(\d{4})-(\d{2})-(\d{2})$/', $date, $aux))
        return checkdate($aux[2], $aux[3], $aux[1]);

    return false;
}

function is_associative($array) {
    return is_array($array) && count($array) > 0&& count(array_diff(array_keys(array_fill(0, count($array), true)), array_keys($array))) != 0;
}

function array_top($array) {
    $c = count($array);

    if($c == 0)
        return null;

    if(is_associative($array)) {
        $keys = array_keys($array);
        return $array[$keys[$c - 1]];
    }

    return $array[$c - 1];
}


function sizeofvar($var) {
    $start_memory = memory_get_usage();
    $tmp = unserialize(serialize($var));
    return memory_get_usage() - $start_memory;
}




