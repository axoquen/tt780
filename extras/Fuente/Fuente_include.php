<?php

$fuente_include = array(
    'FConnector' => '/FConnector.php',
    'FDescriber' => '/FDescriber.php',
    'FBase' => '/FBase.php',
    '/Fuente' => array(
        'Fuente' => '/Fuente.php',
        'FRelation' => '/FRelation.php',
        '/FStructs' => array(
            'FColumn' => '/FColumn.php',
            'FOperation' => '/FOperation.php',
            'FCondition' => '/FCondition.php',
            'FCAnd' => '/FCAnd.php',
            'FCOr' => '/FCOr.php',
            'FOrderBy' => '/FOrderBy.php',
            'FGroupBy' => '/FGroupBy.php',
            'FField' => '/FField.php',
            'FHC' => '/FHC.php',
            'FLex' => '/FLex.php',
        ),
        '/FField' => array(
            'FFieldInteger' => '/FFieldInteger.php',
            'FFieldFloat' => '/FFieldFloat.php',
            'FFieldDate' => '/FFieldDate.php',
            'FFieldDateTime' => '/FFieldDateTime.php',
            'FFieldTime' => '/FFieldTime.php',
            'FFieldText' => '/FFieldText.php',
            'FFieldVarchar' => '/FFieldVarchar.php',
        ),
        '/FColumn' => array(
            'FConcat' => '/FConcat.php',
            'FCount' => '/FCount.php',
            'FDistinct' => '/FDistinct.php',
            'FMax' => '/FMax.php',
            'FMin' => '/FMin.php',
            'FSum' => '/FSum.php',
        ),
    ),

    '/utils' => array(
        'Fwcp' => '/Fwcp.php'
    ),

    // Conector de mysql
    '/Describers/MySQL' => array(
        'MySQL_Connector' => '/MySQL_Connector.php',
        'MySQL_Describer' => '/MySQL_Describer.php',
    ),

    // Conector de mssql
    '/Describers/MSSQL' => array(
        'MSSQL_Connector' => '/MSSQL_Connector.php',
        'MSSQL_Describer' => '/MSSQL_Describer.php',
    ),

);

