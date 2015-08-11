<?php

class FCAnd extends FOperation {
    protected $collection = array();

    function FCAnd() {
        $this->FOperation(func_get_args());
    }
}
