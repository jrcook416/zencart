<?php

declare(strict_types=1);

define('TABLE_IEMS_CUSTOMER_AFFILIATIONS', 'iems_customer_affiliations');
define('TABLE_IEMS_COUNTIES', 'iems_counties');
define('TABLE_IEMS_AGENCIES', 'iems_agencies');
define('TABLE_IEMS_UNITS', 'iems_units');

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
    public bool $hasAffiliation = true;
    public bool $hasValidUnit = true;
    public string $unitName = 'Medic 1';

    public function bindVars(string $sql, string $placeholder, mixed $value, string $type): string
    {
        return str_replace($placeholder, (string)$value, $sql);
    }

    public function Execute(string $sql): IemsCheckoutUnitFakeResult
    {
        if (preg_match('/^\s*(INSERT|UPDATE|DELETE|REPLACE)\b/i', $sql) === 1) {
            throw new RuntimeException('Checkout unit validation must be read-only.');
        }

        if (str_contains($sql, 'SELECT f.county_ID, f.agency_ID')) {
            return new IemsCheckoutUnitFakeResult(
                $this->hasAffiliation ? [['county_ID' => 49, 'agency_ID' => 71]] : []
            );
        }

        if (str_contains($sql, 'SELECT c.county_number')) {
            return new IemsCheckoutUnitFakeResult(
                $this->hasValidUnit
                    ? [[
                        'county_number' => '49',
                        'agency_identifier' => 'IEMS',
                        'unit_identifier' => 'MED1',
                        'unit_name' => $this->unitName,
                    ]]
                    : []
            );
        }

        if (str_contains($sql, 'SELECT u.unit_ID')) {
            return new IemsCheckoutUnitFakeResult(
                $this->hasAffiliation
                    ? [[
                        'unit_ID' => 8,
                        'unit_identifier' => 'MED1',
                        'unit_name' => 'Medic 1',
                    ]]
                    : []
            );
        }

        throw new RuntimeException('Unexpected query: ' . $sql);
    }
}

$assert = static function (bool $condition, string $message): void {
    if (!$condition) {
        throw new RuntimeException($message);
    }
};

$db = new IemsCheckoutUnitFakeDb();
$_SESSION = [
    'customer_id' => 42,
    'cart' => (object)['cartID' => 'cart-a'],
];

require dirname(__DIR__) . '/catalog/includes/classes/IemsCheckoutUnitService.php';

$service = new IemsCheckoutUnitService();
$units = $service->getActiveUnitsForCustomer(42);
$assert($units === [['id' => 8, 'text' => 'MED1 Medic 1']], 'Active units should be scoped to the affiliation.');

$selection = $service->saveSelection(42, '8', 'standard');
$assert($selection['valid'], 'A matching active unit should be accepted.');
$assert($selection['label'] === '49 IEMS MED1 Medic 1', 'The persisted label must be rebuilt from database values.');
$assert(
    $_SESSION[IemsCheckoutUnitService::SESSION_KEY] === [
        'customer_id' => 42,
        'cart_id' => 'cart-a',
        'unit_id' => 8,
        'flow' => 'standard',
    ],
    'Transient state must contain identifiers only.'
);

$assert(
    !$service->validateSelection(42, ['8'])['valid'],
    'Non-scalar unit input must be rejected.'
);
$assert(
    $service->validateSelection(42, '8.0')['error'] === IemsCheckoutUnitService::ERROR_MALFORMED,
    'Non-integer unit input must be rejected.'
);

$db->hasValidUnit = false;
$assert(
    $service->validateSelection(42, '8')['error'] === IemsCheckoutUnitService::ERROR_UNIT,
    'Inactive or cross-hierarchy units must be rejected.'
);
$db->hasValidUnit = true;

$db->hasAffiliation = false;
$assert(
    $service->validateSelection(42, '8')['error'] === IemsCheckoutUnitService::ERROR_AFFILIATION,
    'Missing affiliations must be rejected.'
);
$db->hasAffiliation = true;

$db->unitName = str_repeat('X', 129);
$assert(
    $service->validateSelection(42, '8')['error'] === IemsCheckoutUnitService::ERROR_LABEL_LENGTH,
    'Overlength labels must be rejected rather than truncated.'
);
$db->unitName = 'Medic 1';

$service->saveSelection(42, '8', 'opc');
$_SESSION['cart']->cartID = 'cart-b';
$assert(
    $service->getValidatedSelection(42)['error'] === IemsCheckoutUnitService::ERROR_CART,
    'A selection from a different cart must be rejected.'
);
$assert(
    !isset($_SESSION[IemsCheckoutUnitService::SESSION_KEY]),
    'Stale selections must be cleared.'
);

$pluginRoot = dirname(__DIR__);
$observer = file_get_contents(
    $pluginRoot . '/catalog/includes/classes/observers/class.iems_checkout_unit_observer.php'
);
$requiredObserverFragments = [
    'NOTIFY_HEADER_START_CHECKOUT_SHIPPING',
    'NOTIFY_HEADER_START_CHECKOUT_PAYMENT',
    'NOTIFY_HEADER_START_CHECKOUT_CONFIRMATION',
    'NOTIFY_HEADER_START_CHECKOUT_ONE',
    'NOTIFY_HEADER_START_CHECKOUT_ONE_CONFIRMATION',
    'NOTIFY_CHECKOUT_PROCESS_BEGIN',
    'NOTIFY_PAYMENT_PAYPALEC_BEFORE_SETEC',
    'NOTIFY_PAYPALWPP_BEFORE_DOEXPRESSCHECKOUT',
    'NOTIFY_PAYMENT_PAYPAL_CANCELLED_DURING_CHECKOUT',
    'NOTIFY_ORDER_CART_AFTER_ADDRESSES_SET',
    'NOTIFY_CHECKOUT_PROCESS_BEFORE_CART_RESET',
    "['ec_cancel', 'amp;ec_cancel']",
    "\$order->customer['suburb'] = \$selection['label'];",
    "\$order->delivery['suburb'] = \$selection['label'];",
    "\$order->billing['suburb'] = \$selection['label'];",
];
foreach ($requiredObserverFragments as $fragment) {
    $assert(str_contains($observer, $fragment), 'Missing checkout enforcement behavior: ' . $fragment);
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

echo "IEMS checkout-unit harness passed.\n";
