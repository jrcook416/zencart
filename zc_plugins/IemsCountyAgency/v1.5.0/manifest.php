<?php
return [
    'pluginVersion' => 'v1.5.0',
    'pluginName' => 'IEMS County Agency',
    'pluginDescription' => 'Adds county and agency customer affiliation, profile-restricted agency and unit administration, checkout unit selection, and an optional storefront account-edit lock. Counties remain application read-only.',
    'pluginAuthor' => 'IEMS Team',
    'pluginId' => 0,
    'zcVersions' => ['v220', 'v221', 'v222'],
    'changelog' => 'v1.5.0 requires an agency-valid unit per order, confirms the selection, and stores its formatted label in the order suburb fields without changing customer or address-book data.',
    'github_repo' => '',
    'pluginGroups' => [],
];
