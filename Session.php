<?php

// axoquen@gmail.com

class Session {
    public static $active = false;

    static function isParameter($name, $preg_match = null) {
//        if(HTTPRequest::getParameter("session") == "off")
        if(isset($_SERVER['argc']) && $_SERVER['argc'] >= 1)
            return isset(Session::$active[$name])
                && (!$preg_match || ($preg_match && preg_match($preg_match, Session::$active[$name])));
        
        if($preg_match && isset($_SESSION[$name]))
            return preg_match($preg_match, $_SESSION[$name]) == true;

        return isset($_SESSION[$name]);
    }

    static function getParameter($name, $pordefecto = "", $preg_match = null) {
        $res = $pordefecto;

//        if(HTTPRequest::getParameter("session") == "off") {
        if(isset($_SERVER['argc']) && $_SERVER['argc'] >= 1) {
            if(HTTPRequest::isParameter($name))
                $res = HTTPRequest::getParameter($name, $pordefecto);
            else if(isset(Session::$active[$name])
                  && (!$preg_match || ($preg_match && preg_match($preg_match, Session::$active[$name])))) {
                $res = Session::$active[$name];
            }
        }
        else if(!is_array($name) && isset($_SESSION[$name]))
            $res = $_SESSION[$name];

        if(is_array($name)) {
            $res = array();
            foreach($name as $k => $v)
                $res[$v] = isset($_SESSION[$v]) ? $_SESSION[$v] : $pordefecto;

            return $res;
        }

        return $res;
    }
    
    static function setParameter($name, $value = null, $life_count = 0) {
//        if(HTTPRequest::getParameter("session") == "off") {
        if(isset($_SERVER['argc']) && $_SERVER['argc'] >= 1) {
            Session::$active[$name] = $value;
            return $value;
        }
        
        if($value === null && is_array($name)) {
            foreach($name as $k => $v)
                $_SESSION[$k] = $v;

            return true;
        }

        $_SESSION[$name] = $value;

        if($life_count != 0)
            $_SESSION['session.life_count'][$name] = intval($life_count);

        return $value;
    }

    static function deleteParameter($name) {
//        if(HTTPRequest::getParameter("session") == "off") {
        if(isset($_SERVER['argc']) && $_SERVER['argc'] >= 1) {
            if(!isset(Session::$active[$name]))
                return false;

            $r = Session::$active[$name];
            unset(Session::$active[$name]);

            return $r;
        }

        if(!isset($_SESSION[$name]))
            return false;

        $r = $_SESSION[$name];
        unset($_SESSION[$name]);

        return $r;
    }

////////////////////////////////////////////////////////////////////////////////

    static function start() {
//        if(HTTPRequest::getParameter("session") == "off") {
        if(isset($_SERVER['argc']) && $_SERVER['argc'] >= 1) {
            Session::$active = array();

            return;
        }

        session_start();

        Session::$active = true;

        if(isset($_SESSION['session.life_count'])) {
            $keys = array_keys($_SESSION['session.life_count']);

            foreach ($keys as $k)
                if($_SESSION['session.life_count'][$k] >= 0)
                    $_SESSION['session.life_count'][$k]--;
                else {
                    unset($_SESSION['session.life_count'][$k]);
                    unset($_SESSION[$k]);
                }
        }
    }

    static function finish() {
//        if(HTTPRequest::getParameter("session") == "off") {
        if(isset($_SERVER['argc']) && $_SERVER['argc'] >= 1) {
            Session::$active = false;

            return true;
        }

        Session::$active = false;

        session_unset();
        
        if(session_id() != '')
            session_destroy();

        return true;
    }

    static function clean() {
//        if(HTTPRequest::getParameter("session") == "off") {
        if(isset($_SERVER['argc']) && $_SERVER['argc'] >= 1) {
            Session::$active = array();

            return;
        }

        if(!isset($_SESSION) || !is_array($_SESSION))
            return false;

        $_SESSION = array();
        
        return true;
    }
}

