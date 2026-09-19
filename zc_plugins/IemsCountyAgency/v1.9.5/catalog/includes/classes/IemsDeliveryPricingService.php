<?php

declare(strict_types=1);

require_once __DIR__ . '/IemsCheckoutUnitService.php';

final class IemsDeliveryPricingService
{
    /**
     * Call only after validating the customer's current unit selection.
     *
     * @return array{category: string, cost: float}|null
     */
    public function getPrice(int $unitId): ?array
    {
        global $db;

        if (!$this->schemaReady()) {
            return null;
        }

        $sql = "SELECT u.one_way_miles, a.shipping_category, c.county_number
                  FROM " . TABLE_IEMS_UNITS . " u
                  JOIN " . TABLE_IEMS_AGENCIES . " a
                    ON a.agency_ID = u.agency_ID AND a.county_ID = u.county_ID
                   AND a.status = 1 AND a.delivery_enabled = 1
                  JOIN " . TABLE_IEMS_COUNTIES . " c
                    ON c.county_ID = u.county_ID AND c.status = 1
                 WHERE u.unit_ID = :unitId AND u.status = 1
                 LIMIT 1";
        $row = $db->Execute($db->bindVars($sql, ':unitId', $unitId, 'integer'));
        if ($row->EOF) {
            return null;
        }

        $county = IemsCheckoutUnitService::normalizeCountyNumber($row->fields['county_number'] ?? null);
        $category = $row->fields['shipping_category'] ?? null;
        if ($county === null) {
            return null;
        }
        if ($county === '049') {
            return in_array($category, ['iems', 'marion'], true)
                ? ['category' => $category, 'cost' => 0.00]
                : null;
        }
        if ($category !== 'out_of_county') {
            return null;
        }

        $miles = self::hundredths($row->fields['one_way_miles'] ?? null);
        $rates = $db->Execute(
            "SELECT configuration_key, configuration_value FROM " . TABLE_CONFIGURATION . "
              WHERE configuration_key IN (
                'MODULE_SHIPPING_IEMSDELIVERY_FLAT_RATE',
                'MODULE_SHIPPING_IEMSDELIVERY_PER_MILE_RATE'
              )"
        );
        $values = [];
        while (!$rates->EOF) {
            $key = $rates->fields['configuration_key'];
            if (array_key_exists($key, $values)) {
                return null;
            }
            $values[$key] = self::hundredths($rates->fields['configuration_value'] ?? null);
            $rates->MoveNext();
        }
        $flat = $values['MODULE_SHIPPING_IEMSDELIVERY_FLAT_RATE'] ?? null;
        $perMile = $values['MODULE_SHIPPING_IEMSDELIVERY_PER_MILE_RATE'] ?? null;
        if ($miles === null || $flat === null || $perMile === null) {
            return null;
        }
        if (
            $flat > intdiv(PHP_INT_MAX - 50, 100)
            || ($perMile > 0 && $miles > intdiv(PHP_INT_MAX - $flat * 100 - 50, $perMile))
        ) {
            return null;
        }

        // Integer ten-thousandths preserve fractional cents until the final half-up rounding.
        $cents = intdiv($flat * 100 + $miles * $perMile + 50, 100);
        return ['category' => $category, 'cost' => $cents / 100.0];
    }

    public static function hundredths(mixed $value): ?int
    {
        if (!is_string($value) && !is_int($value)) {
            return null;
        }
        if (preg_match('/^(0|[1-9][0-9]{0,4})(?:\.([0-9]{1,2}))?$/D', (string)$value, $parts) !== 1) {
            return null;
        }

        return (int)$parts[1] * 100 + (int)str_pad($parts[2] ?? '', 2, '0');
    }

    private function schemaReady(): bool
    {
        global $db;

        foreach ([
            [TABLE_IEMS_AGENCIES, 'shipping_category', "enum('marion','iems','out_of_county')", 'NO', 'marion'],
            [TABLE_IEMS_UNITS, 'one_way_miles', 'decimal(7,2)', 'YES', null],
        ] as [$table, $column, $type, $nullable, $default]) {
            $field = $db->Execute("SHOW COLUMNS FROM " . $table . " LIKE '" . $column . "'");
            if (
                $field->EOF
                || ($field->fields['Field'] ?? null) !== $column
                || !array_key_exists('Default', $field->fields)
                || strtolower((string)($field->fields['Type'] ?? '')) !== $type
                || ($field->fields['Null'] ?? null) !== $nullable
                || ($field->fields['Default'] ?? null) !== $default
                || str_contains(strtoupper((string)($field->fields['Extra'] ?? '')), 'GENERATED')
            ) {
                return false;
            }
        }

        return true;
    }
}
