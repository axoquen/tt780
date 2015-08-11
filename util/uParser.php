<?php

// personalizar simbolos

class uParser extends LibBase {
    protected $grammar;
    protected $handlers;
    public $trace = false;

    function __construct($grammar, $handlers) {
        $lines = explode("\n", $grammar);
        $this->grammar = array();

        for($i = 0; $i < count($lines); $i++)
            if(preg_match('/(\w+)\s*-> \s*(.+)/', $lines[$i], $aux))
                $this->grammar[$aux[1]] = explode('|', $aux[2]);

        $this->handlers = $handlers;
    }

    function parse($string) {
        $lexer = new uLexer($string);

        reset($this->grammar);
        list($k, $v) = each($this->grammar);

        $res = $this->__tryRule($k, $lexer);
        if(!$lexer->eos() && $lexer->getToken() != '')
            return false;

        return $res;
    }

    function attachHandler($handler) {
        $this->handlers = $handler;
    }

    function error() {
        
        
        
    }

    protected function __tryRule($rule, $lexer, $tab = 0) {
        if(!isset($this->grammar[$rule]))
            $this->addError("La regla no existe '{$rule}'");

        if(!is_array($this->grammar[$rule]))
            $this->addError("La regla no fue declarada correctamente '{$rule}'");

        for($i = 0; $i < count($this->grammar[$rule]); $i++) {
            if(!is_array($this->grammar[$rule][$i]))
                $this->grammar[$rule][$i] = explode(' ', trim($this->grammar[$rule][$i]));

            if($this->trace !== false)
                echo ($tab != 0 ? '<br>' . implode('', array_fill(0, $tab, "&nbsp; &nbsp; &nbsp;")) : '') . "{$rule} -> " . implode(' ', $this->grammar[$rule][$i]);

            $routine = $this->__tryExpression($rule, $this->grammar[$rule][$i], $lexer, $tab + 1);

            if($this->trace !== false)
                echo ($tab != 0 ? implode('', array_fill(0, $tab, "&nbsp; &nbsp; &nbsp;")) : '') . ":: " . ($routine == true ? 'true' : 'false') . " <br>";

            if($routine === false)
                continue;

            // carga el manejador
            if(is_object($this->handlers)) {
                if(method_exists($this->handlers, "{$rule}_{$i}"))
                    return $this->handlers->{"{$rule}_{$i}"}($routine);
                else if(method_exists($this->handlers, "{$rule}"))
                    return $this->handlers->$rule($routine);
                else if(method_exists($this->handlers, "applyDefault"))
                    return $this->handlers->applyDefault($routine);
            }
            else if(is_dir($this->handlers)) {
                if(file_exists("{$this->handlers}/{$rule}_{$i}.php"))
                    return include("{$this->handlers}/{$rule}_{$i}.php");
                else if(file_exists("{$this->handlers}/{$rule}.php"))
                    return include("{$this->handlers}/{$rule}.php");
                else if(file_exists("{$this->handlers}/applyDefault.php"))
                    return include("{$this->handlers}/applyDefault.php");
            }

            return $routine;
        }

        return false;
    }

    protected function __tryExpression($rule, $expression, $lexer, $tab) {
        $sub = array();
        for($i = 0; $i < count($expression); $i++) {
            switch(true) {
                // no terminal
                case preg_match('/^\{(\w+)\}$/', $expression[$i], $aux):
                    $aux = $this->__tryRule($aux[1], $lexer, $tab);
                    if($aux === false)
                        return false;

                    if($aux !== true)
                        $sub[] = $aux;

                    break;

                // operacion
                case preg_match('/^-(\w+)-$/', $expression[$i], $aux):
                    switch($aux[1]) {
                        case 'eps': 
                            continue;

                            break;

                        case 'string':
                            $l = $lexer->getToken();

                            if($l == '"' || $l == '\'') {
                                $delimiter = $l;
    
                                $escape = false;
                                while(!$lexer->eos()) {
                                    $aux = $lexer->getToken(true);

                                    $l .= $aux;

                                    if($aux == $delimiter && !($escape = $aux == "\\"))
                                        break;
                                }

                                $sub[] = $l;
                            }
                            else {
                                $lexer->backToken();
                                return false;
                            }

                            break;

                        case 'string_id': 
                            $l = $lexer->getToken(); 
                            if(preg_match('/^\w+$/', $l))
                                $sub[] = $l;
                            else {
                                $lexer->backToken();
                                return false;
                            }

                            break;

                        case 'numeric':
                            $r = '';
                            $l = $lexer->getToken();

                            if($l == '+' || $l == '-') {
                                $r = $l;
                                $l = $lexer->getToken();
                            }

                            if(preg_match('/^\d+$/', $l))
                                $r .= $l;
                            else {
                                $lexer->backToken(); 
                                if($r != '')
                                    $lexer->backToken();

                                return false;
                            }

                            $l = $lexer->getToken();
                            if($l == '.') {
                                $r .= '.';

                                $l = $lexer->getToken();
                                if(preg_match('/^\d+$/', $l))
                                    $r .= $l;
                                else
                                    $lexer->backToken();
                            }
                            else
                                $lexer->backToken();

                            if($r)
                                $sub[] = $r;
                            else
                                return false;

                            break;

                        case 'text':
                            $aux = '';

                            do {
                                $l = $lexer->getToken(true);

                                if($l == '<') {
                                    $lexer->backToken();
                                    break;
                                }

                                $aux .= $l;

                            }while(!$lexer->eos());

                            if(!$aux)
                                return false;

                            $sub[] = $aux;

                            break;

                        case 'integer':
                            $l = $lexer->getToken();

                            if(preg_match('/^\d+$/', $l))
                                $sub[] = $l;
                            else {
                                $lexer->backToken();
                                return false;
                            }

                            break;

                        default:
                            $this->addError("El comando '{$aux[1]}' no es valido");
                    }

                    break;

                // terminal
                default:
                    $l = $lexer->getToken();
//var_dump("¬{$l}¬{$expression[$i]}¬");
                    if(strtolower($l) != strtolower($expression[$i])) {
                        $lexer->backToken();
                        return false;
                    }

                    $sub[] = $l;
            }
        }

        return count($sub) > 0 ? $sub : true;
    }

}


