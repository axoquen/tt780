<?php

class parameter_source extends parameter {
    private $target = null;

    function parameter_source($name, $value, $parameters, $origin = null) {
        $default = null;

        if(preg_match('/^source:([^:]+)(?:\:(.+))?$/', $value, $aux)) {
            $value = $aux[1];
            $default = isset($aux[2]) ? $aux[2] : null;
        }

        if($origin == 'configuration_file') {
            $paths = new Paths();

            $this->target = $paths->compose(dirname($parameters->getParameter('configuration_file')), $value);
        }
        else
            $this->target = $value;

        //$this->__save(null, $default);
        
        if(!file_exists($this->target)) {
            if(!@touch($this->target))
                return $this->addError('can\'t create the target: ' . $this->target);
        }

    }

    function __save($value = null, $default = null) {
        if(!file_exists($this->target)) {
            if(!@touch($this->target))
                return $this->addError('can\'t create the target: ' . $this->target);

            if($default)
                $value = $default;
        }

        //if(!$value)
        //    return true;

        // formato
        switch(true) {
            case strtolower(substr($this->target, -6)) == '.array':
                $value = "<?php\n\n\$array = " . $this->__printArray(!is_array($value) ? array() : $value) . ";\n\n";
                break;
            case strtolower(substr($this->target, -4)) == '.txt':
                break;
            default:
                $value = serialize($value);
        }

        $fp = fopen($this->target, 'w');
        fwrite($fp, $value);
        fclose($fp);

        return true;
    }

    function get($value, $parameters) {
        if(!file_exists($this->target))
            return $value;

        // formato
        switch(true) {
            case strtolower(substr($this->target, -6)) == '.array':
                include $this->target;
                $res = isset($array) ? $array : null;
                break;
            case strtolower(substr($this->target, -4)) == '.txt':
                $res = '';
                $fp = fopen($this->target, 'r');
                while($fp && !feof($fp) && ($line = fgets($fp)))
                    $res .= $line;
                fclose($fp);
                break;
            default:
                $fp = fopen($this->target, 'r');
                $res = unserialize(fgets($fp));
                fclose($fp);
        }

        return $res;
    }

    function set($value, $parameters) {
        return $this->__save($value) ? $value : null;
    }

////////////////////////////////////////////////////////////////////////////////

    function __printArray($array, $tab = 1) {
        $t = '';
        for($i = 0; $i < $tab; $i++)
            $t .= "\t";

        $is_associative = is_associative($array);

        $res = '';
        foreach($array as $k => $v) {
            switch(true) {
                case is_string($v) && strpos($v, "\n") :
                    $v = str_replace(array('\$', '$'), '\$', $v);
                    $v = "<<<PPP
{$v}

PPP

";
                    break;
                case is_string($v):
                    $v = "'" . str_replace("'", "\\'", $v) . "'";
                    break;
                case is_bool($v):
                    $v = $v ? 'true' : 'false';
                    break;
                case is_array($v):
                    $v = $this->__printArray($v, $tab + 1);
                    break;
                //case is_callable($v):
                //    $v = $v();
                //    break;
            }

            $res .= $is_associative ?
                        "{$t}\"{$k}\" => {$v},\n" :
                        "{$t}{$v},\n";
        }

        $t = $tab > 1 ? substr($t, ($tab - 1) * -1) : '';

        return "array(\n{$res}{$t})";
    }
}