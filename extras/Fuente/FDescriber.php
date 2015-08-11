<?php

//////////////////////////////////////////////////////////////////////////////////////////////////////////////////
// Describer: descriptor de consultas (plantillas del SQL)                                                      //
//////////////////////////////////////////////////////////////////////////////////////////////////////////////////

abstract class FDescriber extends LibBase {

        // comprueba que la tabla descrita exista en el conector, en caso de no coincidir
        // la genera, ademas de inicializar el descriptor asociado al conector
        abstract function serial(&$fuente, $apply = false);

        // si la tabla descrita existe, genera la estructura desde la fuente
        // e inicializa el descriptor asociado al conector
        abstract function unserial(&$fuente);

        // operaciones
        abstract function insert(&$fuente, $rows);
        abstract function select(&$fuente, $columns, $where, $orderby, $groupby, $start, $limit);
        abstract function update(&$fuente, $values, $where);
        abstract function delete(&$fuente, $where);
}
