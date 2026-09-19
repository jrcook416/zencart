<?php

declare(strict_types=1);

use Zencart\Plugins\Admin\IemsCountyAgency\IemsUnitInput;
use Zencart\Plugins\Admin\IemsCountyAgency\IemsShippingSchema;

require dirname(__DIR__) . '/admin/includes/classes/IemsUnitInput.php';
require_once dirname(__DIR__) . '/admin/includes/classes/IemsShippingSchema.php';

if (($argv[1] ?? '') === '--controller') {
    $scenario = json_decode(base64_decode($argv[2]), true, flags: JSON_THROW_ON_ERROR);
    $agencyPage = ($scenario['page'] ?? '') === 'agencies';
    require dirname(__DIR__) . '/admin/includes/classes/IemsAgencyInput.php';
    define('TABLE_IEMS_AGENCIES', 'harness_agencies');
    define('TABLE_IEMS_UNITS', 'harness_units');
    define('TABLE_IEMS_COUNTIES', 'harness_counties');
    define('TABLE_IEMS_CUSTOMER_AFFILIATIONS', 'harness_affiliations');
    define('FILENAME_IEMS_UNITS', 'iems_units');
    define('FILENAME_IEMS_AGENCIES', 'iems_agencies');
    define('FILENAME_DEFAULT', 'index');
    define('FILENAME_DENIED', 'denied');
    define('MAX_DISPLAY_SEARCH_RESULTS', 20);
    $languageFile = $agencyPage ? 'lang.iems_agencies.php' : 'lang.iems_units.php';
    foreach (require dirname(__DIR__) . '/admin/includes/languages/english/' . $languageFile as $key => $value) {
        define($key, $value);
    }

    class IemsHarnessStop extends RuntimeException
    {
    }

    class IemsHarnessBootstrap
    {
        public $context;
        private int $position = 0;
        public function stream_open($path, $mode, $options, &$openedPath): bool
        {
            return str_ends_with($path, '/includes/application_top.php');
        }
        public function stream_read($count): string
        {
            $data = substr('<?php ', $this->position, $count);
            $this->position += strlen($data);
            return $data;
        }
        public function stream_eof(): bool
        {
            return $this->position >= 6;
        }
        public function stream_stat(): array
        {
            return ['size' => 6, 'mode' => 0100444];
        }
        public function url_stat($path, $flags): array|false
        {
            return str_ends_with($path, '/includes/application_top.php') ? $this->stream_stat() : false;
        }
        public function stream_set_option($option, $arg1, $arg2): bool
        {
            return false;
        }
    }

    class IemsHarnessResult
    {
        public bool $EOF;
        public function __construct(public array $fields = [], private array $remaining = [])
        {
            $this->EOF = $fields === [];
        }
        public function MoveNext(): void
        {
            $this->fields = array_shift($this->remaining) ?? [];
            $this->EOF = $this->fields === [];
        }
    }

    class splitPageResults
    {
        public function __construct(...$args)
        {
            throw new IemsHarnessStop();
        }
    }

    function zen_is_superuser(): bool
    {
        return true;
    }
    function zen_href_link(...$args): string
    {
        return 'harness';
    }
    function zen_redirect($url): never
    {
        throw new IemsHarnessStop();
    }
    function zen_record_admin_activity($message, $level): void
    {
        $GLOBALS['activityLog'][] = $message;
    }

    $messageStack = new class {
        public array $errors = [];
        public function add($message, $level): void
        {
            $this->errors[] = $message;
        }
        public function add_session($message, $level): void
        {
            if ($level === 'error') {
                $this->errors[] = $message;
            }
        }
    };
    $db = new class ($scenario) {
        public array $unit;
        public array $agency;
        public array $writes = [];
        private array $bindings = [];
        public function __construct(private array $scenario)
        {
            $this->unit = [
                'unit_ID' => 1, 'county_ID' => 97, 'agency_ID' => 71,
                'unit_identifier' => 'MED1', 'unit_name' => 'Medic 1',
                'delivery_street_address' => null, 'delivery_city' => null, 'delivery_postcode' => null,
                'one_way_miles' => '12.50', 'status' => 1,
            ];
            $this->agency = [
                'agency_ID' => 71, 'county_ID' => $scenario['oldCounty'] ?? 97, 'agency_identifier' => 'OUT',
                'agency_name' => 'Outside', 'status' => 1, 'county_number' => $scenario['agencyCountyNumber'] ?? '087',
                'county_name' => 'Test', 'county_status' => 1, 'delivery_enabled' => 1,
                'payment_mode' => 'invoice', 'shipping_category' => $scenario['storedCategory'] ?? 'out_of_county',
            ];
        }
        public function bindVars($sql, $name, $value, $type): string
        {
            $this->bindings[$name] = $value;
            return str_replace($name, $type === 'integer' ? (string)$value : "'" . addslashes($value) . "'", $sql);
        }
        public function insert_ID(): int
        {
            return 1;
        }
        public function Execute($sql): IemsHarnessResult
        {
            if (str_starts_with($sql, 'SHOW COLUMNS')) {
                $category = str_contains($sql, 'shipping_category');
                $column = [
                    'Field' => $category ? 'shipping_category' : 'one_way_miles',
                    'Type' => $category ? "enum('marion','iems','out_of_county')" : 'decimal(7,2)',
                    'Null' => $category ? 'NO' : 'YES', 'Default' => $category ? 'marion' : null, 'Extra' => '',
                ];
                if (($this->scenario['schema'] ?? '') === 'missing') {
                    return new IemsHarnessResult();
                }
                if (($this->scenario['schema'] ?? '') === 'malformed') {
                    $column['Type'] = 'varchar(10)';
                }
                return new IemsHarnessResult($column);
            }
            if (preg_match('/^\s*(INSERT|UPDATE)/', $sql)) {
                $this->writes[] = $sql;
                if (str_contains($sql, 'harness_agencies') && str_contains($sql, 'shipping_category')) {
                    $this->agency['shipping_category'] = $this->bindings[':shippingCategory'];
                    $this->agency['county_ID'] = $this->bindings[':countyId'];
                }
                if (str_contains($sql, 'one_way_miles')) {
                    $this->unit['one_way_miles'] = $this->bindings[':oneWayMiles'] === ''
                        ? null : $this->bindings[':oneWayMiles'];
                }
                if (str_contains($sql, 'SET status')) {
                    $this->unit['status'] = $this->bindings[':status'];
                }
                return new IemsHarnessResult();
            }
            if (str_contains($sql, 'FROM harness_units')) {
                return str_contains($sql, 'WHERE unit_ID')
                    ? new IemsHarnessResult($this->unit) : new IemsHarnessResult();
            }
            if (str_contains($sql, 'FROM harness_agencies')) {
                return str_contains($sql, 'agency_identifier =')
                    ? new IemsHarnessResult() : new IemsHarnessResult($this->agency);
            }
            return new IemsHarnessResult(
                ['county_ID' => 97, 'county_number' => '087', 'county_name' => 'Test', 'status' => 1],
                [['county_ID' => 12, 'county_number' => $this->scenario['marionNumber'] ?? '049', 'county_name' => 'Not inferred from name', 'status' => 1]]
            );
        }
    };
    $_SERVER['REQUEST_METHOD'] = 'POST';
    $_GET = [];
    $_POST = [
        'action' => $scenario['action'] ?? 'save',
        'unit_id' => ($scenario['create'] ?? false) ? '' : '1',
        'agency_id' => '71', 'unit_identifier' => 'MED1', 'unit_name' => 'Medic 1',
        'one_way_miles' => $scenario['mileage'] ?? null,
    ];
    if ($agencyPage) {
        $_POST = [
            'action' => $scenario['action'] ?? 'save',
            'agency_id' => ($scenario['create'] ?? false) ? '' : '71',
            'county_id' => (string)($scenario['county'] ?? 97),
            'agency_identifier' => 'OUT', 'agency_name' => 'Indianapolis EMS',
            'delivery_enabled' => '1', 'payment_mode' => 'invoice',
        ];
        if (array_key_exists('category', $scenario)) {
            $_POST['shipping_category'] = $scenario['category'];
        }
    }
    $activityLog = [];
    stream_wrapper_register('iems-harness', IemsHarnessBootstrap::class);
    set_include_path('iems-harness://');
    chdir(__DIR__);
    try {
        require dirname(__DIR__) . ($agencyPage ? '/admin/iems_agencies.php' : '/admin/iems_units.php');
    } catch (IemsHarnessStop) {
    }
    echo json_encode([
        'mileage' => $db->unit['one_way_miles'], 'status' => $db->unit['status'],
        'writes' => $db->writes, 'errors' => $messageStack->errors, 'activity' => $activityLog,
        'category' => $db->agency['shipping_category'], 'county' => $db->agency['county_ID'],
    ], JSON_THROW_ON_ERROR);
    exit;
}

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
$assert(
    $valid['values']['delivery_street_address'] === '',
    'Omitted address fields should normalize to delivery unavailable.'
);

$readyAddress = IemsUnitInput::validate([
    'agency_id' => '71',
    'unit_identifier' => 'MED1',
    'unit_name' => 'Medic 1',
    'delivery_street_address' => '3930 Georgetown Road',
    'delivery_city' => 'Indianapolis',
    'delivery_postcode' => '46254',
]);
$assert($readyAddress['errors'] === [], 'A complete managed delivery address should pass.');

$partialAddress = IemsUnitInput::validate([
    'agency_id' => '71',
    'unit_identifier' => 'MED1',
    'unit_name' => 'Medic 1',
    'delivery_street_address' => '3930 Georgetown Road',
    'delivery_city' => '',
    'delivery_postcode' => '46254',
]);
$assert(
    in_array('address_incomplete', $partialAddress['errors'], true),
    'A partial managed delivery address must be rejected.'
);

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

$unitValues = ['agency_id' => '71', 'unit_identifier' => 'MED1', 'unit_name' => 'Medic 1'];
foreach ([
    [null, null], ['', null], ['0', '0.00'], [0, '0.00'], ['0.01', '0.01'],
    ['12', '12.00'], ['12.5', '12.50'], ['00012.50', '12.50'], ['99999.99', '99999.99'],
] as [$rawMileage, $expected]) {
    $input = IemsUnitInput::validate($unitValues + ['one_way_miles' => $rawMileage]);
    $assert($input['errors'] === [], 'Valid optional mileage must pass: ' . json_encode($rawMileage));
    $assert($input['values']['one_way_miles'] === $expected, 'Mileage must normalize exactly, without floats.');
}
foreach ([
    '-1', '+1', '1e2', '1E2', '1,000', '100000', '99999.999', '0.001', '.1', '1.',
    ' 12', '12 ', "12\n", ' ', true, false, 12.5, [], ['12'], new stdClass(),
] as $rawMileage) {
    $input = IemsUnitInput::validate($unitValues + ['one_way_miles' => $rawMileage]);
    $assert(
        in_array('one_way_miles_invalid', $input['errors'], true),
        'Malformed mileage must be rejected without coercion: ' . json_encode($rawMileage)
    );
}
$mileageColumn = [
    'Field' => 'one_way_miles', 'Type' => 'decimal(7,2)', 'Null' => 'YES', 'Default' => null, 'Extra' => '',
];
$assert(IemsShippingSchema::validMileageColumn($mileageColumn), 'Canonical nullable decimal schema must pass.');
foreach ([
    [],
    array_replace($mileageColumn, ['Type' => 'double']),
    array_replace($mileageColumn, ['Type' => 'decimal(8,2)']),
    array_replace($mileageColumn, ['Type' => 'decimal(7,3)']),
    array_replace($mileageColumn, ['Null' => 'NO']),
    array_replace($mileageColumn, ['Default' => '0.00']),
    array_replace($mileageColumn, ['Extra' => 'VIRTUAL GENERATED']),
] as $invalidColumn) {
    $assert(!IemsShippingSchema::validMileageColumn($invalidColumn), 'Malformed mileage schema must fail closed.');
}
$withoutDefault = $mileageColumn;
unset($withoutDefault['Default']);
$assert(!IemsShippingSchema::validMileageColumn($withoutDefault), 'Absent default metadata must fail closed.');

foreach ([
    [['create' => true, 'mileage' => '0'], '0.00', 1, false],
    [['create' => true, 'mileage' => '99999.99'], '99999.99', 1, false],
    [['create' => true, 'mileage' => ''], null, 1, false],
    [['mileage' => '25.75'], '25.75', 1, false],
    [['mileage' => ''], null, 1, false],
    [['mileage' => '1e2'], '12.50', 0, true],
    [['mileage' => '100000'], '12.50', 0, true],
    [['mileage' => '-0.01'], '12.50', 0, true],
    [['mileage' => '12.501'], '12.50', 0, true],
    [['mileage' => ['12']], '12.50', 0, true],
    [['mileage' => '25', 'schema' => 'missing'], '12.50', 0, true],
    [['mileage' => '25', 'schema' => 'malformed'], '12.50', 0, true],
    [['mileage' => '25', 'agencyCountyNumber' => '0049'], '12.50', 0, true],
    [['mileage' => '25', 'agencyCountyNumber' => '93'], '12.50', 0, true],
    [['mileage' => '25', 'agencyCountyNumber' => '49'], '25.00', 1, false],
    [['action' => 'deactivate'], '12.50', 1, false],
] as [$scenario, $expectedMileage, $expectedWrites, $hasErrors]) {
    $output = [];
    $status = 0;
    exec(
        escapeshellarg(PHP_BINARY) . ' ' . escapeshellarg(__FILE__) . ' --controller '
            . escapeshellarg(base64_encode(json_encode($scenario, JSON_THROW_ON_ERROR))),
        $output,
        $status
    );
    $result = json_decode(implode("\n", $output), true);
    $assert(
        $status === 0 && is_array($result)
            && $result['mileage'] === $expectedMileage
            && count($result['writes']) === $expectedWrites
            && ($result['errors'] !== []) === $hasErrors
            && count($result['activity']) === $expectedWrites,
        'Actual unit controller CRUD failed: ' . json_encode([$scenario, $result])
    );
    if (($scenario['action'] ?? '') === 'deactivate') {
        $assert(($result['status'] ?? null) === 0, 'Deactivation must retain managed mileage, not delete the unit.');
    }
}

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
$globalAdminLanguage = require $pluginRoot
    . '/admin/includes/languages/english/extra_definitions/lang.iems_county_agency_admin.php';
$pageLanguage = require $pluginRoot . '/admin/includes/languages/english/lang.iems_units.php';
$adminAuth = file_get_contents($repositoryRoot . '/admin/includes/init_includes/init_admin_auth.php');
$adminAccess = file_get_contents($repositoryRoot . '/admin/includes/functions/admin_access.php');
$adminSessions = file_get_contents($repositoryRoot . '/admin/includes/init_includes/init_sessions.php');
$adminHtmlOutput = file_get_contents($repositoryRoot . '/admin/includes/functions/html_output.php');
$adminProfiles = file_get_contents($repositoryRoot . '/admin/profiles.php');

$assert($page !== false, 'Unit admin page should be readable.');
$assert($installer !== false, 'Installer should be readable.');
$assert($filenames !== false, 'Filename definitions should be readable.');
$assert($manifest['pluginVersion'] === 'v1.9.1', 'Manifest should identify plugin version v1.9.1.');
$assert(
    ($globalAdminLanguage['BOX_CUSTOMERS_IEMS_UNITS'] ?? null) === 'IEMS Units',
    'The unit menu language key must be globally available before the admin menu is rendered.'
);
$assert(
    ($pageLanguage['BOX_CUSTOMERS_IEMS_UNITS'] ?? null) === ($globalAdminLanguage['BOX_CUSTOMERS_IEMS_UNITS'] ?? null),
    'The page-local unit label must remain consistent with the global admin-menu label.'
);

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
        'delivery_street_address',
        'delivery_city',
        'delivery_postcode',
        'TEXT_ADDRESS_READY',
        "delivery address ' . (\$addressReady ? 'ready' : 'unavailable')",
        'zen_output_string_protected(',
        'IemsShippingSchema::isReady($db)',
        "one_way_miles = NULLIF(:oneWayMiles, '')",
        "'one_way_miles' => \$unit['one_way_miles']",
        "IemsUnitInput::mileage(\$units->fields['one_way_miles'])",
        "one-way miles ' . (\$existingUnit['one_way_miles'] ?? 'unset')",
        'TEXT_ONE_WAY_MILES_HELP',
    ] as $requiredFragment) {
        $assert(str_contains($page, $requiredFragment), 'Missing unit behavior: ' . $requiredFragment);
    }
    $assert(
        substr_count($page, "':oneWayMiles', \$oneWayMiles ?? '', 'string'") === 2,
        'Create and update must bind exact decimal strings, with blank values persisted as NULL.'
    );

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
    foreach (
        [
            "'delivery_street_address', 128",
            "'delivery_city', 128",
            "'delivery_postcode', 64",
            "NULLIF(TRIM(`delivery_street_address`), '')",
            'MODIFY COLUMN `" . $column . "` varchar(" . $length . ") NULL DEFAULT NULL',
        ] as $schemaFragment
    ) {
        $assert(str_contains($installer, $schemaFragment), 'Missing unit-address schema behavior: ' . $schemaFragment);
    }
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
