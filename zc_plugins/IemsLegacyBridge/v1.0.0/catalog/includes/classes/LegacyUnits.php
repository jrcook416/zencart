<?php

declare(strict_types=1);

namespace Zencart\Plugins\Catalog\IemsLegacyBridge;

/**
 * Read-only access to the 1.5.6c `units` and `uom` lookup tables.
 *
 * The unit text is stored verbatim in address_book.entry_suburb and the
 * orders *_suburb columns; the inventory system matches on that exact text,
 * so values are never trimmed, normalised or re-keyed here.
 */
final class LegacyUnits
{
    private const UNITS_TABLE = 'units';
    private const UOM_TABLE = 'uom';

    /** @var array<string, bool> */
    private static array $tableExists = [];

    public static function placeholder(): string
    {
        return defined('IEMS_LEGACY_UNIT_PLACEHOLDER') ? (string)IEMS_LEGACY_UNIT_PLACEHOLDER : '00 PLEASE PICK YOUR UNIT';
    }

    /**
     * Storefront unit options, matching legacy unit_lookup(): every unit except
     * those whose description contains the configured exclusion text.
     *
     * @return array<int, array{id: string, text: string}>
     */
    public static function storefrontOptions(): array
    {
        $exclude = defined('IEMS_LEGACY_UNIT_EXCLUDE') ? trim((string)IEMS_LEGACY_UNIT_EXCLUDE) : '';
        return self::options($exclude);
    }

    /**
     * All unit options (admin use).
     *
     * @return array<int, array{id: string, text: string}>
     */
    public static function allOptions(): array
    {
        return self::options('');
    }

    /**
     * @return array<int, array{id: string, text: string}>
     */
    public static function uomOptions(): array
    {
        global $db;

        if (!self::tableExists(self::UOM_TABLE)) {
            return [];
        }
        $options = [];
        $result = $db->Execute('SELECT `uom` FROM `' . self::UOM_TABLE . '` ORDER BY `uom`');
        foreach ($result as $row) {
            $options[] = ['id' => (string)$row['uom'], 'text' => (string)$row['uom']];
        }
        return $options;
    }

    /**
     * Storefront unit dropdown. A saved unit that is no longer offered (for
     * example a hidden "reserve medic" unit) is kept as an option so that
     * editing an address never silently changes the stored text.
     */
    public static function pullDown(string $name, string $selected, string $parameters = ''): string
    {
        $options = self::storefrontOptions();
        if ($selected !== '' && !in_array($selected, array_column($options, 'id'), true)) {
            array_unshift($options, ['id' => $selected, 'text' => $selected]);
        }
        return zen_draw_pull_down_menu($name, $options, $selected, $parameters);
    }

    public static function isPlaceholder(string $value): bool
    {
        return strpos($value, self::placeholder()) !== false;
    }

    /**
     * @return array<int, array{id: string, text: string}>
     */
    private static function options(string $exclude): array
    {
        global $db;

        if (!self::tableExists(self::UNITS_TABLE)) {
            return [];
        }
        $sql = 'SELECT unit_description FROM `' . self::UNITS_TABLE . '`';
        if ($exclude !== '') {
            $sql .= " WHERE unit_description NOT LIKE '%" . $db->prepare_input($exclude) . "%'";
        }
        $sql .= ' ORDER BY unit_description';

        $options = [];
        $result = $db->Execute($sql);
        foreach ($result as $row) {
            $options[] = ['id' => (string)$row['unit_description'], 'text' => (string)$row['unit_description']];
        }
        return $options;
    }

    private static function tableExists(string $table): bool
    {
        global $db;

        if (!isset(self::$tableExists[$table])) {
            $result = $db->Execute("SHOW TABLES LIKE '" . $db->prepare_input($table) . "'");
            self::$tableExists[$table] = !$result->EOF;
        }
        return self::$tableExists[$table];
    }
}
