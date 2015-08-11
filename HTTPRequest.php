<?php

// axoquen@gmail.com

class HTTPRequest {
    static private function __isCLI() {
        return isset($_SERVER['argc']) && $_SERVER['argc'] > 1;
    }

    static private function __verifyPlaces($places) {
        if($places == 'ALL') {
            if(HTTPRequest::__isCLI()) {
                if(!is_associative($_SERVER['argv'])) {
                    $aux = $_SERVER['argv'];

                    $_SERVER['argv'] = array();
                    for($i = 1; $i < $_SERVER['argc']; $i++) {

                        if(($pos = strpos($aux[$i], "=")) === false) {
                            $_SERVER['argv'][$aux[$i]] = true;
                            continue;
                        }

                        $param = explode('=', trim($aux[$i]));

                        $_SERVER['argv'][trim($param[0])] = $param[1];
                    }
                }

                $places = array(
                    'SERVER[\'argv\']'
                );
            }
            else
                $places = array(
                    'GET',
                    'POST',
                    'COOKIE',
                );
        }
        else if(is_string($places))
            $places = explode(',', $places);

        return $places;
    }

    static private function __getParameter($names, $places = 'ALL') {
        if(!is_array($names))
            $names = array($names);

        $places = HTTPRequest::__verifyPlaces($places);

        $res = array();
        foreach($names as $name) {
            $aux = null;

            foreach($places as $p) {
                if(!eval("return isset(\$_{$p}) && is_array(\$_{$p});"))
                    continue;

                $aux = eval("return isset(\$_{$p}['{$name}']);");

                if(!$aux)
                    continue;

                $aux = eval("return \$_{$p}['{$name}'];");

                $aux = array($name, $aux, $p);

                break;
            }

            $res[] = $aux ?
                        $aux :
                        array($name, $aux, null);
        }

        return count($res) ? $res : null;
    }

////////////////////////////////////////////////////////////////////////////////
// filter parameter

    static function isParameter($name, $preg_match = null, $places = 'ALL') {
        $pre = HTTPRequest::__getParameter($name, $places);

        if(!is_array($name))
            $name = array($name);

        $c_pre = count($pre);

        if(!is_array($pre) || $c_pre != count($name))
            return false;

        for($i = 0; $i < $c_pre; $i++) {
            if($pre[$i][0] != $name[$i]
              || ($preg_match && !preg_match($preg_match, $pre[$i][1]))
              || $pre[$i][2] === null)
                return false;
        }

        return true;
    }

    static function getParameter($name, $default = null, $preg_match = null, $places = 'ALL') {
        $pre = HTTPRequest::__getParameter($name, $places);

        if(!is_array($name))
            $name = array($name);

        if(!is_array($pre))
            $pre = array();

        $res = array();

        $c_name = count($name);

        for($i = 0; $i < $c_name; $i++) {
            if($pre[$i][1] === false || ($preg_match && !preg_match($preg_match, $pre[$i][1])))
                $res[$name[$i]] = $default;
            else if(!is_string($pre[$i][1]))
                $res[$name[$i]] = $pre[$i][1];
            else {
                // sanitized
                $res[$name[$i]] =
                    get_magic_quotes_gpc() ?
                        addslashes($pre[$i][1]) :
                        $pre[$i][1];
                        
                // encoding ISO 8859-1
                if(mb_check_encoding($res[$name[$i]], 'UTF-8'))
                    $res[$name[$i]] = utf8_decode($res[$name[$i]]);
            }

            if($res[$name[$i]] == $default)
                $_GET[$name[$i]] = $default;
        }

        if($i == 0)
            return null;

        if(count($res) > 1)
            return $res;

        $keys = array_keys($res);

        return $res[$keys[0]];
    }

////////////////////////////////////////////////////////////////////////////////
// all parameters

    static function getContext($toomit = null, $places = 'ALL') {
        $res = array();

        if(is_string($toomit) && !is_array($toomit))
            $toomit = array($toomit);

        $places = HTTPRequest::__verifyPlaces($places);

        foreach($places as $p) {
            $p = eval("return \$_{$p};");

            foreach($p as $k => $v)
                if(!$toomit || !in_array($k, $toomit))
                    $res[$k] = $v;
        }

        return $res;
    }

    static function setContext($context) {
        if(!is_array($context))
            return false;

        $_REQUEST = $context;

        return true;
    }

////////////////////////////////////////////////////////////////////////////////
// serialize and unserialize in session

    function save($id, $to_omit = array(), $life_count = 0) {
        if(!Session::$active || !preg_match('/^\w+$/', $id))
            return false;

        return Session::setParameter(
            "restore_{$id}",
            serialize(HTTPRequest::getContext($to_omit)),
            $life_count
        );
    }

    function restore($id) {
        if(!Session::$active
          || !preg_match('/^\w+$/', $id)
          || !Session::isParameter("restore_{$id}"))
            return false;
        
        return HTTPRequest::setContext(
            unserialize(Session::getParameter("restore_{$id}"))
        );
    }
}

