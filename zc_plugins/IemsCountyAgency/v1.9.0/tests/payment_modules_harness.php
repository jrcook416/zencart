<?php

declare(strict_types=1);

$probeMode = $argv[1] ?? '';

define('IS_ADMIN_FLAG', false);
define('TABLE_CONFIGURATION', 'configuration');
define('TABLE_IEMS_AGENCIES', 'iems_agencies');
define('TABLE_IEMS_COUNTIES', 'iems_counties');
define('TABLE_IEMS_CUSTOMER_AFFILIATIONS', 'iems_customer_affiliations');
define(
    'MODULE_PAYMENT_IEMSINVOICE_STATUS',
    in_array($probeMode, ['disabled-invoice', 'disabled-invoice-final'], true) ? 'False' : 'True'
);
define('MODULE_PAYMENT_IEMSINVOICE_SORT_ORDER', '0');
define('MODULE_PAYMENT_IEMSINVOICE_ORDER_STATUS_ID', '0');
define('MODULE_PAYMENT_IEMSUNIT_STATUS', $probeMode === 'disabled-unit' ? 'False' : 'True');
define('MODULE_PAYMENT_IEMSUNIT_SORT_ORDER', '10');
define('MODULE_PAYMENT_IEMSUNIT_ORDER_STATUS_ID', '0');
define('FILENAME_CHECKOUT_PAYMENT', 'checkout_payment');
$current_page_base = FILENAME_CHECKOUT_PAYMENT;

final class IemsPaymentFakeResult
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

final class IemsPaymentFakeDb
{
    public bool $schemaReady = true;
    public bool $hasAffiliation = true;
    public bool $duplicateAffiliation = false;
    public mixed $countyId = 49;
    public mixed $agencyId = 71;
    public mixed $affiliationCountyId = 49;
    public mixed $affiliationAgencyId = 71;
    public string $paymentMode = 'invoice';

    /** @var string[] */
    public array $writes = [];

    public function bindVars(string $sql, string $placeholder, mixed $value, string $type): string
    {
        return str_replace($placeholder, (string)$value, $sql);
    }

    public function Execute(string $sql): IemsPaymentFakeResult
    {
        if (str_contains($sql, "SHOW COLUMNS FROM " . TABLE_IEMS_AGENCIES . " LIKE 'payment_mode'")) {
            return new IemsPaymentFakeResult($this->schemaReady ? [[
                'Field' => 'payment_mode',
                'Type' => 'varchar(16)',
                'Null' => 'NO',
                'Default' => 'invoice',
            ]] : []);
        }

        if (str_contains($sql, 'SELECT f.county_ID AS affiliation_county_ID')) {
            if (!$this->hasAffiliation) {
                return new IemsPaymentFakeResult([]);
            }

            $row = [
                'affiliation_county_ID' => $this->affiliationCountyId,
                'affiliation_agency_ID' => $this->affiliationAgencyId,
                'county_ID' => $this->countyId,
                'agency_ID' => $this->agencyId,
                'payment_mode' => $this->paymentMode,
            ];

            return new IemsPaymentFakeResult($this->duplicateAffiliation ? [$row, $row] : [$row]);
        }

        if (str_contains($sql, 'SELECT configuration_value')) {
            return new IemsPaymentFakeResult([['configuration_value' => 'True']]);
        }

        if (preg_match('/^\s*(INSERT|DELETE)\b/i', $sql) === 1) {
            $this->writes[] = $sql;
            return new IemsPaymentFakeResult([]);
        }

        throw new RuntimeException('Unexpected query: ' . $sql);
    }
}

final class IemsPaymentFakeMessageStack
{
    /** @var array<int, array<int, string>> */
    public array $messages = [];

    public function add_session(...$arguments): void
    {
        $this->messages[] = $arguments;
    }
}

final class IemsPaymentFakeRedirect extends RuntimeException
{
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

function zen_href_link(string $page, string $parameters = '', string $connection = ''): string
{
    return $page;
}

function zen_redirect(string $url): never
{
    throw new IemsPaymentFakeRedirect($url);
}

$assert = static function (bool $condition, string $message): void {
    if (!$condition) {
        throw new RuntimeException($message);
    }
};

$pluginRoot = dirname(__DIR__);
foreach (['iemsinvoice', 'iemsunit'] as $module) {
    $language = require $pluginRoot
        . '/catalog/includes/languages/english/modules/payment/lang.' . $module . '.php';
    foreach ($language as $name => $value) {
        define($name, $value);
    }
}

require $pluginRoot . '/catalog/includes/modules/payment/iemsinvoice.php';
require $pluginRoot . '/catalog/includes/modules/payment/iemsunit.php';

$db = new IemsPaymentFakeDb();
$messageStack = new IemsPaymentFakeMessageStack();
$_SESSION = ['customer_id' => 42];

$service = new IemsPaymentEligibilityService();
$assert(!$service->isEligible(0, 'invoice'), 'A non-positive customer ID must fail closed.');
$assert(!$service->isEligible(42, 'unsupported'), 'An unsupported requested mode must fail closed.');
$serviceContents = file_get_contents(
    $pluginRoot . '/catalog/includes/classes/IemsPaymentEligibilityService.php'
);
$assert(
    str_contains($serviceContents, 'AND c.status = 1')
        && str_contains($serviceContents, 'AND a.county_ID = f.county_ID')
        && str_contains($serviceContents, 'AND a.status = 1')
        && str_contains($serviceContents, 'LIMIT 2'),
    'Eligibility must require one active, internally consistent county/agency affiliation.'
);

if ($probeMode !== '') {
    if ($probeMode === 'disabled-invoice-final') {
        $_SESSION['payment'] = 'iemsinvoice';
        $current_page_base = 'checkout_process';
        try {
            new iemsinvoice();
        } catch (IemsPaymentFakeRedirect $exception) {
            $assert(
                $exception->getMessage() === FILENAME_CHECKOUT_PAYMENT
                    && !isset($_SESSION['payment']),
                'A globally disabled selected module must fail closed before final processing.'
            );
            exit(0);
        }
        throw new RuntimeException('A globally disabled selected module did not stop final processing.');
    }

    $module = $probeMode === 'disabled-invoice' ? new iemsinvoice() : new iemsunit();
    $assert(!$module->enabled, 'A globally disabled payment module must remain unavailable.');
    exit(0);
}

$probeCommand = escapeshellarg(PHP_BINARY) . ' ' . escapeshellarg(__FILE__);
foreach (['disabled-invoice', 'disabled-unit', 'disabled-invoice-final'] as $probe) {
    passthru($probeCommand . ' ' . escapeshellarg($probe), $probeStatus);
    $assert($probeStatus === 0, 'Global module disablement failed for ' . $probe . '.');
}

$invoice = new iemsinvoice();
$unit = new iemsunit();
$assert($invoice->enabled, 'Invoice mode must enable only Invoice Billing to Agency.');
$assert(!$unit->enabled, 'Invoice mode must not enable Indianapolis EMS Unit.');
$assert(
    $invoice->title === 'Invoice Billing to Agency'
        && $unit->title === 'Indianapolis EMS Unit',
    'Customer-facing payment labels must match the approved text exactly.'
);
$assert(
    $invoice->selection() === [
        'id' => 'iemsinvoice',
        'module' => 'Invoice Billing to Agency',
    ],
    'The invoice method must not collect extra checkout fields.'
);

$db->paymentMode = 'iems_unit';
$assert(!(new iemsinvoice())->enabled, 'IEMS unit mode must hide invoice billing.');
$assert((new iemsunit())->enabled, 'IEMS unit mode must enable only Indianapolis EMS Unit.');

$db->schemaReady = false;
$assert(!(new iemsinvoice())->enabled && !(new iemsunit())->enabled, 'Missing schema must fail closed.');
$db->schemaReady = true;
$db->hasAffiliation = false;
$assert(!(new iemsunit())->enabled, 'Unaffiliated customers must fail closed.');
$db->hasAffiliation = true;
$db->duplicateAffiliation = true;
$assert(!(new iemsunit())->enabled, 'Duplicate affiliations must fail closed.');
$db->duplicateAffiliation = false;
$db->affiliationCountyId = 48;
$assert(!(new iemsunit())->enabled, 'An inconsistent county affiliation must fail closed.');
$db->affiliationCountyId = 49;
$db->affiliationAgencyId = 70;
$assert(!(new iemsunit())->enabled, 'An inconsistent agency affiliation must fail closed.');
$db->affiliationAgencyId = 71;
$db->countyId = 'invalid';
$assert(!(new iemsunit())->enabled, 'Malformed affiliation identifiers must fail closed.');
$db->countyId = 49;
$db->paymentMode = 'both';
$assert(!(new iemsinvoice())->enabled && !(new iemsunit())->enabled, 'Invalid stored modes must fail closed.');
$db->paymentMode = 'invoice';
$iemsGuestCheckout = true;
$assert(!(new iemsinvoice())->enabled, 'Guest checkout must fail closed.');
$iemsGuestCheckout = false;
unset($_SESSION['customer_id']);
$assert(!(new iemsinvoice())->enabled, 'A missing signed-in customer must fail closed.');

$_SESSION = ['customer_id' => 42, 'payment' => 'iemsinvoice'];
$invoice = new iemsinvoice();
$db->paymentMode = 'iems_unit';
$redirected = false;
try {
    $invoice->before_process();
} catch (IemsPaymentFakeRedirect $exception) {
    $redirected = $exception->getMessage() === FILENAME_CHECKOUT_PAYMENT;
}
$assert($redirected, 'A stale payment mode must stop final order processing.');
$assert(!isset($_SESSION['payment']), 'A stale payment method must be cleared before redirect.');
$assert($messageStack->messages !== [], 'A stale payment method must produce a customer-facing error.');

$db->paymentMode = 'invoice';
$_SESSION = ['customer_id' => 42, 'payment' => 'iemsinvoice'];
$current_page_base = 'checkout_process';
$db->paymentMode = 'iems_unit';
$redirected = false;
try {
    new iemsinvoice();
} catch (IemsPaymentFakeRedirect $exception) {
    $redirected = $exception->getMessage() === FILENAME_CHECKOUT_PAYMENT;
}
$assert(
    $redirected && !isset($_SESSION['payment']),
    'A mode changed before checkout_process must redirect before core dereferences the disabled module.'
);
$current_page_base = FILENAME_CHECKOUT_PAYMENT;

$db->writes = [];
$invoice->install();
$unit->install();
$assert(count($db->writes) === 6, 'Each payment module must install status, order-status, and sort controls.');
$assert(
    $invoice->keys() === [
        'MODULE_PAYMENT_IEMSINVOICE_STATUS',
        'MODULE_PAYMENT_IEMSINVOICE_ORDER_STATUS_ID',
        'MODULE_PAYMENT_IEMSINVOICE_SORT_ORDER',
    ],
    'Invoice module configuration keys are incomplete.'
);
$assert(
    $unit->keys() === [
        'MODULE_PAYMENT_IEMSUNIT_STATUS',
        'MODULE_PAYMENT_IEMSUNIT_ORDER_STATUS_ID',
        'MODULE_PAYMENT_IEMSUNIT_SORT_ORDER',
    ],
    'IEMS unit module configuration keys are incomplete.'
);

(new iemsinvoice(uninstalling: true))->remove();
(new iemsunit(uninstalling: true))->remove();
$assert(count($db->writes) === 8, 'Payment module removal must delete only each module configuration.');

$installer = file_get_contents($pluginRoot . '/Installer/ScriptedInstaller.php');
$assert(
    str_contains($installer, "new iemsinvoice(uninstalling: true)")
        && str_contains($installer, "new iemsunit(uninstalling: true)"),
    'Plugin uninstall must remove both payment-module configurations.'
);
$assert(
    !str_contains($installer, 'DROP COLUMN `payment_mode`')
        && !str_contains($installer, 'DROP TABLE IF EXISTS `iems_agencies`'),
    'Plugin uninstall must preserve agency payment data.'
);

echo "IEMS payment modules/eligibility harness passed.\n";
