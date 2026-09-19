<?php

if (!defined('IS_ADMIN_FLAG')) {
    die('Illegal Access');
}

$autoLoadConfig[150][] = [
    'autoType' => 'class',
    'loadFile' => 'observers/class.iems_county_agency_customers_observer.php',
    'classPath' => DIR_WS_CLASSES,
];

$autoLoadConfig[150][] = [
    'autoType' => 'classInstantiate',
    'className' => 'zcObserverIemsCountyAgencyCustomers',
    'objectName' => 'zcObserverIemsCountyAgencyCustomers',
];

$autoLoadConfig[150][] = [
    'autoType' => 'class',
    'loadFile' => 'observers/class.iems_orders_observer.php',
    'classPath' => DIR_WS_CLASSES,
];

$autoLoadConfig[150][] = [
    'autoType' => 'classInstantiate',
    'className' => 'zcObserverIemsOrders',
    'objectName' => 'zcObserverIemsOrders',
];
