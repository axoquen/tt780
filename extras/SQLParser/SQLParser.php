<?php

class SQLParser extends uParser {

//| {UPDATE} | {DELETE} | {INSERT}

    function __construct($handlers = null) {
            if(!$handlers)
                $handlers = str_replace("\\", '/', dirname(__FILE__)) . '/handlers/';
/*
            boolean_expression -> {expression} {expression_op}
            expression_op -> AND {boolean_expression} | OR {boolean_expression} | -eps-
            expression -> ( {boolean_expression} ) | {expression_compose}
            expression_compose -> {comparation} {expression_op}
*/

        parent::__construct(
<<<PPP
            S -> {SELECT}

            SELECT -> SELECT {columns} FROM {relations} {conditions} {groupby} {orderby} {limit}

            columns -> {columns_field} {columns_list} | *
            relations -> {relation} {relations_list}
            conditions -> WHERE {boolean_expression} | -eps-
            groupby -> GROUP BY {field} {fields_list} | -eps-
            orderby -> ORDER BY {orderby_field} {orderby_field_list} | -eps-
            limit -> LIMIT -integer- {limit_end} | -eps-

            columns_field -> {element} {alias}
            columns_list -> , {columns_field} {columns_list} | -eps-

            relation -> {source} {alias}
            relations_list -> {relations_list_operation} {relation} {relations_list_condition} {relations_list} | -eps-
            relations_list_condition -> ON {boolean_expression} | -eps-
            relations_list_operation -> JOIN | LEFT JOIN

            fields_list -> , {field} {field_list} | -eps-

            orderby_field -> {field} {orderby_field_type}
            orderby_field_type -> DESC | ASC
            orderby_field_list -> , {orderby_field} {orderby_field_list} | -eps-

            limit_end -> , -integer- | -eps-

            field -> {identifier} {field_specific}
            field_specific -> . {identifier} | -eps-
            source -> {identifier} | ( {SELECT} )
            alias -> AS -string_id- | -eps-

            identifier -> -string_id- | ` [string_id] `

            boolean_expression -> {boolean_expression_term} {boolean_expression_p}
            boolean_expression_p -> OR {boolean_expression_term} {boolean_expression_p} | -eps-

            boolean_expression_term -> {expression} {boolean_expression_term_p}
            boolean_expression_term_p -> AND {expression} {boolean_expression_term_p} | -eps-

            expression -> ( {boolean_expression} ) | {comparation}

            comparation -> {element} {comparation_comparator} {element}

            element -> * | {function} {values} | {field} | {values} | {value}
            comparation_comparator -> = | != | < | > | >= | <= | not like | like | not in | in

            function -> count | max | min | distinct
            values -> ( {element} {values_list} )
            values_list -> , {element} {values_list} | -eps-
            value -> -numeric- | -string-

PPP
            ,
            $handlers
        );
    }
}