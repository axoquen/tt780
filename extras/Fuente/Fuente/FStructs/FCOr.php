<?php

class FCOr extends FOperation {
    protected $collection = array();

    function FCOr() {
            $this->FOperation(func_get_args());
    }
}
