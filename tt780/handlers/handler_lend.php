<?php


// solo en ambientes linux, la ejecucion remota es por ssh y scp

class handler_lend extends Handler {
    static protected $trace = false;        // show steps... debug 
    protected $control = null;
    
    static $hl = array();
    protected static $hf = array();


    // $uri = url remote "{user}@{host}[:{port}]" (no password soported),
    //          or local path "path:{path_to_lender_script[_file]}"
    // $in_configuration['app'] = id of client
    // $in_configuration['point'] = optional, lender script name , default: "index.php"
    // $in_configuration['alias'] = optional, final name of handler on lender system
    // $in_configuration['log'] = optional, save log messages , format: "path:{file}"

    static function __tt780_include($query, $uri, $search = null, $in_configuration = null, $pointer = null) {
        if(handler_lend::$trace)
            var_dump(Handler::__from());

        if(!is_string($uri))
            return null;
        
        $connectTo = $uri;

        if(preg_match('/^(\w+)@([\w\.\-]+)(?:\:(.+))?$/', $connectTo, $aux))
            handler_lend::$hl[$query] = array(
                'type' => 'ssh-client',
                'conf' => $connectTo,
                'user' => $aux[1],
                'host' => $aux[2],
                'point' => isset($aux[3]) ? $aux[3] : 'index.php'
            );
        else if(preg_match('/path:(.*)/', $connectTo, $aux)) {
            $paths = new Paths();

            handler_lend::$hl[$query] = array(
                'type' => 'local',
                'path' => $paths->compose(
                    $search,
                    dirname($aux[1]) . '/'
                ),
                'point' => isset($in_configuration['point']) ? $in_configuration['point'] : basename($aux[1])           // 'index.php'
            );

            if(!handler_lend::$hl[$query]['point'])
                handler_lend::$hl[$query]['point'] = 'index.php';
        }

        if(isset(handler_lend::$hl[$query])) {
            handler_lend::$hl[$query]['search'] = $search;

            if($in_configuration) {
                if(isset($in_configuration['log']))
                    handler_lend::$hl[$query]['log'] = Paths::compose(
                        handler_lend::$hl[$query]['search'],
                        substr($in_configuration['log'], 5)
                    );

                if(isset($in_configuration['alias']) && preg_match('/^\w+(\.\w+)?$/', $in_configuration['alias'])) {
                    handler_lend::$hl[$query]['alias'] = array(
                        $pointer,
                        $in_configuration['alias']
                    );
                }

            }
        }

        return handler_lend::__lend_test($query);
    }


    function __tt780_start($query, $contactTo, $in_configuration) {
        if(handler_lend::$trace)
            var_dump(Handler::__from());

        if(!isset($in_configuration['app'])) {
            $m = 'No hay identificador de aplicacion "app"';
            error_log($query . ": {$m}");
            $this->addError($m);
        }

        $session = session_id();

        //if(!isset(handler_lend::$hl[$query]['log']) && isset($in_configuration['log']) && strpos($in_configuration['log'], 'path:') === 0)
        //    handler_lend::$hl[$query]['log'] = Paths::compose(
        //        handler_lend::$hl[$query]['search'],
        //        substr($in_configuration['log'], 5)
        //    );

        $data = array(
            'app' => $in_configuration['app'],
            'session' => $session,
            'point' => handler_lend::$hl[$query]['point']
        );

        Session::setParameter("lender.{$query}", $data);
        
        handler_lend::__log($query, '__tt780_start');

        return true;
    }

    function __tt780_is_executable($query) {
        if(handler_lend::$trace)
            var_dump(Handler::__from());

        return true;
    }

    function __tt780_execute($query, $parameters) {
        if(handler_lend::$trace)
            var_dump(Handler::__from());

        if(!isset(handler_lend::$hl[$query]))
            $this->addError('No fue inicializado correctamente');

        $parameters = $parameters->toArray();




        $data = Session::getParameter("lender.{$query}");

        $token = handler_lend::__ssh($query, "php {$data['point']} lend={$data['app']} session={$data['session']}");

        if(!is_array($token) || count($token) == 0 || !preg_match('/^\w+$/', $token[0])) {
            $m = 'No hay token de autorizacion';
            error_log($query . ": {$m}");
            $this->addError($m);
        }

        $data['token'] = $token[0];
        $data['destiny'] = $token[1];



        $temp = tmpfile();

        $to_query = isset(handler_lend::$hl[$query]['alias']) ?
                        str_replace(handler_lend::$hl[$query]['alias'][0], handler_lend::$hl[$query]['alias'][1], $query) :
                        $query;

        fwrite($temp, "query: {$to_query}\n\n");

        foreach($parameters as $k => $v) {
            if($k == 'configuration_file')
                continue;
            
            fwrite($temp, "--{$k}---------------------\n" . serialize($v) . "\n\n");

            handler_lend::__log($query, "=> {$k}: " . (is_object($v) || is_array($v) ? serialize($v) : $v));
        }

        $about_temp = stream_get_meta_data($temp);

        handler_lend::__scp($query, $about_temp['uri'], $data['destiny']);

        unlink($about_temp['uri']);

        $output = handler_lend::__ssh($query, "php {$data['point']} lend={$data['app']} token={$data['token']} session={$data['session']}");

        handler_lend::__log($query, '-----');
        try {
            //$res = @unserialize(@implode("", $output));
            $res =  @unserialize(@base64_decode(@implode("", $output)));

            handler_lend::__log($query, 'traduccion:' . print_r($res, true));

            return $res;
        }
        catch(Exception $e) {
            $m = 'Ha ocurrido un error en la recuperacion de la respuesta';
            error_log($query . ": {$m}");
            $this->addError($m);

            return null;
        }
    }

    
    
    
    
    
    
    
    
    
    
    
    
    function __construct($control) {
        if(handler_lend::$trace)
            var_dump(Handler::__from());

        $this->control = $control;
    }

    private static function __exec($command, $query = null) {
        if(handler_lend::$trace)
            var_dump(Handler::__from());

        if($query)
            handler_lend::__log($query, $command);

        $output = array();

        exec($command, $output);

        handler_lend::__log($query, 'resultado: ' . implode("\n", $output));

        return $output;
    }
    
    private static function __log($query, $message) {
        if(handler_lend::$trace)
            var_dump(Handler::__from());

        if(isset(handler_lend::$hl[$query]['log']))
            file_put_contents(handler_lend::$hl[$query]['log'], '[' . date('Y-m-d H:i:s') . "] [{$query}] {$message}\n", FILE_APPEND);
    }

    private static function __ssh($query, $command) {
        if(handler_lend::$trace)
            var_dump(Handler::__from());

        if(handler_lend::$hl[$query]['type'] == 'ssh-client') {
            $aux1 = basename(handler_lend::$hl[$query]['point']);
            $aux2 = dirname(handler_lend::$hl[$query]['point']);
        
            return handler_lend::__exec(
                "ssh "
                . " " . handler_lend::$hl[$query]['user'] . "@" . handler_lend::$hl[$query]['host']
                . " \"cd {$aux2} && {$command}\"  2>&1",
                $query
            );
        }

        if(handler_lend::$hl[$query]['type'] == 'local') {
            $pwd = getcwd();
            return handler_lend::__exec(
                "cd " . handler_lend::$hl[$query]['path']
                . " && {$command}  2>&1"
                . " && cd {$pwd}",
                $query
            );
        }

        return null;
    }

    private static function __scp($query, $origin, $destiny) {
        if(handler_lend::$trace)
            var_dump(Handler::__from());

        if(handler_lend::$hl[$query]['type'] == 'ssh-client')
            return handler_lend::__exec(
                "scp "
                . " {$origin}"
                . " " . handler_lend::$hl[$query]['user'] . "@" . handler_lend::$hl[$query]['host'] . ":{$destiny}",
                $query
            );

        if(handler_lend::$hl[$query]['type'] == 'local') {
//            $destiny = handler_lend::$hl[$query]['path'] . $destiny;
            $destiny = Paths::compose(handler_lend::$hl[$query]['path'], $destiny);

            return handler_lend::__exec(
                "cp {$origin} {$destiny}",
                $query
            );
        }

        return null;
    }

    static function __lend_test($query) {
        if(handler_lend::$trace)
            var_dump(Handler::__from());

        if(!isset(handler_lend::$hl[$query])) {
            error_log($query . ': El lender no fue correctamente declarado');
            return false;
        }

        $output = handler_lend::__ssh($query, "ls -l " . handler_lend::$hl[$query]['point'] . " | awk '{ print $9 }' ");

        // basename no necesariamente regresa un archivo, sino el ultimo elemento en el path, suponemos que siempre es un archivo php

        if(is_array($output)) {
            foreach($output as $o)
                if(strpos($o, handler_lend::$hl[$query]['point']) !== false) {
                    return true;
                }

            error_log($query . ': No existe el punto lender: ' . handler_lend::$hl[$query]['point']);
        }
        else
            error_log($query . ': No hubo respuesta del lender en "' . handler_lend::$hl[$query]['point'] . '"');

        return false;
    }
}


