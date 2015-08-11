<?php

function printAssoc($array, $exclude = null, $format = 0) {
    $content = '';

    if(!is_array($exclude))
        $exclude = $exclude != null ? array($exclude) : array();

    if(is_array($array)) {
        switch ($format) {
            case 1:
                foreach($array as $k => $v)
                    if(!in_array($k, $exclude))
                        $content .= '<input type="hidden" name="' . $k . '" value="' . $v . '">' . "\n";
                break;
            case 0: default:
                foreach($array as $k => $v)
                    if(!in_array($k, $exclude))
                        $content .= "&{$k}={$v}";
                $content = substr($content, 1);
        }
    }

    return $content;
}