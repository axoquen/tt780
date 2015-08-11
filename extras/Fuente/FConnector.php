<?php

//////////////////////////////////////////////////////////////////////////////////////////////////
// Conect: describe una conección a un DBMS y sirve de interfaz para realizar consultas	sobre 	//
//////////////////////////////////////////////////////////////////////////////////////////////////

abstract class FConnector extends LibBase {
        
        // ejecuta la consulta $query($string) sobre el enlace al DBMS
        abstract function &executeQuery($query, $interrupt = true, $raw = true);

        // regresa un descriptor de consultas
        abstract function &getDescriber();
}
