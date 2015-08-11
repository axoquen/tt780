<?php

class FColumn extends PuntoCritico {
    protected $reference;
    protected $alias;

    function FColumn($reference, $alias = "") {
            $this->reference = $reference;
            $this->alias = $alias;
    }

    function getReference() {
            return $this->reference;
    }

    function setReference($reference) {
            $this->reference = $reference;
    }
    
    function setAlias($alias) {
            $this->alias = $alias;
    }

    function getAlias() {
            return $this->alias;
    }
}
