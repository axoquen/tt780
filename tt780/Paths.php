<?php

class Paths {

    static function __clean($ruta) {
        return str_replace("\\" , "/", $ruta);
    }

    static function __split($ruta) {
        $ruta = explode("/", Paths::__clean($ruta));

        $r = array();
        foreach($ruta as $k)
            if($k != '')
                $r[] = $k;

        return $r;
    }

    static function __resolve($path_base, $path_relative) {
        $path_base = Paths::__split($path_base);
        $path_relative = Paths::__split($path_relative);

        foreach($path_relative as $v) {
            if($v == '..')
                array_pop($path_base);
            else
                array_push($path_base, $v);
        }
        
        $res = array();
        
        foreach($path_base as $v) {
            if($v == '')
                continue;

            $res[] = $v;
        }

        return $res;
    }

    static function addError($message) {
        $error = new LibBase();

        $error->addError($message);
    }

    // path_destiny y path_origin posiciones relativas al directorio de trabajo actual
    // Regresa el path para que la posicion path_origin 'vea' a la posicion path destiny
    static function difference($path_origin, $path_destiny) {
        if($path_destiny && $path_destiny{0} == "/")
            return $path_destiny . (substr($path_destiny, -1) == '/' ? '' : '/');

        $res = array();

        $actual = getcwd();

        $plain_origin = Paths::__resolve($actual, $path_origin);
        $plain_destiny = Paths::__resolve($actual, $path_destiny);
        
        $sames = array();
        for($i = 0; isset($plain_origin[$i]) && isset($plain_destiny[$i]); $i++) {
            if($plain_origin[$i] != $plain_destiny[$i])
                break;

            array_push($sames, $plain_origin[$i]);
        }

        // $i == count($same)
        for($j = count($plain_origin); $j > $i; $j--)
            $res[] = '..';

        for(; $j < count($plain_destiny); $j++) {

            $res[] = $plain_destiny[$j];
        }

        return implode('/', $res) . '/';
    }

    // path2 esta en relacion a path1, y path1 esta en relacion de la ruta actual de trabajo
    // Regresa la ruta para que el directorio de trabajo actual vea a path2
    static function compose($path1, $path2) {
        // si path2 es ruta estatica no hay operaciones
        if($path2 != '' && $path2{0} == '/')
            return $path2;

        $is_static = $path1 && $path1{0} == '/';

        // las rutas se descomponen en arreglos
        $ddir1 = Paths::__split($path1);
        $ddir2 = Paths::__split($path2);

        $res = array();

        $isDir = 0;
        foreach ($ddir1 as $directory) {
            if($directory == ".." && $isDir > 0) {
                array_pop($res);
                $isDir--;

                continue;
            }

            if($directory != "") {
                if($directory != "..")
                    $isDir++;

                array_push($res, $directory);
            }
        }

        foreach ($ddir2 as $directory) {
            if($directory == ".." && $isDir > 0) {
                array_pop($res);
                $isDir--;
                continue;
            }

            if($directory != "..")
                $isDir++;

            array_push($res, $directory);
        }

        if(count($res) == 1 && $res[0] == "")
            return "";

        $res = implode("/", $res);

        return ($is_static ? '/' : '') . $res . (is_dir($res) ? '/' : '');
    }

    static function absolute($path1) {
        
    }
}


