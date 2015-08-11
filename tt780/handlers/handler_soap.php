<?php

class handler_soap extends Handler {
    private $uri;
    private $web;
    private $func;

    private $cat = null;
    private $client;

    // $uri: {namespace}
    //       or "path:{file_wsdl}"
    // $in_configuration['web']: url to webservice,
    // $in_configuration['function']: optional, name of web service function, othercase take method name in the query string

    
    static function __tt780_include($query, $uri, $search = null, $in_configuration = null, $pointer = null) {
        if(strpos('path:', $uri) !== false) {
            $uri = str_replace('path:', '', $uri);

            if($search)
                $uri = Paths::compose( $search, dirname($pages[$uri]) ) . basename($pages[$uri]);

            return file_exists($uri) ?
                        $uri :
                        null;
        }

        if(preg_match('/^\w+$/', $uri))
            return $uri;

        return true;
    }

    function __tt780_start($query, $uri, $in_configuration) {
        $this->uri = $uri;

        if(isset($in_configuration['function']) && !preg_match('/^\w+$/', $in_configuration['function'])) {
            $this->addError("webservice function dont exists: \"{$in_configuration['function']}\"");
            return null;
        }

        if(!isset($in_configuration['web'])
           || !preg_match('/^(http(s?):\/{2})?[a-zA-Z0-9\_]+([a-zA-Z0-9\_\.\-]+)*(\.[a-zA-Z0-9]{2,3})+(\:\d+)?(\/[a-zA-Z0-9\_\-\s\.\/\?\%\#\&\=]*)?$/', $in_configuration['web']))
           return null;

//        if(!($file_headers = @get_headers($in_configuration['web'])) || strpos('404', $file_headers[0]) !== false)
        if(!$this->__urlExists($in_configuration['web']))
            $this->addError("No webservices on it: {$in_configuration['web']}");

        
        if(isset($in_configuration['function']))
            $this->func = $in_configuration['function'];
            
        $this->web = $in_configuration['web'];

        
        //if(!class_exists('SoapClient'))
        //    $this->addError('No php compatible');
        //try {
        //  $this->client = new SoapClient(null, array('uri' => $uri, 'location' => $in_configuration['web']));
        //}
        //catch(SoapFault $fault) {
        //    $this->addError($fault->faultstring);
        //    return null;
        //}
        //
        //// if wsdl exists...
        //if(!preg_match('/^\w+$/', $uri) || !$this->cat) {
        //    try {
        //      $functions = $this->client->__getFunctions();
        //
        //    }
        //    catch(SoapFault $fault) {
        //        $this->addError($fault->faultstring);
        //    }
        //
        //    if($functions) {
        //        $this->cat = array();
        //        foreach($functions as $f) {
        //            $aux = array();
        //            if(preg_match('/^\w+ (\w+)\(([^\)]*)/', $f, $aux))
        //                $this->cat[$aux[1]] = $aux[2];
        //        }
        //    }
        //}

        require_once(realpath(dirname(__FILE__) . '/../../extras/nusoap/nusoap.php'));

        $this->client = new nusoap_client($in_configuration['web']);


        return true;
    }

    // sin wsdl no se puede verificar ?
    function __tt780_is_executable($query) {
        return true;
    }

    function __tt780_execute($query, $parameters) {
        $query = explode('.', $query);

        if($this->func)
            $query[1] = $this->func;

        $parameters = $parameters->toArray();

        unset($parameters['configuration_file']);
        unset($parameters['handler_file']);
        unset($parameters['handler_query']);

        try {

          //$res = $this->client->{$query[1]}($parameters);
          //$res = $this->client->__soapCall($query[1], $parameters);

          $res = $this->client->call($query[1], $parameters, $this->uri);

          if(is_object($res))
            $res =  (array) $res;
          else if($res == '_null_')
            $res = null;

          
        }
        catch(SoapFault $fault) {
            $this->addError("Cliente Call Say: " . $fault->faultstring);
        }

        return $res;
    }

    
    
    
    function __urlExists($url = NULL) {
        if($url == NULL) return false;  
        $ch = curl_init($url);  
        curl_setopt($ch, CURLOPT_TIMEOUT, 5);  
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 5);  
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);  
        $data = curl_exec($ch);

        $httpcode = curl_getinfo($ch, CURLINFO_HTTP_CODE);  
        curl_close($ch);  

        return $httpcode>=200 && $httpcode<300;
    }
}

