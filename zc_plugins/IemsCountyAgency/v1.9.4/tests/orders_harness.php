<?php

declare(strict_types=1);

class base
{
    /** @var array<int, string> */
    public array $events = [];

    /** @param array<int, string> $events */
    public function attach(object $observer, array $events): void
    {
        $this->events = $events;
    }
}

function zen_output_string_protected(string $value): string
{
    return htmlspecialchars($value, ENT_QUOTES);
}

$assert = static function (bool $condition, string $message): void {
    if (!$condition) {
        throw new RuntimeException($message);
    }
};

$pluginRoot = dirname(__DIR__);
require $pluginRoot . '/admin/includes/classes/observers/class.iems_orders_observer.php';

$observer = new zcObserverIemsOrders();
$assert(
    $observer->events === [
        'NOTIFY_ADMIN_ORDERS_EDIT_BEGIN',
        'NOTIFY_END_ZEN_ADDRESS_FORMAT',
    ],
    'The admin order observer must attach only to order capture and address formatting.'
);

$dispatch = static function (
    zcObserverIemsOrders $observer,
    string $event,
    mixed $p1,
    mixed &$p2
): void {
    $notifier = null;
    $p3 = null;
    $p4 = null;
    $p5 = null;
    $p6 = null;
    $p7 = null;
    $observer->update($notifier, $event, $p1, $p2, $p3, $p4, $p5, $p6, $p7);
};

$delivery = [
    'name' => 'Jeremiah Cook',
    'company' => 'Indianapolis EMS',
    'street_address' => '3930 Georgetown Road',
    'suburb' => '29 HEND UNIT7 Engine 7',
    'city' => 'Indianapolis',
    'postcode' => '46258',
    'state' => 'Indiana',
    'country' => ['title' => 'United States'],
    'format_id' => 2,
];
$order = (object)[
    'info' => ['shipping_module_code' => 'iemsdelivery'],
    'delivery' => $delivery,
];
$orderId = 123;
$dispatch($observer, 'NOTIFY_ADMIN_ORDERS_EDIT_BEGIN', $orderId, $order);
$assert(
    isset($order->delivery['iems_admin_orders_marker']),
    'The Orders hook must mark only the in-memory IEMS delivery address.'
);

$customerAddress = $order->delivery;
unset($customerAddress['iems_admin_orders_marker']);
$formattedAddress = 'stock customer address';
$customerDetails = [
    'address' => $customerAddress,
    'cr' => '<br>',
];
$dispatch($observer, 'NOTIFY_END_ZEN_ADDRESS_FORMAT', $customerDetails, $formattedAddress);
$assert(
    $formattedAddress === 'stock customer address',
    'Customer and billing blocks must remain standard even if their values match delivery.'
);

$formattedAddress = 'stock address';
$details = [
    'address' => $order->delivery,
    'cr' => '<br>',
];
$dispatch($observer, 'NOTIFY_END_ZEN_ADDRESS_FORMAT', $details, $formattedAddress);
$assert(
    $formattedAddress === '29, HEND<br>UNIT7 Engine 7',
    'A unit order must show only county/agency on line one and the unit on line two.'
);
$assert(
    !str_contains($formattedAddress, 'Indiana')
        && !str_contains($formattedAddress, 'United States')
        && !str_contains($formattedAddress, '3930 Georgetown Road'),
    'The compact admin delivery block must omit the zone, country, and conventional address lines.'
);

$fallbackObserver = new zcObserverIemsOrders();
$fallbackDelivery = $delivery;
$fallbackDelivery['suburb'] = '49 IEMS Indianapolis EMS';
$fallbackOrder = (object)[
    'info' => ['shipping_module_code' => 'iemspickup'],
    'delivery' => $fallbackDelivery,
];
$dispatch($fallbackObserver, 'NOTIFY_ADMIN_ORDERS_EDIT_BEGIN', $orderId, $fallbackOrder);
$formattedAddress = 'stock fallback address';
$details = [
    'address' => $fallbackOrder->delivery,
    'cr' => '<br>',
];
$dispatch($fallbackObserver, 'NOTIFY_END_ZEN_ADDRESS_FORMAT', $details, $formattedAddress);
$assert(
    $formattedAddress === '49, IEMS<br>Indianapolis EMS',
    'An agency-fallback order must show the agency on line two.'
);

$nonIemsObserver = new zcObserverIemsOrders();
$nonIemsOrder = (object)[
    'info' => ['shipping_module_code' => 'flat'],
    'delivery' => $delivery,
];
$dispatch($nonIemsObserver, 'NOTIFY_ADMIN_ORDERS_EDIT_BEGIN', $orderId, $nonIemsOrder);
$formattedAddress = 'stock non-IEMS address';
$details = [
    'address' => $delivery,
    'cr' => '<br>',
];
$dispatch($nonIemsObserver, 'NOTIFY_END_ZEN_ADDRESS_FORMAT', $details, $formattedAddress);
$assert(
    $formattedAddress === 'stock non-IEMS address',
    'Non-IEMS orders must retain standard admin address formatting.'
);

$malformedObserver = new zcObserverIemsOrders();
$malformedDelivery = $delivery;
$malformedDelivery['suburb'] = 'not-an-iems-label';
$malformedOrder = (object)[
    'info' => ['shipping_module_code' => 'iemsdelivery'],
    'delivery' => $malformedDelivery,
];
$dispatch($malformedObserver, 'NOTIFY_ADMIN_ORDERS_EDIT_BEGIN', $orderId, $malformedOrder);
$formattedAddress = 'stock malformed address';
$details = [
    'address' => $malformedOrder->delivery,
    'cr' => '<br>',
];
$dispatch($malformedObserver, 'NOTIFY_END_ZEN_ADDRESS_FORMAT', $details, $formattedAddress);
$assert(
    $formattedAddress === 'stock malformed address',
    'Malformed retained IEMS labels must fail closed to standard address formatting.'
);

echo "IEMS admin orders address harness passed.\n";
