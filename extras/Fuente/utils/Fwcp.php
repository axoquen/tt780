<?php

class Fwcp extends LibBase {
/*
    S -> expresion lista_expresiones
    expresion -> ( S ) | condicion
    lista_expresiones -> operador expresion lista_expresiones  | eps
    operador -> and | or
    condicion -> elemento comparador elemento
    elemento -> funcion ( argumentos ) | valor | ( valor lista_valores )
    argumentos -> elemento lista_argumentos | eps
    lista_argumentos -> , argumentos | eps
    valor -> 'cadena' | numero | campo | fuente.campo
    lista_valores -> , valor lista_valores | eps
    comparador -> = | != | < | > | >= | <= | not like | like | not in | in
*/

    static $comparators = array();

    function aWhere($string) {
        $lex = new uLexer($string);
        return $this->aWhere_S($lex);
    }

    function aWhere_S($lex) {
        $res = $this->aWhere_expresion($lex);
        if($res == null)
            $this->addError("No es una condici&oacute;n: <b>{$lex}</b>");

        $lista = $this->aWhere_lista_expresiones($lex);
        if($lista) {
            $lista->addCondition($res);
            $res = $lista;
        }

        return $res;
    }

    function aWhere_expresion($lex) {
        $res = null;

        $token = $lex->getToken();
        if($token == "(") {
            $res = $this->aWhere_S($lex);

            $token = $lex->getToken();
            if($token != ")")
                $this->addError("Falta cierre de par&oacute;ntesis: <b>{$lex}</b>");
        }
        else {
            $lex->backToken();

            $res = $this->aWhere_condicion($lex);
        }

        return $res;
    }

    function aWhere_lista_expresiones($lex) {
        $res = null;

        $operador = strtolower($lex->getToken());

        if($operador == "and" || $operador == "or") {
            $res = $this->aWhere_expresion($lex);

            if($res == null)
                $this->addError("Error en la operaci&oacute;n: <b>{$lex}</b>");

            $lista = $this->aWhere_lista_expresiones($lex);
            if($lista != null) {
                $lista->addCondition($res);
                $res = $lista;
            }
            else {
                if($operador == "and")
                    $res = new FCAnd($res);
                else
                    $res = new FCOr($res);
            }
        }
        else
            $lex->backToken();

        return $res;
    }

    function aWhere_condicion($lex) {
        $res = null;

        $elemento_1 = $this->aWhere_elemento($lex);
        if($elemento_1 != null) {
            $comparador = $this->aWhere_comparador($lex);

            if($comparador == null)
                $this->addError($lex, "Mal formaci&oacute;n de la condici&oacute;n: comparador no valido \"" . print_r($comparador, true) . "\"");

            $elemento_2 = $this->aWhere_elemento($lex);

            if($elemento_2 == null)
                $this->addError($lex, "Mal formaci&oacute;n de la condici&oacute;n: operando derecho no valido");

            $res = new FCondition($elemento_1, $comparador, $elemento_2);
        }

        return $res;
    }

    function aWhere_comparador($lex) {
        //= | != | < | > | >= | <= | not like | like | not in | in | is | is not
        $res = null;

        if(count(FBase::$comparators) == 0)
            FBase::$comparators = array(
                "=" => array(
                    "<" => array(),
                    ">" => array()
                ), 
                "!" => array(
                    "=" => array(),
                ), 
                "<" => array(
                    "=" => array()
                ), 
                ">" => array(
                    "=" => array()
                ),
                "like" => array(),
                "in" => array(),
                "not" => array(
                    "like" => array(),
                    "in" => array()
                ),
                "is" => array(
                    "not" => array()
                )
            );

        $cursor = &FBase::$comparators;
        $token = null;

        while(($token = $lex->getToken()) != null && isset($cursor[$token])) {
            if($res == null)
                $res = array();

            $res[] = $token;
            $cursor = &$cursor[$token];
        }
        
        if($token != null && !isset($cursor[$token]))
            $lex->backToken();

        if(is_array($res)) {

            $separator = "";
            if($res[0] == "not" || $res[0] == "is")
                $separator = " ";

            $res = implode($separator, $res);
        }

        return $res;
    }

    function aWhere_elemento($lex) {
        $res = $this->aWhere_funcion($lex); 
        if($res)
            return $res;

        $res = $this->aWhere_valor($lex); 
        if($res)
            return $res;

        $token = $lex->getToken(); 

        // ( valor lista_valores )
        if($token == "(") {
            $res = $this->aWhere_valor($lex);

            if($res == null)
                $this->addError($lex, "El valor no es valido");

            $lista = $this->aWhere_lista_valores($lex);
            if($lista) {
                $lista[] = $res;
                $res = $lista;
            }
            else
                $res = array($res);

            $token = $lex->getToken();
            if($token != ")")
                $this->addError($lex, "Falta cierre de par&oacute;ntesis");

//            for($i = 0; $i < count($res); $i++)
//                $res[$i] = $this->__verifyReferenceToField($res[$i]);
//            $res = implode(",", $res);
        }

        return $res;
    }

    function aWhere_funcion($lex) {
        $token = $lex->getToken();

        // funcion ( argumentos )
        if(!isset(FBase::$functions[strtolower($token)])) {
            $lex->backToken();
            return null;
        }

        $par_open = $lex->getToken();
        
        if($par_open != '(')
            $this->addError($lex, "Uso de funcion no valida");

        $argumentos = $this->aWhere_argumentos($lex);

        $par_close = $lex->getToken();
        if($par_close != ')')
            $this->addError($lex, "No ha cerrado correctamente el parentesis"); 

        $token = strtolower($token);
        $token = FBase::$functions[$token];

        eval("\$res = new {$token}(\$argumentos); ");

        return $res;
    }

    function aWhere_argumentos($lex) {
        $elemento = $this->aWhere_elemento($lex);

        if(!$elemento)
            return null;

        if(!is_array($elemento))
            $elemento = array($elemento);

        $lista_argumentos = $this->aWhere_lista_argumentos($lex);

        if(is_array($lista_argumentos))
            $elemento = array_merge($elemento, $lista_argumentos);

        return $elemento;
    }

    function aWhere_lista_argumentos($lex) {
        $coma = $lex->getToken();
        if($coma != ',') {
            $lex->backToken();
            return null;
        }

        return $this->aWhere_argumentos($lex);
    }

    function aWhere_valor($lex) {
        $res = null;

        $case = null;
        $with_separators = false;
        $simbol = "";
        while(($token = $lex->getToken($with_separators)) != null) {
            if($case == null) {

                if(is_numeric($token) || $token == "+" || $token == "-" || $token == ".")
                    $case = "numeric";
                else if($token == "'" || $token == '"') {
                    $case = "string";
                    $with_separators = true;
                    $simbol = $token;
                }
                else if(preg_match("/^\d{4}-\d{2}-\d{2}$/", $token)) { 
                    $res = $token;
                    break;
                }
                else if(preg_match("/^\w+$/",$token)) {
                    $ot = $lex->getToken();
                     if($ot == '.') {
                         $ot = $lex->getToken();
                         if(preg_match("/^\w+$/", $ot))
                             $token = "{$token}.{$ot}";
                         else {
                             $lex->backToken();
                             $lex->backToken();
                         }
                     }
                     else
                         $lex->backToken();

                    $res = $token;
                    break;
                }
                else if($token == 'null') {
                    $res = 'null';
                    break;
                }
                else {
                    $lex->backToken();
                    break;
                }
            }

            $escape = false;
            if($case == "numeric") {
                if($token == "+" || $token == "-" || $token == ".") {
                    if(!$escape) {
                        if(($token == "+" || $token == "-") && $res != "") {
                            $lex->backToken();
                            break;
                        }

                        $escape = true;
                        $res .= $token;
                    }
                    else {
                        $lex->backToken();
                        break;
                    }
                }
                else if(is_numeric($token))
                    $res .= $token;
                else {
                    $lex->backToken();
                    break;
                }
            }
            else {
                if($token == "\\")
                    $escape = true;

                $res .= $token;

                if($token == "'" || $token == '"') {
                    if($escape)
                        $escape = false;
                    else if($res != $token && $token == $simbol)
                        break;
                }
            }
        }

        return $res;
    }

    function aWhere_lista_valores($lex) {
        $res = null;
    
        $token = $lex->getToken();
        if($token == ",") {
            $res = $this->aWhere_valor($lex);
            if($res) {
                $lista = $this->aWhere_lista_valores($lex);
                if($lista) {
                    $lista[] = $res;
                    $res = $lista;
                }
                else
                    $res = array($res);
            }
            else
                $this->addError($lex, "El valor no es valido");
        }
        else
            $lex->backToken();

        return $res;
    }

}