<?php

if (!defined('IS_ADMIN_FLAG')) {
    die('Illegal Access');
}

$autoLoadConfig[100][] = [
    'autoType' => 'class',
    'loadFile' => 'observers/class.iems_account_edit_lock_observer.php',
    'classPath' => DIR_WS_CLASSES,
];

$autoLoadConfig[100][] = [
    'autoType' => 'classInstantiate',
    'className' => 'zcObserverIemsAccountEditLock',
    'objectName' => 'zcObserverIemsAccountEditLock',
];
