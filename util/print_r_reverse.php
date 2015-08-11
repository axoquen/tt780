<?php




function print_r_reverse($output) {

    $expecting = 0;                     // 0=nothing in particular, 1=array open paren '(', 2=array element or close paren ')'
    $lines = explode("\n", $output);
    $result = null;
    $topArray = null;
    $arrayStack = array();
    $matches = null;

    while (!empty($lines) && $result === null) {
        $line = array_shift($lines);
        $trim = trim($line);
        if ($trim == 'Array' || preg_match('/^\w+ Object$/', $trim)) {
            if ($expecting == 0)  {
                $topArray = array();
                $expecting = 1;
            }
            else
                trigger_error("Unknown array.");
        }
        else if ($expecting == 1 && $trim == '(')
            $expecting = 2;
        // array element
        else if ($expecting == 2 && preg_match('/^\[(.+?)\] \=\>( .+)?$/', $trim, $matches)) {
            $fullMatch = $matches[0];
            $key  = $matches[1];
             $element = isset($matches[2]) ?  $matches[2] : null;

            $element = trim($element);

            if(preg_match('/^(\w+)\:(\w+)$/', $key, $attribute))
                $key = str_replace(array('private', 'protected', 'public', 'static'), array('-', '#', '+', '::'), $attribute[2]) . ' ' . $attribute[1];

            if ($element == 'Array' || preg_match('/^\w+ Object$/', $trim)) {
                $topArray[$key] = array();
                $newTopArray =& $topArray[$key];
                $arrayStack[] =& $topArray;
                $topArray =& $newTopArray;
                $expecting = 1;
            }
            else
                $topArray[$key] = $element;
        }
        // end current array
        else if ($expecting == 2 && $trim == ')') {
            if (empty($arrayStack))
                $result = $topArray;
            // pop into parent array
            else {
                // safe array pop
                $keys = array_keys($arrayStack);
                $lastKey = array_pop($keys);
                $temp =& $arrayStack[$lastKey];
                unset($arrayStack[$lastKey]);
                $topArray =& $temp;
            }
        }
        // Added this to allow for multi line strings.
        else if (!empty($trim) && $expecting == 2) {
            // Expecting close parent or element, but got just a string
            $topArray[$key] .= "\n".$line;
        }
        else if (!empty($trim))
            $result = $line;
    }

    $output = implode("\n", $lines);
    return $result;
}







