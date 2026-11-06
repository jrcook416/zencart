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

if ($failures !== []) {
    fwrite(STDERR, implode(PHP_EOL, $failures) . PHP_EOL);
    exit(1);
}

echo 'IEMS legacy bridge order-addresses harness passed.' . PHP_EOL;
