<?php

function printDump($var, $title = '') {
    
    $printBigAssoc = function ($assoc, $t = 1) use (&$printBigAssoc) {
    
        $is_object = null;
        if(is_array($assoc) || ($is_object = is_object($assoc))) {
            $cassoc = $is_object ? get_class($assoc) : count($assoc);
            $mt = 20;
    
            $display = "display: " . ($t == 1 ? 'block' : 'none') . ';';
            $script = (!$is_object && $cassoc > 0) ?
                            "> <div style=\"position: absolute; top: 1px; left: -15px; width: 10px; height: 10px; border: 1px solid #000; background: #fff; cursor: pointer; line-height: 8px; text-align: center; \" onclick=\"javascript: var list=this.parentNode.parentNode.getElementsByClassName('list')[0]; var is_hidden = list.style.display == 'none'; list.style.display= is_hidden ? 'block' : 'none'; this.innerHTML = is_hidden ? '-' : '+'; \">+</div" :
                            '';
    
            if($is_object) {
                $assoc = print_r_reverse(print_r($assoc, true));
                $title = 'Object';
            }
    
            $is_associative = is_associative($assoc);
            
            if(!$is_object)
                $title = $is_associative ? 'Associative' : 'Array';
    
            $contenido = "<span{$script}>{$title} ({$cassoc})</span><div class=\"list\" style=\"{$display}\">\n";
            foreach($assoc as $k => $v) {
                $v = $printBigAssoc($v, $t + 1);

                if($is_associative)
                    $k = "<span style=\"font-weight: bold; \">{$k}</span>";

                $contenido .= "
                    <div style=\"margin-left: {$mt}px; position: relative; top: 0px; left: 0px; \">
                       {$k} : {$v}
                    </div>
                ";

            }
    
            return $contenido . "</div>\n";
        }
    
    
        if(is_bool($assoc))
            return $assoc ? 'TRUE' : 'FALSE';
    
        if(is_null($assoc))
            return 'NULL';
    
        return $assoc;
    };
    
    
    
    $title = $title ?
            "<div style=\"position: absolute; top: 3px; left: 10px; font-size: 15px; font-weight: bold; color: #666; \">{$title}</div>" :
            '';

    echo "<div style=\"position: absolute; top: 50px; right: 50px; width: 600px; height: 400px; border: 1px solid #000; background: #f0f0f0; \">"
        . $title
        . "<div style=\"position: absolute; top: 5px; right: 12px; font-size: 15px; font-weight: bold; color: #666; \">" . printSize(sizeofvar($var)) . "</div>"
        . "<div style=\"position: absolute; top: 28px; left: 10px; right: 10px; bottom: 10px; overflow: auto; font-size: 13px; \">"
        . $printBigAssoc( $var )
        . "</div>"
        . "</div>";

}

