<?php

declare(strict_types=1);

class base
{
    public function attach($observer, array $events): void
    {
    }
}

$root = dirname(__DIR__) . '/catalog/includes/classes';
require $root . '/LegacyUnits.php';
require $root . '/observers/class.iems_legacy_bridge_observer.php';

$failures = [];
$assert = static function (bool $condition, string $message) use (&$failures): void {
    if (!$condition) {
        $failures[] = $message;
    }
};

$observer = (new ReflectionClass('zcObserverIemsLegacyBridge'))->newInstanceWithoutConstructor();

$unitAddress = [
    'firstname' => 'Jane',
    'lastname' => 'Medic',
    'company' => 'Indianapolis EMS',
    'street_address' => '3930 N Meridian St',
    'suburb' => '49 IEMS MED1 Medic 1',
    'city' => 'Indianapolis',
    'postcode' => '46208',
    'state' => 'Indiana',
    'state_code' => 'IN',
    'zone_id' => 22,
    'country' => ['id' => 223, 'title' => 'United States', 'iso_code_2' => 'US', 'iso_code_3' => 'USA'],
    'country_id' => 223,
    'format_id' => 2,
];
$accountAddress = [
    'firstname' => 'Jane',
    'lastname' => 'Medic',
    'company' => '',
    'street_address' => '12 Home Ave',
    'suburb' => '00 PLEASE PICK YOUR UNIT',
    'city' => 'Carmel',
    'postcode' => '46032',
    'state' => 'Indiana',
    'state_code' => 'IN',
    'zone_id' => 22,
    'country' => ['id' => 223, 'title' => 'United States', 'iso_code_2' => 'US', 'iso_code_3' => 'USA'],
    'country_id' => 223,
    'format_id' => 2,
    'telephone' => '317-555-0100',
    'email_address' => 'jane@example.test',
];

$order = new stdClass();
$order->customer = $accountAddress;
$order->billing = $accountAddress;
$order->delivery = $unitAddress;
$observer->matchOrderAddressesToDelivery($order);

foreach (['street_address', 'suburb', 'city', 'postcode', 'state', 'zone_id', 'country', 'country_id', 'format_id', 'company'] as $key) {
    $assert($order->billing[$key] === $unitAddress[$key], "Billing $key should match the delivery address.");
    $assert($order->customer[$key] === $unitAddress[$key], "Customer $key should match the delivery address.");
}
$assert($order->customer['telephone'] === '317-555-0100', 'Customer telephone should be kept.');
$assert($order->customer['email_address'] === 'jane@example.test', 'Customer email address should be kept.');
$assert(!array_key_exists('telephone', $order->billing), 'Billing should not gain contact fields.');
$assert($order->delivery === $unitAddress, 'Delivery address should be unchanged.');

$virtual = new stdClass();
$virtual->customer = $accountAddress;
$virtual->billing = $accountAddress;
$virtual->delivery = ['firstname' => '', 'lastname' => '', 'street_address' => '', 'suburb' => ''];
$observer->matchOrderAddressesToDelivery($virtual);
$assert($virtual->billing === $accountAddress, 'Virtual order billing should be unchanged.');
$assert($virtual->customer === $accountAddress, 'Virtual order customer should be unchanged.');

$placeholder = new stdClass();
$placeholder->customer = $accountAddress;
$placeholder->billing = $accountAddress;
$placeholder->delivery = array_merge($unitAddress, ['suburb' => '00 PLEASE PICK YOUR UNIT']);
$observer->matchOrderAddressesToDelivery($placeholder);
$assert($placeholder->billing === $accountAddress, 'Placeholder unit should leave billing unchanged.');
$assert($placeholder->customer === $accountAddress, 'Placeholder unit should leave customer unchanged.');

$missing = new stdClass();
$missing->customer = $accountAddress;
$missing->billing = $accountAddress;
$missing->delivery = false;
$observer->matchOrderAddressesToDelivery($missing);
$assert($missing->billing === $accountAddress, 'A missing delivery address should leave billing unchanged.');

$_SESSION = ['customer_id' => 5, 'sendto' => 42, 'billto' => 7];
$observer->syncBillingAddressWithDelivery();
$assert($_SESSION['billto'] === 42, 'billto should follow sendto.');

$_SESSION = ['customer_id' => 5, 'billto' => 7];
$observer->syncBillingAddressWithDelivery();
$assert($_SESSION['billto'] === 7, 'billto should stay when sendto is unset.');

// Header Checkout link and the checkout_shipping_address gate.
define('FILENAME_CHECKOUT_SHIPPING', 'checkout_shipping');
define('FILENAME_CHECKOUT_SHIPPING_ADDRESS', 'checkout_shipping_address');
if (!function_exists('zen_href_link')) {
    function zen_href_link($page = '', $params = '', $transport = 'NONSSL')
    {
        return 'index.php?main_page=' . $page;
    }
}
if (!function_exists('zen_redirect')) {
    function zen_redirect($url, $code = null)
    {
        throw new RuntimeException($url);
    }
}
$fakeCart = new class {
    public $cartID = 'c1';
    public $count = 2;
    public $type = 'physical';
    public function count_contents() { return $this->count; }
    public function get_content_type() { return $this->type; }
};
$redirectsTo = static function (string $page) use ($observer): ?string {
    $a = $b = $c = $d = $e = $f = null;
    try {
        $observer->update($observer, 'NOTIFY_HEADER_START_' . $page, [], $a, $b, $c, $d, $e, $f);
    } catch (RuntimeException $redirect) {
        return $redirect->getMessage();
    }
    return null;
};
$submitStep = static function () use ($observer): void {
    global $current_page_base;
    $current_page_base = 'checkout_shipping_address';
    $url = 'index.php?main_page=checkout_shipping';
    $x = null;
    $observer->update($observer, 'NOTIFY_ZEN_REDIRECT', ['url' => $url, 'httpResponseCode' => null], $x, $x, $x, $x, $x, $x);
    $current_page_base = 'checkout_shipping';
};
$gateTarget = 'index.php?main_page=checkout_shipping_address';

$_SESSION = ['customer_id' => 5, 'cart' => $fakeCart];
foreach (['CHECKOUT_SHIPPING', 'CHECKOUT_PAYMENT', 'CHECKOUT_CONFIRMATION', 'CHECKOUT_PROCESS', 'CHECKOUT_ONE'] as $step) {
    $assert($redirectsTo($step) === $gateTarget, "$step must redirect to the address step before it is done.");
}

$submitStep();
$assert(($_SESSION['iems_shipping_address_step']['cart'] ?? null) === 'c1', 'Submitting the address step should be marked.');
foreach (['CHECKOUT_SHIPPING', 'CHECKOUT_PAYMENT', 'CHECKOUT_CONFIRMATION'] as $step) {
    $assert($redirectsTo($step) === null, "$step should pass once the address step is done.");
}

$fakeCart->cartID = 'c2';
$assert($redirectsTo('CHECKOUT_PAYMENT') === $gateTarget, 'A cart change must require the address step again.');

$submitStep();
$_SESSION['customer_id'] = 6;
$assert($redirectsTo('CHECKOUT_PAYMENT') === $gateTarget, 'A different customer must redo the address step.');
$_SESSION['customer_id'] = 5;

$x = null;
$observer->update($observer, 'NOTIFY_CHECKOUT_PROCESS_BEFORE_CART_RESET', 1, $x, $x, $x, $x, $x, $x);
$assert(!isset($_SESSION['iems_shipping_address_step']), 'Placing the order should clear the mark.');

$GLOBALS['current_page_base'] = 'checkout_payment';
$observer->update($observer, 'NOTIFY_ZEN_REDIRECT', ['url' => 'index.php?main_page=checkout_payment'], $x, $x, $x, $x, $x, $x);
$assert(!isset($_SESSION['iems_shipping_address_step']), 'Redirects from other pages must not mark the step.');
$GLOBALS['current_page_base'] = 'checkout_shipping_address';
$observer->update($observer, 'NOTIFY_ZEN_REDIRECT', ['url' => 'index.php?main_page=login'], $x, $x, $x, $x, $x, $x);
$assert(!isset($_SESSION['iems_shipping_address_step']), 'A redirect to login must not mark the step.');

$_SESSION = ['cart' => $fakeCart];
$assert($redirectsTo('CHECKOUT_PAYMENT') === null, 'Logged-out customers are left to core (login first).');
$_SESSION = ['customer_id' => 5, 'cart' => $fakeCart];
$fakeCart->type = 'virtual';
$assert($redirectsTo('CHECKOUT_PAYMENT') === null, 'Virtual carts have no shipping address step.');
$fakeCart->type = 'physical';
$fakeCart->count = 0;
$assert($redirectsTo('CHECKOUT_PAYMENT') === null, 'Empty carts are left to core.');
$fakeCart->count = 2;

// Header link rewrite (tpl_header.php caller only).
$linkFromFile = static function (string $file, string $page, string $params = '') use ($observer): string {
    $tmp = sys_get_temp_dir() . DIRECTORY_SEPARATOR . $file;
    file_put_contents($tmp, '<?php return static function ($o, $page, $params) { $x = null; $link = null; $o->update($o, "NOTIFY_SEFU_INTERCEPT", [], $link, $page, $params, $x, $x, $x); return $page; };');
    $fn = require $tmp;
    unlink($tmp);
    return $fn($observer, $page, $params);
};
$assert($linkFromFile('tpl_header.php', 'checkout_shipping') === 'checkout_shipping_address', 'Header Checkout link should go to the address step.');
$assert($linkFromFile('tpl_modules_mobile_menu.php', 'checkout_shipping') === 'checkout_shipping_address', 'Mobile menu Checkout link should go to the address step.');
$assert($linkFromFile('tpl_other.php', 'checkout_shipping') === 'checkout_shipping', 'Other callers must keep checkout_shipping.');
$assert($linkFromFile('tpl_header.php', 'checkout_payment') === 'checkout_payment', 'Other pages must not be rewritten.');
$assert($linkFromFile('tpl_header.php', 'checkout_shipping', 'a=b') === 'checkout_shipping', 'Links with parameters must not be rewritten.');

if ($failures !== []) {
    fwrite(STDERR, implode(PHP_EOL, $failures) . PHP_EOL);
    exit(1);
}

echo 'IEMS legacy bridge order-addresses harness passed.' . PHP_EOL;
