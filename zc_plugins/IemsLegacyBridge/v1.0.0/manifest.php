<?php
return [
    'pluginVersion' => 'v1.0.0',
    'pluginName' => 'IEMS Legacy Bridge',
    'pluginDescription' => 'Carries the 1.5.6c store behaviour forward unchanged to Zen Cart 2.2.2: the unit dropdown from the legacy units table (written verbatim to the suburb fields), unit-of-measure display and editing from products.products_price_uom, and the Shipping Export inventory feed. Reads existing data only; no schema changes except adding orders.downloaded_ship if it is missing.',
    'pluginAuthor' => 'IEMS Team',
    'pluginId' => 0,
    'zcVersions' => ['v222'],
    'changelog' => '',
    'github_repo' => '',
    'pluginGroups' => [],
];
