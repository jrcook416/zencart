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

define('TABLE_HEADING_ZONE_INFO', 'Zone Info');

$failures = [];
$assert = static function (bool $condition, string $message) use (&$failures): void {
    if (!$condition) {
        $failures[] = $message;
    }
};

$pluginRoot = dirname(__DIR__);
$repositoryRoot = dirname($pluginRoot, 3);
require $pluginRoot . '/admin/includes/classes/observers/class.iems_orders_list_observer.php';

$GLOBALS['show_zone_info'] = true;
$observer = new zcObserverIemsOrdersList();
$assert($GLOBALS['show_zone_info'] === false, 'The observer must hide the stock zone column.');
$assert(
    $observer->events === [
        'NOTIFY_ADMIN_ORDERS_SEARCH_PARMS',
        'NOTIFY_ADMIN_ORDERS_LIST_EXTRA_COLUMN_HEADING',
        'NOTIFY_ADMIN_ORDERS_LIST_EXTRA_COLUMN_DATA',
    ],
    'The observer must attach only to the Orders-listing query and column notifiers.'
);

$dispatch = static function (zcObserverIemsOrdersList $observer, string $event, mixed $p1, mixed &$p2, mixed &$p3, mixed &$p4 = null): void {
    $notifier = null;
    $p5 = null;
    $p6 = null;
    $p7 = null;
    $observer->update($notifier, $event, $p1, $p2, $p3, $p4, $p5, $p6, $p7);
};

// Query fields: stock fields are kept and only missing ones are appended.
$stockFields = ', o.customers_company, o.delivery_state, o.delivery_country, o.customers_state, o.customers_country, o.shipping_module_code ';
$search = '';
$distinct = '';
$newFields = $stockFields;
$dispatch($observer, 'NOTIFY_ADMIN_ORDERS_SEARCH_PARMS', 'kw', $search, $distinct, $newFields);
$assert(str_starts_with($newFields, rtrim($stockFields)), 'Stock list fields must be preserved.');
$assert(substr_count($newFields, 'o.delivery_suburb') === 1, 'delivery_suburb must be selected exactly once.');
$assert(substr_count($newFields, 'o.shipping_module_code') === 1, 'Existing fields must not be duplicated.');
$again = $newFields;
$dispatch($observer, 'NOTIFY_ADMIN_ORDERS_SEARCH_PARMS', 'kw', $search, $distinct, $again);
$assert(substr_count($again, 'o.delivery_suburb') === 1, 'Repeated notification must not duplicate fields.');

// Heading: initialises false and appends to other observers' headings.
$unused = null;
$headings = false;
$dispatch($observer, 'NOTIFY_ADMIN_ORDERS_LIST_EXTRA_COLUMN_HEADING', [], $headings, $unused);
$assert($headings === [['align' => 'left', 'text' => 'Zone Info']], 'Heading must reuse the stock zone label.');
$headings = [['text' => 'Other']];
$dispatch($observer, 'NOTIFY_ADMIN_ORDERS_LIST_EXTRA_COLUMN_HEADING', [], $headings, $unused);
$assert(count($headings) === 2 && $headings[0]['text'] === 'Other', 'Other observers\' headings must be kept.');

$cell = static function (array $fields) use ($dispatch, $observer): string {
    $data = false;
    $dispatch($observer, 'NOTIFY_ADMIN_ORDERS_LIST_EXTRA_COLUMN_DATA', [], $fields, $data);
    return $data[0]['text'];
};

$base = [
    'delivery_state' => 'Indiana',
    'delivery_country' => 'United States',
    'customers_state' => 'Ohio',
    'customers_country' => 'Canada',
];

// Same output as the 9/19 admin/orders.php edit.
$assert(
    $cell($base + ['shipping_module_code' => 'iemsdelivery', 'delivery_suburb' => '49 IEMS M12 Medic 12']) === '49 IEMS<br>M12 Medic 12',
    'IEMS delivery must render county + agency, then unit.'
);
$assert(
    $cell($base + ['shipping_module_code' => 'iemspickup', 'delivery_suburb' => '  049  WAYNE   Wayne Township Fire  ']) === '049 WAYNE<br>Wayne Township Fire',
    'IEMS pickup must render the trimmed agency fallback label.'
);
$assert(
    $cell($base + ['shipping_module_code' => 'iemsdelivery', 'delivery_suburb' => '49 <b>X</b> U1 "Unit"']) === '49 &lt;b&gt;X&lt;/b&gt;<br>U1 &quot;Unit&quot;',
    'Output must be escaped.'
);
$assert(
    $cell($base + ['shipping_module_code' => 'iemsdelivery', 'delivery_suburb' => '49 IEMS']) === 'Indiana<br>United States',
    'A malformed IEMS label must fall back to the stock display.'
);
$assert(
    $cell($base + ['shipping_module_code' => 'flat', 'delivery_suburb' => '49 IEMS M12 Medic 12']) === 'Indiana<br>United States',
    'Non-IEMS shipping must keep the stock delivery display.'
);
$assert(
    $cell(['shipping_module_code' => 'flat', 'delivery_country' => '', 'customers_state' => 'Ohio', 'customers_country' => 'Canada']) === 'Ohio<br>Canada',
    'Orders without a delivery country must show the customer zone.'
);

// The stock column hook still exists in core and core is untouched by the plugin.
$orders = file_get_contents($repositoryRoot . '/admin/orders.php');
$assert($orders !== false, 'admin/orders.php should be readable.');
$assert(str_contains($orders, '$show_zone_info = $show_zone_info ?? true;'), 'Core must still honour $show_zone_info.');
$assert(str_contains($orders, "notify('NOTIFY_ADMIN_ORDERS_LIST_EXTRA_COLUMN_DATA'"), 'Core must still provide the extra column notifier.');
$assert(!str_contains($orders, 'iemsdelivery'), 'admin/orders.php must not contain IEMS customizations.');

if ($failures !== []) {
    fwrite(STDERR, implode(PHP_EOL, $failures) . PHP_EOL);
    exit(1);
}

echo "IEMS orders-list harness passed.\n";
