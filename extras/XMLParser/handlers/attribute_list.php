<?php

// $routine variable available with all coincidences in the rule in a array
// return coincidence processed

// without data
if(is_bool($routine))
    return true;

$attributes = array(
    $routine[0][0] => $routine[0][2]
);

if(isset($routine[1]))
    $attributes = array_merge($attributes, $routine[1]);


return $attributes;