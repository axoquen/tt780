<?php

include "../Session.php";
include "../HTTPRequest.php";

include "../LibBase.php";
include "../util/Workflow.php";
include "../util/uForm.php";
include "../util/printAssoc.php";

Session::start();



$uform = new uForm();


$res = null;
do {
    $res = $uform->generate(
        '__form',
        '__apply',
        array(
            'action' => 'repositories'
        ),
        $res
    );
}
while(is_array($res));


if(is_bool($res))
    echo 'Terminado';
else
    echo $res;



function __form($extra) {


    $row = array(
        'numero' => '',
        'letra' => ''
    );
    $messages = array();

    foreach($row as $k => $v) {
        $row[$k] = str_replace(array('>', '<', '"'), '', HTTPRequest::getParameter($k));
        
        if($extra && isset($extra[$k]))
            $messages[$k] = "<span style=\"color: #f00; \">{$extra[$k]}</span><br>";
    }

    return <<<PPP
    <form>
    __HIDDENS__
    
    numero: <input type="text" name="numero" value="{$row['numero']}"> *<br>
    {$messages['numero']}
    letra: <input type="text" name="letra" value="{$row['letra']}"><br>
    {$messages['letra']}
    <input type="submit">
    
    </form>
    
PPP;
}

function __apply($extra) {
    $messages = array();

    if(!HTTPRequest::isParameter('numero', '/^\d+$/'))
        $messages['numero'] = 'El valor no es valido';

    $letra = HTTPRequest::getParameter('letra', '');
    if($letra && !preg_match('/^[A-Za-z]+$/', $letra))
        $messages['letra'] = 'El campo incluir puras letras';

     if(count($messages) > 0)
        return $messages;
    
    
    echo "Ejecutar operacion en la BD<br>";
    
    return true;

}



