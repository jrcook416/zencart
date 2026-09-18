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

    /** @var string[] */
    public array $writes = [];

    public function bindVars(string $sql, string $placeholder, mixed $value, string $type): string
    {
        return str_replace($placeholder, (string)$value, $sql);
    }

    public function Execute(string $sql): IemsShippingFakeResult
    {
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
foreach (['disabled-pickup', 'disabled-delivery'] as $probe) {
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
    'Both IEMS costs must remain hardcoded numeric zero.'
);
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
    ],
    'Delivery must retain enable and sort-order configuration only.'
);

$db->writes = [];
$pickup->install();
$delivery->install();
$assert(
    count($db->writes) === 7,
    'Pickup install should write status, sort, and one batched five-field address row; delivery writes two rows.'
);
foreach ($db->writes as $write) {
    $assert(!str_contains($write, '_COST'), 'IEMS module configuration must not expose a cost.');
}

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

echo "IEMS shipping modules/address harness passed.\n";
