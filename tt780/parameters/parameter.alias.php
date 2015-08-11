<?php


class parameter_alias extends parameter {
    private $alias = null;
    private $def = null;

    function parameter_alias($name, $value, $parameters) {
        if(preg_match('/^alias:([^:]+)(?:\:(.+))?$/', $value, $aux)) {
            $this->alias = $aux[1];

            if(isset($aux[2]))
                $this->def = $aux[2];
        }
        else
            $this->alias = $name;
    }

    function get($value, $parameters) {
        return $parameters->getParameter($this->alias, $value ? $value : $this->def);
    }

    function set($value, $parameters) {
        return $parameters->setParameter($this->alias, $value);
    }
}
