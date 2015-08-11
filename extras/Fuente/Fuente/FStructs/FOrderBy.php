<?php

class FOrderBy extends LibBase {
        const ASC = 'ASC';
        const DESC = 'DESC';

        private $field;
        private $order;

        function FOrderBy($field, $order = FOrderBy::ASC) {
                if(!is_string($field))
                        $field = "# {$field} #";

                $this->field = $field;

                if($order != FOrderBy::ASC && $order != FOrderBy::DESC)
                        $this->addError("El modificador no es de tipo valido: \"$field '$order'\"");

                $this->order = $order;
        }

        function getType() {
                return $this->order;
        }

        function getField() {
                return $this->field;
        }

        function setField($field) {
                $this->field = $field;
        }
}
