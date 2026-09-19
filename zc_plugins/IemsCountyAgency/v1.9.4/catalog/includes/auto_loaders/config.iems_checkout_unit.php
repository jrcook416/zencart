<?php

if (!defined('IS_ADMIN_FLAG')) {
    die('Illegal Access');
}

$autoLoadConfig[149][] = [
    'autoType' => 'class',
    'loadFile' => 'IemsCheckoutUnitService.php',
    'classPath' => DIR_WS_CLASSES,
];

$autoLoadConfig[150][] = [
    'autoType' => 'class',
    'loadFile' => 'observers/class.iems_checkout_unit_observer.php',
    'classPath' => DIR_WS_CLASSES,
];

$autoLoadConfig[150][] = [
    'autoType' => 'classInstantiate',
    'className' => 'zcObserverIemsCheckoutUnit',
    'objectName' => 'zcObserverIemsCheckoutUnit',
];
