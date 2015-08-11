<?php

// axoquen@gmail.com - Enero 2011

class Control extends LibBase {
    protected $id = null;         // cache identifier
    protected $cursor = array();  // stack

    static $types = array(
        'component' => 'handler_class',
        'module'    => 'handler_module',
        'lend'      => 'handler_lend',
        'soap'      => 'handler_soap',
//        'lambda'  no need handler
    );

    function __construct() {
        // the "cache"... XD
        if(!isset($GLOBALS['tt780']))
            $GLOBALS['tt780'] = array();

        // make a id to global cache
        while(true) {
            $this->id = '';
            for($i = 0; $i < 4; $i++)
                $this->id .= rand(1, 9);

           if(!isset($GLOBALS['tt780'][$this->id]))
                break;
        }

        if(func_num_args() == 0)
            $this->addError("You must give me a configuration");

        $files = func_get_args();

        foreach($files as $definition) {
            // configuration source
            //   configuration (array) = paths, parameters, declarations.. etc
            //   search_path (string) = access resources path

            // ... array
            if(is_array($definition)) {
                $GLOBALS['tt780'][$this->id]['configuration'] = $definition;
//                $GLOBALS['tt780'][$this->id]['search_path'] = dirname($_SERVER['PHP_SELF']) . '/';
                $GLOBALS['tt780'][$this->id]['search_path'] = '';
            }
            // ... file (php file with $configuration array)
            else if(is_file($definition)) {
                include $definition;
    
                $GLOBALS['tt780'][$this->id]['configuration'] = $configuration;
                
                $aux = dirname($definition);
    
                $GLOBALS['tt780'][$this->id]['search_path'] = $aux == '.' ? '' : $aux;
            }
            else
                $this->addError('Configuration is not valid');
        }

        // global parameters
        $GLOBALS['tt780'][$this->id]['globals'] = new Parameters();

        if(isset($GLOBALS['tt780'][$this->id]['configuration']['parameters'])) {
            $GLOBALS['tt780'][$this->id]['globals']->concat($GLOBALS['tt780'][$this->id]['configuration']['parameters']);
            unset($GLOBALS['tt780'][$this->id]['configuration']['parameters']);
        }

        $GLOBALS['tt780'][$this->id]['globals']->setParameter(
            'configuration_file',
            is_array($definition) ?
                $GLOBALS['tt780'][$this->id]['search_path'] . basename($_SERVER['SCRIPT_NAME']) :
                $definition
        );
    }

////////////////////////////////////////////////////////////////////////////////

    function execute($query, $extra = null, $tracking = false) {
        if(preg_match('/^\w+\.__\w+$/', $query))
            $this->addError("The '__method' form are not accesibles");

        // get path and type
        $exists = $this->__h_include($query, $tracking);

        // local parameters
        $p = array(
            'handler_file' => $exists ? $exists[0] : null,
            'handler_query' => $query
        );

        // add query to stack
        array_push($this->cursor, $p);

        if(!$exists)
            $this->addError("Handler \"{$query}\" don't exists");

        // start handler
        if(!$this->__h_start($query, $exists[0], $exists[1], $exists[2]))
            $this->addError("Handler \"{$query}\" can't start");

        // parse query
        list($handler, $method, $query, $explicit) = $this->__h_extract($query);

        $new_parameters = new Parameters();

        $new_parameters->origin('configuration_file');

        $new_parameters->concat($GLOBALS['tt780'][$this->id]['globals']);

        // load in cascade
        $hc = explode('_', $handler);
        $hc_c = '';
        foreach($hc as $h) {
            $hc_c = "{$hc_c}{$h}";
            if(isset($GLOBALS['tt780'][$this->id]['parameters'][$hc_c]))
                $new_parameters->concat($GLOBALS['tt780'][$this->id]['parameters'][$hc_c]);
            $hc_c .= '_';
        }

        $new_parameters->concat($GLOBALS['tt780'][$this->id]['parameters'][$handler]);
        $new_parameters->concat($GLOBALS['tt780'][$this->id]['parameters']["{$handler}.{$method}"]);

        $new_parameters->origin(null);

        $new_parameters->concat($extra);
        $new_parameters->concat($p);
        
        $cache = null;
        if(isset($GLOBALS['tt780'][$this->id]['cache'])
          && isset($GLOBALS['tt780'][$this->id]['cache'][$query])
          && ($cache = 'P' . @md5(@serialize($new_parameters->toArray())))
          && array_key_exists($cache, $GLOBALS['tt780'][$this->id]['cache'][$query])
        ) {
            if($tracking)
                echo "<b>return from cache: </b>" . print_r($GLOBALS['tt780'][$this->id]['cache'][$query][$cache], true);

            return $GLOBALS['tt780'][$this->id]['cache'][$query][$cache];
        }
        

        // execute
        if($exists[1] == 'lambda')
            $res = $GLOBALS['tt780'][$this->id]['includes'][$exists[2]][0]($new_parameters);
        else
            $res = $GLOBALS['tt780'][$this->id]['handler'][$exists[2]]->__tt780_execute(
                $query,
                $new_parameters
            );

        if($cache)
            $GLOBALS['tt780'][$this->id]['cache'][$query][$cache] = $res;

        // remove query from stack
        array_pop($this->cursor);

        return $res;
    }

////////////////////////////////////////////////////////////////////////////////

    function getParameter($name, $default = null) {
        // {handler}.{method}:{parameters}


        if(preg_match("/^(\w+(?:\.\w+)?):(\w+)$/", $name, $aux)) {

            if($aux[2] == 'handler_file') {
                $res = $this->__h_include("{$aux[1]}.main");

                return $res[0];
            }

            $value = null;

            // load in cascade ...
            $hc = explode('_', $aux[1]);
            $origin = null;
	    $ct = count($hc);

            for($i = $ct; $i > 0; $i--) {
                $hc_c = implode('_', $hc);

                // ... from cache
                if(isset($GLOBALS['tt780'][$this->id]['parameters'][$hc_c]) && isset($GLOBALS['tt780'][$this->id]['parameters'][$hc_c][$aux[2]])) {
                    $value = $GLOBALS['tt780'][$this->id]['parameters'][$hc_c][$aux[2]];
                    break;
                }

                // ... from configuration
                if(isset($GLOBALS['tt780'][$this->id]['configuration'][$hc_c])
                   && is_array($GLOBALS['tt780'][$this->id]['configuration'][$hc_c])
                   && isset($GLOBALS['tt780'][$this->id]['configuration'][$hc_c]['parameters'])
                   && isset($GLOBALS['tt780'][$this->id]['configuration'][$hc_c]['parameters'][$aux[2]])
                   ) {
                    $origin = true;
                    $value = $GLOBALS['tt780'][$this->id]['configuration'][$hc_c]['parameters'][$aux[2]];

                    break;
                }

                array_pop($hc);
            }

            if($value) {
                $pre = array();
                $pre[$aux[2]] = $value;

                $pre2 = new Parameters();

                if($origin)
                    $pre2->origin('configuration_file');
               
                $pre2->concat($GLOBALS['tt780'][$this->id]['globals']); // por si necesita valores de contexto
                $pre2->concat($pre);

                $res = $pre2->getParameter($aux[2], $default);

                return $res;
            }
        }

        // ???
        $cursor = count($this->cursor);
        if($cursor > 0
          && isset($this->cursor[$cursor - 1])
          && isset($this->cursor[$cursor - 1][$name])
        )
            return $this->cursor[$cursor - 1][$name];

        return $GLOBALS['tt780'][$this->id]['globals']->getParameter($name, $default);
    }

    function setParameter($name, $value) {
        if(!preg_match("/^(\w+(?:\.\w+)?):(\w+)$/", $name, $aux))
            return null;

        if(!$this->__h_include("{$aux[1]}"))
            return null;

        if(!isset($GLOBALS['tt780'][$this->id]['parameters'][$aux[1]]))
            $GLOBALS['tt780'][$this->id]['parameters'][$aux[1]] = array();

        $GLOBALS['tt780'][$this->id]['parameters'][$aux[1]][$aux[2]] = $value;

        return $value;
    }

////////////////////////////////////////////////////////////////////////////////

    function isExecutable($query) {
        $exists = $this->__h_include($query);

        if(!$exists)
            return false;

        if($exists[1] == 'lambda')
            return true;

        $this->__h_start($query, $exists[0], $exists[1], $exists[2]);
        
        list($handler, $method, $query, $explicit) = $this->__h_extract($query);

        return $GLOBALS['tt780'][$this->id]['handler'][$exists[2]]->__tt780_is_executable($query);
    }

    protected function getStack() {
        return $this->cursor;
    }

////////////////////////////////////////////////////////////////////////////////

    function addConfiguration($arr, $complement = false) {
        if(!is_array($arr) || !is_associative($arr))
            return false;

        $func = $complement ? 'array_merge_recursive' : 'array_merge';

        $GLOBALS['tt780'][$this->id]['configuration'] = $func(
            $GLOBALS['tt780'][$this->id]['configuration'],
            $arr
        );
    }

////////////////////////////////////////////////////////////////////////////////

    // parse query string
    private function __h_extract($query) {
        $explicit = null;
        $handler = '';
        $method = '';
        
        switch(true) {
            case preg_match('/^(' . implode('|', array_keys(Control::$types)) . '):\s*(.+)$/', $query, $aux):
                $explicit = array_slice($aux, 1);
                break;
            case preg_match('/^(\w+)\.(\w+)$/', $query, $aux):
                $handler = $aux[1];
                $method = $aux[2];
                break;
            default:
                $method = 'main';
                $handler = $query;
                $query = "{$handler}.{$method}";
        }

        return array($handler, $method, $query, $explicit);
    }


    // search handler using configuration parameters
    //   return array with localization/initializaion info
    //     0 => path to file handler
    //     1 => type handler
    //     2 => pointer in cache

    private function __h_include($query, $tracking = false) {
        if(!isset($GLOBALS['tt780'][$this->id]['includes']))
            $GLOBALS['tt780'][$this->id]['includes'] = array();

        if($tracking)
            echo "<b>include</b>: {$query}. <br>\n";

        // in "cache"
        if(isset($GLOBALS['tt780'][$this->id]['includes'][$query])) {
            if($tracking)
                echo "localization in cache<br>\n";
            return $GLOBALS['tt780'][$this->id]['includes'][$query];
        }


        list($handler, $method, $query, $explicit) = $this->__h_extract($query);

        // search
        
        // ... resource extern to configuration file,
        // "in-line" declared, run-time query  :       component:/path/file/class.php
        if($explicit) {
            if(!file_exists($explicit[1]))
                return null;

            if($tracking)
                echo "extern configuration: {$explicit[1]}<br>\n";

            return $GLOBALS['tt780'][$this->id]['includes'][$query] = array(
                $explicit[1],
                $explicit[0],
                $query
            );
        }
        
        // ... resource lambda
        if(
          isset($GLOBALS['tt780'][$this->id]['configuration'][$query])
          && is_object($GLOBALS['tt780'][$this->id]['configuration'][$query])
          && ($GLOBALS['tt780'][$this->id]['configuration'][$query] instanceof Closure)) {
            
            if($tracking)
                echo "explicitly declared like: lambda function <br>\n";
            
            return $GLOBALS['tt780'][$this->id]['includes'][$query] = array(
                $GLOBALS['tt780'][$this->id]['configuration'][$query],
                'lambda',
                $query
            );
        }


        // ... resource explicitly declared
        if(
           ($pointer = (isset($GLOBALS['tt780'][$this->id]['configuration']["{$handler}.{$method}"]) ? "{$handler}.{$method}" : null))
           ||
           ($pointer = (isset($GLOBALS['tt780'][$this->id]['configuration'][$handler]) ? $handler : null))
          ) {
            if($tracking)
                echo "explicitly declared in configuration like: '{$pointer}'<br>\n";

            // extends... 20140206
            if($pointer == "{$handler}.{$method}" && isset($GLOBALS['tt780'][$this->id]['configuration'][$handler]))
                $GLOBALS['tt780'][$this->id]['configuration'][$pointer] = array_merge(
                    $GLOBALS['tt780'][$this->id]['configuration'][$handler],
                    $GLOBALS['tt780'][$this->id]['configuration']["{$handler}.{$method}"]
                );

            foreach(Control::$types as $t_key => $t_handler)
                if(is_array($GLOBALS['tt780'][$this->id]['configuration'][$pointer])
                  && isset($GLOBALS['tt780'][$this->id]['configuration'][$pointer][$t_key])) {
                    if($tracking)
                        echo "\t type explicitly declared like \"{$t_key}\" whit \"{$GLOBALS['tt780'][$this->id]['configuration'][$pointer][$t_key]}\"<br>\n";

                    if(!class_exists($t_handler))
                        tt780_loader($t_handler);

                    // $query
                    // $uri    ... uri, configuration string of the handlar
                    // $search ... if the values on uri string these need adjust in relation to this path 
                    // $in_configuration ... parameters
                    // $pointer ... $query string is no necesary the same key on the configuration file (pointer)

                    eval("
                        \$res = {$t_handler}::__TT780_include(
                            '{$query}',
                            \$GLOBALS['tt780'][\$this->id]['configuration'][\$pointer][\$t_key],
                            \$GLOBALS['tt780'][\$this->id]['search_path'],

                            \$GLOBALS['tt780'][\$this->id]['configuration'][\$pointer],
                            \$pointer
                        );
                    ");

                    if($tracking)
                        echo "\t including with " . ($res ? print_r($res, true) : '--') . "<br>\n";

                    return
                        $res ?
                            ($GLOBALS['tt780'][$this->id]['includes'][$query] = array(
                                $res,
                                $t_key,
                                $pointer
                            )) :
                            null;
                }
                
        }


        // ... resource localization by conventions
        if(!isset($GLOBALS['tt780'][$this->id]['configuration']['convention']))
            return null;

        $h = array_keys($GLOBALS['tt780'][$this->id]['configuration']['convention']);

        foreach($h as $mode) {
            // clean plural name ¬¬
            // $mode = "{$t_key}s";
 
            $t_key = preg_replace('/s$/', '', $mode);

            if(!isset(Control::$types[$t_key]))
                continue;

            $t_handler = Control::$types[$t_key];

            if($tracking)
                echo "localization by conventions: like {$mode}<br>\n";

            if(!isset($GLOBALS['tt780'][$this->id]['configuration']['convention'][$mode]))
                continue;

            if(!is_array($GLOBALS['tt780'][$this->id]['configuration']['convention'][$mode]))
                $GLOBALS['tt780'][$this->id]['configuration']['convention'][$mode] = array(
                    $GLOBALS['tt780'][$this->id]['configuration']['convention'][$mode]
                );

            foreach($GLOBALS['tt780'][$this->id]['configuration']['convention'][$mode] as $reg_exp => $template) {
                if($tracking)
                    echo "localization by conventions: try {$reg_exp} <br>\n";

                $last = error_get_last();

                $preg_match = @preg_match($reg_exp, $query, $aux);

                if(($err = error_get_last()) !== null
                  && $err['message'] != $last['message'] 
                  && $err['file'] != $last['file'] 
                  && $err['line'] != $last['line'])
                    trigger_error(
                              defined('TT780_DEBUG') ? 
                                  ("Regular expresion error: '{$reg_exp}': " . $err['message']
                                    . "<br>In convention configuration \"" . $GLOBALS['tt780'][$this->id]['globals']->getParameter('configuration_file') . "\"")  :
                                  "System Error",
                              E_USER_ERROR
                    );

                if($preg_match === false) {
                    if($tracking)
                        echo "... NO<br>\n";

                    continue;
                }

                $replaces = array(
                    '__HANDLER__' => $handler,
                    '__METHOD__' => $method
                );

                for($i = 1; $i < count($aux); $i++)
                    $replaces["\${$i}"] = $aux[$i];

                if(!is_array($template))
                    $template = array($template);

                foreach($template as $t) {
                    if(!class_exists($t_handler))
                        tt780_loader($t_handler);

                    if($tracking)
                        echo "&nbsp; &nbsp; search in: {$t}";

                    $res = Paths::compose(
                        $GLOBALS['tt780'][$this->id]['search_path'],
                        str_replace(array_keys($replaces), $replaces, $t)
                    );

                    // check if can include
                    eval("
                        \$exists = {$t_handler}::__TT780_include(
                            '{$query}',
                            '{$res}'
                        );
                    ");

                    if(!$exists) {
                        if($tracking)
                            echo "... NO<br>\n";

                        continue;
                    }

                    if($tracking) {
                        echo "... <b>YES</b><br>\n";
                        echo "&nbsp; &nbsp; &nbsp; {$t_key}:{$handler} {$res}";
                    }

                    return $GLOBALS['tt780'][$this->id]['includes'][$query] = array(
                        $res,
                        $t_key,
                        $handler
                    );
                }
            }
        }

        return null;
    }


    // get and create a handler for each call, attach parameters tree and prevent duplication of objects
    private function __h_start($query, $uri, $type, $pointer) {
        list($handler, $method, $query, $explicit) = $this->__h_extract($query);

        if(preg_match('/^([a-zA-Z0-9]+)_/', $handler, $aux))
            $this->__loadParameters($aux[1]);

        // start from convention
        if(!isset($GLOBALS['tt780'][$this->id]['handler'][$pointer])) {
            if($type != 'lambda')
                try {
                    if(!class_exists(Control::$types[$type]))
                        tt780_loader(Control::$types[$type]);
    
                    eval("\$GLOBALS['tt780'][\$this->id]['handler'][\$pointer] = new " . Control::$types[$type] . "(\$this); ");
                }
                catch(Exception $e) {
                    $this->addError('No handler created');
                }

            $this->__loadParameters($handler);
        }

        $this->__loadParameters("{$handler}.{$method}");


        if($type == 'lambda')
            return true;

        // method start
        $res = $GLOBALS['tt780'][$this->id]['handler'][$pointer]->__tt780_start(
            $query,
            $uri,
            isset($GLOBALS['tt780'][$this->id]['configuration'][$pointer]) ?
                $GLOBALS['tt780'][$this->id]['configuration'][$pointer] :
                null
        );
        
        // save on cache explicity
        if(isset($GLOBALS['tt780'][$this->id]['configuration'][$query])
           && isset($GLOBALS['tt780'][$this->id]['configuration'][$query]['cache'])
           && $GLOBALS['tt780'][$this->id]['configuration'][$query]) {

            if(!isset($GLOBALS['tt780'][$this->id]['cache']))
                $GLOBALS['tt780'][$this->id]['cache'] = array();

            if(!isset($GLOBALS['tt780'][$this->id]['cache'][$query]))
                $GLOBALS['tt780'][$this->id]['cache'][$query] = array();
        }

        if(!$res) {
            $this->addError("No service started");
            return false;
        }

        return true;
    }

    // load array parameters in "cache"
    private function __loadParameters($scope) {
        // check "cache"
        if(!isset($GLOBALS['tt780'][$this->id]['parameters'][$scope]))
            $GLOBALS['tt780'][$this->id]['parameters'][$scope] = array();

        // check declaration
        if(!isset($GLOBALS['tt780'][$this->id]['configuration'][$scope]))
            return;

        $explicit = $GLOBALS['tt780'][$this->id]['configuration'][$scope];

        if(!is_array($explicit) || !isset($explicit['parameters']) || !is_array($explicit['parameters']))
            return;

        // get entries without overwriting
        foreach($explicit['parameters'] as $k => $v) {
            if(!isset($GLOBALS['tt780'][$this->id]['parameters'][$scope][$k]))
                $GLOBALS['tt780'][$this->id]['parameters'][$scope][$k] = $v;
        }
    }

    
    // deprecated
    //function getInclude($query) {
    //    return ($res = $this->__h_include($query)) ?
    //                $res[0] :
    //                '';
    //}
}

