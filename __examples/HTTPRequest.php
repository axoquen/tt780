<?php

include "../Session.php";
include "../HTTPRequest.php";

// $_GET, $_POST, $_COOKIE = $_REQUEST


/*

$_REQUEST['name']
preg_match('/^\w+$/', $_REQUEST['name']);
$name = stripslashes($_REQUEST['name']);




*/

var_dump(HTTPRequest::isParameter('one'));

$_GET['one'] = true;
$_GET['two'] = '2';

var_dump(HTTPRequest::isParameter(array('one', 'two')));

$_GET['one'] = '1';

var_dump(HTTPRequest::isParameter(array('one', 'two'), '/^\d+$/'));

$_GET = array();

var_dump(HTTPRequest::isParameter(array('one', 'two'), null, 'GET'));

echo "============================================================== <br>\n";

var_dump(HTTPRequest::getParameter('one', '1', '/\d+/'));

var_dump(HTTPRequest::getParameter(array('one', 'two'), '1', '/\d+/'));

echo "============================================================== <br>\n";

var_dump(HTTPRequest::getContext('one'), $_REQUEST);

var_dump(HTTPRequest::setContext(array('two' => 2)), $_REQUEST);

echo "============================================================== <br>\n";

