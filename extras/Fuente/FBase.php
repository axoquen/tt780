<?php

// axoquen@gmail.com


abstract class FBase extends LibBase {
    protected $connector;                   // FConnector instance
    protected $describer;                   // FDescriber instance
    
    static $references = array();           // fields references
    
    abstract protected function __getReferences($flags = null);
    
    abstract protected function __resolveReferences($references);

////////////////////////////////////////////////////////////////////////////////        


        // $like_objects == true  regresa un arreglo de objetos, o un arreglo de cadenas en caso contrario
        protected function __verifyColumns($fields, $like_objects = false, $fuente_fields = null, $fuente_name = null)  {
                if(!$fuente_fields)
                        $fuente_fields = array_keys($this->fields);

                $nfields = array();

                // para seleccionar 'todos los campos'
                if((is_array($fields) && (count($fields) == 0 || $fields[0] == "*")) || $fields == null || (is_string($fields) && $fields == "*")) {
                        foreach ($fuente_fields as $a => $field)
                                $nfields[] = $like_objects ?
                                        new FColumn($field, preg_match('/^(\w+)\.(\w+)$/', $a, $aux) ?
                                                $aux[2] :
                                                (is_integer($a) ? null : $a)) :
                                        $field;

                        return $nfields;
                }

                if(is_array($fields)) {
                        foreach($fields as $name)
                                $nfields = array_merge($nfields, $this->__verifyColumns($name, true, $fuente_fields, $fuente_name));
                }
                else if(is_object($fields)) {
                        if($fields instanceof FColumn) {
                                $aux = $this->__verifyColumns($fields->getReference(), false, $fuente_fields, $fuente_name);
                                $fields->setReference($aux[0]);
                        }
                        else if(! $fields instanceof FHC)
                                $fields = new FColumn("# " . get_class($fields) . " #");

                        $nfields[] = $fields;
                }
                // cadena con alias
                else if(preg_match("/^([^ ]+) +as +(\w+)$/i", trim($fields), $aux)) {
                        $field = $this->__verifyColumns($aux[1], true, $fuente_fields, $fuente_name);
                        $field[0]->setAlias($aux[2]);
                        $nfields[] = $field[0];
                }
                // cadena con llamada a una funcion
                else if(preg_match("/^(\w+) *\((.+)\)$/", trim($fields), $aux) && FBase::$functions[strtolower(trim($aux[1]))]) {
                        $references = $this->__verifyColumns($aux[2], false, $fuente_fields, $fuente_name);
                        eval('$nfields[] = new ' . FBase::$functions[strtolower(trim($aux[1]))] . '($references);');
                }
                // lista de columnas
                else if(is_string($fields) && strpos($fields, ",") !== false)
                        $nfields = array_merge($nfields, $this->__verifyColumns(explode(",", $fields), false, $fuente_fields, $fuente_name));
                // campo sencillo o valor
                // ... regresar objeto
                else if($like_objects)
                        $nfields[] = new FColumn($this->__verifyReferenceToField(trim($fields), $fuente_fields, $fuente_name), trim($fields));
                // ... regresar cadena
                else
                        $nfields[] = $this->__verifyReferenceToField(trim($fields), $fuente_fields, $fuente_name);

                return $nfields;
        }

        protected function __verifyWhere($where, $fuente_fields = null, $fuente_name = null) {
                if(!$fuente_fields)
                        $fuente_fields = array_keys($this->fields);

                $nwhere = null;

                if(is_array($where)) {
                        $nwhere = new FCAnd();

                        foreach($where as $name) {
                                $n = $this->__verifyWhere($name, $fuente_fields, $fuente_name);
                                if($n)
                                        $nwhere->addCondition($n);
                        }

                        if($nwhere->isEmpty())
                                $nwhere = null;
                }
                // objeto
                else if($where instanceof FOperation) {
                        if($where instanceof FCondition) {
                                if(is_string($where->getLeft()))
                                        $where->setLeft($this->__verifyReferenceToField($where->getLeft(), $fuente_fields, $fuente_name));
                                else if(is_array($where->getLeft())) {
                                        $array = $where->getLeft();
                                        for($i = 0; $i < count($array); $i++)
                                                $array[$i] = $this->__verifyReferenceToField($array[$i], $fuente_fields, $fuente_name);

                                        $where->setLeft($array);
                                }

                                if(is_string($where->getRight()))
                                        $where->setRight($this->__verifyReferenceToField($where->getRight(), $fuente_fields, $fuente_name));
                                else if(is_array($where->getRight())) {
                                        $array = $where->getRight();
                                        for($i = 0; $i < count($array); $i++)
                                                $array[$i] = $this->__verifyReferenceToField($array[$i], $fuente_fields, $fuente_name);

                                        $where->setRight($array);
                                }

                                $nwhere = $where;
                        }
                        else {
                                $where->reset();
                                if($where->count() > 1) {
                                        $nwhere = $where instanceof FCOr ? new FCOr() : new FCAnd();

                                        while(($obj = $where->next()) != null)
                                                $nwhere->addCondition($this->__verifyWhere($obj, $fuente_fields, $fuente_name));
                                }
                                else if ($where->count() == 1)
                                        $nwhere = $this->__verifyWhere($where->next(), $fuente_fields, $fuente_name);
                        }
                }
                else if($where instanceof FHC)
                        $nwhere = $where;
                // expresion
                else if(is_string($where) && $where != '') {
                        $nwhere = $this->aWhere($where);

                        // sobrevalida... ¿¿??
                        if($nwhere)
                                $nwhere = $this->__verifyWhere($nwhere, $fuente_fields, $fuente_name);
                }
                else if($where != null)
                        $nwhere = new FCondition("# $where #", "", "");

                return $nwhere;
        }

        protected function __verifyOrderBy($orderby, $fuente_fields = null, $fuente_nombre = null) {
                if(!$fuente_fields)
                        $fuente_fields = array_keys($this->fields);

                $norderby = null;

                if(is_array($orderby)) {
                        $norderby = array();

                        foreach($orderby as $fuente => $name) {
                                $aux = $this->__verifyOrderBy($name, $fuente_fields, $fuente_nombre);
                                if($aux)
                                        $norderby = array_merge($norderby, $aux);
                        }
                }
                else if($orderby instanceof FOrderBy) {
                        $orderby->setField($this->__verifyReferenceToField($orderby->getField(), $fuente_fields, $fuente_nombre));
                        $norderby = array($orderby);
                }
                else if($orderby instanceof FHC)
                        $norderby = array($orderby);
                else if(is_string($orderby)) {
                        $orderby = explode(",", $orderby);

                        if(is_array($orderby) && count($orderby) > 0)
                                foreach ($orderby as $str) {
                                        if(preg_match("/^([^ ]+)( +DESC|ASC)?/i", trim($str), $aux)) {
                                                $field = $this->__verifyReferenceToField(trim($aux[1]), $fuente_fields, $fuente_nombre);

                                                if(!isset($aux[2]))
                                                        $aux[2] = FOrderBy::ASC;

                                                if($norderby == null)
                                                        $norderby = array();

                                                $norderby[] = new FOrderBy($field, strtoupper(trim($aux[2])));
                                        }
                        }
                }
                else if($norderby != null)
                        $norderby = array(new FOrderBy("# $orderby #", ""));

                if($norderby != null && count($norderby) == 0)
                        $norderby = null;

                return $norderby;
        }

        protected function __verifyGroupBy($groupby, $fuente_fields = null, $fuente_nombre = null) {
                if(!$fuente_fields)
                        $fuente_fields = array_keys($this->fields);

                $ngroupby = null;
                        
                if(is_array($groupby)) {
                        $ngroupby = array();

                        foreach($groupby as $fuente => $name) {
                                $aux = $this->__verifyGroupBy($name, $fuente_fields, $fuente_nombre);
                                if($aux)
                                        $ngroupby = array_merge($ngroupby, $aux);
                        }
                }
                else if($groupby instanceof FGroupBy) {
                        $groupby->setField($this->__verifyReferenceToField($groupby->getField(), $fuente_fields, $fuente_nombre));
                        $ngroupby = array($groupby);
                }
                else if($groupby instanceof FHC)
                        $ngroupby = array($groupby);
                else if(is_string($groupby)) {
                        $groupby = explode(",", $groupby);

                        $ngroupby = array();
                        foreach ($groupby as $str)
                                $ngroupby[] = new FGroupBy($this->__verifyReferenceToField(trim($str), $fuente_fields, $fuente_nombre));
                }
                else if($groupby != null)
                        $ngroupby = array(new FGroupBy("# $groupby #"));

                if($ngroupby != null && count($ngroupby) == 0)
                        $ngroupby = null;

                return $ngroupby;
        }

}

