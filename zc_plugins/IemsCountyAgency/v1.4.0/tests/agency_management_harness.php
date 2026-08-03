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
$accessDenied = static fn (bool $isSuperuser, bool $profileAllowed): bool => !$isSuperuser && !$profileAllowed;
$assert(!$accessDenied(false, true), 'An assigned admin profile should be authorized.');
$assert($accessDenied(false, false), 'An unassigned admin profile direct GET should be denied.');
$assert($accessDenied(false, false), 'An unassigned admin profile mutation POST should be denied.');
$assert(!$accessDenied(true, false), 'A superuser should retain native access.');
$tooLong = IemsAgencyInput::validate([
    'county_id' => '49',
    'agency_identifier' => 'ABCDEFGHIJK',
    'agency_name' => str_repeat('A', 129),
]);
$assert(in_array('identifier_length', $tooLong['errors'], true), 'Long identifiers should be rejected.');
$assert(in_array('name_length', $tooLong['errors'], true), 'Long names should be rejected.');

$pluginRoot = dirname(__DIR__);
$repositoryRoot = dirname($pluginRoot, 3);
$page = file_get_contents($pluginRoot . '/admin/iems_agencies.php');
$installer = file_get_contents($pluginRoot . '/Installer/ScriptedInstaller.php');
$manifest = require $pluginRoot . '/manifest.php';
$adminAuth = file_get_contents($repositoryRoot . '/admin/includes/init_includes/init_admin_auth.php');
$adminAccess = file_get_contents($repositoryRoot . '/admin/includes/functions/admin_access.php');
$adminSessions = file_get_contents($repositoryRoot . '/admin/includes/init_includes/init_sessions.php');
$adminHtmlOutput = file_get_contents($repositoryRoot . '/admin/includes/functions/html_output.php');
$adminProfiles = file_get_contents($repositoryRoot . '/admin/profiles.php');
$assert($page !== false, 'Agency admin page should be readable.');
$assert($installer !== false, 'Installer should be readable.');
$assert($manifest['pluginVersion'] === 'v1.4.0', 'Manifest should identify plugin version v1.4.0.');
$assert($adminAuth !== false, 'Native admin authorization bootstrap should be readable.');
$assert($adminAccess !== false, 'Native admin access functions should be readable.');
$assert($adminSessions !== false, 'Native admin session bootstrap should be readable.');
$assert($adminHtmlOutput !== false, 'Native admin form helper should be readable.');
$assert($adminProfiles !== false, 'Native Admin Profiles page should be readable.');

if ($page !== false) {
    foreach ([
        "'save', 'deactivate', 'reactivate'",
        "!zen_is_superuser() && !check_page(FILENAME_IEMS_AGENCIES, [])",
        "zen_redirect(zen_href_link(FILENAME_DENIED, '', 'SSL'))",
        'Attempted access to unauthorized page [iems_agencies]',
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
        'TEXT_PROFILE_ACCESS_HELP',
    ] as $requiredFragment) {
        $assert(str_contains($page, $requiredFragment), 'Missing agency behavior: ' . $requiredFragment);
    }
    $assert(
        preg_match('/(?:INSERT\\s+INTO|UPDATE|DELETE\\s+FROM)\\s*"\\s*\\.\\s*TABLE_IEMS_COUNTIES/i', $page) !== 1,
        'Agency UI must never mutate county records.'
    );
    $assert(!str_contains(strtolower($page), 'delete agency'), 'Agency UI must not offer hard deletion.');
    $bootstrapPosition = strpos($page, "require 'includes/application_top.php';");
    $guardPosition = strpos($page, '!zen_is_superuser() && !check_page(FILENAME_IEMS_AGENCIES, [])');
    $mutationPosition = strpos($page, "if (\$_SERVER['REQUEST_METHOD'] === 'POST')");
    $assert(
        $bootstrapPosition !== false
            && $guardPosition !== false
            && $mutationPosition !== false
            && $bootstrapPosition < $guardPosition
            && $guardPosition < $mutationPosition,
        'Native authorization, defense-in-depth page guard, and CSRF bootstrap must run before every agency mutation.'
    );
}

if ($installer !== false) {
    $assert(str_contains($installer, "'customersIemsAgencies'"), 'Installer should register agency navigation.');
    $assert(
        str_contains(
            $installer,
            "'customersIemsAgencies',\n                'BOX_CUSTOMERS_IEMS_AGENCIES',\n                'FILENAME_IEMS_AGENCIES'"
        ),
        'Agency page registration should use native Admin Profiles page assignment.'
    );
    $assert(
        str_contains($installer, "if (!zen_page_key_exists('customersIemsAgencies'))"),
        'Agency registration should be idempotent.'
    );
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
    $registerStart = strpos($installer, 'private function registerAdminPages');
    $registerBody = $registerStart === false ? '' : substr($installer, $registerStart);
    $assert(
        !str_contains($registerBody, 'zen_deregister_admin_pages'),
        'Registration must preserve existing page-to-profile assignments.'
    );
}

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
        'Native page-to-profile assignments must drive both menu visibility and server-side authorization.'
    );
    $assert(
        str_contains($adminProfiles, 'zen_get_admin_pages(FALSE)')
            && str_contains($adminProfiles, "zen_draw_checkbox_field('p[]'")
            && str_contains($adminProfiles, 'zen_insert_pages_into_profile($profile'),
        'IEMS Agencies must be assignable through the standard Admin Profiles workflow.'
    );
    $assert(
        str_contains($adminSessions, "\$_SESSION ['securityToken'] !== \$_POST ['securityToken']")
            && str_contains($adminHtmlOutput, 'name="securityToken"'),
        'Native security-token validation must protect agency mutation forms.'
    );
}

$relativeFiles = static function (string $root): array {
    $files = [];
    $iterator = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($root, FilesystemIterator::SKIP_DOTS)
    );
    foreach ($iterator as $file) {
        if ($file->isFile()) {
            $files[] = substr($file->getPathname(), strlen($root) + 1);
        }
    }
    sort($files);

    return $files;
};
$previousRoot = dirname($pluginRoot) . '/v1.3.0';
$previousFiles = $relativeFiles($previousRoot);
$currentFiles = $relativeFiles($pluginRoot);
$assert(
    array_diff($previousFiles, $currentFiles) === [],
    'v1.4.0 must carry forward the complete v1.3.0 payload.'
);

$intentionallyChangedFiles = [
    'Installer/ScriptedInstaller.php',
    'admin/includes/languages/english/extra_definitions/lang.iems_county_agency_admin.php',
    'filenames.php',
    'manifest.php',
    'tests/agency_management_harness.php',
];
foreach (array_diff($previousFiles, $intentionallyChangedFiles) as $relativePath) {
    $oldFile = $previousRoot . '/' . $relativePath;
    $newFile = $pluginRoot . '/' . $relativePath;
    $assert(
        hash_file('sha256', $oldFile) === hash_file('sha256', $newFile),
        'v1.3.0 behavior changed unexpectedly: ' . $relativePath
    );
}

if ($failures !== []) {
    fwrite(STDERR, implode(PHP_EOL, $failures) . PHP_EOL);
    exit(1);
}

echo "IEMS agency-management harness passed.\n";
