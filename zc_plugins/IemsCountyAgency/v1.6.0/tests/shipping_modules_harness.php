<?php

declare(strict_types=1);

define('IS_ADMIN_FLAG', false);
define('TABLE_CONFIGURATION', 'configuration');
define('TABLE_IEMS_AGENCIES', 'iems_agencies');
define('TABLE_IEMS_COUNTIES', 'iems_counties');
define('TABLE_IEMS_CUSTOMER_AFFILIATIONS', 'iems_customer_affiliations');
define('MODULE_SHIPPING_IEMS_PICKUP_STATUS', 'True');
define('MODULE_SHIPPING_IEMS_PICKUP_SORT_ORDER', '0');
define('MODULE_SHIPPING_IEMS_DELIVERY_STATUS', 'True');
define('MODULE_SHIPPING_IEMS_DELIVERY_SORT_ORDER', '10');

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
    public bool $fieldExists = true;
    public string $fieldType = 'tinyint(1)';
    public string $fieldNull = 'NO';
    public mixed $fieldDefault = '0';

    /** @var array<int, array{delivery_enabled: mixed}> */
    public array $affiliationRows = [['delivery_enabled' => '0']];

    public int $fieldQueries = 0;
    public int $eligibilityQueries = 0;

    /** @var string[] */
    public array $writes = [];

    public function bindVars(string $sql, string $placeholder, mixed $value, string $type): string
    {
        return str_replace($placeholder, (string)$value, $sql);
    }

    public function Execute(string $sql): IemsShippingFakeResult
    {
        if (str_contains($sql, 'SHOW COLUMNS FROM ' . TABLE_IEMS_AGENCIES)) {
            $this->fieldQueries++;

            return new IemsShippingFakeResult(
                $this->fieldExists
                    ? [[
                        'Field' => 'delivery_enabled',
                        'Type' => $this->fieldType,
                        'Null' => $this->fieldNull,
                        'Default' => $this->fieldDefault,
                    ]]
                    : []
            );
        }

        if (str_contains($sql, 'SELECT a.delivery_enabled')) {
            foreach (
                [
                    'JOIN ' . TABLE_IEMS_COUNTIES,
                    'c.status = 1',
                    'JOIN ' . TABLE_IEMS_AGENCIES,
                    'a.county_ID = f.county_ID',
                    'a.status = 1',
                    'WHERE f.customer_id = ',
                    'LIMIT 2',
                ] as $requiredSql
            ) {
                if (!str_contains($sql, $requiredSql)) {
                    throw new RuntimeException('Eligibility query is missing: ' . $requiredSql);
                }
            }
            $this->eligibilityQueries++;

            return new IemsShippingFakeResult($this->affiliationRows);
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
foreach (['iems_pickup', 'iems_delivery'] as $module) {
    $language = require $pluginRoot
        . '/catalog/includes/languages/english/modules/shipping/lang.' . $module . '.php';
    foreach ($language as $name => $value) {
        define($name, $value);
    }
}

require $pluginRoot . '/catalog/includes/classes/IemsShippingEligibilityService.php';
require $pluginRoot . '/catalog/includes/modules/shipping/iems_pickup.php';
require $pluginRoot . '/catalog/includes/modules/shipping/iems_delivery.php';

$db = new IemsShippingFakeDb();
$service = new IemsShippingEligibilityService();

$assert(!$service->isPickupEligible(0), 'Unsigned customers must not qualify for pickup.');
$assert(!$service->isDeliveryEligible(0), 'Unsigned customers must not qualify for delivery.');
$assert($service->isPickupEligible(42), 'A valid active affiliation must qualify for pickup.');
$assert(!$service->isDeliveryEligible(42), 'Delivery must default disabled for an eligible agency.');

$db->affiliationRows = [['delivery_enabled' => '1']];
$assert($service->isDeliveryEligible(42), 'The current enabled agency flag must qualify delivery.');

$db->affiliationRows = [];
$assert(
    !$service->isPickupEligible(42) && !$service->isDeliveryEligible(42),
    'Missing, inactive, or cross-county affiliation rows must fail closed.'
);

$db->affiliationRows = [
    ['delivery_enabled' => '1'],
    ['delivery_enabled' => '1'],
];
$assert(
    !$service->isPickupEligible(42) && !$service->isDeliveryEligible(42),
    'Duplicate affiliation rows must fail closed as a database mismatch.'
);

$db->affiliationRows = [['delivery_enabled' => '2']];
$assert(
    !$service->isPickupEligible(42) && !$service->isDeliveryEligible(42),
    'Unexpected delivery field values must fail both shipping methods closed.'
);

$db->fieldExists = false;
$db->affiliationRows = [['delivery_enabled' => '1']];
$assert(
    !$service->isPickupEligible(42) && !$service->isDeliveryEligible(42),
    'A missing delivery field must fail both shipping methods closed.'
);
$db->fieldExists = true;

$_SESSION = [
    'customer_id' => 42,
    'iems_checkout_unit' => [
        'selection_type' => 'unit',
        'reference_id' => 8,
    ],
];
$db->affiliationRows = [['delivery_enabled' => '1']];
$pickup = new iems_pickup();
$delivery = new iems_delivery();
$assert($pickup->enabled && $delivery->enabled, 'Both modules should enable for an eligible agency.');

$iemsGuestCheckout = true;
$assert(
    (new iems_pickup())->quote()['methods'] === []
        && (new iems_delivery())->quote()['methods'] === [],
    'One-Page Checkout guests with a numeric pseudo-customer ID must fail closed.'
);
$iemsGuestCheckout = false;

$pickupQuote = $pickup->quote();
$deliveryQuote = $delivery->quote();
$assert(
    $pickupQuote['methods'][0]['cost'] === 0.00
        && $deliveryQuote['methods'][0]['cost'] === 0.00,
    'Pickup and delivery quote costs must remain hardcoded numeric zero.'
);
$assert(
    $pickupQuote['methods'][0]['title'] === 'Pickup at IEMS Logistics'
        && $deliveryQuote['methods'][0]['title'] === 'Delivery to Location',
    'Each shipping module must return its approved customer-facing title.'
);

$_SESSION['iems_checkout_unit']['selection_type'] = 'agency';
$_SESSION['iems_checkout_unit']['reference_id'] = 71;
$assert(
    $pickup->quote()['methods'] !== [] && $delivery->quote()['methods'] !== [],
    'Shipping eligibility must be independent of unit versus agency-fallback selection.'
);

$db->affiliationRows = [['delivery_enabled' => '0']];
$fieldQueries = $db->fieldQueries;
$eligibilityQueries = $db->eligibilityQueries;
$assert(
    $delivery->quote()['methods'] === [],
    'Disabling delivery must remove its quote immediately without session caching.'
);
$assert(
    $db->fieldQueries === $fieldQueries + 1
        && $db->eligibilityQueries === $eligibilityQueries + 1,
    'Delivery quote processing must re-query current schema and affiliation state.'
);
$db->affiliationRows = [['delivery_enabled' => '1']];
$fieldQueries = $db->fieldQueries;
$eligibilityQueries = $db->eligibilityQueries;
$assert(
    $delivery->quote()['methods'] !== [],
    'Enabling delivery must restore its quote immediately without session caching.'
);
$assert(
    $db->fieldQueries === $fieldQueries + 1
        && $db->eligibilityQueries === $eligibilityQueries + 1,
    'Repeated delivery quote processing must re-query current schema and affiliation state.'
);

unset($_SESSION['customer_id']);
$assert(
    (new iems_pickup())->quote()['methods'] === []
        && (new iems_delivery())->quote()['methods'] === [],
    'Guest module construction and quoting must fail closed.'
);

$_SESSION['customer_id'] = 42;
$pickup = new iems_pickup();
$delivery = new iems_delivery();
$assert(
    $pickup->keys() === [
        'MODULE_SHIPPING_IEMS_PICKUP_STATUS',
        'MODULE_SHIPPING_IEMS_PICKUP_SORT_ORDER',
    ],
    'Pickup must expose only enable and sort-order configuration.'
);
$assert(
    $delivery->keys() === [
        'MODULE_SHIPPING_IEMS_DELIVERY_STATUS',
        'MODULE_SHIPPING_IEMS_DELIVERY_SORT_ORDER',
    ],
    'Delivery must expose only enable and sort-order configuration.'
);

$db->writes = [];
$pickup->install();
$delivery->install();
$assert(count($db->writes) === 4, 'Each module install should create exactly two configuration rows.');
foreach ($db->writes as $write) {
    $assert(
        !str_contains($write, '_COST')
            && !str_contains($write, 'ALTER TABLE')
            && !str_contains($write, 'iems_agencies'),
        'Shipping module configuration must not expose cost or manage schema.'
    );
}
$pickup->remove();
$delivery->remove();
$assert(count($db->writes) === 6, 'Each module remove should delete only its configuration keys.');

$moduleFinder = file_get_contents($repositoryRoot . '/includes/classes/ResourceLoaders/ModuleFinder.php');
$shipping = file_get_contents($repositoryRoot . '/includes/classes/shipping.php');
$opcShipping = file_get_contents(
    $repositoryRoot . '/includes/templates/template_default/templates/tpl_modules_checkout_one_shipping.php'
);
$assert(
    str_contains($moduleFinder, "/catalog/includes/modules/' . \$this->moduleDir"),
    'ModuleFinder must discover plugin-packaged shipping modules.'
);
$assert(
    str_contains($shipping, "new ModuleFinder('shipping', new FileSystem())")
        && str_contains($shipping, "loadModuleLanguageFile(\$quote_module['file'], 'shipping')"),
    'Standard shipping initialization must discover module code and plugin language files.'
);
$assert(
    str_contains($opcShipping, 'shipping'),
    'One-Page Checkout must continue to use the shared shipping module pipeline.'
);

$installer = file_get_contents($pluginRoot . '/Installer/ScriptedInstaller.php');
$assert(
    str_contains($installer, "SHOW COLUMNS FROM `iems_agencies` LIKE 'delivery_enabled'")
        && str_contains($installer, 'ADD COLUMN `delivery_enabled` tinyint(1) NOT NULL DEFAULT 0')
        && str_contains($installer, 'MODIFY COLUMN `delivery_enabled` tinyint(1) NOT NULL DEFAULT 0')
        && substr_count($installer, '$this->addDeliveryEnabledColumn();') === 2,
    'Plugin install and upgrade must idempotently add or normalize a default-disabled delivery field.'
);
$assert(
    !str_contains($installer, 'DROP COLUMN `delivery_enabled`')
        && !str_contains($installer, 'DROP TABLE IF EXISTS `iems_agencies`'),
    'Plugin uninstall must preserve all IEMS data and the delivery field.'
);
$assert(
    str_contains($installer, 'new iems_pickup(uninstalling: true)')
        && str_contains($installer, 'new iems_delivery(uninstalling: true)')
        && substr_count($installer, '->remove();') === 2,
    'Plugin uninstall must remove both shipping modules through their configuration-only lifecycle.'
);

$db->fieldType = 'varchar(10)';
$db->fieldNull = 'YES';
$db->fieldDefault = null;
$db->affiliationRows = [['delivery_enabled' => '1']];
$assert(
    !$service->isPickupEligible(42) && !$service->isDeliveryEligible(42),
    'A malformed pre-existing delivery column must fail runtime eligibility closed.'
);

echo "IEMS shipping modules harness passed.\n";
