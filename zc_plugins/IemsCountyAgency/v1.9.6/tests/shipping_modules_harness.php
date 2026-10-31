<?php

declare(strict_types=1);

$probeMode = $argv[1] ?? '';

define('IS_ADMIN_FLAG', false);
define('TABLE_CONFIGURATION', 'configuration');
define('TABLE_IEMS_AGENCIES', 'iems_agencies');
define('TABLE_IEMS_COUNTIES', 'iems_counties');
define('TABLE_IEMS_CUSTOMER_AFFILIATIONS', 'iems_customer_affiliations');
define('TABLE_IEMS_UNITS', 'iems_units');
define('TABLE_COUNTRIES', 'countries');
define('TABLE_ZONES', 'zones');
define('MODULE_SHIPPING_IEMSPICKUP_STATUS', $probeMode === 'disabled-pickup' ? 'False' : 'True');
define('MODULE_SHIPPING_IEMSPICKUP_SORT_ORDER', '0');
define('MODULE_SHIPPING_IEMSPICKUP_RECIPIENT', 'IEMS Logistics');
define('MODULE_SHIPPING_IEMSPICKUP_COMPANY', '');
define('MODULE_SHIPPING_IEMSPICKUP_STREET_ADDRESS', '3930 Georgetown Road');
define('MODULE_SHIPPING_IEMSPICKUP_CITY', 'Indianapolis');
define('MODULE_SHIPPING_IEMSPICKUP_POSTCODE', '46254');
define('MODULE_SHIPPING_IEMSDELIVERY_STATUS', $probeMode === 'disabled-delivery' ? 'False' : 'True');
define('MODULE_SHIPPING_IEMSDELIVERY_SORT_ORDER', '10');

final class IemsShippingFakeResult
{
    public bool $EOF;
    public array $fields;

    /** @var array<int, array<string, mixed>> */
    private array $rows;
    private int $index = 0;

    /** @param array<int, array<string, mixed>> $rows */
    public function __construct(array $rows)
    {
        $this->rows = $rows;
        $this->EOF = $rows === [];
        $this->fields = $rows[0] ?? [];
    }

    public function MoveNext(): void
    {
        $this->index++;
        $this->EOF = !isset($this->rows[$this->index]);
        $this->fields = $this->rows[$this->index] ?? [];
    }

    public function RecordCount(): int
    {
        return count($this->rows);
    }
}

final class IemsShippingFakeDb
{
    public bool $deliveryEnabled = true;
    public bool $hasAffiliation = true;
    public bool $hasUnit = true;
    public bool $schemaReady = true;
    public string $streetAddress = '100 Unit Lane';
    public string $city = 'Indianapolis';
    public string $postcode = '46204';
    public mixed $countyNumber = '049';
    public mixed $category = 'marion';
    public mixed $miles = null;
    public array $rates = [
        'MODULE_SHIPPING_IEMSDELIVERY_FLAT_RATE' => '35.00',
        'MODULE_SHIPPING_IEMSDELIVERY_PER_MILE_RATE' => '0.55',
    ];
    public bool $shippingSchemaReady = true;
    public array $schemaOverride = [];
    public array $mileageSchemaOverride = [];

    /** @var string[] */
    public array $writes = [];

    public function bindVars(string $sql, string $placeholder, mixed $value, string $type): string
    {
        return str_replace($placeholder, (string)$value, $sql);
    }

    public function Execute(string $sql): IemsShippingFakeResult
    {
        if (str_contains($sql, "LIKE 'shipping_category'") || str_contains($sql, "LIKE 'one_way_miles'")) {
            $category = str_contains($sql, "LIKE 'shipping_category'");
            return new IemsShippingFakeResult($this->shippingSchemaReady ? [array_replace([
                'Field' => $category ? 'shipping_category' : 'one_way_miles',
                'Type' => $category ? "enum('marion','iems','out_of_county')" : 'decimal(7,2)',
                'Null' => $category ? 'NO' : 'YES',
                'Default' => $category ? 'marion' : null,
            ], $category ? $this->schemaOverride : $this->mileageSchemaOverride)] : []);
        }
        if (str_contains($sql, 'SELECT u.one_way_miles')) {
            foreach (['a.county_ID = u.county_ID', 'a.status = 1', 'a.delivery_enabled = 1', 'c.status = 1', 'u.status = 1'] as $guard) {
                if (!str_contains($sql, $guard)) {
                    throw new RuntimeException('Pricing query missing hierarchy guard: ' . $guard);
                }
            }
            return new IemsShippingFakeResult([[
                'one_way_miles' => $this->miles,
                'county_number' => $this->countyNumber,
                'shipping_category' => $this->category,
            ]]);
        }
        if (str_contains($sql, 'SELECT configuration_key, configuration_value')) {
            $rows = [];
            foreach ($this->rates as $key => $value) {
                $rows[] = ['configuration_key' => $key, 'configuration_value' => $value];
            }
            return new IemsShippingFakeResult($rows);
        }
        if (str_contains($sql, 'SHOW COLUMNS FROM ' . TABLE_IEMS_AGENCIES)) {
            return new IemsShippingFakeResult($this->schemaReady ? [[
                'Field' => 'delivery_enabled',
                'Type' => 'tinyint(1)',
                'Null' => 'NO',
                'Default' => '0',
            ]] : []);
        }

        if (str_contains($sql, 'SHOW COLUMNS FROM ' . TABLE_IEMS_UNITS)) {
            preg_match("/LIKE '([^']+)'/", $sql, $matches);
            $column = $matches[1] ?? '';
            $types = [
                'delivery_street_address' => 'varchar(128)',
                'delivery_city' => 'varchar(128)',
                'delivery_postcode' => 'varchar(64)',
            ];

            return new IemsShippingFakeResult(
                $this->schemaReady && isset($types[$column])
                    ? [[
                        'Field' => $column,
                        'Type' => $types[$column],
                        'Null' => 'YES',
                        'Default' => null,
                    ]]
                    : []
            );
        }

        if (str_contains($sql, 'SELECT c.county_ID, c.county_number')) {
            return new IemsShippingFakeResult($this->hasAffiliation ? [[
                'county_ID' => 49,
                'county_number' => '49',
                'agency_ID' => 71,
                'agency_identifier' => 'IEMS',
                'agency_name' => 'Indianapolis EMS',
                'delivery_enabled' => $this->deliveryEnabled ? '1' : '0',
            ]] : []);
        }

        if (str_contains($sql, 'SELECT u.unit_ID, u.unit_identifier')) {
            return new IemsShippingFakeResult($this->hasUnit ? [[
                'unit_ID' => 8,
                'unit_identifier' => 'MED1',
                'unit_name' => 'Medic 1',
            ]] : []);
        }

        if (str_contains($sql, 'SELECT u.unit_identifier, u.unit_name')) {
            return new IemsShippingFakeResult($this->hasUnit ? [[
                'unit_identifier' => 'MED1',
                'unit_name' => 'Medic 1',
                'delivery_street_address' => $this->streetAddress,
                'delivery_city' => $this->city,
                'delivery_postcode' => $this->postcode,
            ]] : []);
        }

        if (str_contains($sql, 'FROM ' . TABLE_COUNTRIES . ' c')) {
            return new IemsShippingFakeResult([[
                'countries_id' => 223,
                'countries_name' => 'United States',
                'countries_iso_code_2' => 'US',
                'countries_iso_code_3' => 'USA',
                'address_format_id' => 2,
                'zone_id' => 18,
                'zone_name' => 'Indiana',
            ]]);
        }

        if (str_contains($sql, 'SELECT configuration_value')) {
            return new IemsShippingFakeResult([['configuration_value' => 'True']]);
        }

        if (preg_match('/^\s*(INSERT|DELETE)\b/i', $sql) === 1) {
            $this->writes[] = $sql;
            return new IemsShippingFakeResult([]);
        }

        throw new RuntimeException('Unexpected query: ' . $sql);
    }
}

final class IemsShippingFakeCart
{
    public function __construct(public string $cartID = 'cart-a')
    {
    }
}

class base
{
    public function attach(object $observer, array $events): void
    {
    }
}

$iemsGuestCheckout = false;
function zen_in_guest_checkout(): bool
{
    global $iemsGuestCheckout;
    return $iemsGuestCheckout;
}

abstract class ZenShipping extends base
{
    protected int $_check;
    public string $code;
    public string $description;
    public bool $enabled;
    public string $icon;
    public array $quotes;
    public $sort_order;
    public string $tax_basis;
    public $tax_class;
    public string $title;

    abstract public function quote($method = ''): array;
    abstract public function keys(): array;
    abstract public function install(): void;

    public function remove(): void
    {
        global $db;
        $db->Execute(
            "DELETE FROM " . TABLE_CONFIGURATION . "
              WHERE configuration_key IN ('" . implode("', '", $this->keys()) . "')"
        );
    }
}

$assert = static function (bool $condition, string $message): void {
    if (!$condition) {
        throw new RuntimeException($message);
    }
};

$pluginRoot = dirname(__DIR__);
$repositoryRoot = dirname($pluginRoot, 3);
if ($probeMode === 'language-loading') {
    require $pluginRoot . '/catalog/includes/classes/IemsShippingEligibilityService.php';
    $assert(IemsShippingEligibilityService::deliveryTitle('marion') === null, 'Missing language data must fail closed.');
    $languageLoader = new class {
        public function loadModuleLanguageFile(string $file, string $type): bool
        {
            if ($file !== 'iemsdelivery.php' || $type !== 'shipping') {
                throw new RuntimeException('Incorrect native module language lookup.');
            }
            define('MODULE_SHIPPING_IEMSDELIVERY_TEXT_MARION', 'Localized Marion delivery');
            return true;
        }
    };
    $assert(
        IemsShippingEligibilityService::deliveryTitle('marion') === 'Localized Marion delivery',
        'Early revalidation must use the native language loader, not a hardcoded English fallback.'
    );
    exit(0);
}
foreach (['iemspickup', 'iemsdelivery'] as $module) {
    $language = require $pluginRoot
        . '/catalog/includes/languages/english/modules/shipping/lang.' . $module . '.php';
    foreach ($language as $name => $value) {
        define($name, $value);
    }
}

$makeSelection = static function (string $type = 'unit'): array {
    return [
        'customer_id' => 42,
        'cart_id' => 'cart-a',
        'selection_type' => $type,
        'reference_id' => $type === 'unit' ? 8 : 71,
        'flow' => 'standard',
    ];
};

$directModule = $probeMode;
if (in_array($directModule, ['disabled-pickup', 'disabled-delivery'], true)) {
    $db = new IemsShippingFakeDb();
    $_SESSION = [
        'customer_id' => 42,
        'cart' => new IemsShippingFakeCart(),
        'iems_checkout_unit' => $makeSelection(),
    ];
    require $pluginRoot . '/catalog/includes/classes/IemsShippingAddressService.php';
    $module = $directModule === 'disabled-pickup'
        ? IemsShippingAddressService::MODULE_PICKUP
        : IemsShippingAddressService::MODULE_DELIVERY;
    if ((new IemsShippingAddressService())->getDestination(42, $module) !== null) {
        throw new RuntimeException('A disabled IEMS module produced a final order destination.');
    }
    exit(0);
}

if (in_array($directModule, ['iemspickup', 'iemsdelivery'], true)) {
    $db = new IemsShippingFakeDb();
    $_SESSION = [
        'customer_id' => 42,
        'cart' => new IemsShippingFakeCart(),
        'iems_checkout_unit' => $makeSelection(),
    ];
    require $pluginRoot . '/catalog/includes/modules/shipping/' . $directModule . '.php';
    $module = new $directModule();
    if (
        !$module->enabled
        || !class_exists('IemsShippingEligibilityService', false)
        || ($directModule === 'iemspickup' && !class_exists('IemsShippingAddressService', false))
    ) {
        throw new RuntimeException('Direct module inclusion did not load a working helper chain.');
    }
    exit(0);
}

$probeCommand = escapeshellarg(PHP_BINARY) . ' ' . escapeshellarg(__FILE__);
foreach (['iemspickup', 'iemsdelivery'] as $module) {
    passthru($probeCommand . ' ' . escapeshellarg($module), $probeStatus);
    $assert($probeStatus === 0, 'Fresh direct inclusion failed for ' . $module . '.');
}
foreach (['disabled-pickup', 'disabled-delivery', 'language-loading'] as $probe) {
    passthru($probeCommand . ' ' . escapeshellarg($probe), $probeStatus);
    $assert($probeStatus === 0, 'Disabled-module final-order guard failed for ' . $probe . '.');
}

require $pluginRoot . '/catalog/includes/modules/shipping/iemspickup.php';
require $pluginRoot . '/catalog/includes/modules/shipping/iemsdelivery.php';

$db = new IemsShippingFakeDb();
$_SESSION = [
    'customer_id' => 42,
    'cart' => new IemsShippingFakeCart(),
];
$pickup = new iemspickup();
$delivery = new iemsdelivery();
$assert(
    $pickup->quote()['methods'] === [] && $delivery->quote()['methods'] === [],
    'IEMS methods must not quote before the server confirms a cart-bound selection.'
);

$_SESSION[IemsCheckoutUnitService::SESSION_KEY] = $makeSelection();
$pickup = new iemspickup();
$delivery = new iemsdelivery();
$assert($pickup->enabled && $delivery->enabled, 'A ready active unit should enable both methods.');
$assert(
    $pickup->quote()['methods'][0]['cost'] === 0.00
        && $delivery->quote()['methods'][0]['cost'] === 0.00,
    'Pickup and Marion County delivery must cost numeric zero.'
);
$assert($pickup->quote()['methods'][0]['title'] === 'Pickup at IEMS Logistics', 'Pickup label must remain exact.');
$assert($delivery->quote()['methods'][0]['title'] === 'Marion County Agency Delivery', 'County 049 defaults to Marion delivery.');
$db->countyNumber = '49';
$assert($delivery->quote()['methods'][0]['title'] === 'Marion County Agency Delivery', 'Legacy county 49 must also identify Marion County 049.');
$db->category = 'iems';
$assert($delivery->quote()['methods'][0]['title'] === 'Indianapolis EMS / Eskenazi Delivery', 'The explicit IEMS category must select the exact label.');
$db->countyNumber = '029';
$db->category = 'out_of_county';
$db->miles = '10.50';
$assert(
    $delivery->quote()['methods'][0] === [
        'id' => 'iemsdelivery',
        'title' => 'Out-of-County Agency Delivery',
        'cost' => 40.78,
    ],
    'Default rates must calculate 35 + 10.50 x 0.55, rounded half-up.'
);
foreach (['0' => 35.0, '1' => 35.55, '0.01' => 35.01, '0.10' => 35.06, '99999.99' => 55034.99] as $miles => $cost) {
    $db->miles = (string)$miles;
    $assert($delivery->quote()['methods'][0]['cost'] === $cost, 'Exact decimal price mismatch: ' . $miles);
}
foreach ([null, '', '-1', 'NaN', 'INF', '1e2', '1.234', '100000', ' 1', '1 ', [], true, 1.5] as $miles) {
    $db->miles = $miles;
    $assert($delivery->quote()['methods'] === [], 'Malformed or missing mileage must not quote.');
}
$db->miles = '10.00';
foreach (['1', '01', '001', '29', '029', '92', '092'] as $county) {
    $db->countyNumber = $county;
    $assert($delivery->quote()['methods'][0]['cost'] === 40.50, 'Valid legacy/padded out-of-county codes must price identically.');
}
foreach ([null, '', '-1', 'NaN', '1e2', '0.555', '100000', [], true] as $rate) {
    $db->rates['MODULE_SHIPPING_IEMSDELIVERY_PER_MILE_RATE'] = $rate;
    $assert($delivery->quote()['methods'] === [], 'Malformed rate must not produce a quote.');
}
unset($db->rates['MODULE_SHIPPING_IEMSDELIVERY_PER_MILE_RATE']);
$assert($delivery->quote()['methods'] === [], 'Missing rate must not fall back to the default.');
$db->rates['MODULE_SHIPPING_IEMSDELIVERY_PER_MILE_RATE'] = '0.55';
$db->rates['MODULE_SHIPPING_IEMSDELIVERY_FLAT_RATE'] = '12.34';
$assert($delivery->quote()['methods'][0]['cost'] === 17.84, 'Rates must be freshly loaded for each quote.');
$db->rates['MODULE_SHIPPING_IEMSDELIVERY_FLAT_RATE'] = '35.00';
foreach ([
    ['049', 'out_of_county'], ['49', 'out_of_county'], ['029', 'marion'], ['029', 'iems'],
    ['049', 'invalid'], [null, 'marion'], ['049', null], ['049', []],
    ['', 'out_of_county'], [' 49', 'marion'], ['49 ', 'marion'], ['49.0', 'marion'],
    ['4.9e1', 'marion'], ['0049', 'marion'], ['0', 'out_of_county'], ['000', 'out_of_county'],
    ['093', 'out_of_county'], ['999', 'out_of_county'], ['49x', 'marion'],
] as [$county, $category]) {
    $db->countyNumber = $county;
    $db->category = $category;
    $assert($delivery->quote()['methods'] === [], 'Inconsistent county/category must fail closed.');
}
$db->countyNumber = '049';
$db->category = 'marion';
$db->shippingSchemaReady = false;
$assert($delivery->quote()['methods'] === [], 'Missing shipping schema must fail closed.');
$assert($pickup->quote()['methods'] !== [], 'Missing delivery data must leave eligible pickup available.');
$db->shippingSchemaReady = true;
foreach ([['Type' => 'varchar(24)'], ['Null' => 'YES'], ['Default' => 'iems'], ['Field' => 'wrong'], ['Extra' => 'STORED GENERATED']] as $override) {
    $db->schemaOverride = $override;
    $assert($delivery->quote()['methods'] === [], 'Malformed shipping schema must fail closed.');
}
$db->schemaOverride = [];
foreach ([['Type' => 'float'], ['Null' => 'NO'], ['Default' => '0.00'], ['Field' => 'wrong'], ['Extra' => 'VIRTUAL GENERATED']] as $override) {
    $db->mileageSchemaOverride = $override;
    $assert($delivery->quote()['methods'] === [], 'Malformed mileage schema must fail closed.');
}
$db->mileageSchemaOverride = [];
$assert($delivery->quote('forged')['methods'] === [], 'Unknown delivery method must fail closed.');
$assert($pickup->quote('forged')['methods'] === [], 'Unknown pickup method must fail closed.');
$assert(
    $pickup->code === 'iemspickup'
        && $delivery->code === 'iemsdelivery'
        && explode('_', 'iemspickup_iemspickup') === ['iemspickup', 'iemspickup']
        && explode('_', 'iemsdelivery_iemsdelivery') === ['iemsdelivery', 'iemsdelivery'],
    'Exact underscore-free module codes must preserve Zen Cart parser behavior.'
);

$db->deliveryEnabled = false;
$assert($delivery->quote()['methods'] === [], 'The current agency flag must disable delivery immediately.');
$assert($pickup->quote()['methods'] !== [], 'The current agency flag must not disable pickup.');
$db->deliveryEnabled = true;
$db->streetAddress = '';
$assert($delivery->quote()['methods'] === [], 'A missing unit address must fail delivery closed.');
$db->streetAddress = '100 Unit Lane';

$db->hasUnit = false;
$_SESSION[IemsCheckoutUnitService::SESSION_KEY] = $makeSelection('agency');
$pickup = new iemspickup();
$delivery = new iemsdelivery();
$assert($pickup->quote()['methods'] !== [], 'A confirmed zero-unit agency fallback must retain pickup.');
$assert($delivery->quote()['methods'] === [], 'An agency fallback must never qualify for unit delivery.');

$iemsGuestCheckout = true;
$assert((new iemspickup())->quote()['methods'] === [], 'Guest checkout must fail pickup closed.');
$assert((new iemsdelivery())->quote()['methods'] === [], 'Guest checkout must fail delivery closed.');
$iemsGuestCheckout = false;

$addressService = new IemsShippingAddressService();
$pickupAddress = $addressService->getDestination(42, IemsShippingAddressService::MODULE_PICKUP);
$assert(
    $pickupAddress !== null
        && $pickupAddress['firstname'] === 'IEMS Logistics'
        && $pickupAddress['street_address'] === '3930 Georgetown Road'
        && $pickupAddress['state'] === 'Indiana'
        && $pickupAddress['country']['title'] === 'United States'
        && $pickupAddress['suburb'] === '49 IEMS Indianapolis EMS',
    'Pickup must resolve its configured destination and preserve the fallback label.'
);

$assert(
    $pickup->keys() === [
        'MODULE_SHIPPING_IEMSPICKUP_STATUS',
        'MODULE_SHIPPING_IEMSPICKUP_RECIPIENT',
        'MODULE_SHIPPING_IEMSPICKUP_COMPANY',
        'MODULE_SHIPPING_IEMSPICKUP_STREET_ADDRESS',
        'MODULE_SHIPPING_IEMSPICKUP_CITY',
        'MODULE_SHIPPING_IEMSPICKUP_POSTCODE',
        'MODULE_SHIPPING_IEMSPICKUP_SORT_ORDER',
    ],
    'Pickup must expose the complete fixed-destination configuration.'
);
$assert(
    $delivery->keys() === [
        'MODULE_SHIPPING_IEMSDELIVERY_STATUS',
        'MODULE_SHIPPING_IEMSDELIVERY_SORT_ORDER',
        'MODULE_SHIPPING_IEMSDELIVERY_FLAT_RATE',
        'MODULE_SHIPPING_IEMSDELIVERY_PER_MILE_RATE',
    ],
    'Delivery must retain native controls and expose global rates.'
);

$db->writes = [];
$pickup->install();
$delivery->install();
$assert(
    count($db->writes) === 8,
    'Module install must include the global rate configuration.'
);
foreach ($db->writes as $write) {
    $assert(!str_contains($write, '_COST'), 'IEMS module configuration must not expose a cost.');
}
$assert(
    str_contains(implode("\n", $db->writes), "'MODULE_SHIPPING_IEMSDELIVERY_FLAT_RATE', '35.00'")
        && str_contains(implode("\n", $db->writes), "'MODULE_SHIPPING_IEMSDELIVERY_PER_MILE_RATE', '0.55'"),
    'Installation must seed the specified default rates.'
);
$delivery->remove();
$assert(
    str_contains(end($db->writes), 'MODULE_SHIPPING_IEMSDELIVERY_PER_MILE_RATE')
        && !str_contains(end($db->writes), TABLE_IEMS_UNITS),
    'Native remove deletes global module configuration, never unit mileage.'
);

$pickupModule = file_get_contents(
    $pluginRoot . '/catalog/includes/modules/shipping/iemspickup.php'
);
$deliveryModule = file_get_contents(
    $pluginRoot . '/catalog/includes/modules/shipping/iemsdelivery.php'
);
$requiredHelperLoad = "require_once dirname(__DIR__, 2) . '/classes/IemsShippingEligibilityService.php';";
$assert(
    str_contains($pickupModule, $requiredHelperLoad)
        && str_contains($deliveryModule, $requiredHelperLoad),
    'Each shipping module must deterministically load eligibility on direct inclusion.'
);

$checkoutShipping = file_get_contents(
    $repositoryRoot . '/includes/modules/pages/checkout_shipping/header_php.php'
);
$opcAjax = file_get_contents($repositoryRoot . '/includes/classes/ajax/zcAjaxOnePageCheckout.php');
$assert(
    str_contains($checkoutShipping, "list(\$module, \$method) = explode('_', \$_POST['shipping'])")
        && str_contains($opcAjax, "explode('_', \$_POST['shipping_selection'])"),
    'Standard and OPC parsing guards must remain aligned with exact module IDs.'
);

$installer = file_get_contents($pluginRoot . '/Installer/ScriptedInstaller.php');
foreach (
    [
        "'delivery_street_address', 128",
        "'delivery_city', 128",
        "'delivery_postcode', 64",
        'addPickupAddressConfiguration',
        'MODULE_SHIPPING_IEMSPICKUP_RECIPIENT',
    ] as $fragment
) {
    $assert(str_contains($installer, $fragment), 'Installer is missing v1.7 behavior: ' . $fragment);
}

require $repositoryRoot . '/includes/classes/traits/NotifierManager.php';
require $repositoryRoot . '/includes/classes/shipping.php';
require $pluginRoot . '/catalog/includes/classes/observers/class.iems_checkout_unit_observer.php';

class IemsHarnessShipping extends shipping
{
    public function notify(
        string $eventID,
        mixed $param1 = [],
        mixed &$param2 = null,
        mixed &$param3 = null,
        mixed &$param4 = null,
        mixed &$param5 = null,
        mixed &$param6 = null,
        mixed &$param7 = null,
        mixed &$param8 = null,
        mixed &$param9 = null
    ): void {
        global $observer;
        $observer->update($this, $eventID, $param1, $param2, $param3, $param4, $param5, $param6, $param7);
    }
}

$db = new IemsShippingFakeDb();
$_SESSION[IemsCheckoutUnitService::SESSION_KEY] = $makeSelection();
$observer = new zcObserverIemsCheckoutUnit();
$GLOBALS['iemspickup'] = new iemspickup();
$GLOBALS['iemsdelivery'] = new iemsdelivery();
$GLOBALS['iemspickup']->quote();
$GLOBALS['iemsdelivery']->quote();
$shipping = (new ReflectionClass(IemsHarnessShipping::class))->newInstanceWithoutConstructor();
foreach ([['iemsdelivery.php', 'iemspickup.php'], ['iemspickup.php', 'iemsdelivery.php']] as $modules) {
    $shipping->modules = $modules;
    $assert(
        $shipping->cheapest()['id'] === 'iemspickup_iemspickup',
        'Core shipping::cheapest() must dispatch the pickup preference regardless of module sort.'
    );
}
$GLOBALS['iemspickup']->enabled = false;
$assert($shipping->cheapest()['id'] === 'iemsdelivery_iemsdelivery', 'Disabled pickup must never become the default.');

echo "IEMS shipping modules/address/pricing/default-selection harness passed.\n";
