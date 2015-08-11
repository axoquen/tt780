<?php

// axoquen@gmail.com - Enero 2011

// handler class
// execute a encapsulated method class whith stdout redirected to a string

class handler_class extends Handler {
    private $singleton;
    private $uri;
    private $classname;

    // $uri = local path to class file "path:{file}[:{class_name}]"
    // $in_configuration['class'] = optional, class name, default: file name in $uri
    
    static function __tt780_include($query, $uri, $search = null) {
        if(!is_string($uri))
            return null;
        
        if(strpos($uri, ':') != -1) {
            $aux = explode(':', $uri);
            $uri = $aux[0];
        }

        // adjust path to be visible from 'search' location, maybe with absolute path (realpath) its more easy... anyway
        if($uri && $search)
            $uri = Paths::compose(
                $search,
                $uri
            );

        return file_exists($uri) ?
                $uri :
                null;
    }

    function __tt780_start($query, $uri, $in_configuration) {
        // preserve the singleton
        if($this->singleton)
            return true;

        $control = $this;

        $class = isset($in_configuration['class']) ? $in_configuration['class'] : null;

        if(!$class && isset($in_configuration['component']) && strpos($in_configuration['component'], ':') != -1) {
            $aux = explode(':', $in_configuration['component']);
            if(isset($aux[1]))
                $class = $aux[1];
        }

        // verifica si tiene ruta de definicion explicita o se utiliza la convencion "nombre de archivo es nombre de clase"
        if(!$class)
            $class = preg_replace('/(\.\w+)$/', '', basename($uri));

        require_once $uri;

        if(!class_exists($class))
            $this->addError("Class '{$class}' dont exist in '{$uri}'");

        try {
            eval("\$this->singleton = new {$class}(\$this->control); ");
        }
        catch(Exception $e) {
            $this->addError('The object not created');
            return false;
        }

        $this->uri = $uri;

        return true;
    }
    
    function __tt780_is_executable($query) {
        $query = explode('.', $query);

        return
            preg_match('/^\w+$/', $query[1])
            &&
            (
              (method_exists($this->singleton, $query[1]) && is_callable(array($this->singleton, $query[1])))
              ||
              file_exists(dirname($this->uri) . '/' . get_class($this->singleton) . '.' . $query[1] . '.php')
            );
    }

    function __tt780_execute($query, $parameters) {

        $query = explode('.', $query);

        if(!method_exists($this->singleton, $query[1]) && !file_exists(dirname($this->uri) . '/' . get_class($this->singleton) . '.' . $query[1] . '.php'))
            $this->addError("The object \"{$query[0]}\" no have the operation \"{$query[1]}\"");

        if(method_exists($this->singleton, $query[1]) && !is_callable(array($this->singleton, $query[1])))
            $this->addError("\"{$query[1]}\" is not accessible");

        return $this->singleton->{$query[1]}($parameters);
    }


////////////////////////////////////////////////////////////////////////////////

    function execute($query, $extra = null) {
        return $this->control->execute(
            $query,
            $extra
        );
    }

    function __call($name, $arguments) {
        $rc = new ReflectionClass($this);

        $file = dirname($rc->getFileName());

        $file = "{$file}/" . get_class($this) . ".{$name}.php";

        if(!file_exists($file))
            $this->addError('Method extended dont exists');

        eval('$' . get_class($this) . ' = $this; ');

        $control = $this->control;
        $parameters = $arguments ? $arguments[0] : new Parameters();

        ob_start();
            $return = include($file);
            $ob = ob_get_contents();
        ob_end_clean();

        if($ob && $return !== 1)
            return $ob . $return;

        return $ob ? $ob : $return;
    }

    function __args($args, $params) {
        $res = array();

        if(!is_array($params))
            $params = array($params);

        if(count($args) == 1) {
            if(is_array($args[0])) {
                foreach($params as $k)
                    $res[$k] = isset($args[0][$k]) ? $args[0][$k] : null;
            }
            else if(is_object($args[0]) && method_exists($args[0], 'getParameter')) {
                foreach ($params as $k)
                    $res[$k] = $args[0]->getParameter($k, null);
            }
            else
                $res[$params[0]] = $args[0];
        }
        else {
            $i = 0;
            foreach ($params as $k) {
                $res[$k] = isset($args[$i]) ? $args[$i] : null;
                $i++;
            }
        }

        return $res;
    }
}

