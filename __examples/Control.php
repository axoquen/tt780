<?php

include "../LibBase.php";
include "../HTTPRequest.php";
include "../Session.php";
include "../tt780/Handler.php";
include "../tt780/Control.php";
include "../tt780/Parameters.php";
include "../tt780/Paths.php";
include "../tt780/handlers/handler_lend.php";
include "../tt780/handlers/handler_class.php";
include "../tt780/handlers/handler_module.php";
include "../tt780/handlers/handler_soap.php";
include "../tt780/parameters/parameter.php";
include "../tt780/parameters/parameter.path.php";
include "../tt780/parameters/parameter.source.php";

include "../util/print_r_reverse.php";
include "../util/printSize.php";
include "../util/printDump.php";


function is_associative($array) {
    return is_array($array) && count($array) > 0&& count(array_diff(array_keys(array_fill(0, count($array), true)), array_keys($array))) != 0;
}


function sizeofvar($var) {
    $start_memory = memory_get_usage();
    $tmp = unserialize(serialize($var));
    return memory_get_usage() - $start_memory;
}



function __test_calls($control) {
    //echo $control->execute('module_01.method_01');
    //echo $control->execute('module_02');
    //echo $control->execute('module_03.main');
    //
    //echo $control->execute('component_04.method_01');
    //echo $control->execute('component_04');
    //
    //Session::start();
    //echo $control->execute('lend_01.test');
    //
    //echo $control->execute('lambda_1.test');
    //
    //echo $control->execute('soap_1.uno', array('tres' => 'Concepto'));
}



//echo "<h1>configuration file</h1>\n";
//
//$control_01 = new Control('extras/__configuration.php');
//
//__test_calls($control_01);



echo "<h1>Control</h1>\n";

$control_02 = new Control(array(
    //'module_01' => array(
    //    'module' => array(
    //        'method_01' => 'configuration_array/module.01.php'
    //    ),
    //    'parameters' => array(
    //        'name' => 'world'
    //    )
    //),
    //
    //'component_04' => array(
    //    'component' => 'configuration_array/c4.php'
    //),
    //
    //'convention' => array(
    //    'module' => array(
    //        '/^module_(\d+)\.(\w+)$/' => array(
    //            'configuration_array/$1.php',
    //            'configuration_array/$1.$2.php'
    //        )
    //    )
    //),
    //
    //'lambda_1.test' => function ($parameters) {
    //    return 'Desde el lambda';
    //},
    //
    //'lend_01.test' => array(
    //    'lend' => 'path:extras/lender.php',
    //    'app' => 'test',
    //    'alias' => 'test.test',
    //    // 'log' => 'path:extras/temp/lend.log',
    //    // 'point' => 'lender.php',
    //),
    //
    'soap_1.verify' => array(                      // handler.method, if no method all querys translate to web service function 
        'soap' => 'CENTER',
        'web' => 'http://10.0.0.233/lib/__examples/extras/soap.php',   // other test 'http://www.archivomas.com/facturas/center/ws.users'
        // 'function' => 'verify'
        
        // 'cache' => true
    ),
    //

));


//__test_calls($control_02);


var_dump($control_02->execute('soap_1.verify', array('username' => 'angelr', 'password' => 'AXO', 'project' => 'dms'), true));


printDump($GLOBALS['tt780']);




