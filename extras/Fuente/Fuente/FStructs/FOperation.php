<?php

abstract class FOperation extends LibBase {
        protected $cur = 0;

        function FOperation($arrObjFCondition = array()) {
                for($i = 0; $i < count($arrObjFCondition); $i++)
                        if($this->__isValid($arrObjFCondition[$i]))
                                $this->collection[] = $arrObjFCondition[$i];
        }

        function addCondition($objFCondition) {
                if(!$this->__isValid($objFCondition))
                        return;

                $this->collection[] = $objFCondition;
        }

        function isEmpty() {
                return count($this->collection) == 0;
        }

        function next() {
                $res = null;

                if(count($this->collection) > $this->cur && $this->cur >= 0) {
                        $res = $this->collection[$this->cur];
                        $this->cur++;
                }

                return $res;
        }

        function reset() {
                $this->cur = 0;

                if(count($this->collection))
                        foreach($this->collection as $objc)
                                if($objc instanceof FOperation)
                                        $objc->reset();
        }

        function count() {
                return count($this->collection);
        }

        private function __isValid($condition) {
                return  $condition != null && 
                        ((is_object($condition) && ($condition instanceof FIH || ($condition instanceof FOperation && !$condition->isEmpty())))
                          || (is_string($condition) && trim($condition) != ''));
        }
}
