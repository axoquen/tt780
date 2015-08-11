<?php

$GLOBALS['tt780']['Parameters'] = array();

class Parameters extends LibBase {
    protected $collection;
    protected $this_ID = null;

    function __construct($parameter = null) {
        $this->collection = array();

        // 'origin' table (outside of the object)
        // to determine the origin of the name / value
        // (configuration, explicit in runtime, extent)

        if(!isset($GLOBALS['tt780']['Parameters']['origin']))
            $GLOBALS['tt780']['Parameters']['origin'] = array();

        $this->this_ID = spl_object_hash($this);

        if(!isset($GLOBALS['tt780']['Parameters']['origin'][$this->this_ID]))
            $GLOBALS['tt780']['Parameters']['origin'][$this->this_ID] = array(
                'actual' => null,
                'table' => array()
            );

        $aux = func_get_args();
        if(is_array($aux))
            foreach($aux as $p)
                $this->concat($p);
    }

////////////////////////////////////////////////////////////////////////////////

    function isParameter($name, $preg_match = null) {
        if(!is_array($name))
            $name = array($name);

        foreach ($name as $value) {
            if(!isset($this->collection[$value])
              || (
                $preg_match 
                && is_string($this->collection[$value]) 
                && !preg_match($preg_match, $this->collection[$value])
              ))
                return false;
        }

        return true;
    }

    function getParameter($name, $default = null, $preg_match = null, $autosave = false) {
        if(!is_array($name))
            $name = array($name);

        $res = array();
        foreach ($name as $value) {
            $res[$value] = $default;

            if(!isset($this->collection[$value]))
                continue;

            switch(true) {
                case $this->__isObject($value):
                    $res[$value] = $this->collection[$value]->get($default, $this);

                    break;
                // function lambda
                case is_string($this->collection[$value])
                      && strpos($this->collection[$value], 'lambda_') === 1
                      && is_callable($this->collection[$value]) :

                    $res[$value] = $this->collection[$value] = call_user_func($this->collection[$value]);
                    break;
                default:
                    $res[$value] = $this->collection[$value];
            }
        }

        $keys = array_keys($res);
        foreach($keys as $key) {
            if($preg_match && (!is_string($res[$key]) || !preg_match($preg_match, $res[$key])))
                $res[$key] = $default;

            if($autosave)
                $res[$key] = $this->setParameter($key, $res[$key]);
        }

        if(count($res) > 1)
            return $res;

        return $res[$keys[0]];
    }

    function setParameter($name, $value = null) {

        $args = $name;
        if(func_num_args() == 2 && is_string($name))
            $args = array(
                $name => $value
            );

        if(is_object($name) && get_class($name) == 'Parameters')
            $args = $name->collection;

        if(!is_array($args))
            $this->addError('Mal uso de setParameters');

        foreach($args as $k => $v) {
            switch(true) {
                case $this->__isObject($k):
                    $value = $this->collection[$k]->set($v, $this);
                    break;
                default:
                    if(!isset($this->collection[$k]))
                        $GLOBALS['tt780']['Parameters']['origin'][$this->this_ID]['table'][$k] = $GLOBALS['tt780']['Parameters']['origin'][$this->this_ID]['actual'];

                    $this->collection[$k] = $v;
            }
        }

        return $this->getParameter($name);
    }

////////////////////////////////////////////////////////////////////////////////

    function length() {
        return count($this->collection);
    }

    function concat($objParameters) {
        if(is_object($objParameters)) {
            if(is_object($objParameters) && method_exists($objParameters, 'getParameter')) {
                $keys = array_keys($objParameters->collection);
                foreach ($keys as $key) {
                    $GLOBALS['tt780']['Parameters']['origin'][$this->this_ID]['table'][$key] = $GLOBALS['tt780']['Parameters']['origin'][$this->this_ID]['actual'];

                    $this->collection[$key] = $objParameters->collection[$key];
                }
            }
        }
        else if(is_array($objParameters))
            foreach($objParameters as $name => $value) {
                $GLOBALS['tt780']['Parameters']['origin'][$this->this_ID]['table'][$name] = $GLOBALS['tt780']['Parameters']['origin'][$this->this_ID]['actual'];

                $this->collection[$name] = $value;
            }
    }

////////////////////////////////////////////////////////////////////////////////

    function toArray() {
        $res = array();

        foreach($this->collection as $key => $obj)
            $res[$key] = $obj;

        return $res;
    }

////////////////////////////////////////////////////////////////////////////////

    function __isObject($name) {
        if(!isset($this->collection[$name]))
            return false;

        if( is_string($this->collection[$name])
            && preg_match('/^(\w+)\:/', $this->collection[$name], $aux)
            && tt780_loader("parameter_{$aux[1]}")
           )
            eval("\$this->collection[\$name] = new parameter_{$aux[1]}("
                    . "\$name, "
                    . "\$this->collection[\$name], "
                    . "\$this, "
                    . "\$GLOBALS['tt780']['Parameters']['origin'][\$this->this_ID]['table'][\$name]"
                . "); ");

        return is_object($this->collection[$name])
                && method_exists($this->collection[$name], 'refresh');
    }

////////////////////////////////////////////////////////////////////////////////

    function tryParameter($list, $default = null, $preg_match = null) {
        if(!is_array($list))
            $list = array($list);

        foreach($list as $l) {
            if(preg_match('/^\w+$/', $l)) {
                $r = $this->getParameter($l, '--tryParameter--', $preg_match);
                if($r !== '--tryParameter--' && $r != null)
                    return $r;
            }
            else if(@eval("\$r = {$l};") !== false && $r != null) {
                if($preg_match && (!is_string($r) || !preg_match($preg_match, $r)))
                    continue;

                return $r;
            }
        }

        return $default;
    }

    function origin($origin = null) {
        $GLOBALS['tt780']['Parameters']['origin'][$this->this_ID]['actual'] = $origin;
    }

}
