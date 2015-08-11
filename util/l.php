<?php

// utilidades de idioma


$l_dir = dirname(__FILE__) . "/dictionaries/";
$l_lang = 'es';

$l_track = array();
$l_dictionary = array();
$l_dictionary_evaluate = array();
$l_dictionary_changes = array();

function l($text, $level = 0) {
    global $l_track;
    global $l_dictionary;
    global $l_dictionary_evaluate;
    global $l_dictionary_changes;

    global $l_dir;
    global $l_lang;

    // obtiene el origen de la llamada
    $l_track[$text] = debug_backtrace();

    if(!isset($l_track[$text][$level])) {
        error_log("LEVEL {$level}." . print_r($l_track[$text], true));
    }
    
    
    $l_track[$text] = array(
        'file' => $l_track[$text][$level]['file'],
        'line' => $l_track[$text][$level]['line'],
    );

    // hace un id unico para el origen
    $target = $l_track[$text]['file'];
    $target = substr($target, strpos($target, "/w2/") + 4);
    $target = preg_replace('/\.php$/', '.array', str_replace('/', '-', $target));
    

    $l_track[$text]['target'] = $target;
    
    $eval = function ($text, $line) use ($l_lang, $target) {
        // no nexcesita la copia del scope actual si no el acceso a la variable global
        global $l_dictionary;
        global $l_dictionary_changes;

        if(isset($l_dictionary[$l_lang][$target][$text]))
            return $l_dictionary[$l_lang][$target][$text];

        $l_dictionary[$l_lang][$target][$text] = $text;

        
        // agrega la nueva entrada al diccionario
        if(!isset($l_dictionary_changes[$l_lang]))
            $l_dictionary_changes[$l_lang] = array();

        $l_dictionary_changes[$l_lang][$target] = $text;

        return $text;
    };

    // si ya esta cargado el diccionario
    if(isset($l_dictionary[$l_lang][$target]))
        return $eval($text, $l_track[$text]['line']);
    
    // para no evaluar constantemente si el archivo existe
    if(!isset($l_dictionary_evaluate[$target])) {
        $l_dictionary_evaluate[$target] = file_exists("{$l_dir}{$l_lang}/{$target}");

        if($l_dictionary_evaluate[$target]) {
            include "{$l_dir}{$l_lang}/{$target}";

            if(isset($array)) {
                $l_dictionary[$l_lang][$target] = $array;
                return $eval($text, $l_track[$text]['line']);
            }
        }
    }

    // si no hay un diccionario cargado y no hay archivo
    if(!isset($l_dictionary[$l_lang]))
        $l_dictionary[$l_lang] = array();

    if(!isset($l_dictionary[$l_lang][$target]))
        $l_dictionary[$l_lang][$target] = array();


    // agrega el nuevo diccionario
    if(!isset($l_dictionary_changes[$l_lang]))
        $l_dictionary_changes[$l_lang] = array();

    $l_dictionary_changes[$l_lang][$target] = $text;

    
    $l_dictionary[$l_lang][$target][$text] = $text;

    return $text;
}

function l_print() {
    global $l_track;
    global $l_dir;
    
    echo "<div style=\"position: absolute; top: 30px; right: 30px; width: 500px; height: 500px; overflow: auto; border: 1px solid #000; background: #ccc; color: #000 !important; z-index: 100000; \">";
    
    foreach($l_track as $k => $v)
        echo "<div style=\"margin: 10px; \"><span style=\"color: #f00; \">{$k}:</span> <b>{$v['target']}</b> {$v['line']}</div> ";

    echo "</div>";

}

function l_save() {
    global $l_dictionary_changes;
    global $l_dictionary;
    global $l_dir;

    if(!is_dir($l_dir) || !is_writable($l_dir))
        echo "LANGUAGE: no es posible serializar el diccionario \"{$l_dir}\"\n";

    $parameters = new Parameters();

    $i = array();
    foreach($l_dictionary_changes as $lang => $targets) {

        if(!is_dir("{$l_dir}{$lang}/")) {
            echo "LANGUAGE: \"{$lang}\" no es un directorio accesible\n";
            continue;
        }

        foreach($targets as $target => $text) {

            touch("{$l_dir}{$lang}/{$target}");

            if(!class_exists('parameter_source'))
                tt780_loader('parameter_source');

            $p = new parameter_source($target, "source:{$l_dir}{$lang}/{$target}", $parameters);
            
            ksort($l_dictionary[$lang][$target]);

            $p->set($l_dictionary[$lang][$target], $parameters);
            $i[] = "[{$lang}] {$target} ({$text})";
        }
    }

    if(count($i))
        mail("axoquen@gmail.com", '[archivo +] Nueva entrada de diccionario', "Se ha agregado una nueva entrada de diccionario: \n\n " . implode("\n", $i) . "\n\n checalo Angel ...\n\n\n");
}


