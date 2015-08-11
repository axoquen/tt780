<?php

$configuration = array(
    'convention' => array(
        'module' => array(
            '/^(\w+)\.(\w+)$/' => array(
                'configuration_file/$1/$2.php',
                'configuration_file/$1/$1.$2.php',
                'configuration_file/$1.$2.php',
            )
        ),
        'component' => array(
            '/^(\w+)/' => 'configuration_file/__HANDLER__.php',
        )
    ),

    'module_03' => array(
        'parameters' => array(
            'type' => 'Module... maybe'
        ),
    ),

    'parameters' => array(
        'general' => 'is file'
    )
);
