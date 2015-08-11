<?php

class parameter_file extends parameter {
    private $path = null;
    private $name = null;
    
    function parameter_file($name, $value, $parameters, $origin = null) {
        $this->name = basename(substr($value, 5));

        $paths = new Paths();
        $this->path = $paths->compose(
            dirname($parameters->getParameter('configuration_file')),
            substr(dirname($value) . '/', 5)
        );
    }

    function get($value) {
        return $this->path . $this->name;
    }

    function set($value) {
        $this->name = basename($value);
        $this->path = dirname($value) . '/';

        return $this->path . $this->name;
    }
}