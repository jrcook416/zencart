<?php
return [
    'pluginVersion' => 'v1.5.5',
    'pluginName' => 'IEMS County Agency',
    'pluginDescription' => 'Adds county and agency customer affiliation, profile-restricted agency and unit administration, checkout unit or agency-fallback selection, and an optional storefront account-edit lock. Counties remain application read-only.',
    'pluginAuthor' => 'IEMS Team',
    'pluginId' => 0,
    'zcVersions' => ['v220', 'v221', 'v222'],
    'changelog' => 'v1.5.5 preserves required active-unit selection and adds an explicit agency fallback only when the customer has a valid active agency with zero active units. Selection stays transient and order-only.',
    'github_repo' => '',
    'pluginGroups' => [],
];
