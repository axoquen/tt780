<?php

class XMLParser extends uParser {

    function __construct($handlers = null) {
        if(!$handlers)
            $handlers = str_replace("\\", '/', dirname(__FILE__)) . '/handlers/';

        parent::__construct(
         "
            S -> < ? xml {attribute_list} ? > {element_content}
            element -> {element_empty} | {element_content}
            element_empty -> < -string_id- / >
            element_content -> < -string_id- {attribute_list} > {content_list} < / -string_id- >
            attribute_list -> {attribute} {attribute_list} | -eps-
            attribute -> -string_id- = -string-
            content_list -> {content} {content_list} | -eps-
            content -> {element} | -text- | -eps-
        ",
            $handlers
        );
    }
}

