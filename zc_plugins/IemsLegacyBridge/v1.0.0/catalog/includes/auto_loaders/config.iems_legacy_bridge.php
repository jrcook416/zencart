<?php

if (!defined('IS_ADMIN_FLAG')) {
    die('Illegal Access');
}

$autoLoadConfig[100][] = [
    'autoType' => 'class',
    'loadFile' => 'observers/class.iems_legacy_bridge_observer.php',
    'classPath' => DIR_WS_CLASSES,
];

$autoLoadConfig[100][] = [
    'autoType' => 'classInstantiate',
    'className' => 'zcObserverIemsLegacyBridge',
    'objectName' => 'zcObserverIemsLegacyBridge',
];
