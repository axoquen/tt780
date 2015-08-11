<?php

// axoquen@gmail.com
// Separa las cadenas por lexemas

class uLexerFile extends LibBase {
    const LENGTH = 150;

    protected $fp;
    protected $block = array(0 => '', 1 => '');

    protected $target = 0;
    protected $cursor = 0;

    protected $blocks_reads = 0;

    function __construct($source) {
        if(!file_exists($source) || !is_readable($source))
            return $this->addError("The source is not a valid file: <b>\"{$source}\"</b>");

        $this->fp = fopen($source, 'r');

        $this->__loadBlock($this->target);
    }
    
    private function __loadBlock($target) {
        if($this->blocks_reads == 0)
            $this->block[$target] = !feof($this->fp) ? fgets($this->fp, uLexerFile::LENGTH) : false;
        else
            $this->blocks_reads--;
    }

    function next() {
        if($this->cursor >= strlen($this->block[$this->target])) {
            $this->target++;
            if(count($this->block) >= $this->target)
                $this->target = 0;

            $this->__loadBlock($this->target);
            $this->cursor = 0;
        }

        if($this->block[$this->target] === false)
            return false;

        return $this->block[$this->target]{$this->cursor++};
    }

    function back() {
        $this->cursor--;

        if($this->cursor < 0) {
            $this->cursor = 149;
            $this->target--;

            $this->blocks_reads++;

            if($this->target < 0)
                $this->target = count($this->block) - 1;
        }

        return $this->block[$this->target]{$this->cursor};
    }

    function char() {
        return array(
            $this->cursor + $this->blocks_reads * uLexerFile::LENGTH,
            $this->block[$this->target]{$this->cursor++}
        );
    }
    
    function eos() {
        return feof($this->fp) && $this->cursor >= strlen($this->block[$this->target]) && $this->blocks_reads == 0;
    }
}

////////////////////////////////////////////////////////////////////////////////

class uLexerString extends LibBase {
    protected $string;
    protected $cursor = 0;


    function __construct($source) {
        if(!is_string($source))
            return $this->addError("The source is not a valid strinf: \"{$source}\"");
/*
        echo "<br>=======================<br>";
        for($i = 0; $i < strlen($source); $i++)
            echo "(" . ord($source{$i}) . ")";
        echo "<br>=======================<br>";
*/

        $this->string = $source;
    }

    function next() {
        if($this->cursor >= strlen($this->string))
            return false;

        return $this->string{$this->cursor++};
    }

    function back() {
        if($this->cursor == 0)
            return false;

        $this->cursor--;

        return $this->string{$this->cursor};
    }

    function char() {
        return array(
            $this->cursor,
            $this->cursor >= strlen($this->string) ? null: $this->string{$this->cursor}
        );
    }

    function eos() {
        return $this->cursor >= strlen($this->string);
    }
}

////////////////////////////////////////////////////////////////////////////////

class uLexer extends LibBase {
    protected $source;

    protected $cursor = 0;
    protected $step = array();

    static protected $separators = array(
        ' ', "\t", "\n", "\r"
    );

    static protected $simbols = array(
        '(', ')', '!', '>', '<', '=', '\'', '"', '.', ',', "\\", ';', '{', '}', '$', '+', '-', '.', ':', '?', '/', '[', ']',
    );

    function __construct($source) {
        $this->source = is_file($source) ? new uLexerFile($source) : new uLexerString($source);
    }

    function getToken($include_separators = false) {
        $res = "";

        array_push($this->step, $this->cursor);

        $escape = false;
        
        while(!$this->eos() && ($char = $this->source->next()) !== false) {
            $this->cursor++;
            if(in_array($char, uLexer::$separators)) {
                if($res == '') {
                    if($include_separators)
                        return $char;

                    continue;
                }

                break;
            }

            if(in_array($char, uLexer::$simbols)) {
                if($res == '')
                    $res = $char;
                else {
                    $this->cursor--;
                    $this->source->back();
                }

                break;
            }

            $res .= $char;
        }
//        var_dump("#{$res}# :: {$ant}({$antch}) :: {$this->cursor}(" . ord($this->char(true)) . ")");
        return $res;
    }

    function backToken() {
        if(count($this->step) == 0)
            return;

        $backs = array_pop($this->step);

        $i = 0;
        while(($this->cursor - $backs) > 0) {
            $this->cursor--;
            $this->source->back();
//            echo "(" . ord($this->char(true)) . ")";
            $i++;
        }
//        echo "= $i<br>\n";

        $this->cursor = $backs;

//        var_dump("#" . $this->cursor . "(" . ord($this->char(true)) . ")");
    }

    function char($char = false) {
        $res = $this->source->char();
        
        return $char ? $res[1] : $res;
    }

    function eos() {
        return $this->source->eos();
    }
}
