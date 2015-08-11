<?php

include '../../lib.php';



$lend = HTTPRequest::getParameter('lend');
$session = HTTPRequest::getParameter('session');

//$path_app = $parameters->getParameter('path_app');
//$path_temp = $parameters->getParameter('path_temp');


$path_app = '';
$path_temp = 'temp/';

$log = "{$path_temp}lender.log";

if(!$lend || !$session) {
    file_put_contents($log, "[" . date('Y-m-d H:i:s') . "] Solicitud incorrecta\n", FILE_APPEND);
    return null;
}


if(!HTTPRequest::isParameter('token')) {
    // solicitud de procesamiento
    if(!file_exists("{$path_app}app.{$lend}")) {
        file_put_contents($log, "[" . date('Y-m-d H:i:s') . "] [{$lend}] [{$session}] Solicitud de {$lend} denegada\n", FILE_APPEND);
        return null;
    }

    $token = $lend . time();

    $i = 0;
    while(file_exists("{$path_temp}" . md5($token . "_" . $i)) && $i < 50)
        $i++;

    if($i >= 50) {
        file_put_contents($log, "[" . date('Y-m-d H:i:s') . "] [{$lend}] [{$session}] Maximo de solicitudes permitidas para {$lend}. Ha sido denegada\n", FILE_APPEND);
        return null;
    }

    $token = md5($token . "_" . $i);

    file_put_contents("{$path_temp}token.{$session}", $lend . "\n" . $token . "\n");

//    file_put_contents($log, "[" . date('Y-m-d H:i:s') . "] [{$lend}] [{$session}] creando token {$token}\n", FILE_APPEND);

    $getcwd = getcwd() . '/';

    echo <<<PPP
{$token}
{$getcwd}{$path_temp}request.{$session}

PPP;


    return;

}




$token = HTTPRequest::getParameter('token', null, '/^\w+$/');

if(!file_exists("{$path_temp}token.{$session}")) {
    file_put_contents($log, "[" . date('Y-m-d H:i:s') . "] [{$lend}] [{$session}] Solicitud de {$lend}: no hay archivo de sesion\n", FILE_APPEND);
    return null;
}

$data = explode("\n", file_get_contents("{$path_temp}token.{$session}"));

if(!$token || $data[1] != $token || !file_exists("{$path_temp}request.{$session}")) {
    file_put_contents($log, "[" . date('Y-m-d H:i:s') . "] [{$lend}] [{$session}] Solicitud de {$lend}: {$token} no reconocido\n", FILE_APPEND);
    return null;
}


$hf = fopen("{$path_temp}request.{$session}", 'r');

$data = array();
$parameters = array();

$k = null;
while(!feof($hf) && ($line = fgets($hf))) {
    $aux = array();
    if(preg_match('/^(\w+): (.+)\s$/', $line, $aux)) {
        $data[$aux[1]] = $aux[2];
    }
    if(preg_match('/^--(\w+)-+\s$/', $line, $aux)){
        $k = $aux[1];
        $parameters[$k] = '';
    }
    else if($k)
        $parameters[$k] .= $line;
}

fclose($hf);


foreach($parameters as $k => $v)
    if(($aux = @unserialize(trim($v))))
        $parameters[$k] = $aux;

//file_put_contents($log, "[" . date('Y-m-d H:i:s') . "] [{$lend}] [{$session}] {$token} para {$data['query']}\n " . print_r($parameters, true) . "\n", FILE_APPEND);

@unlink("{$path_temp}request.{$session}");
@unlink("{$path_temp}token.{$session}");

$parameters['lend'] = $lend;
$parameters['session'] = $session;






////////////////////////////////////////////////
// test

$control = new Control(array(

    'test.test' => function ($parameters) {
        $name = $parameters->getParameter('name');

        return "Hi {$name}, im the lender";
    },

));



///////////////////////////////////////////////


echo @base64_encode(serialize(
    $control->execute(
        $data['query'],
        $parameters
    )
));



