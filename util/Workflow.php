<?php

// axoquen@gmail.com - Enero 2011

class Workflow extends LibBase{
    var $flows = array();
    var $sincro = "proc";
    var $control = null;
    var $progresion = null;

    function Workflow($arr = null) {
        if(is_array($arr)) {
            foreach($arr as $k => $v)
                if(isset($this->$k) || $k == 'control')
                    $this->$k = $v;
        }
        else
            $this->control = $arr;
    }

    // agrega un nuevo flujo,
    function addFlow($id_flow, $phases) {
        // estructura de la lista de fases para el flujo
        // $phases = array(
        //     array(
        //         "nombre_de_metodo",
        //        array(
        //             "parametro 1",
        //             "parametro 2",
        //             "parametro 3",
        //             ...
        //         )
        //  ),
        //     ...
        // )

        $this->flows[$id_flow] = $phases;
    }

    function flow($id_flow, $extra_parameters = array()) {

        // si no hay cursor del flujo o la variable de paso no es valida el flujo inicia desde la fase 0
        if($this->__s_gp("cursor_{$this->sincro}_{$id_flow}", -2) == -2 || $this->__h_gp($this->sincro, -1) != $this->__s_gp($this->sincro, -2))
            $this->__s_sp("cursor_{$this->sincro}_{$id_flow}", 0);

        // si es valida la variable de paso, el flujo continua a la siguiente fase
        if($this->__h_gp($this->sincro, -1) == $this->__s_gp($this->sincro, -2))
            $this->__s_sp("cursor_{$this->sincro}_{$id_flow}", $this->__s_gp("cursor_{$this->sincro}_{$id_flow}", 0) + 1);

        // progresion
        if($this->progresion == null || !is_callable($this->progresion))
            $this->__s_sp($this->sincro, $this->__s_gp($this->sincro, 0) + 1);
        else
            $this->__s_sp($this->sincro, $this->progresion($this->__s_gp($this->sincro, '')));

        $cursor = $this->__s_gp("cursor_{$this->sincro}_{$id_flow}");
        $parameters = array_merge($extra_parameters, array($this->sincro => $this->__s_gp($this->sincro, 0)));

        // 20150223
        // si ocurre un error en la ultima etapa, al regresar y solicitar la siguiente iteracion, se genera una posicion no valida y se cicla
        if(!isset($this->flows[$id_flow][$cursor])) {
            $_SESSION["cursor_{$this->sincro}_{$id_flow}"] = -2;
            $cursor = 0;
        }

        return $this->__dispatcher($this->flows[$id_flow][$cursor], $parameters);
    }

    // obtiene el numero de fase en que se encuentra el flujo $id_flujo, o si se especifica, mueve el cursor a la posicion $position
    function cursor($id_flow, $position = null) {
        if($position !== null) {
            // posicion de reinicio
            if($position > count($this->flows[$id_flow]) || $position < 0)
                return $this->__s_sp("cursor_{$this->sincro}_{$id_flow}", -2);
            else
                return $this->__s_sp("cursor_{$this->sincro}_{$id_flow}", $position);
        }

        return $this->__s_gp("cursor_{$this->sincro}_{$id_flow}");
    }

////////////////////////////////////////////////////////////////////////////////

    function __dispatcher($request_phase, $parameters) {
        if(is_callable($request_phase))
            return call_user_func($request_phase, $parameters, $this);

        if(is_array($request_phase))
            $this->addError("el metodo no fue accesible");

        if(method_exists($this, $request_phase))
            return $this->$request_phase($parameters, $this);

        if($this->control) {
            // tt780 :: is handler
            if(preg_match('/^(\w+\.\w+)(\((?:,?\s*\w+\s*=\s*\"[^\"]*\"\s*)+\))$/', $request_phase, $aux)) {
                $params = array();
                if(preg_match_all('/(\w+)\s*=\s*\"([^\"]+)\"\s*/', $aux[2], $aux_2)) {
                    for($i = 0; $i < count($aux_2[0]); $i++)
                        $params[$aux_2[1][$i]] = $aux_2[2][$i];
                }

                // parche: views_c solo lee los parametros pasados---
                $params['parameters'] = $parameters;
                // ---

                return $this->control->execute(
                        $aux[1],
                        $params
                );
            }

            // tt780
            if(method_exists($this->control, 'isExecutable') 
              && $this->control->isExecutable($request_phase)) {

                return $this->control->execute($request_phase, $parameters);
            }
        }

        $this->addError(($this->control == null ? ' No ha proporcionado un despachador.<br>' : '') . "WorkFlow no tiene acceso a la operación \"" . print_r($request_phase, true) . "\"");
    }

////////////////////////////////////////////////////////////////////////////////

    function __h_ep($name, $default = "") {
        if(isset($_GET[$name]) || isset($_POST[$name]))
            return true;

        return false;
    }

    function __h_gp($name, $default = "") {
        $res = $default;
        
        if(isset($_GET[$name]) && strlen($_GET[$name]) > 0)
            $res = $_GET[$name];
        else if(isset($_POST[$name]) && strlen($_POST[$name]) > 0)
            $res = $_POST[$name];

        return $res;
    }

    function __s_gp($name, $default = "") {
        if(Session_id() == null)
            $this->addError("WorkFlow necesita que se haya iniciado una sesion");

        $res = $default;

        if(isset($_SESSION[$name]))
            $res = $_SESSION[$name];

        return $res;
    }

    function __s_sp($name, $value) {
        if(Session_id() == null)
            $this->addError("WorkFlow necesita que se haya iniciado una sesion");

        return $_SESSION[$name] = $value;
    }
}
