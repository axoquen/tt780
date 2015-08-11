<?php

class component_04 extends handler_class {

    function method_01($parameters) {
        return "<p><b>component_04.method_01</b>: " . ($parameters->getParameter('general')) . "</p>";
    }

    function main($parameters) {
        return "<p><b>component_04.main</b></p>";
    }
}