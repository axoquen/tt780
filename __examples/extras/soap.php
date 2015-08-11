<?php



//class prueba {
//
//    function uno() {
//        return 1;
//    }
//
//    function dos($obj) {
//        //error_log(print_r(func_get_args(), true));
//        return "Esta es una prueba de {$obj['tres']} - {$obj['cuatro']}";
//    }
//
//}
//
//
//$server = new SoapServer(null, array('uri' => 'http://10.0.0.233/'));
//
//$server->setClass('prueba');
//
//$server->handle();
//
//


include "../../LibBase.php";
include "../../HTTPRequest.php";
include "../../Session.php";
include "../../tt780/Handler.php";
include "../../tt780/Control.php";
include "../../tt780/Parameters.php";
include "../../tt780/Paths.php";
include "../../tt780/parameters/parameter.php";


$control = new Control(array(
    'users_c.verify' => function ($parameters) {
//        return null;

//        return false;

        return array(
            'username' => $parameters->getParameter('username'),
            'name' => 'Prueba',
            'type' => 'test',
            'email' => 'axoquen@gmail.com'
        );
    }
));



//////////////////////////////////////////////////////////////////////////////////////////////////////////



$xml = file_get_contents('php://input');


if(!preg_match('/\<SOAP\-ENV\:Body\>\<\w+\:([^\> ]+)[^\>]*\>/', $xml, $aux)) {
    //file_put_contents('error', 'Es un error');
    die();
}


//require_once(realpath(dirname(__FILE__) . '/../lib/nusoap/nusoap.php'));
require_once(realpath(dirname(__FILE__) . '/../../extras/nusoap/nusoap.php'));


//$h = HTTPRequest::getParameter('soap');
$h = 'users';
$c = $aux[1];


preg_match('/xml [^ ]+ encoding="([^"]+)"/', $xml, $aux2);


$parser = new nusoap_parser($xml, $aux2[1], '', 1);


if(!$control->isExecutable("{$h}_c.{$c}")) {
    header($_SERVER["SERVER_PROTOCOL"]." 404 Not Found");
    die();
}



function var_error_log( $object=null ){
    ob_start();                    // start buffer capture
    var_dump( $object );           // dump the values
    $contents = ob_get_contents(); // put the buffer into a variable
    ob_end_clean();                // end capture
    return  $contents ;        // log contents of the result of var_dump( $object )
}


$parameters = $parser->get_soapbody();



$GLOBALS['res'] = $control->execute("{$h}_c.{$c}", $parameters);


$server = new soap_server;

$server->configureWSDL('hellotesting', 'CENTER');



if(is_array($GLOBALS['res'] ) && count($GLOBALS['res'] ) > 0) {
    $pre = 'tns';
    $ct = $c;
    $def = array();
    foreach($GLOBALS['res']  as $k => $v ) {
        $def[$k] = array(
            'name' => $k,
            'type' => 'xsd:string'
        );
    }

    $server->wsdl->addComplexType(
        $ct,                         // name of complex type
        'complexType',
        'struct',
        'all',
        '',
        $def
    );
}
else if(is_string($GLOBALS['res'] )) {
    $pre = 'xsd';
    $ct = 'string';
}
else if(is_integer($GLOBALS['res'] )) {
    $pre = 'xsd';
    $ct = 'integer';
}
else if(is_float($GLOBALS['res'] )) {
    $pre = 'xsd';
    $ct = 'decimal';
}
else if(is_bool($GLOBALS['res'] )) {
    $pre = 'xsd';
    $ct = 'boolean';
}
else if(!$GLOBALS['res'] ) {
    $pre = 'xsd';
    $ct = 'string';
    $GLOBALS['res']  = '_null_';
}


// var_error_log($res);
// error_log("{$pre}:{$ct}");



eval("
    function {$c}(\$parameters = null) {
        return \$GLOBALS['res'];
    }
");

$server->register($c, array(), array('return' => "{$pre}:{$ct}"), 'CENTER', false, 'rpc', 'encoded', 'Servicio dinamico');

    


$server->service( $xml );


exit();


