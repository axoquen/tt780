<?php

class parameter_session extends parameter {
    private $name = null;

    function parameter_session($name, $value, $parameters) {
        $this->name = '';
        $default = null;

        if(preg_match('/^session:([^:]+)(?:\:(.+))?$/', $value, $aux)) {
            $this->name = $aux[1];

            if(isset($aux[2]))
                $default = $aux[2];
        }

        if($default && !Session::isParameter($this->name))
            Session::setParameter($this->name, $default);
    }

    function get($value, $parameters) {
        return Session::getParameter($this->name);
    }

    function set($value, $parameters) {
        return Session::setParameter($this->name);
    }
}
