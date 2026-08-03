<?php

declare(strict_types=1);

use Zencart\Plugins\Admin\IemsCountyAgency\IemsUnitInput;

require dirname(__DIR__) . '/admin/includes/classes/IemsUnitInput.php';

$failures = [];
$assert = static function (bool $condition, string $message) use (&$failures): void {
    if (!$condition) {
        $failures[] = $message;
    }
};

$valid = IemsUnitInput::validate([
    'agency_id' => '71',
    'unit_identifier' => 'MED1',
    'unit_name' => 'Medic 1',
]);
$assert($valid['errors'] === [], 'Valid unit input should pass.');
$assert($valid['values']['unit_name'] === 'Medic 1', 'Validated values should be preserved.');

$nonScalar = IemsUnitInput::validate([
    'agency_id' => ['71'],
    'unit_identifier' => ['MED1'],
    'unit_name' => ['Medic 1'],
]);
$assert(
    $nonScalar['errors'] === ['agency_required', 'identifier_required', 'name_required'],
    'Non-scalar unit fields should be rejected.'
);

$invalid = IemsUnitInput::validate([
    'agency_id' => '7.1',
    'unit_identifier' => 'med-1',
    'unit_name' => "Invalid\nName",
]);
$assert(in_array('agency_invalid', $invalid['errors'], true), 'Malformed agency IDs should be rejected.');
$assert(in_array('identifier_format', $invalid['errors'], true), 'Identifiers must be uppercase alphanumeric.');
$assert(in_array('name_format', $invalid['errors'], true), 'Control characters in names should be rejected.');
$assert(IemsUnitInput::positiveId('12') === 12, 'Positive numeric IDs should parse.');
$assert(IemsUnitInput::positiveId('0') === null, 'Zero IDs should be rejected.');
$assert(IemsUnitInput::positiveId('1e2') === null, 'Non-digit IDs should be rejected.');
$assert(IemsUnitInput::positiveId(['12']) === null, 'Non-scalar IDs should be rejected.');

$tooLong = IemsUnitInput::validate([
    'agency_id' => '71',
    'unit_identifier' => 'ABCDEFGHIJK',
    'unit_name' => str_repeat('A', 129),
]);
$assert(in_array('identifier_length', $tooLong['errors'], true), 'Long identifiers should be rejected.');
$assert(in_array('name_length', $tooLong['errors'], true), 'Long names should be rejected.');

$accessDenied = static fn (bool $isSuperuser, bool $profileAllowed): bool => !$isSuperuser && !$profileAllowed;
$assert(!$accessDenied(false, true), 'An assigned admin profile should be authorized.');
$assert($accessDenied(false, false), 'An unassigned admin profile direct GET should be denied.');
$assert($accessDenied(false, false), 'An unassigned admin profile mutation POST should be denied.');
$assert(!$accessDenied(true, false), 'A superuser should retain native access.');

$pluginRoot = dirname(__DIR__);
$repositoryRoot = dirname($pluginRoot, 3);
$page = file_get_contents($pluginRoot . '/admin/iems_units.php');
$installer = file_get_contents($pluginRoot . '/Installer/ScriptedInstaller.php');
$manifest = require $pluginRoot . '/manifest.php';
$filenames = file_get_contents($pluginRoot . '/filenames.php');
$adminAuth = file_get_contents($repositoryRoot . '/admin/includes/init_includes/init_admin_auth.php');
$adminAccess = file_get_contents($repositoryRoot . '/admin/includes/functions/admin_access.php');
$adminSessions = file_get_contents($repositoryRoot . '/admin/includes/init_includes/init_sessions.php');
$adminHtmlOutput = file_get_contents($repositoryRoot . '/admin/includes/functions/html_output.php');
$adminProfiles = file_get_contents($repositoryRoot . '/admin/profiles.php');

$assert($page !== false, 'Unit admin page should be readable.');
$assert($installer !== false, 'Installer should be readable.');
$assert($filenames !== false, 'Filename definitions should be readable.');
$assert($manifest['pluginVersion'] === 'v1.4.0', 'Manifest should identify plugin version v1.4.0.');

if ($page !== false) {
    foreach ([
        "!zen_is_superuser() && !check_page(FILENAME_IEMS_UNITS, [])",
        "zen_redirect(zen_href_link(FILENAME_DENIED, '', 'SSL'))",
        'Attempted access to unauthorized page [iems_units]',
        "'save', 'deactivate', 'reactivate'",
        '$formMode = $isCreate ? \'new\' : \'edit\'',
        "INSERT INTO \" . TABLE_IEMS_UNITS",
        "UPDATE \" . TABLE_IEMS_UNITS",
        'iems_units_identifier_exists(',
        'ERROR_DUPLICATE_IDENTIFIER',
        "SET county_ID = :countyId,\n                        agency_ID = :agencyId",
        'a.agency_identifier LIKE ',
        'u.status = ',
        'u.county_ID = ',
        'u.agency_ID = ',
        'new splitPageResults(',
        "zen_record_admin_activity(\n                    'IEMS unit created",
        "zen_record_admin_activity(\n                'IEMS unit updated",
        "zen_record_admin_activity('IEMS unit '",
        "zen_draw_form('iems_unit', FILENAME_IEMS_UNITS, '', 'post'",
        "zen_draw_hidden_field('action', \$statusAction)",
        "'iems_unit_status_' . \$unitId",
        'TEXT_PROFILE_ACCESS_HELP',
        'TEXT_STATUS_CONSEQUENCE',
        'TEXT_AGENCY_DERIVATION_HELP',
        'zen_output_string_protected(',
    ] as $requiredFragment) {
        $assert(str_contains($page, $requiredFragment), 'Missing unit behavior: ' . $requiredFragment);
    }

    $assert(
        preg_match('/(?:INSERT\\s+INTO|UPDATE|DELETE\\s+FROM)\\s*"\\s*\\.\\s*TABLE_IEMS_COUNTIES/i', $page) !== 1,
        'Unit UI must never mutate county records.'
    );
    $assert(!str_contains($page, 'DELETE FROM " . TABLE_IEMS_UNITS'), 'Unit UI must not hard-delete units.');
    $assert(!str_contains(strtolower($page), 'delete unit'), 'Unit UI must not offer hard deletion.');

    $bootstrapPosition = strpos($page, "require 'includes/application_top.php';");
    $guardPosition = strpos($page, '!zen_is_superuser() && !check_page(FILENAME_IEMS_UNITS, [])');
    $mutationPosition = strpos($page, "if (\$_SERVER['REQUEST_METHOD'] === 'POST')");
    $assert(
        $bootstrapPosition !== false
            && $guardPosition !== false
            && $mutationPosition !== false
            && $bootstrapPosition < $guardPosition
            && $guardPosition < $mutationPosition,
        'Native authorization, defense-in-depth page guard, and CSRF bootstrap must run before every unit mutation.'
    );

    $countyAssignmentPosition = strpos($page, "\$countyId = (int)\$agency['county_ID'];");
    $updatePosition = strpos($page, "UPDATE \" . TABLE_IEMS_UNITS");
    $assert(
        $countyAssignmentPosition !== false
            && $updatePosition !== false
            && $countyAssignmentPosition < $updatePosition,
        'Unit county must be derived from the validated agency before persistence.'
    );
}

if ($installer !== false) {
    $assert(str_contains($installer, "'customersIemsUnits'"), 'Installer should register unit navigation.');
    $assert(
        str_contains(
            $installer,
            "'customersIemsUnits',\n                'BOX_CUSTOMERS_IEMS_UNITS',\n                'FILENAME_IEMS_UNITS'"
        ),
        'Unit page registration should use native Admin Profiles page assignment.'
    );
    $assert(
        str_contains($installer, "if (!zen_page_key_exists('customersIemsUnits'))"),
        'Unit registration should be idempotent.'
    );
    foreach (['iems_counties', 'iems_agencies', 'iems_units', 'iems_customer_affiliations'] as $table) {
        $assert(
            !str_contains($installer, 'DROP TABLE IF EXISTS `' . $table . '`'),
            'Plugin lifecycle must preserve table ' . $table . '.'
        );
    }
    $upgradeStart = strpos($installer, 'protected function executeUpgrade');
    $uninstallStart = strpos($installer, 'protected function executeUninstall');
    $upgradeBody = (
        $upgradeStart !== false
        && $uninstallStart !== false
        && $uninstallStart > $upgradeStart
    ) ? substr($installer, $upgradeStart, $uninstallStart - $upgradeStart) : '';
    $assert(!str_contains($upgradeBody, 'DELETE '), 'Upgrade must not delete IEMS data or configuration.');
    $assert(!str_contains($upgradeBody, 'DROP TABLE'), 'Upgrade must not drop IEMS data.');
    $registerStart = strpos($installer, 'private function registerAdminPages');
    $registerBody = $registerStart === false ? '' : substr($installer, $registerStart);
    $assert(
        !str_contains($registerBody, 'zen_deregister_admin_pages'),
        'Registration must preserve existing page-to-profile assignments.'
    );
}

$assert(
    $filenames !== false && str_contains($filenames, "FILENAME_IEMS_UNITS', 'iems_units'"),
    'Unit filename must be plugin-owned.'
);

$nativeFiles = [$adminAuth, $adminAccess, $adminSessions, $adminHtmlOutput, $adminProfiles];
if (!in_array(false, $nativeFiles, true)) {
    $assert(
        str_contains($adminAuth, '!zen_is_superuser()')
            && str_contains($adminAuth, 'check_page($page, $_GET) === false')
            && str_contains($adminAuth, "zen_redirect(zen_href_link(FILENAME_DENIED, '', 'SSL'))"),
        'Native bootstrap must deny unauthorized direct GET and POST requests while retaining superuser bypass.'
    );
    $assert(
        str_contains($adminAccess, 'TABLE_ADMIN_PAGES_TO_PROFILES')
            && str_contains($adminAccess, 'function zen_get_admin_menu_for_user()')
            && str_contains($adminAccess, 'function check_page('),
        'Native page-to-profile assignments must drive menu visibility and server-side authorization.'
    );
    $assert(
        str_contains($adminProfiles, 'zen_get_admin_pages(FALSE)')
            && str_contains($adminProfiles, "zen_draw_checkbox_field('p[]'")
            && str_contains($adminProfiles, 'zen_insert_pages_into_profile($profile'),
        'IEMS Units must be assignable through the standard Admin Profiles workflow.'
    );
    $assert(
        str_contains($adminSessions, "\$_SESSION ['securityToken'] !== \$_POST ['securityToken']")
            && str_contains($adminHtmlOutput, 'name="securityToken"'),
        'Native security-token validation must protect unit mutation forms.'
    );
}

if ($failures !== []) {
    fwrite(STDERR, implode(PHP_EOL, $failures) . PHP_EOL);
    exit(1);
}

echo "IEMS unit-management harness passed.\n";
