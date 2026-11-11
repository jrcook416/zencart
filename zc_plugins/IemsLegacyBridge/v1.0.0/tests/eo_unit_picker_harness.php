<?php

declare(strict_types=1);

class base
{
    public function attach($observer, array $events): void
    {
    }
}

class FakeResult implements IteratorAggregate
{
    public bool $EOF;

    public function __construct(private array $rows)
    {
        $this->EOF = $rows === [];
    }

    public function getIterator(): Iterator
    {
        return new ArrayIterator($this->rows);
    }
}

class FakeDb
{
    public array $units = [];

    public function Execute(string $sql): FakeResult
    {
        if (str_starts_with($sql, 'SHOW TABLES')) {
            return new FakeResult($this->units === [] ? [] : [['Tables_in_db' => 'units']]);
        }
        return new FakeResult(array_map(static fn(string $unit): array => ['unit_description' => $unit], $this->units));
    }

    public function prepare_input(string $value): string
    {
        return $value;
    }
}

$root = dirname(__DIR__);
require $root . '/catalog/includes/classes/LegacyUnits.php';
require $root . '/admin/includes/classes/observers/class.iems_legacy_bridge_admin_observer.php';

$failures = [];
$assert = static function (bool $condition, string $message) use (&$failures): void {
    if (!$condition) {
        $failures[] = $message;
    }
};

$observer = (new ReflectionClass('zcObserverIemsLegacyBridgeAdmin'))->newInstanceWithoutConstructor();
$picker = new ReflectionMethod($observer, 'addEditOrdersUnitPicker');
$picker->setAccessible(true);

$db = new FakeDb();
$db->units = ['00 PLEASE PICK YOUR UNIT', '49 IEMS MED1 Medic 1', '50 R&D "Reserve" Unit'];

$run = static function (string $name, array $fields) use ($observer, $picker): array {
    $rows = [];
    $picker->invokeArgs($observer, [$name, $fields, &$rows]);
    return $rows;
};

// A stored unit is preselected; the dropdown keeps EO's form key and drops the label/tooltip wrapper.
$rows = $run('delivery', ['suburb' => '49 IEMS MED1 Medic 1']);
$assert(count($rows) === 1, 'One extra row should be added.');
$input = $rows[0]['input'] ?? '';
$assert(($rows[0]['label'] ?? null) === '', 'The row must have an empty label so EO echoes it unwrapped.');
$assert(str_contains($input, '<select name="suburb" id="delivery_suburb_unit"'), 'Select should post as "suburb".');
$assert(preg_match('/name="(\S*)"/', $input, $m) === 1 && $m[1] === 'suburb', 'EO should read the field name as suburb.');
$assert(substr_count($input, 'selected="selected"') === 1, 'Exactly one option should be selected.');
$assert(str_contains($input, '<option value="49 IEMS MED1 Medic 1" selected="selected">'), 'Stored unit should be selected.');
$assert(str_contains($input, "getElementById('delivery_suburb_unit')") && str_contains($input, "s.id='delivery_suburb'"), 'Script should swap in the select under the standard id.');
$assert(substr_count($input, '<option') === 3, 'No extra option when the value is in the table.');

// Values are escaped, and an EO-escaped current value is decoded once before matching.
$rows = $run('customer', ['suburb' => '50 R&amp;D &quot;Reserve&quot; Unit']);
$input = $rows[0]['input'];
$assert(str_contains($input, 'value="50 R&amp;D &quot;Reserve&quot; Unit" selected="selected"'), 'Escaped unit should match and be selected once.');
$assert(substr_count($input, '<option') === 3, 'Escaped match should not add an option.');

// A value that is not in the table stays selectable and selected.
$rows = $run('billing', ['suburb' => 'Old Station 9']);
$input = $rows[0]['input'];
$assert(str_contains($input, '<option value="Old Station 9" selected="selected">Old Station 9</option>'), 'Unknown current value should be kept and selected.');
$assert(substr_count($input, '<option') === 4, 'Unknown value should be one extra option.');
$assert(str_contains($input, 'id="billing_suburb_unit"'), 'Billing should use the billing id.');

// An empty value stays empty (nothing is silently changed to the first unit).
$rows = $run('delivery', ['suburb' => '']);
$input = $rows[0]['input'];
$assert(str_contains($input, '<option value="" selected="selected"></option>'), 'Empty value should keep a selected blank option.');
$assert(substr_count($input, 'selected="selected"') === 1, 'Only the blank option should be selected.');

// No-ops: no suburb key, or no units table.
$assert($run('delivery', ['street_address' => 'x']) === [], 'No suburb key means no row.');
$db->units = [];
$assert($run('delivery', ['suburb' => 'x']) === [], 'No units table means no row.');

if ($failures !== []) {
    fwrite(STDERR, implode(PHP_EOL, $failures) . PHP_EOL);
    exit(1);
}

echo 'IEMS legacy bridge Edit Orders unit-picker harness passed.' . PHP_EOL;
