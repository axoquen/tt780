<?php

include "../Session.php";
include "../HTTPRequest.php";

include "../LibBase.php";
include "../util/Workflow.php";
include "../util/uForm.php";
include "../util/printAssoc.php";

Session::start();



$uform = new uForm();


$res = $uform->generate(
    '__form',
    '__apply',
    array(
        'action' => 'repositories'
    ),
    null
);




function __form($context, $extra) {
    
    
    return <<<PPP
    <form>
    __HIDDENS__
    
    <input type="text" name="uno" value=""><br>
    <input type="submit">
    
    </form>
    
PPP;
}

function __apply($context, $extra) {
    echo "Operacion en la BD";
}

echo $res;

