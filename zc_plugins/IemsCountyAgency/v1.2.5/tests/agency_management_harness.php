<?php

declare(strict_types=1);

use Zencart\Plugins\Admin\IemsCountyAgency\IemsAgencyInput;

require dirname(__DIR__) . '/admin/includes/classes/IemsAgencyInput.php';

$failures = [];
$assert = static function (bool $condition, string $message) use (&$failures): void {
    if (!$condition) {
        $failures[] = $message;
    }
};

$valid = IemsAgencyInput::validate([
    'county_id' => '49',
    'agency_identifier' => 'IEMS01',
    'agency_name' => 'Indianapolis EMS',
]);
$assert($valid['errors'] === [], 'Valid agency input should pass.');
$assert($valid['values']['agency_name'] === 'Indianapolis EMS', 'Validated values should be preserved.');

$nonScalar = IemsAgencyInput::validate([
    'county_id' => ['49'],
    'agency_identifier' => ['IEMS'],
    'agency_name' => ['Indianapolis EMS'],
]);
$assert(
    $nonScalar['errors'] === ['county_required', 'identifier_required', 'name_required'],
    'Non-scalar agency fields should be rejected.'
);

$invalid = IemsAgencyInput::validate([
    'county_id' => '4.9',
    'agency_identifier' => 'iems-01',
    'agency_name' => "Invalid\nName",
]);
$assert(in_array('county_invalid', $invalid['errors'], true), 'Malformed county IDs should be rejected.');
$assert(in_array('identifier_format', $invalid['errors'], true), 'Identifiers must be uppercase alphanumeric.');
$assert(in_array('name_format', $invalid['errors'], true), 'Control characters in names should be rejected.');
$assert(IemsAgencyInput::positiveId('12') === 12, 'Positive numeric IDs should parse.');
$assert(IemsAgencyInput::positiveId('0') === null, 'Zero IDs should be rejected.');
$assert(IemsAgencyInput::positiveId('1e2') === null, 'Non-digit IDs should be rejected.');
$assert(IemsAgencyInput::positiveId(['12']) === null, 'Non-scalar IDs should be rejected.');
$tooLong = IemsAgencyInput::validate([
    'county_id' => '49',
    'agency_identifier' => 'ABCDEFGHIJK',
    'agency_name' => str_repeat('A', 129),
]);
$assert(in_array('identifier_length', $tooLong['errors'], true), 'Long identifiers should be rejected.');
$assert(in_array('name_length', $tooLong['errors'], true), 'Long names should be rejected.');

$pluginRoot = dirname(__DIR__);
$page = file_get_contents($pluginRoot . '/admin/iems_agencies.php');
$installer = file_get_contents($pluginRoot . '/Installer/ScriptedInstaller.php');
$assert($page !== false, 'Agency admin page should be readable.');
$assert($installer !== false, 'Installer should be readable.');

if ($page !== false) {
    foreach ([
        "'save', 'deactivate', 'reactivate'",
        'a.agency_identifier LIKE ',
        '$formMode = $isCreate ? \'new\' : \'edit\'',
        'INSERT INTO " . TABLE_IEMS_AGENCIES',
        'UPDATE " . TABLE_IEMS_AGENCIES',
        'iems_agency_identifier_exists(',
        'ERROR_DUPLICATE_IDENTIFIER',
        'TABLE_IEMS_CUSTOMER_AFFILIATIONS',
        'TABLE_IEMS_UNITS',
        "SET status = :status",
        "zen_record_admin_activity(\n                    'IEMS agency created",
        "zen_record_admin_activity(\n                'IEMS agency updated",
        "zen_record_admin_activity('IEMS agency '",
        "zen_draw_form('iems_agency', FILENAME_IEMS_AGENCIES, '', 'post'",
        "zen_draw_hidden_field('action', \$statusAction)",
        "zen_draw_form(\n                            'iems_agency_status_",
        "new splitPageResults(\n    \$currentPage,\n    MAX_DISPLAY_SEARCH_RESULTS,\n    \$agenciesQueryRaw,\n    \$agenciesQueryNumRows",
        "\$agenciesSplit->display_count(\n                \$agenciesQueryNumRows",
        "\$agenciesSplit->display_links(\n                \$agenciesQueryNumRows",
    ] as $requiredFragment) {
        $assert(str_contains($page, $requiredFragment), 'Missing agency behavior: ' . $requiredFragment);
    }
    $assert(
        preg_match('/(?:INSERT\\s+INTO|UPDATE|DELETE\\s+FROM)\\s*"\\s*\\.\\s*TABLE_IEMS_COUNTIES/i', $page) !== 1,
        'Agency UI must never mutate county records.'
    );
    $assert(!str_contains(strtolower($page), 'delete agency'), 'Agency UI must not offer hard deletion.');
}

if ($installer !== false) {
    $assert(str_contains($installer, "'customersIemsAgencies'"), 'Installer should register agency navigation.');
    $assert(
        !str_contains($installer, 'DROP TABLE IF EXISTS `iems_agencies`'),
        'Plugin lifecycle must not drop the foundation agency table.'
    );
    $upgradeStart = strpos($installer, 'protected function executeUpgrade');
    $uninstallStart = strpos($installer, 'protected function executeUninstall');
    $upgradeBody = (
        $upgradeStart !== false
        && $uninstallStart !== false
        && $uninstallStart > $upgradeStart
    ) ? substr($installer, $upgradeStart, $uninstallStart - $upgradeStart) : '';
    $assert(!str_contains($upgradeBody, 'DELETE '), 'Upgrade must not delete existing IEMS data.');
    $assert(!str_contains($upgradeBody, 'DROP TABLE'), 'Upgrade must not drop existing IEMS data.');
}

$carriedForwardFiles = [
    'admin/includes/auto_loaders/config.iems_county_agency_customers.php',
    'admin/includes/classes/observers/class.iems_county_agency_customers_observer.php',
    'catalog/includes/auto_loaders/config.iems_account_edit_lock.php',
    'catalog/includes/auto_loaders/config.iems_county_agency_account_edit.php',
    'catalog/includes/auto_loaders/config.iems_county_agency_create_account.php',
    'catalog/includes/classes/observers/class.iems_account_edit_lock_observer.php',
    'catalog/includes/classes/observers/class.iems_county_agency_account_edit_observer.php',
    'catalog/includes/classes/observers/class.iems_county_agency_create_account_observer.php',
    'catalog/includes/languages/english/extra_definitions/lang.iems_county_agency_account_edit.php',
    'catalog/includes/languages/english/extra_definitions/lang.iems_county_agency_create_account.php',
];
foreach ($carriedForwardFiles as $relativePath) {
    $oldFile = dirname($pluginRoot) . '/v1.2.0/' . $relativePath;
    $newFile = $pluginRoot . '/' . $relativePath;
    $assert(
        is_file($oldFile) && is_file($newFile) && hash_file('sha256', $oldFile) === hash_file('sha256', $newFile),
        'Phase 4 behavior changed unexpectedly: ' . $relativePath
    );
}

if ($failures !== []) {
    fwrite(STDERR, implode(PHP_EOL, $failures) . PHP_EOL);
    exit(1);
}

echo "IEMS agency-management harness passed.\n";
