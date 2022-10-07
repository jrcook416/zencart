<?php
// -----
//
// Last updated 20210305-lat9 for EO v4.6.0
// 
if (!defined ('IS_ADMIN_FLAG')) { 
    die ('Illegal Access'); 
}

$autoLoadConfig[200][] = [
    'autoType' => 'init_script',
    'loadFile' => 'init_edit_units.php'
];
