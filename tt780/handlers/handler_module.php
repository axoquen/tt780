<?php

// axoquen@gmail.com - Enero 2011

// handler module
// simple handler, execute a script whith normal stdout handler


class handler_module extends Handler {
    private $pages = array();

    
    // $uri: a script "path:{file}",
    //        or a path location of a family script "path:{directory}" (each script is a method on the handler),
    //        or a array (k => v), where "k" is method name and "v" is script location

    static function __tt780_include($query, $uri, $search = null) {
        $query = explode('.', $query);
        
        $pages = !is_array($uri) ?
                        array(
                            $query[1] => $uri
                        ) :
                        $uri;

        if(!isset($pages[$query[1]]))
            return null;

        if(strpos('path:', $pages[$query[1]]) !== false)
            $pages[$query[1]] = str_replace('path:', '', $pages[$query[1]]);

        // adjust path to be visible from 'search' location, maybe with absolute path (realpath) its more easy... anyway
        if($search)
            $pages[$query[1]] = Paths::compose( $search, dirname($pages[$query[1]]) ) . basename($pages[$query[1]]);

        if(is_dir($pages[$query[1]]))
            $pages[$query[1]] = "{$pages[$query[1]]}{$query[1]}.php";

        return file_exists($pages[$query[1]]) ?
                $pages[$query[1]] :
                null;
    }

    function __tt780_start($query, $uri, $in_configuration) {
        $query = explode('.', $query);

        if(isset($this->pages[$query[1]]))
            return true;

        if(!file_exists($uri))
            $this->addError("The script \"{$query}\" don't exists in \"{$uri}\"");

        $this->pages[$query[1]] = $uri;

        return true;
    }

    function __tt780_is_executable($query) {
        $query = explode('.', $query);

        return isset($this->pages[$query[1]]);
    }

    function __tt780_execute($query, $parameters) {
        $query = explode('.', $query);

        if(!isset($this->pages[$query[1]]))
            $this->addError("No se supone que tenia que pasar esto");

        $control = $this->control;

        $return = include $this->pages[$query[1]];

        return $return !== 1 ?
                        $return :
                        null;
    }

}
