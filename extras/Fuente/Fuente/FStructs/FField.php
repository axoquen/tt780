<?php

abstract class FField extends PuntoCritico {
        const NOT_NULL = 1;
        const PRIMARY_KEY = 2;
        const AUTO_INCREMENT = 4;

        private $name;
        private $options = 0;

        function FField($name, $options = 0, $default = null) {
                $this->name = $name;
                foreach ($options as $opt)
                        if(abs($opt) > 4 || log($opt, 2) % 1 > 0)
                                $this->agrErr("El modificador no es valido: {$opt}");
                        else
                                $this->options |= $opt;
        }

        function isFlag($flag) {
                return $flag & $this->options;
        }

        function getOptions() {
                $res = array();

                if($this->options == 0)
                        return array();

/*
                $cifras = 1;
                for($intentos = 0; $intentos != $cifras; $intentos++)
                        if($this->options / pow(2, $intentos) >= 2)
                                $cifras++;

                $numero = $this->options;
                for($peso = $cifras; $peso >= 1; $peso--){
                        $res[] = floor($numero / pow(2, $peso - 1));
                        $numero -=  floor($numero / pow(2, $peso - 1)) * pow(2, $peso - 1)
                }
 */
 
                $exp_mayor = 1;
                for($intentos = 0; $intentos != $exp_mayor; $intentos++)
                        if($this->options / pow(2, $exp_mayor) >= 2)
                                $exp_mayor++;

                $numero = $this->options;
                for($i = $exp_mayor; $i >= 0; $i--) {
                        $pow = pow(2, $i);
                        if(floor($numero / $pow) == 1)
                                $res[] = $pow;

                        $numero -= floor($numero /$pow) * $pow;
                }

                return $res;
        }

        function getName() {
                return $this->name;
        }
}

