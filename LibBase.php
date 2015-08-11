<?php

// axoquen@gmail.com

class LibBase {

    function addError($message, $die = true) {
        $class = get_class($this);

        $errores = '';
        $stack = debug_backtrace();

        $is_tt780 = 0;

        $tt780_stack = null;
        foreach($stack as $item) {

            if(isset($item['file']) && preg_match("/lib.tt780/", $item['file']))
                continue;

            if(isset($item['class'])) {
                if("{$item['class']}{$item['type']}{$item['function']}" == 'Control->execute') {
                    if(!$tt780_stack)
                        $tt780_stack = $item['object']->getStack();

                    $i = array_pop($tt780_stack);

                    $item['function'] .= "('<i>{$i['handler_query']}</i>')";
                }

                $item['class'] .= $item['type'];
            }
            else
                $item['class'] = '';

            if(isset($item['function']) && strpos($item['function'], '(') === false)
                $item['function'] .= '()';

            if(!isset($item['file'])) {
                $item['file'] = 'No File';
                $item['line'] = '';
            }

            $errores .= "<div style=\"margin-left: 10px; \"><b>{$item['class']}{$item['function']}</b> : {$item['file']} - <span style=\"font-style: oblique; \">{$item['line']}</span></div>\n";
        }

        $message = <<<PPP
<div class="error" style="border: 1px solid #000; background-color: #f0f0f0; margin: 10px; ">
  <div style="margin: 10px; color: #f00;"><b style="color: #f00; ">{$class}</b>: {$message}</div>
  {$errores}
  <br>
</div>
PPP;


        if($die)
            die($message);

        return $message;
    }
}

