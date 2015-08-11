<?php

// axoquen@gmail.com
// Agosto 2011

class uList {
    protected $control = null;      // dispatcher, search TT780 Framework

    protected $cursor = "1";         
    protected $perpage = "20";

    protected $variable_cursor = "cur";
    protected $variable_perpage = "porpag";
    protected $variable_orderby = "ob";
    protected $variable_ordertype = "ot";

    protected $template_info = '';
    
    protected $version = null;

    function __construct($arr = null) {
        if(is_array($arr)) {
            foreach($arr as $k => $v) {
                if(property_exists('uList', $k) || $k == 'control')
                    $this->$k = $v;
            }
        }
        else
            $this->control = $arr;
    }

////////////////////////////////////////////////////////////////////////////////

    function generate($function_print, $filtered = null, $ordered = null, $context = null, $parameters = null) {
        if(!is_array($context))
            $context = array();

        $list_context = $context;

        list($where, $ccontexto) = $this->generateWhere($filtered, $function_print);
        list($orderby, $ocontexto) = $this->generateOrderBy($ordered, $function_print);

        $list_context = array_merge($list_context, $ccontexto);
        $list_context = array_merge($list_context, $ocontexto);


        $cursor = intval($this->__HTTPRequest_getParameter($this->variable_cursor, $this->cursor));
        $perpage = intval($this->__HTTPRequest_getParameter($this->variable_perpage, $this->perpage));

        if($cursor < 0 || intval($cursor) == 0)
            $cursor = 1;

        if($perpage <= 0)
            $perpage = 1;

        if($this->perpage != $perpage)
            $list_context[$this->variable_perpage] = $perpage;

        $start = ($cursor - 1) * $perpage;
        $end = $start + $perpage;

        // render
        if(is_callable($function_print))
            $aux = call_user_func($function_print, $start, $end, $where, $orderby, $list_context, $parameters);
        else if($this->control && $this->control->isExecutable($function_print)) {
            $pl = new Parameters($parameters);

            $pl->setParameter(array(
                'start' => $start,
                'end' => $end,
                'where' => $where,
                'order' => $orderby,
                'list_context' => $list_context
            ));

            $aux = $this->control->execute($function_print, $pl);
        }
        else
            return "uList can't execute: <b>" . print_r($function_print, true) . "</b>";

        if(!is_array($aux) || count($aux) != 2)
            return $aux;

        list($list, $total) = $aux;

        if(!is_string($list) || trim($list) == '')
            return '';

        if(!is_integer($total))
            $total = intval($total);


        $controls = array(
            'PAGES' => 'printPages',
            'INFO' => 'printInfo',
            'ORDER' => 'printAnchor',
            'PARAMETER' => 'printParameter',
        );

        if(!preg_match_all('/__(\w+)(?:\((.*)\))?__/', $list, $entry))
            return $list;

        $pre_perpage = $this->perpage;
        $this->perpage = $perpage;

        $replaces = array();
        for($i = 0; $i < count($entry[0]); $i++) {
            $entry[1][$i] = strtoupper($entry[1][$i]);

            if(!isset($controls[$entry[1][$i]]))
                continue;

            $attribs = array();
            if(isset($entry[2][$i])
              && is_string($entry[2][$i])
              && preg_match_all('/(\w+)\s*=\s*"([^"]+)"/', $entry[2][$i], $aux)) {

                for($j = 0; $j < count($aux[0]); $j++)
                    $attribs[strtolower($aux[1][$j])] = isset($aux[1][$j]) ? $aux[2][$j] : true;
            }

            $replaces[$entry[0][$i]] = $this->{$controls[$entry[1][$i]]}(
                $attribs,
                $context,
                $list_context,
                array(
                    'start' => $start,
                    'end' => $end,
                    'total' => $total,
                    'cursor' => $cursor,
                )
            );
        }

        $this->perpage = $pre_perpage;

        return str_replace(array_keys($replaces), $replaces, $list);
    }

////////////////////////////////////////////////////////////////////////////////

    function printAnchor($attributes, $context, $generated, $properties) {

        $base = array(
            'label' => '',
            'attributes' => array()
        );

        $id = $attributes['id'];

        if(isset($generated[$this->variable_orderby]) && $generated[$this->variable_orderby] == $id) {
            $aux = isset($attributes['class']) || !isset($attributes['CLASS']) ? 'class' : 'CLASS';

            $class = $generated[$this->variable_ordertype] == 'DESC' ?
                    'desc' :
                    'asc';

            $attributes[$aux] = (isset($attributes[$class]) ? "{$attributes[$class]} " : '') . $class;
        }

        foreach($attributes as $k => $v) {
            if($k == 'label') {
                $base[$k] = $v;
                continue;
            }

            $base['attributes'][] = "{$k}=\"{$v}\"";
        }

        if(!$base['label'])
            return '';

        $base['attributes'] = count($base['attributes']) ?
                ' ' . implode(' ', $base['attributes']) :
                '';

        $additional_context = $generated;

        unset($additional_context[$this->variable_ordertype]);
        unset($additional_context[$this->variable_orderby]);

        $url = $this->__printContext(
                $context + $additional_context,
                array(
                    $this->variable_orderby => $id,
                    $this->variable_ordertype =>
                        isset($generated[$this->variable_orderby]) && $generated[$this->variable_orderby] == $id ?
                            (
                              isset($generated[$this->variable_ordertype])
                                && $generated[$this->variable_ordertype] == 'DESC' ?
                                    'ASC' :
                                    'DESC'
                            ) :
                            'ASC'
                )
        );

        return "<a href=\"{$url}\"{$base['attributes']}>{$base['label']}</a>";
    }

    function printParameter($attributes, $context, $generated) {
        $aux = array();
        foreach($attributes as $k => $v) {
            if(!is_string($v))
                $v = $k;

            if(!preg_match_all('/\{(\w+)\}/', $v, $a))
                continue;

            $replaces = array();
            for($i = 0; $i < count($a); $i++)
                $replaces[$a[0][$i]] = isset($generated[$a[1][$i]]) ?
                    $generated[$a[1][$i]] :
                    (
                        isset($context[$a[1][$i]]) ?
                            $context[$a[1][$i]] :
                            ''
                    );

            $aux[] = str_replace(array_keys($replaces), $replaces, $v);
        }

        return implode('', $aux);
    }

    function printInfo($attributes, $context, $generated, $properties) {
        $shows = $this->perpage < $properties['total'] - $properties['start'] ?
                $this->perpage :
                $properties['total'] - $properties['start'];

        if($properties['total'] == 0)
            $properties['start'] = 0;

        $template = $this->template_info;
        if(!$template) {
            $sp = array(
                1, 10, 20, 50, 100
            );

            $select_perpage = '';
            foreach ($sp as $p)
                $select_perpage .= "<option value=\"{$p}\"" . ($this->perpage == $p ? ' selected' : '') . ">{$p}</option>";

            $template = <<<PPP
    <div class="info">
      <span class="info_total">Total row(s): <b>__TOTAL__</b>.</span>
      <span class="info_showing">
        Show 
        <select onchange="javascript: location.href='__CONTEXT__&{$this->variable_perpage}=' + this.options[this.selectedIndex].value;">
        {$select_perpage}
        </select>
        at time.
      </span>
      <span class="info_interval">From __START__ to __END__.</span>
    </div>
PPP;
        }

        $replaces = array(
            '__TOTAL__' => $properties['total'],
            '__START__' => $properties['start'] + 1,
            '__END__' => $properties['end'],
            '__CONTEXT__' => $this->__printContext($context + $generated)
        );

        return str_replace(array_keys($replaces), $replaces, $template);
    }

    function printPages($attributes, $context, $generated, $properties) {
        if($properties['total'] == 0)
            return '';

        $pages = (int)($properties['total']/$this->perpage) + (($properties['total'] % $this->perpage > 0)? 1 : 0);

        if($pages <= 1)
            return '';

        if($this->__HTTPRequest_isParameter($this->variable_perpage))
            $context[$this->variable_perpage] = $this->perpage;

        $str_context = $this->__printContext($context, $generated);

        switch(true) {
            case $this->version == 'pages' || (!$this->version && $pages <= 10):
                $templates = array(
                    'pages' => '<div class="pages">__BACK__ __PAGES__ __NEXT__ </div>',
                    'pages_page' => '<a href="__ACTION_PAGE__" class="page">__PAGE__</a>',
                    'pages_actual' => '<a href="__ACTION_PAGE__" class="actual">__PAGE__</a>',
                    'pages_next' => '<a href="__ACTION_NEXT__" class="next">Siguiente &gt; &gt;</a>',
                    'pages_back' => '<a href="__ACTION_BACK__" class="back">&lt; &lt; Anterior</a>',
                );

                break;
            case $this->version == 'select' || (!$this->version && $pages <= 100):
                $templates = array(
                    'pages' => "<div class=\"pages\">__BACK__ <select onchange=\"location.href='{$str_context}&{$this->variable_cursor}=' + this.options[this.selectedIndex].value; \">__PAGES__</select> __NEXT__ </div>",
                    'pages_page' => '<option value="__PAGE__">Pagina __PAGE__</option>',
                    'pages_actual' => '<option value="__PAGE__" selected>Pagina __PAGE__</option>',
                    'pages_next' => '<a href="__ACTION_NEXT__" class="next">Siguiente &gt; &gt;</a>',
                    'pages_back' => '<a href="__ACTION_BACK__" class="back">&lt; &lt; Anterior</a>',
                );

                break;
            default:
                $templates = array(
                    'pages' => "<div class=\"pages\">__BACK__ <span class=\"input\"><input type=\"text\" value=\"{$properties['cursor']}\" onkeypress=\"javascript: if(event.keyCode == 13) this.parentNode.getElementsByTagName('input')[1].onclick() \"> de <span>{$pages}</span> <input type=\"button\" class=\"go\" value=\"Ver\" onclick=\"javascript: location.href='{$str_context}&{$this->variable_cursor}=' + this.parentNode.getElementsByTagName('input')[0].value; return false; \"></span> __NEXT__ </div>",
                    'pages_next' => '<a href="__ACTION_NEXT__" class="next">Siguiente &gt; &gt;</a>',
                    'pages_back' => '<a href="__ACTION_BACK__" class="back">&lt; &lt; Anterior</a>',
                );
        }

        $strpags = '';
        $back = '';
        $next = '';

        if(isset($templates['pages_page']))
            for($i = 1; $i <= $pages; $i++)
                $strpags .= str_replace(
                    array(
                          '__ACTION_PAGE__',
                          '__PAGE__'
                    ),
                    array(
                          "{$str_context}&{$this->variable_cursor}={$i}",
                        $i
                    ),
                    $i == $properties['cursor'] ? $templates['pages_actual'] : $templates['pages_page']
                );

        if($properties['cursor'] > 1)
            $back = str_replace("__ACTION_BACK__", "{$str_context}&{$this->variable_cursor}=". ($properties['cursor'] - 1), $templates['pages_back']);

        if($properties['cursor'] < $pages)
            $next = str_replace("__ACTION_NEXT__", "{$str_context}&{$this->variable_cursor}=". ($properties['cursor'] + 1), $templates['pages_next']);

        $replaces = array(
            '__BACK__' => $back,
            '__PAGES__' => $strpags,
            '__NEXT__' => $next,
        );

        return str_replace(array_keys($replaces), $replaces, $templates['pages']);
    }

////////////////////////////////////////////////////////////////////////////////

    function __printContext($array, $extra = null) {
        if(!is_array($array))
            return '';

        if(is_array($extra))
            $array = array_merge($array, $extra);

        $str_array = '';
        foreach($array as $name => $value)
            $str_array .= "&{$name}={$value}";

        return '?' . substr($str_array, 1);
    }

    // no se puede usar $_REQUEST, en algunos scripts -- por comodidad -- se modifican $_GET o $_POST
    function __HTTPRequest_isParameter($name) {
        return isset($_GET[$name]) || isset($_POST[$name]);
    }

    function __HTTPRequest_getParameter($name, $default = "", $reg_exp = '/[^\'\;\"]/') {
        $res = $default;

        if(isset($_GET[$name]) && strlen($_GET[$name]) > 0)
            $res = $_GET[$name];
        else if(isset($_POST[$name]) && strlen($_POST[$name]) > 0)
            $res = $_POST[$name];

        if($reg_exp && !preg_match($reg_exp, $res))
            return $default;

        // se evitan caracteres reservados para sql
        return trim(preg_replace('/`|´|\'/', '', $res));
    }

////////////////////////////////////////////////////////////////////////////////

    function generateOrderBy($orders) {
        if(!is_array($orders) || count($orders) == 0)
            return array($orders, array());


        $ob = $this->__HTTPRequest_getParameter($this->variable_orderby, '');
        $ot = $this->__HTTPRequest_getParameter($this->variable_ordertype, 'ASC');

        if(!isset($orders[$ob])) {
            $ob_keys = array_keys($orders);
            $ob = $ob_keys[0];
        }

        if($ot != 'DESC' && $ot != 'ASC')
            $ot = 'ASC';

        $orderby = '';

        if(!is_array($orders[$ob]))
            $orders[$ob] = array($orders[$ob]);

        foreach($orders[$ob] as $field) {
            $type = $ot;
            if(preg_match('/(\w+(?:\s*\([^\);]+\))?) (\w+)/', $field, $aux)) {
                $field = $aux[1];
                if('DESC' == strtoupper($aux[2]))
                    $type = $ot == 'DESC' ? 'ASC' : 'DESC';
            }

            $orderby .= ",{$field} {$type}";
        }

        return array(
            substr($orderby, 1),
            array(
                $this->variable_orderby => $ob,
                $this->variable_ordertype => $ot
            )
        );
    }

    function generateWhere($correspondencias, $function_print = null) {
        if(!is_array($correspondencias) || count($correspondencias) == 0)
            return array($correspondencias, array());

        $condicion = null;
        $contexto = array();

        foreach($correspondencias as $nombre => $campos) {
            if(!$this->__HTTPRequest_isParameter($nombre))
                continue;

            $valor = $this->__HTTPRequest_getParameter($nombre);

            $c = null;
            
            switch(true) {
                case is_array($function_print) && method_exists($function_print[0], "__criterio_{$nombre}"):
                    $funcion = "__criterio_{$nombre}";
                    $c = $function_print[0]->$funcion($campos, $valor);

                    break;
                case function_exists("__criterio_{$nombre}"): 
                    $funcion = "__criterio_{$nombre}";
                    $c = $funcion($campos, $valor);

                    break;

                case preg_match("/^(\w+)_/", $nombre, $aux) && method_exists($this, "__criterio_{$aux[1]}"):
                    $funcion = "__criterio_{$aux[1]}";
                    $c = $this->$funcion($campos, $valor);

                    break;
            }

            if($c) {
                if($condicion == null)
                    $condicion = array();

                $condicion[] = $c;
            }
            
            $contexto[$nombre] = $valor; 
        }

        if($condicion)
            $condicion = implode(' and ', $condicion);

        return array($condicion, $contexto);
    }

////////////////////////////////////////////////////////////////////////////////


    // catalogue
    function __criterio_cat($campo, $valor) {
        if($valor == '' || $valor == 'no_select')
            return null;

        if(is_array($campo))
            return $this->__apply('__criterio_text', $campo, $valor);

        $valor = str_replace("'", '', $valor);

        return "{$campo} = '{$valor}'";
    }


    // integer
    function __criterio_int($campo, $valor) {
        if(!is_numeric($valor))
            return null;
        
        if(is_array($campo))
            return $this->__apply('__criterio_int', $campo, $valor);

        return "{$campo} = '{$valor}'";
    }

    // strings
    function __criterio_text($campo, $valor) {
        if($valor == '' || $valor == 'no_select')
            return null;

        if(is_array($campo))
            return $this->__apply('__criterio_text', $campo, $valor);


        $aux = explode(' ', trim(" {$valor}"));

        $b = '';
        $cc = array();
        foreach($aux as $v) {
            if($v == '')
                continue;

            // mantener articulos "del, "al", "el", "a", "o", "y"... etc junto a la palabra proxima por la derecha
            //if(strlen($v) < 2) {
            //    $b .= ($b != '' ? ' ' : '') . $v;
            //    continue;
            //}

            if($b != '') {
                $v = "{$b} {$v}";
                $b = '';
            }

            $cc[] = "{$campo} like '%{$v}%'";
        }

        if(!count($cc))
            return null;

        return "( " . implode(' and ', $cc) . " )";
    }

    // composite string
    function __criterio_com($campo, $valor) {
        if(!is_array($campo))
            return $this->__criterio_text($campo, $valor);

        $aux = explode(' ', trim($valor));

        $b = '';
        foreach($aux as $v) {
            if($v == '')
                continue;

            // mantener articulos "del, "al", "el", "a", "o", "y"... etc junto a la palabra proxima por la derecha
            if(strlen($v) < 3) {
                $b .= ($b != '' ? ' ' : '') . $v;
                continue;
            }

            if($b != '') {
                $v = "{$b} {$v}";
                $b = '';
            }

            $cc = array();
            foreach($campo as $c)
                $cc[] = "{$c} like '%{$v}%'";

            if(count($cc) > 0)
                $condicion[] = "( " . implode(' or ', $cc) . " )";
        }

        return (isset($condicion) ? "(" . implode (" and ", $condicion) . ")" : null);
    }

    // dates
    function __criterio_date($campo, $valor) {
        if(!($valor = $this->__checkDate($valor)))
            return null;

        if(is_array($campo))
            return $this->__apply('__criterio_date', $campo, $valor);

        return "{$campo} = '{$valor}'";
    }

    function __criterio_dateday($campo, $valor) {
        $valor = intval($valor);
        if($valor <= 0 || $valor > 31 )
            return null;

        if($valor < 10)
            $valor = "0{$valor}";

        return "{$campo} like '%-%-{$valor}'";
    }

    function __criterio_datemonth($campo, $valor) {
        $valor = intval($valor);
        if($valor <= 0 || $valor > 12 )
            return null;

        if($valor < 10)
            $valor = "0{$valor}";

        return "{$campo} like '%-{$valor}-%'";
    }

    function __criterio_dateyear($campo, $valor) {
        $valor = intval($valor);
        if($valor <= 0)
            return null;

        if($valor < 100)
            $valor = "20{$valor}";

        return "{$campo} like '{$valor}-%-%'";
    }

    function __criterio_datestart($campo, $valor) {
        if(!($valor = $this->__checkDate($valor)))
            return null;

        if(is_array($campo))
            return $this->__apply('__criterio_datestart', $campo, $valor);

        return "{$campo} >= '{$valor}'";
    }

    function __criterio_datefinal($campo, $valor) {
        if(!($valor = $this->__checkDate($valor)))
            return null;

        if(is_array($campo))
            return $this->__apply('__criterio_datefinal', $campo, $valor);

        return "{$campo} <= '{$valor}'";
    }

    function __criterio_datetime($campo, $valor) {
        if(is_array($campo))
            return $this->__apply('__criterio_datetime', $campo, $valor);

        if(($wtime = $this->__checkDate($valor, true)))
            return "{$campo} = '{$wtime}'";

        if(!($valor = $this->__checkDate($valor)))
            return null;

        return "({$campo} >= '{$valor} 00:00:00' and {$campo} <= '{$valor} 23:59:59')";
    }

    function __criterio_datetimeday($campo, $valor) {
        $valor = intval($valor);
        if($valor <= 0 || $valor > 31 )
            return null;

        if($valor < 10)
            $valor = "0{$valor}";
            
        if(is_array($campo)) {
            $str = array();
            foreach($campo as $c)
                $str[] = "{$c} like '%-%-{$valor} %'";
            
            return '(' . implode(" OR ", $str) . ')';
        }

        return "{$campo} like '%-%-{$valor} %'";
    }

    function __criterio_datetimemonth($campo, $valor) {
        $valor = intval($valor);
        if($valor <= 0 || $valor > 12 )
            return null;

        if($valor < 10)
            $valor = "0{$valor}";

        if(is_array($campo)) {
            $str = array();
            foreach($campo as $c)
                $str[] = "{$c} like '%-{$valor}-% %'";

            return '(' . implode(" OR ", $str) . ')';
        }

        return "{$campo} like '%-{$valor}-% %'";
    }

    function __criterio_datetimeyear($campo, $valor) {
        $valor = intval($valor);
        if($valor <= 0)
            return null;

        if($valor < 100)
            $valor = "20{$valor}";

        if(is_array($campo)) {
            $str = array();
            foreach($campo as $c)
                $str[] = "{$c} like '{$valor}-%-% %'";
    
            return '(' . implode(" OR ", $str) . ')';
        }

        return "{$campo} like '{$valor}-%-% %'";
    }

    function __criterio_datetimestart($campo, $valor) {
        $wtime = null;
        if(!($wtime = $this->__checkDate($valor, true))) {
            if(!($wtime = $this->__checkDate($valor)))
                return null;

            $wtime = "{$wtime} 00:00:00";
        }

        if(is_array($campo))
            return $this->__apply('__criterio_datefinal', $campo, $wtime);

        return "{$campo} >= '{$wtime}'";
    }

    function __criterio_datetimefinal($campo, $valor) {
        $wtime = null;
        if(!($wtime = $this->__checkDate($valor, true))) {
            if(!($wtime = $this->__checkDate($valor)))
                return null;

            $wtime = "{$wtime} 23:59:59";
        }

        if(is_array($campo))
            return $this->__apply('__criterio_datefinal', $campo, $wtime);

        return "{$campo} <= '{$wtime}'";
    }

////////////////////////////////////////////////////////////////////////////////

    function __apply($criterio, $array, $value) {
        if(count($array) == 0)
            return null;
            
        $v = array();
        foreach($array as  $c)
            $v[] = $this->$criterio($c, $value);

        return "( " . implode(' or ', $v) .  " )";
    }

    function __checkDate($value, $time = false) {
        $time = $time ? '( \d{2}:\d{2}:\d{2})' : '';

        if(preg_match("/^(\d{2})[-\/](\d{2})[-\/](\d{4}){$time}/", $value, $aux))
            $value = "{$aux[3]}-{$aux[2]}-{$aux[1]}" . (isset($aux[4]) ? $aux[4] : '');

        if(!preg_match("/^\d{4}[-\/]\d{2}[-\/]\d{2}{$time}$/", $value))
            return null;

        return $value;
    }
}


