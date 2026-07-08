<?php

if (!defined('IS_ADMIN_FLAG')) {
 die('Illegal Access');
}
$autoLoadConfig[139][] = [
    'autoType'=>'class',

    // the filename, relative to the `classes` folder:
    'loadFile'=>'observers/class.post_auto.php',
    'classPath'=>DIR_WS_CLASSES
];
$autoLoadConfig[139][] = [
    'autoType'=>'classInstantiate',

    // the name of the class as declared inside the observer class file
    'className'=>'zcObserverPostAuto',

    // the name of the global object into which the class is instantiated
    'objectName'=>'zcObserverPostAuto'
];
