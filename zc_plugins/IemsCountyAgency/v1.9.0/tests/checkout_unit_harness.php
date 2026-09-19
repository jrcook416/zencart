<?php

declare(strict_types=1);

define('TABLE_IEMS_CUSTOMER_AFFILIATIONS', 'iems_customer_affiliations');
define('TABLE_IEMS_COUNTIES', 'iems_counties');
define('TABLE_IEMS_AGENCIES', 'iems_agencies');
define('TABLE_IEMS_UNITS', 'iems_units');
define('TABLE_COUNTRIES', 'countries');
define('TABLE_ZONES', 'zones');
define('TABLE_CONFIGURATION', 'configuration');
define('FILENAME_CHECKOUT_SHIPPING', 'checkout_shipping');
define('FILENAME_CHECKOUT_PAYMENT', 'checkout_payment');
define('FILENAME_CHECKOUT_ONE', 'checkout_one');
define('STORE_PRODUCT_TAX_BASIS', 'Shipping');
define('STORE_ZONE', 18);
define('MODULE_SHIPPING_IEMSPICKUP_RECIPIENT', 'IEMS Logistics');
define('MODULE_SHIPPING_IEMSPICKUP_COMPANY', 'Indianapolis EMS');
define('MODULE_SHIPPING_IEMSPICKUP_STREET_ADDRESS', '3930 Georgetown Road');
define('MODULE_SHIPPING_IEMSPICKUP_CITY', 'Indianapolis');
define('MODULE_SHIPPING_IEMSPICKUP_POSTCODE', '46254');
define('MODULE_SHIPPING_IEMSPICKUP_STATUS', 'True');
define('MODULE_SHIPPING_IEMSDELIVERY_STATUS', 'True');

final class IemsCheckoutUnitFakeResult
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
}

final class IemsCheckoutUnitFakeDb
{
    public bool $hasActiveAffiliation = true;
    public bool $hasValidUnit = true;
    public int $countyId = 49;
    public string $countyNumber = '49';
    public int $agencyId = 71;
    public string $agencyIdentifier = 'IEMS';
    public string $agencyName = 'Indianapolis EMS';
    public string $unitName = 'Medic 1';
    public string $streetAddress = '3930 Georgetown Road';
    public string $city = 'Indianapolis';
    public string $postcode = '46254';
    public bool $deliveryEnabled = true;
    public bool $schemaReady = true;
    public string $shippingCounty = '049';
    public string $shippingCategory = 'marion';
    public mixed $miles = '10.50';
    public string $flatRate = '35.00';
    public string $perMileRate = '0.55';

    /** @var array<int, array<string, mixed>> */
    public array $activeUnits = [[
        'unit_ID' => 8,
        'unit_identifier' => 'MED1',
        'unit_name' => 'Medic 1',
    ]];

    public function bindVars(string $sql, string $placeholder, mixed $value, string $type): string
    {
        return str_replace($placeholder, (string)$value, $sql);
    }

    public function Execute(string $sql): IemsCheckoutUnitFakeResult
    {
        if (str_contains($sql, "LIKE 'shipping_category'") || str_contains($sql, "LIKE 'one_way_miles'")) {
            $category = str_contains($sql, "LIKE 'shipping_category'");
            return new IemsCheckoutUnitFakeResult([[
                'Field' => $category ? 'shipping_category' : 'one_way_miles',
                'Type' => $category ? "enum('marion','iems','out_of_county')" : 'decimal(7,2)',
                'Null' => $category ? 'NO' : 'YES',
                'Default' => $category ? 'marion' : null,
            ]]);
        }
        if (str_contains($sql, 'SELECT u.one_way_miles')) {
            return new IemsCheckoutUnitFakeResult([[
                'one_way_miles' => $this->miles,
                'county_number' => $this->shippingCounty,
                'shipping_category' => $this->shippingCategory,
            ]]);
        }
        if (str_contains($sql, 'SELECT configuration_key, configuration_value')) {
            return new IemsCheckoutUnitFakeResult([
                ['configuration_key' => 'MODULE_SHIPPING_IEMSDELIVERY_FLAT_RATE', 'configuration_value' => $this->flatRate],
                ['configuration_key' => 'MODULE_SHIPPING_IEMSDELIVERY_PER_MILE_RATE', 'configuration_value' => $this->perMileRate],
            ]);
        }
        if (preg_match('/^\s*(INSERT|UPDATE|DELETE|REPLACE)\b/i', $sql) === 1) {
            throw new RuntimeException('Checkout selection validation must be read-only.');
        }

        if (str_contains($sql, 'SHOW COLUMNS FROM ' . TABLE_IEMS_AGENCIES)) {
            return new IemsCheckoutUnitFakeResult([[
                'Field' => 'delivery_enabled',
                'Type' => 'tinyint(1)',
                'Null' => 'NO',
                'Default' => '0',
            ]]);
        }

        if (str_contains($sql, 'SHOW COLUMNS FROM ' . TABLE_IEMS_UNITS)) {
            preg_match("/LIKE '([^']+)'/", $sql, $matches);
            $column = $matches[1] ?? '';
            $types = [
                'delivery_street_address' => 'varchar(128)',
                'delivery_city' => 'varchar(128)',
                'delivery_postcode' => 'varchar(64)',
            ];

            return new IemsCheckoutUnitFakeResult(
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

        if (str_contains($sql, 'FROM ' . TABLE_COUNTRIES . ' c')) {
            return new IemsCheckoutUnitFakeResult([[
                'countries_id' => 223,
                'countries_name' => 'United States',
                'countries_iso_code_2' => 'US',
                'countries_iso_code_3' => 'USA',
                'address_format_id' => 2,
                'zone_id' => 18,
                'zone_name' => 'Indiana',
            ]]);
        }

        if (str_contains($sql, 'SELECT c.county_ID, c.county_number')) {
            foreach (
                [
                    'JOIN ' . TABLE_IEMS_COUNTIES,
                    'c.status = 1',
                    'JOIN ' . TABLE_IEMS_AGENCIES,
                    'a.county_ID = f.county_ID',
                    'a.status = 1',
                ] as $requiredSql
            ) {
                if (!str_contains($sql, $requiredSql)) {
                    throw new RuntimeException('Affiliation query is missing: ' . $requiredSql);
                }
            }

            return new IemsCheckoutUnitFakeResult(
                $this->hasActiveAffiliation
                    ? [[
                        'county_ID' => $this->countyId,
                        'county_number' => $this->countyNumber,
                        'agency_ID' => $this->agencyId,
                        'agency_identifier' => $this->agencyIdentifier,
                        'agency_name' => $this->agencyName,
                        'delivery_enabled' => $this->deliveryEnabled ? '1' : '0',
                    ]]
                    : []
            );
        }

        if (str_contains($sql, 'SELECT u.unit_ID, u.unit_identifier')) {
            foreach (['u.county_ID = ', 'u.agency_ID = ', 'u.status = 1'] as $requiredSql) {
                if (!str_contains($sql, $requiredSql)) {
                    throw new RuntimeException('Active-unit query is missing: ' . $requiredSql);
                }
            }

            return new IemsCheckoutUnitFakeResult($this->activeUnits);
        }

        if (str_contains($sql, 'SELECT u.unit_identifier, u.unit_name')) {
            foreach (['u.unit_ID = ', 'u.county_ID = ', 'u.agency_ID = ', 'u.status = 1'] as $requiredSql) {
                if (!str_contains($sql, $requiredSql)) {
                    throw new RuntimeException('Unit-validation query is missing: ' . $requiredSql);
                }
            }

            return new IemsCheckoutUnitFakeResult(
                $this->hasValidUnit
                    ? [[
                        'unit_identifier' => 'MED1',
                        'unit_name' => $this->unitName,
                        'delivery_street_address' => $this->streetAddress,
                        'delivery_city' => $this->city,
                        'delivery_postcode' => $this->postcode,
                    ]]
                    : []
            );
        }

        throw new RuntimeException('Unexpected query: ' . $sql);
    }
}

class base
{
    public function attach(object $observer, array $events): void
    {
    }
}

final class IemsCheckoutUnitFakeMessageStack
{
    /** @var array<int, array{stack: string, message: string, type: string}> */
    public array $messages = [];

    public function add(string $stack, string $message, string $type): void
    {
        $this->messages[] = [
            'stack' => $stack,
            'message' => $message,
            'type' => $type,
        ];
    }

    public function add_session(string $stack, string $message, string $type): void
    {
        $this->add($stack, $message, $type);
    }
}

final class IemsCheckoutUnitFakeCart
{
    public function __construct(public string $cartID)
    {
    }

    public function get_content_type(): string
    {
        return 'physical';
    }
}

final class IemsCheckoutUnitFakeRedirect extends RuntimeException
{
}

/**
 * @param array<int, array{id: int|string, text: string}> $options
 */
function zen_draw_pull_down_menu(
    string $name,
    array $options,
    string $default,
    string $parameters
): string {
    $html = '<select name="' . $name . '" ' . $parameters . '>';
    foreach ($options as $option) {
        $value = (string)$option['id'];
        $selected = $value === $default ? ' selected="selected"' : '';
        $html .= '<option value="' . htmlspecialchars($value, ENT_QUOTES) . '"' . $selected . '>';
        $html .= htmlspecialchars($option['text'], ENT_QUOTES) . '</option>';
    }

    return $html . '</select>';
}

function zen_output_string_protected(string $value): string
{
    return htmlspecialchars($value, ENT_QUOTES);
}

function zen_href_link(string $page, string $parameters, string $connection): string
{
    return $page;
}

function zen_redirect(string $url): never
{
    throw new IemsCheckoutUnitFakeRedirect($url);
}

$assert = static function (bool $condition, string $message): void {
    if (!$condition) {
        throw new RuntimeException($message);
    }
};

$pluginRoot = dirname(__DIR__);
$language = require $pluginRoot
    . '/catalog/includes/languages/english/extra_definitions/lang.iems_checkout_unit.php';
foreach ($language as $name => $value) {
    define($name, $value);
}
$language = require $pluginRoot . '/catalog/includes/languages/english/modules/shipping/lang.iemsdelivery.php';
foreach ($language as $name => $value) {
    define($name, $value);
}

$db = new IemsCheckoutUnitFakeDb();
$_SESSION = [
    'customer_id' => 42,
    'cart' => new IemsCheckoutUnitFakeCart('cart-a'),
];

require $pluginRoot . '/catalog/includes/classes/IemsCheckoutUnitService.php';
require $pluginRoot . '/catalog/includes/classes/observers/class.iems_checkout_unit_observer.php';

$service = new IemsCheckoutUnitService();
$options = $service->getCheckoutOptionsForCustomer(42);
$assert(
    $options === [['id' => 8, 'text' => 'MED1 Medic 1']],
    'Active units must remain the only checkout choices when any active unit exists.'
);
$assert(
    $service->getActiveUnitsForCustomer(42) === $options,
    'The v1.5.0 active-unit lookup behavior must remain available.'
);

$selection = $service->saveSelection(42, '8', 'standard');
$assert($selection['valid'], 'A matching active unit should be accepted.');
$assert(
    $selection['selection_type'] === IemsCheckoutUnitService::SELECTION_TYPE_UNIT
    && $selection['reference_id'] === 8
    && $selection['selection_value'] === '8',
    'A unit selection must use the generalized selection result.'
);
$assert(
    $selection['label'] === '49 IEMS MED1 Medic 1',
    'The unit label must be rebuilt from current database values.'
);
$assert(
    $selection['street_address'] === '3930 Georgetown Road'
        && $selection['city'] === 'Indianapolis'
        && $selection['postcode'] === '46254'
        && $selection['delivery_enabled'],
    'A validated unit must carry only freshly queried managed delivery data.'
);
$assert(
    $_SESSION[IemsCheckoutUnitService::SESSION_KEY] === [
        'customer_id' => 42,
        'cart_id' => 'cart-a',
        'selection_type' => IemsCheckoutUnitService::SELECTION_TYPE_UNIT,
        'reference_id' => 8,
        'flow' => 'standard',
    ],
    'Transient state must contain only customer, cart, type, reference, and flow identifiers.'
);

$assert(
    !$service->validateSelection(42, ['8'])['valid'],
    'Non-scalar selection input must be rejected.'
);
$assert(
    $service->validateSelection(42, '8.0')['error'] === IemsCheckoutUnitService::ERROR_MALFORMED,
    'Non-integer unit input must be rejected.'
);
$assert(
    $service->validateSelection(42, 'agency-fallback-forged')['error']
        === IemsCheckoutUnitService::ERROR_MALFORMED,
    'A forged fallback token must be rejected.'
);

$db->hasValidUnit = false;
$assert(
    $service->validateSelection(42, '8')['error'] === IemsCheckoutUnitService::ERROR_UNIT,
    'Inactive or cross-hierarchy units must be rejected.'
);
$db->hasValidUnit = true;

$db->hasActiveAffiliation = false;
$assert(
    $service->validateSelection(42, '8')['error'] === IemsCheckoutUnitService::ERROR_AFFILIATION,
    'An inactive or internally inconsistent affiliation hierarchy must be rejected.'
);
$assert(
    $service->getCheckoutOptionsForCustomer(42) === [],
    'An inactive hierarchy must not expose a unit or fallback option.'
);
$db->hasActiveAffiliation = true;

$db->unitName = str_repeat('X', 129);
$assert(
    $service->validateSelection(42, '8')['error'] === IemsCheckoutUnitService::ERROR_LABEL_LENGTH,
    'Overlength unit labels must be rejected rather than truncated.'
);
$db->unitName = 'Medic 1';

$service->saveSelection(42, '8', 'opc');
$assert($service->getSelectionFlow() === 'opc', 'One-Page Checkout flow state must be retained.');
$_SESSION['cart']->cartID = 'cart-b';
$assert(
    $service->getValidatedSelection(42)['error'] === IemsCheckoutUnitService::ERROR_CART,
    'A selection from a different cart must be rejected.'
);
$assert(
    !isset($_SESSION[IemsCheckoutUnitService::SESSION_KEY]),
    'Stale cart selections must be cleared.'
);
$_SESSION['cart']->cartID = 'cart-a';

$db->activeUnits = [];
$options = $service->getCheckoutOptionsForCustomer(42);
$agencyLabel = '49 IEMS Indianapolis EMS';
$assert(
    $options === [[
        'id' => IemsCheckoutUnitService::AGENCY_FALLBACK_TOKEN,
        'text' => $agencyLabel,
    ]],
    'A valid active agency with zero active units must expose exactly one fallback entry.'
);
$assert(
    !isset($_SESSION[IemsCheckoutUnitService::SESSION_KEY]),
    'Displaying the agency fallback must not auto-select it.'
);
$assert(
    $service->getValidatedSelection(42)['error'] === IemsCheckoutUnitService::ERROR_MISSING,
    'The agency fallback must require explicit selection.'
);

$selection = $service->saveSelection(
    42,
    IemsCheckoutUnitService::AGENCY_FALLBACK_TOKEN,
    'standard'
);
$assert($selection['valid'], 'The explicit fallback token should be accepted when no active units exist.');
$assert(
    $selection['selection_type'] === IemsCheckoutUnitService::SELECTION_TYPE_AGENCY
    && $selection['reference_id'] === 71
    && $selection['selection_value'] === IemsCheckoutUnitService::AGENCY_FALLBACK_TOKEN,
    'The fallback must derive and retain the current agency reference server-side.'
);
$assert(
    $selection['label'] === $agencyLabel,
    'The agency label must be rebuilt as county number, agency identifier, and agency name.'
);
$assert(
    $_SESSION[IemsCheckoutUnitService::SESSION_KEY] === [
        'customer_id' => 42,
        'cart_id' => 'cart-a',
        'selection_type' => IemsCheckoutUnitService::SELECTION_TYPE_AGENCY,
        'reference_id' => 71,
        'flow' => 'standard',
    ],
    'Fallback state must remain transient and identifier-only.'
);

$observer = new zcObserverIemsCheckoutUnit();
$renderSelector = new ReflectionMethod($observer, 'renderSelector');
$fallbackHtml = $renderSelector->invoke($observer, $options, '');
$assert(
    str_contains($fallbackHtml, $agencyLabel),
    'The checkout fallback label must match the server-built order label.'
);

$db->activeUnits = [[
    'unit_ID' => 8,
    'unit_identifier' => 'MED1',
    'unit_name' => 'Medic 1',
]];
$service->saveSelection(42, '8', 'standard');
$_SESSION['shipping'] = ['id' => 'flat_flat', 'title' => 'Flat', 'cost' => 5.00];
$_POST = ['action' => 'process', 'iems_unit_id' => '9'];
$handleShippingPage = new ReflectionMethod($observer, 'handleShippingPage');
$redirected = false;
try {
    $handleShippingPage->invoke($observer);
} catch (IemsCheckoutUnitFakeRedirect) {
    $redirected = true;
}
$assert(
    $redirected && !isset($_SESSION['shipping']),
    'The no-JavaScript first step and any changed unit must clear shipping and refresh before quoting.'
);
$_POST = [];
$reloadedObserver = new zcObserverIemsCheckoutUnit();
$reloadedHandleShippingPage = new ReflectionMethod($reloadedObserver, 'handleShippingPage');
$reloadedHandleShippingPage->invoke($reloadedObserver);
$assert(
    ($GLOBALS['iems_checkout_shipping_ready'] ?? false) === true
        && $service->getValidatedSelection(42)['selection_value'] === '9',
    'The redirected GET must retain the cart-bound selection and advance beyond step one.'
);
$messageStack = new IemsCheckoutUnitFakeMessageStack();
$GLOBALS['quotes'] = [];
$GLOBALS['free_shipping'] = false;
$finalizeShippingPage = new ReflectionMethod($reloadedObserver, 'finalizeShippingPage');
$finalizeShippingPage->invoke($reloadedObserver);
$assert(
    ($GLOBALS['iems_checkout_shipping_methods_available'] ?? true) === false
        && end($messageStack->messages)['message'] === ERROR_IEMS_CHECKOUT_NO_SHIPPING_METHODS,
    'A confirmed selection with zero actual quote methods must fail explicitly instead of silently looping.'
);
$GLOBALS['quotes'] = [[
    'error' => 'Unavailable for this destination.',
    'methods' => [['id' => 'zones', 'cost' => 5.00]],
]];
$finalizeShippingPage->invoke($reloadedObserver);
$assert(
    ($GLOBALS['iems_checkout_shipping_methods_available'] ?? true) === false,
    'An errored quote must not count as a selectable method.'
);
unset(
    $GLOBALS['quotes'],
    $GLOBALS['free_shipping'],
    $GLOBALS['iems_checkout_shipping_methods_available']
);
$db->activeUnits = [];
$service->saveSelection(42, IemsCheckoutUnitService::AGENCY_FALLBACK_TOKEN, 'standard');

$db->activeUnits = [[
    'unit_ID' => 8,
    'unit_identifier' => 'MED1',
    'unit_name' => 'Medic 1',
]];
$service->saveSelection(42, '8', 'standard');
$_SESSION['shipping'] = [
    'id' => 'iemsdelivery_iemsdelivery',
    'title' => 'IEMS Agency Delivery (Marion County Agency Delivery)',
    'cost' => 0.00,
];
$order = (object)[
    'content_type' => 'physical',
    'info' => ['shipping_method' => $_SESSION['shipping']['title'], 'shipping_cost' => 0.00],
    'customer' => ['suburb' => 'old-customer', 'street_address' => 'customer-street'],
    'delivery' => ['suburb' => 'old-delivery', 'street_address' => 'address-book-street'],
    'billing' => ['suburb' => 'old-billing', 'street_address' => 'billing-street', 'zone_id' => 12],
];
$setOrderSuburbs = new ReflectionMethod($observer, 'setOrderSuburbs');
$taxCountryId = 38;
$taxZoneId = 74;
$setOrderSuburbs->invokeArgs($observer, [$order, &$taxCountryId, &$taxZoneId]);
$assert(
    $order->delivery['firstname'] === 'MED1 Medic 1'
        && $order->delivery['company'] === 'Indianapolis EMS'
        && $order->delivery['street_address'] === '3930 Georgetown Road'
        && $order->delivery['city'] === 'Indianapolis'
        && $order->delivery['postcode'] === '46254'
        && $order->delivery['state'] === 'Indiana'
        && $order->delivery['country']['title'] === 'United States'
        && $order->delivery['suburb'] === '49 IEMS MED1 Medic 1',
    'Delivery must use the current managed unit destination while preserving the canonical IEMS label.'
);
$assert(
    $order->customer['street_address'] === 'customer-street'
        && $order->billing['street_address'] === 'billing-street',
    'The delivery override must not alter customer or billing address data.'
);
$assert(
    $taxCountryId === 223 && $taxZoneId === 18,
    'Shipping-basis product tax must use the effective IEMS delivery destination.'
);
$enforceDelivery = new ReflectionMethod($observer, 'enforceOrderSelection');
$enforceDelivery->invoke($observer, $order);
$assert(
    $order->info['shipping_method'] === 'IEMS Agency Delivery (Marion County Agency Delivery)'
        && $order->info['shipping_cost'] === 0.00,
    'Final order must retain the selected delivery label and calculated cost.'
);
$validShipping = $_SESSION['shipping'];
$db->shippingCategory = 'iems';
$redirected = false;
try {
    $enforceDelivery->invoke($observer, $order);
} catch (IemsCheckoutUnitFakeRedirect) {
    $redirected = true;
}
$assert($redirected && !isset($_SESSION['shipping']), 'A stale same-price category must stop final order creation.');
$db->shippingCounty = '029';
$db->shippingCategory = 'out_of_county';
$paidShipping = [
    'id' => 'iemsdelivery_iemsdelivery',
    'title' => 'IEMS Agency Delivery (Out-of-County Agency Delivery)',
    'cost' => 40.78,
];
$_SESSION['shipping'] = $paidShipping;
$order->info = ['shipping_method' => $paidShipping['title'], 'shipping_cost' => $paidShipping['cost']];
$enforceDelivery->invoke($observer, $order);
foreach (['miles' => '20.00', 'flatRate' => '40.00', 'perMileRate' => '0.56'] as $field => $newValue) {
    $oldValue = $db->$field;
    $db->$field = $newValue;
    $_SESSION['shipping'] = $paidShipping;
    $redirected = false;
    try {
        $enforceDelivery->invoke($observer, $order);
    } catch (IemsCheckoutUnitFakeRedirect) {
        $redirected = true;
    }
    $assert($redirected && !isset($_SESSION['shipping']), 'Changed ' . $field . ' must reject stale final-order pricing.');
    $db->$field = $oldValue;
}
$_SESSION['shipping'] = $paidShipping;
$order->info['shipping_cost'] = 0;
$redirected = false;
try {
    $enforceDelivery->invoke($observer, $order);
} catch (IemsCheckoutUnitFakeRedirect) {
    $redirected = true;
}
$assert($redirected, 'A stale already-built order total must not survive final price revalidation.');
$db->shippingCounty = '049';
$db->shippingCategory = 'marion';
$preferPickup = new ReflectionMethod($observer, 'preferPickup');
$pickupRate = ['id' => 'iemspickup_iemspickup', 'title' => 'Pickup at IEMS Logistics', 'cost' => 0.00, 'module' => 'iemspickup'];
$cheapest = $validShipping;
$rates = [$validShipping, $pickupRate];
$preferPickup->invokeArgs($observer, [&$cheapest, $rates]);
$assert($cheapest === $pickupRate, 'Eligible pickup must win a free-delivery tie independent of display order.');
$cheapest = $validShipping;
$preferPickup->invokeArgs($observer, [&$cheapest, [$validShipping]]);
$assert($cheapest === $validShipping, 'No unavailable pickup method may be invented.');
$_SESSION['shipping'] = $validShipping;
$cheapest = $validShipping;
$preferPickup->invokeArgs($observer, [&$cheapest, $rates]);
$assert($_SESSION['shipping'] === $validShipping, 'Default selection must not overwrite an explicit delivery session selection.');
unset($_SESSION['shipping']);
$db->activeUnits = [];
$service->saveSelection(42, IemsCheckoutUnitService::AGENCY_FALLBACK_TOKEN, 'standard');
$pickupOrder = (object)[
    'content_type' => 'physical',
    'customer' => ['suburb' => 'old-customer', 'city' => 'customer-city'],
    'delivery' => ['suburb' => 'old-delivery', 'city' => 'address-book-city'],
    'billing' => ['suburb' => 'old-billing', 'city' => 'billing-city', 'zone_id' => 12],
];
$_SESSION['shipping'] = [
    'id' => 'iemspickup_iemspickup',
    'title' => 'Pickup at IEMS Logistics',
    'cost' => 0.00,
];
$taxCountryId = 38;
$taxZoneId = 74;
$setOrderSuburbs->invokeArgs($observer, [$pickupOrder, &$taxCountryId, &$taxZoneId]);
$assert(
    $pickupOrder->delivery['firstname'] === 'IEMS Logistics'
        && $pickupOrder->delivery['company'] === 'Indianapolis EMS'
        && $pickupOrder->delivery['state'] === 'Indiana'
        && $pickupOrder->delivery['country']['title'] === 'United States'
        && $pickupOrder->delivery['suburb'] === $agencyLabel,
    'Pickup must use the fixed module destination while preserving the fallback label.'
);
$assert(
    $pickupOrder->customer['city'] === 'customer-city'
        && $pickupOrder->billing['city'] === 'billing-city',
    'Pickup must leave customer and billing address arrays untouched apart from canonical suburbs.'
);
unset($_SESSION['shipping']);
$assert(
    !str_contains(
        $fallbackHtml,
        'value="' . IemsCheckoutUnitService::AGENCY_FALLBACK_TOKEN . '" selected="selected"'
    ),
    'The checkout fallback entry must not be selected by default.'
);
$selectedFallbackHtml = $renderSelector->invoke(
    $observer,
    $options,
    $selection['selection_value']
);
$assert(
    str_contains(
        $selectedFallbackHtml,
        'value="' . IemsCheckoutUnitService::AGENCY_FALLBACK_TOKEN . '" selected="selected"'
    ),
    'An explicitly saved fallback should remain selected for the same customer and cart.'
);

$messageStack = new IemsCheckoutUnitFakeMessageStack();
$showConfirmation = new ReflectionMethod($observer, 'showConfirmation');
$showConfirmation->invoke($observer);
$assert(
    $messageStack->messages[0]['message'] === sprintf(TEXT_IEMS_CHECKOUT_UNIT_CONFIRMATION, $agencyLabel),
    'Checkout confirmation must display the identical agency label.'
);

$order = (object)[
    'content_type' => 'physical',
    'customer' => ['suburb' => 'old-customer'],
    'delivery' => ['suburb' => 'old-delivery'],
    'billing' => ['suburb' => 'old-billing', 'zone_id' => 12],
];
$setOrderSuburbs = new ReflectionMethod($observer, 'setOrderSuburbs');
$taxCountryId = 38;
$taxZoneId = 74;
$setOrderSuburbs->invokeArgs($observer, [$order, &$taxCountryId, &$taxZoneId]);
$assert(
    $order->customer['suburb'] === $agencyLabel
    && $order->delivery['suburb'] === $agencyLabel
    && $order->billing['suburb'] === $agencyLabel,
    'The identical agency label must be assigned to all three order suburb fields.'
);

$db->agencyName = str_repeat('X', 129);
$assert(
    $service->validateSelection(42, IemsCheckoutUnitService::AGENCY_FALLBACK_TOKEN)['error']
        === IemsCheckoutUnitService::ERROR_LABEL_LENGTH,
    'Overlength agency fallback labels must be rejected rather than truncated.'
);
$db->agencyName = 'Indianapolis EMS';

$service->saveSelection(42, IemsCheckoutUnitService::AGENCY_FALLBACK_TOKEN, 'standard');
$db->activeUnits = [[
    'unit_ID' => 8,
    'unit_identifier' => 'MED1',
    'unit_name' => 'Medic 1',
]];
$assert(
    $service->getValidatedSelection(42)['error'] === IemsCheckoutUnitService::ERROR_FALLBACK,
    'Adding or reactivating a unit before order creation must invalidate the fallback.'
);
$assert(
    !isset($_SESSION[IemsCheckoutUnitService::SESSION_KEY]),
    'A fallback invalidated by an active unit must be cleared.'
);
$assert(
    $service->validateSelection(42, IemsCheckoutUnitService::AGENCY_FALLBACK_TOKEN)['error']
        === IemsCheckoutUnitService::ERROR_FALLBACK,
    'The fallback token must be rejected whenever an active unit exists.'
);

$db->activeUnits = [];
$service->saveSelection(42, IemsCheckoutUnitService::AGENCY_FALLBACK_TOKEN, 'standard');
$db->activeUnits = [[
    'unit_ID' => 8,
    'unit_identifier' => 'MED1',
    'unit_name' => 'Medic 1',
]];
$order = (object)[
    'customer' => ['suburb' => 'old-customer'],
    'delivery' => ['suburb' => 'old-delivery'],
    'billing' => ['suburb' => 'old-billing'],
];
$enforceOrderSelection = new ReflectionMethod($observer, 'enforceOrderSelection');
$redirected = false;
try {
    $enforceOrderSelection->invoke($observer, $order);
} catch (IemsCheckoutUnitFakeRedirect) {
    $redirected = true;
}
$assert(
    $redirected,
    'A fallback invalidated at the order-create boundary must stop order creation.'
);
$assert(
    $order->customer['suburb'] === 'old-customer'
    && $order->delivery['suburb'] === 'old-delivery'
    && $order->billing['suburb'] === 'old-billing',
    'An invalidated fallback must not produce success-shaped order suburb values.'
);

$db->activeUnits = [];
$service->saveSelection(42, IemsCheckoutUnitService::AGENCY_FALLBACK_TOKEN, 'standard');
$db->agencyId = 72;
$assert(
    $service->getValidatedSelection(42)['error'] === IemsCheckoutUnitService::ERROR_AFFILIATION,
    'A fallback must be rejected if the active affiliation changes agencies.'
);
$db->agencyId = 71;

foreach (['49', '049'] as $countyNumber) {
    $db->countyNumber = $countyNumber;
    $assert(
        $service->validateSelection(42, IemsCheckoutUnitService::AGENCY_FALLBACK_TOKEN)['valid'],
        'Both supported Marion county-code representations must retain checkout eligibility.'
    );
}
foreach (['', '000', '093', '49x', ' 49', '0049', '4.9e1', '49.0'] as $countyNumber) {
    $db->countyNumber = $countyNumber;
    $assert(
        $service->validateSelection(42, IemsCheckoutUnitService::AGENCY_FALLBACK_TOKEN)['error']
            === IemsCheckoutUnitService::ERROR_AFFILIATION,
        'Invalid county codes must fail closed before pickup or delivery selection.'
    );
}
$db->countyNumber = '49';

$service->saveSelection(42, IemsCheckoutUnitService::AGENCY_FALLBACK_TOKEN, 'standard');
$assert(
    $service->getValidatedSelection(43)['error'] === IemsCheckoutUnitService::ERROR_CART,
    'A selection from a different customer must be rejected.'
);
$assert(
    !isset($_SESSION[IemsCheckoutUnitService::SESSION_KEY]),
    'A cross-customer selection must be cleared.'
);

$service->saveSelection(42, IemsCheckoutUnitService::AGENCY_FALLBACK_TOKEN, 'standard');
$service->clearSelection();
$assert(
    !isset($_SESSION[IemsCheckoutUnitService::SESSION_KEY]),
    'Cancellation and lifecycle cleanup must remove transient selection state.'
);

$observerContents = file_get_contents(
    $pluginRoot . '/catalog/includes/classes/observers/class.iems_checkout_unit_observer.php'
);
$requiredObserverFragments = [
    'NOTIFY_HEADER_START_CHECKOUT_SHIPPING',
    'NOTIFY_HEADER_END_CHECKOUT_SHIPPING',
    'NOTIFY_HEADER_START_CHECKOUT_PAYMENT',
    'NOTIFY_HEADER_START_CHECKOUT_CONFIRMATION',
    'NOTIFY_HEADER_START_CHECKOUT_ONE',
    'NOTIFY_HEADER_START_CHECKOUT_ONE_CONFIRMATION',
    'NOTIFY_CHECKOUT_PROCESS_BEGIN',
    'NOTIFY_PAYMENT_PAYPALEC_BEFORE_SETEC',
    'NOTIFY_PAYPALWPP_BEFORE_DOEXPRESSCHECKOUT',
    'NOTIFY_PAYMENT_PAYPAL_CANCELLED_DURING_CHECKOUT',
    'NOTIFY_ORDER_CART_AFTER_ADDRESSES_SET',
    'NOTIFY_ORDER_CART_EXTERNAL_TAX_DURING_ORDER_CREATE',
    'NOTIFY_CHECKOUT_PROCESS_BEFORE_CART_RESET',
    'NOTIFY_HEADER_START_SHOPPING_CART',
    'NOTIFY_HEADER_START_LOGOFF',
    "['ec_cancel', 'amp;ec_cancel']",
    "\$order->customer['suburb'] = \$label;",
    "\$order->delivery['suburb'] = \$label;",
    "\$order->billing['suburb'] = \$label;",
    'unset($_SESSION[\'shipping\']);',
    "\$GLOBALS['iems_checkout_shipping_ready'] = \$selection['valid'];",
    'IemsShippingAddressService',
];
foreach ($requiredObserverFragments as $fragment) {
    $assert(str_contains($observerContents, $fragment), 'Missing checkout lifecycle behavior: ' . $fragment);
}

$repositoryRoot = dirname($pluginRoot, 3);
$templateFiles = [
    'includes/templates/template_default/templates/tpl_checkout_shipping_default.php',
    'includes/templates/bootstrap/templates/tpl_checkout_shipping_default.php',
    'includes/templates/iems/templates/tpl_checkout_shipping_default.php',
    'includes/templates/template_default/templates/tpl_checkout_payment_default.php',
    'includes/templates/bootstrap/templates/tpl_checkout_payment_default.php',
    'includes/templates/iems/templates/tpl_checkout_payment_default.php',
    'includes/templates/template_default/templates/tpl_checkout_one_default.php',
    'includes/templates/bootstrap/templates/tpl_checkout_one_default.php',
    'includes/templates/iems/templates/tpl_checkout_one_default.php',
];
foreach ($templateFiles as $templateFile) {
    $contents = file_get_contents($repositoryRoot . '/' . $templateFile);
    $assert(
        str_contains($contents, 'iems_checkout_unit_selector_html'),
        'Checkout selector is missing from ' . $templateFile
    );
}
foreach (array_slice($templateFiles, 0, 3) as $templateFile) {
    $contents = file_get_contents($repositoryRoot . '/' . $templateFile);
    $assert(
        preg_match_all(
            '/\$GLOBALS\[\'iems_checkout_shipping_ready\'\]\s*\?\?\s*true/',
            $contents
        ) === 2,
        'The v1.7 observer must gate shipping without breaking older or disabled plugin versions: ' . $templateFile
    );
    $assert(
        str_contains($contents, 'iems_checkout_shipping_methods_available')
            && str_contains($contents, '$iems_checkout_can_continue'),
        'A confirmed selection with no quote methods must display an error path without a looping submit: '
            . $templateFile
    );
}

echo "IEMS checkout unit/agency fallback harness passed.\n";
