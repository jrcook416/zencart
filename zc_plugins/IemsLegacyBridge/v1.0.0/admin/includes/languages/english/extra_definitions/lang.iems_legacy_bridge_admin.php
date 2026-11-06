<?php
$define = [
    'BOX_CONFIGURATION_IEMS_LEGACY_BRIDGE' => 'IEMS Legacy Bridge',
    'BOX_TOOLS_SHIPPING_EXPORT' => 'Export Shipping/Order Information',
    'TEXT_IEMS_LEGACY_PRODUCTS_UOM' => 'Units of Measure',
    'TABLE_HEADING_IEMS_LEGACY_UNIT' => 'Unit',
    // Customer::getOrderHistory() needs this for orders whose status ID has no orders_status
    // row (migrated data). Stock Zen Cart defines it only on the storefront, so admin crashed.
    // Empty on purpose: those orders render like any other, with no status text.
    'TEXT_UNKNOWN_ORDERS_STATUS_NAME' => '',
];

return $define;
