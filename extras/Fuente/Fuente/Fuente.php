<?php

//////////////////////////////////////////////////////////////////////////////////////////////////////////////////
// Fuente: define una interfaz a utilizar para solicitar recursos a un conector a traves de consultas SQL       //
//////////////////////////////////////////////////////////////////////////////////////////////////////////////////

class Fuente extends FBase {
        protected $alias;

        function Fuente($name, $objConector, $alias = null) {
                $this->name = $name;
                $this->connector = $objConector;
                $this->alias = $name;

                if(!is_string($name) || !preg_match("/^\w+$/", $name))
                        $this->addError("El primer argumento debe ser una cadena con formato de 'identificador' y usted dio : \"{$name}\"");

                if(! $objConector instanceof Connector)
                        $this->addError("El segundo argumento debe ser un conector valido y usted dio: \"" . print_r($objConector) . "\"");

                $this->describer = $this->connector->getDescriber();
        }

////////////////////////////////////////////////////////////////////////////////
// Configuracion 

        function addField($objFField, $alias = null) {
                if(!$objFField instanceof FField)
                        $this->addError("El primer argumento debe ser la definicion de un campo y usetd dio: \"" . print_r($objFField, true) . "\"");

                if($alias === null)
                        $alias = $objFField->getName();

                if(!is_string($alias))
                        $this->addError("El segundo argumento debe ser una cadena y usted dio: \"" . print_r($alias, true) . "\"");

                $this->fields[$alias] = $objFField;
        }

        // compara la estructura con la existente en la BD
        function serial($apply = false) {
                $equal = true;

                $res = $this->describer->unserial($this);

                // si existe en la BD se compara
                if($res) {
                        if(count($this->fields) > 0) {
                                foreach ($this->fields as $k => $v) {
                                        if(!isset($res[$k]))
                                                $equal = false;

                                        // los modificadores se aplican segun las capacidades del DBMS, no se ha cubierto 
                                        // la forma de comparar el objeto con la base de datos, se limita a buscar 
                                        // si existen o no los campos 
/*                                        $ops = $v->getOptions();
                                        foreach ($ops as $o) {
                                                if(!$res[$k]->isFlag($o)) {
                                                        $equal = false;
                                                        break;
                                                }
                                        }
*/
                                        if(!$equal)
                                                break;
                                }
                        }
                        else
                                $equal = false;
                }
                
                // si se solicita se crea la tabla segun las capacidades del DBMS
                else if($apply)
                        $equal = $this->describer->serial($this, true);

                if(!$equal)
                        $this->addError("La definicion del objeto y la tabla en la base de datos no son equivalentes");

                return $equal;
        }

        // construye la estructura apartir de la existente en la BD
        function unserial() {
                $res = $this->describer->unserial($this);
                if($res) {
                        $this->fields = $res;
                        return true;
                }

                return false;
        }

////////////////////////////////////////////////////////////////////////////////

        function getName() {
                return $this->name;
        }

        function &getConnector() {
                return $this->connector;
        }

        function getAlias() {
                return $this->alias;
        }

        function getFields($flag = null, $onlynames = true) {
                if($flag != null)
                        $pre_res = $this->__searchFlag($flag);
                else
                        $pre_res = $this->fields;

                if(is_array($pre_res)) {
                        if($onlynames) {
                                foreach($pre_res as $objField)
                                        $res[] = $objField->getName();

                                return $res;
                        }

                        return $pre_res;
                }

                if($pre_res && $onlynames)
                        return $pre_res->getName();

                return $pre_res;
        }

////////////////////////////////////////////////////////////////////////////////
// Operaciones generales

        function insert($registers, $query = false) {
                // $registers debe ser un arreglo
                if(!is_array($registers))
                        $this->addError("La estructura debe ser valida: \"" . print_r($registers) . "\"");

                $regs = array();

                // si el primer elemento es un arreglo se considera $registers como un 'arreglo de registros'
                if(is_array($registers[0])) {
                        foreach($registers as $register)
                                $regs[] = $this->__verifyRow($register);
                }
                // se concidera a $registers como un solo registro
                else
                        $regs[] = $this->__verifyRow($registers);

                $res = $this->describer->insert($this, $regs);

                if($query)
                        return $res;

                return $this->connector->executeQuery($res);
        }

        function select($fields = null, $where = null, $orderby = null, $groupby = null, $start = -1, $limit = -1, $query = false) {
                $fields = $this->__verifyColumns($fields, true); 
                $where = $this->__verifyWhere($where);
                $orderby = $this->__verifyOrderBy($orderby);
                $groupby = $this->__verifyGroupBy($groupby);

                $res = $this->describer->select($this, $fields, $where, $orderby, $groupby, $start, $limit);

                if($query)
                        return $res;

                return $this->connector->executeQuery($res, true, $this->raw);
        }

        function update($register, $where = null, $query = false) {
                $list_fields = array_keys($this->fields);

                $register = $this->__verifyRow($register);
                $where = $this->__verifyWhere($where);

                if($where == null)
                        $this->addError("Como medida de seguridad no puede modificar sin establecer una condicion valida");

                $res = $this->describer->update($this, $register, $where);

                if($query)
                        return $res;

                return $this->connector->executeQuery($res);
        }

        function delete($where = null, $query = false) {
                $list_fields = array_keys($this->fields);

                $where = $this->__verifyWhere($where);

                if($where == null)
                        $this->addError("Como medida de seguridad no puede borrar sin establecer una condicion valida");

                $res = $this->describer->delete($this, $where);

                if($query)
                        return $res;

                return $this->connector->executeQuery($res);
        }

////////////////////////////////////////////////////////////////////////////////

        protected function __searchFlag($flag) {
                $res = array();

                reset($this->fields); 
                foreach($this->fields as $field) { 
                        if($field->isFlag($flag))
                                $res[] = $field;
                }

                if(count($res) == 0)
                        $res = null;

                return $res;
        }

////////////////////////////////////////////////////
// validaciones de estructura

        // busca si el valor es una referencia a campo o es un valor
        protected function __verifyReferenceToField($name, $fuente_fields = null, $fuente_name = null) {
                if($fuente_fields == null)
                        $fuente_fields = $this->fields;

                switch(true) {
                        case preg_match("/^'[^']*'$/", $name):
                                return $name;

                        case preg_match("/^(\w+)$/", $name) && in_array($name, $fuente_fields):
                                if($fuente_name != null)
                                        $fuente_name .= ".";

                                return $fuente_name . $name;

                        case $name == '*' || $name == 'null':
                                return $name;
                }

                return "'{$name}'";
        }
}

