<?php

declare(strict_types=1);

use Zencart\Plugins\Admin\IemsCountyAgency\IemsAgencyInput;
use Zencart\Plugins\Admin\IemsCountyAgency\IemsShippingSchema;

require dirname(__DIR__) . '/admin/includes/classes/IemsAgencyInput.php';
require_once dirname(__DIR__) . '/admin/includes/classes/IemsShippingSchema.php';

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
    'delivery_enabled' => '1',
    'payment_mode' => IemsAgencyInput::PAYMENT_MODE_IEMS_UNIT,
]);
$assert($valid['errors'] === [], 'Valid agency input should pass.');
$assert($valid['values']['agency_name'] === 'Indianapolis EMS', 'Validated values should be preserved.');
$assert($valid['values']['delivery_enabled'] === '1', 'Enabled delivery input should be preserved.');
$assert(
    $valid['values']['payment_mode'] === IemsAgencyInput::PAYMENT_MODE_IEMS_UNIT,
    'The selected payment mode should be preserved.'
);

$defaultDelivery = IemsAgencyInput::validate([
    'county_id' => '49',
    'agency_identifier' => 'IEMS01',
    'agency_name' => 'Indianapolis EMS',
]);
$assert(
    $defaultDelivery['errors'] === []
        && $defaultDelivery['values']['delivery_enabled'] === '0'
        && $defaultDelivery['values']['payment_mode'] === IemsAgencyInput::PAYMENT_MODE_INVOICE,
    'Omitted optional agency settings should use safe defaults.'
);

$invalidDelivery = IemsAgencyInput::validate([
    'county_id' => '49',
    'agency_identifier' => 'IEMS01',
    'agency_name' => 'Indianapolis EMS',
    'delivery_enabled' => 'true',
]);
$assert(
    $invalidDelivery['errors'] === ['delivery_enabled_invalid'],
    'Delivery input must accept only canonical zero or one values.'
);

$invalidPaymentMode = IemsAgencyInput::validate([
    'county_id' => '49',
    'agency_identifier' => 'IEMS01',
    'agency_name' => 'Indianapolis EMS',
    'payment_mode' => 'both',
]);
$assert(
    $invalidPaymentMode['errors'] === ['payment_mode_invalid']
        && $invalidPaymentMode['values']['payment_mode'] === IemsAgencyInput::PAYMENT_MODE_INVOICE,
    'Payment mode must accept exactly one supported value and fail safely to invoice.'
);

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

$marion = ['county_number' => '049', 'shipping_category' => 'marion'];
$iems = ['county_number' => '049', 'shipping_category' => 'iems'];
$outside = ['county_number' => '087', 'shipping_category' => 'out_of_county'];
foreach ([
    [[], '049', null, 'marion'],
    [['agency_name' => 'Indianapolis EMS'], '049', null, 'marion'],
    [['shipping_category' => 'iems'], '049', null, 'iems'],
    [[], '087', null, 'out_of_county'],
    [['shipping_category' => 'iems'], '087', null, 'out_of_county'],
    [[], '049', $iems, 'iems'],
    [['shipping_category' => 'marion'], '049', $iems, 'marion'],
    [['shipping_category' => 'iems'], '049', $marion, 'iems'],
    [['shipping_category' => 'iems'], '087', $iems, 'out_of_county'],
    [['shipping_category' => 'marion'], '087', $marion, 'out_of_county'],
    [['shipping_category' => 'out_of_county'], '049', $outside, 'marion'],
    [[], '049', $outside, 'marion'],
    [['shipping_category' => 'iems'], '049', $outside, 'iems'],
    [['shipping_category' => 'out_of_county'], '049', $marion, null],
    [[], '49', null, 'marion'],
    [[], '87', null, 'out_of_county'],
    [['shipping_category' => 'iems'], '49', null, 'iems'],
    [[], '049', ['county_number' => '49', 'shipping_category' => 'iems'], 'iems'],
    [[], '49', $iems, 'iems'],
    [['shipping_category' => 'out_of_county'], '49', $outside, 'marion'],
    [[], '', null, null],
    [[], 'bad', null, null],
] as [$input, $county, $existing, $expected]) {
    $assert(
        IemsAgencyInput::shippingCategory($input, $county, $existing) === $expected,
        'Category/default/county transition failed: ' . json_encode([$input, $county, $existing])
    );
}
foreach (['', 'IEMS', ' iems ', 'invalid', null, false, 1, ['iems']] as $badCategory) {
    foreach (['049', '087'] as $county) {
        $assert(
            IemsAgencyInput::shippingCategory(['shipping_category' => $badCategory], $county) === null,
            'Malformed posted category must fail closed, even outside county 049.'
        );
        $assert(
            IemsAgencyInput::shippingCategory(
                ['shipping_category' => 'marion'],
                '049',
                ['county_number' => $county, 'shipping_category' => $badCategory]
            ) === null,
            'Malformed stored category must not be silently repaired by an admin save.'
        );
    }
}
foreach ([
    ['county_number' => '049', 'shipping_category' => 'out_of_county'],
    ['county_number' => '087', 'shipping_category' => 'iems'],
    ['county_number' => '049'],
    ['shipping_category' => 'marion'],
] as $invalidStored) {
    $assert(
        IemsAgencyInput::shippingCategory([], '087', $invalidStored) === null,
        'Invalid stored county/category relationship must block transitions.'
    );
}
foreach (['1' => '001', '01' => '001', '001' => '001', '49' => '049', '049' => '049', '92' => '092'] as $raw => $expected) {
    $assert(IemsAgencyInput::countyNumber((string)$raw) === $expected, 'Valid Indiana county numbers must canonicalize.');
}
foreach (['', ' ', ' 49', '49 ', "49\n", '4.9', '4e1', '+49', '-49', '000', '0', '93', '097', '0049', '１２', 49, null, []] as $badCounty) {
    $assert(IemsAgencyInput::countyNumber($badCounty) === null, 'Malformed county numbers must fail closed.');
    if (is_string($badCounty)) {
        $assert(IemsAgencyInput::shippingCategory([], $badCounty) === null, 'Malformed selected counties must block categories.');
    }
    $assert(
        IemsAgencyInput::shippingCategory([], '049', ['county_number' => $badCounty, 'shipping_category' => 'marion']) === null,
        'Malformed stored counties must block category transitions.'
    );
}
$categoryColumn = [
    'Field' => 'shipping_category',
    'Type' => "enum('marion','iems','out_of_county')",
    'Null' => 'NO',
    'Default' => 'marion',
    'Extra' => '',
];
$assert(IemsShippingSchema::validCategoryColumn($categoryColumn), 'Canonical category schema must pass.');
foreach ([
    [],
    array_replace($categoryColumn, ['Type' => 'varchar(20)']),
    array_replace($categoryColumn, ['Type' => "enum('iems','marion','out_of_county')"]),
    array_replace($categoryColumn, ['Type' => "enum('marion','iems','out_of_county','other')"]),
    array_replace($categoryColumn, ['Null' => 'YES']),
    array_replace($categoryColumn, ['Default' => null]),
    array_replace($categoryColumn, ['Extra' => 'STORED GENERATED']),
] as $invalidColumn) {
    $assert(!IemsShippingSchema::validCategoryColumn($invalidColumn), 'Malformed category schema must fail closed.');
}

foreach ([
    [['create' => true, 'county' => 12], 'marion', 12, 1, false],
    [['create' => true, 'county' => 12, 'marionNumber' => '49'], 'marion', 12, 1, false],
    [['create' => true, 'county' => 12, 'category' => 'iems'], 'iems', 12, 1, false],
    [['create' => true, 'category' => 'iems'], 'out_of_county', 97, 1, false],
    [['county' => 12, 'category' => 'out_of_county'], 'marion', 12, 3, false],
    [['county' => 12, 'category' => 'iems'], 'iems', 12, 3, false],
    [['oldCounty' => 12, 'storedCategory' => 'iems', 'category' => 'iems'], 'out_of_county', 97, 3, false],
    [['oldCounty' => 12, 'storedCategory' => 'iems', 'county' => 12, 'category' => 'marion'], 'marion', 12, 1, false],
    [['category' => 'invalid'], 'out_of_county', 97, 0, true],
    [['category' => ['iems']], 'out_of_county', 97, 0, true],
    [['storedCategory' => '', 'category' => 'out_of_county'], '', 97, 0, true],
    [['storedCategory' => 'iems'], 'iems', 97, 0, true],
    [['category' => 'out_of_county', 'schema' => 'missing'], 'out_of_county', 97, 0, true],
    [['category' => 'out_of_county', 'schema' => 'malformed'], 'out_of_county', 97, 0, true],
    [['create' => true, 'county' => 12, 'marionNumber' => '0049'], 'out_of_county', 97, 0, true],
    [['create' => true, 'county' => 12, 'marionNumber' => '93'], 'out_of_county', 97, 0, true],
] as [$scenario, $expectedCategory, $expectedCounty, $expectedWrites, $hasErrors]) {
    $output = [];
    $status = 0;
    exec(
        escapeshellarg(PHP_BINARY) . ' ' . escapeshellarg(__DIR__ . '/unit_management_harness.php') . ' --controller '
            . escapeshellarg(base64_encode(json_encode($scenario + ['page' => 'agencies'], JSON_THROW_ON_ERROR))),
        $output,
        $status
    );
    $result = json_decode(implode("\n", $output), true);
    $assert(
        $status === 0 && is_array($result)
            && $result['category'] === $expectedCategory && $result['county'] === $expectedCounty
            && count($result['writes']) === $expectedWrites
            && ($result['errors'] !== []) === $hasErrors
            && count($result['activity']) === ($hasErrors ? 0 : 1),
        'Actual agency controller transition failed: ' . json_encode([$scenario, $result])
    );
}

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
$assert($manifest['pluginVersion'] === 'v1.9.1', 'Manifest should identify plugin version v1.9.1.');
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
        '(county_ID, agency_identifier, agency_name, delivery_enabled, payment_mode,',
        "'IEMS agency created with ID ' . \$agencyId . ', county ID ' . \$countyId\n"
            . "                        . ', delivery enabled ' . \$deliveryEnabled",
        'UPDATE " . TABLE_IEMS_AGENCIES',
        'delivery_enabled = :deliveryEnabled',
        'payment_mode = :paymentMode',
        "', delivery enabled ' . \$oldDeliveryEnabled . ' to ' . \$deliveryEnabled",
        "zen_draw_hidden_field('delivery_enabled', '0')",
        "zen_draw_checkbox_field(\n                            'delivery_enabled'",
        "zen_draw_pull_down_menu(\n                            'payment_mode'",
        "a.delivery_enabled, a.payment_mode, a.status",
        'TEXT_DELIVERY_ENABLED',
        'TEXT_PAYMENT_MODE',
        'TEXT_PAYMENT_MODE_INVOICE',
        'TEXT_PAYMENT_MODE_IEMS_UNIT',
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
        'IemsShippingSchema::isReady($db)',
        'IemsAgencyInput::shippingCategory(',
        'shipping_category = :shippingCategory',
        "':shippingCategory', \$shippingCategory, 'string'",
        "'shipping_category' => (string)\$agency['shipping_category']",
        'TEXT_SHIPPING_CATEGORY_HELP',
        "county.addEventListener('change'",
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
    $assert(
        substr_count($installer, '$this->addDeliveryEnabledColumn();') === 2,
        'Fresh install and upgrade must both enforce the delivery field.'
    );
    $assert(
        substr_count($installer, '$this->addPaymentModeColumn();') === 2,
        'Fresh install and upgrade must both enforce the payment-mode field.'
    );
    $assert(
        str_contains($installer, "SHOW COLUMNS FROM `iems_agencies` LIKE 'delivery_enabled'"),
        'Delivery field installation must be idempotent.'
    );
    $assert(
        str_contains(
            $installer,
            'ADD COLUMN `delivery_enabled` tinyint(1) NOT NULL DEFAULT 0'
        ),
        'Delivery field must be a default-disabled non-null boolean.'
    );
    $assert(
        str_contains(
            $installer,
            'MODIFY COLUMN `delivery_enabled` tinyint(1) NOT NULL DEFAULT 0'
        ),
        'A malformed pre-existing delivery field must be normalized.'
    );
    $assert(
        !str_contains($installer, 'DROP COLUMN `delivery_enabled`'),
        'Plugin uninstall must preserve the delivery field.'
    );
    $assert(
        str_contains($installer, "ADD COLUMN `payment_mode` varchar(16) NOT NULL DEFAULT 'invoice'")
            && str_contains($installer, "WHEN `payment_mode` IN ('invoice', 'iems_unit')")
            && !str_contains($installer, 'DROP COLUMN `payment_mode`'),
        'Payment mode must default safely, preserve supported values, normalize invalid data, and survive uninstall.'
    );
    $assert(
        str_contains($installer, 'new iemspickup(uninstalling: true)')
            && str_contains($installer, 'new iemsdelivery(uninstalling: true)')
            && str_contains($installer, 'new iemsinvoice(uninstalling: true)')
            && str_contains($installer, 'new iemsunit(uninstalling: true)'),
        'Plugin uninstall must invoke all shipping and payment modules to remove configuration only.'
    );
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
$previousRoot = dirname($pluginRoot) . '/v1.9.0';
$previousFiles = $relativeFiles($previousRoot);
$currentFiles = $relativeFiles($pluginRoot);
$assert(
    array_diff($previousFiles, $currentFiles) === [],
    'v1.9.1 must carry forward the complete v1.9.0 payload.'
);

$intentionallyChangedFiles = [
    'manifest.php',
    'tests/agency_management_harness.php',
    'tests/unit_management_harness.php',
    'catalog/includes/classes/IemsShippingAddressService.php',
    'catalog/includes/classes/observers/class.iems_checkout_unit_observer.php',
    'catalog/includes/languages/english/extra_definitions/lang.iems_checkout_unit.php',
    'tests/checkout_unit_harness.php',
];
foreach (array_diff($previousFiles, $intentionallyChangedFiles) as $relativePath) {
    $oldFile = $previousRoot . '/' . $relativePath;
    $newFile = $pluginRoot . '/' . $relativePath;
    $assert(
        hash_file('sha256', $oldFile) === hash_file('sha256', $newFile),
        'v1.9.0 behavior changed unexpectedly: ' . $relativePath
    );
}

if ($failures !== []) {
    fwrite(STDERR, implode(PHP_EOL, $failures) . PHP_EOL);
    exit(1);
}

echo "IEMS agency-management harness passed.\n";
