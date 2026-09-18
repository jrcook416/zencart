<?php
return [
    'pluginVersion' => 'v1.6.0',
    'pluginName' => 'IEMS County Agency',
    'pluginDescription' => 'Adds county and agency customer affiliation, profile-restricted agency and unit administration, checkout unit or agency-fallback selection, agency-owned pickup and delivery eligibility, and an optional storefront account-edit lock. Counties remain application read-only.',
    'pluginAuthor' => 'IEMS Team',
    'pluginId' => 0,
    'zcVersions' => ['v220', 'v221', 'v222'],
    'changelog' => 'v1.6.0 adds an agency-managed delivery flag and independent zero-cost pickup and delivery shipping modules. Both revalidate the signed-in customer affiliation from current database state and fail closed.',
    'github_repo' => '',
    'pluginGroups' => [],
];
