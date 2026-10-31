<?php
return [
    'pluginVersion' => 'v1.9.6',
    'pluginName' => 'IEMS County Agency',
    'pluginDescription' => 'Adds county and agency customer affiliation, profile-restricted agency and unit administration, managed unit addresses and mileage, agency shipping categories, configurable out-of-county delivery rates, pickup-preferred checkout, and agency-selected offline payments. Counties remain application read-only.',
    'pluginAuthor' => 'IEMS Team',
    'pluginId' => 0,
    'zcVersions' => ['v220', 'v221', 'v222'],
    'changelog' => 'v1.9.6 shows the IEMS county/agency and unit on the Admin Orders listing for IEMS pickup and delivery orders through a plugin observer, replacing direct edits to admin/orders.php. All v1.9.5 checkout, shipping, payment, and installer behavior is unchanged.',
    'github_repo' => '',
    'pluginGroups' => [],
];
