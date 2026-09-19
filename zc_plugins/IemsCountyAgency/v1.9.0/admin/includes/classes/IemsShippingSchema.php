<?php

declare(strict_types=1);

namespace Zencart\Plugins\Admin\IemsCountyAgency;

final class IemsShippingSchema
{
    public static function isReady(object $db): bool
    {
        try {
            $category = $db->Execute("SHOW COLUMNS FROM " . TABLE_IEMS_AGENCIES . " LIKE 'shipping_category'");
            if ($category->EOF || !self::validCategoryColumn($category->fields)) {
                return false;
            }
            $mileage = $db->Execute("SHOW COLUMNS FROM " . TABLE_IEMS_UNITS . " LIKE 'one_way_miles'");

            return !$mileage->EOF && self::validMileageColumn($mileage->fields);
        } catch (\Throwable) {
            return false;
        }
    }

    public static function validCategoryColumn(array $column): bool
    {
        return ($column['Field'] ?? null) === 'shipping_category'
            && ($column['Type'] ?? null) === "enum('marion','iems','out_of_county')"
            && ($column['Null'] ?? null) === 'NO'
            && ($column['Default'] ?? null) === 'marion'
            && ($column['Extra'] ?? null) === '';
    }

    public static function validMileageColumn(array $column): bool
    {
        return ($column['Field'] ?? null) === 'one_way_miles'
            && ($column['Type'] ?? null) === 'decimal(7,2)'
            && ($column['Null'] ?? null) === 'YES'
            && array_key_exists('Default', $column)
            && $column['Default'] === null
            && ($column['Extra'] ?? null) === '';
    }
}
