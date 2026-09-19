<?php
return [
    'pluginVersion' => 'v1.9.0',
    'pluginName' => 'IEMS County Agency',
    'pluginDescription' => 'Adds county and agency customer affiliation, profile-restricted agency and unit administration, managed unit addresses and mileage, agency shipping categories, configurable out-of-county delivery rates, pickup-preferred checkout, and agency-selected offline payments. Counties remain application read-only.',
    'pluginAuthor' => 'IEMS Team',
    'pluginId' => 0,
    'zcVersions' => ['v220', 'v221', 'v222'],
    'changelog' => 'v1.9.0 adds retained agency shipping categories and nullable unit mileage, one-time county-based category migration, global out-of-county flat/per-mile rates, category-specific delivery labels, and pickup-preferred checkout with live fail-closed revalidation. Existing module settings and administrator category choices are preserved on repeat upgrades.',
    'github_repo' => '',
    'pluginGroups' => [],
];
