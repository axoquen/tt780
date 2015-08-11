<?php

class parameter_path extends parameter {
    private $value = null;
    
    function parameter_path($name, $value, $parameters) {
        $paths = new Paths();

        $this->value = $paths->compose(
            dirname($parameters->getParameter('configuration_file')),
            substr($value, 5) . '/'
        );
    }

    function get($value) {
        return $this->value;
    }

    function set($value) {
        $this->value = $value;

        return $this->value;
    }
}