<?php

declare(strict_types=1);

/**
 * Replaces the stock Orders-listing zone column with an equivalent column that shows the
 * IEMS county/agency and unit (or agency fallback) for orders shipped by an IEMS module.
 *
 * Zen Cart provides no notifier for the zone cell itself, so the stock column is hidden
 * through the documented $show_zone_info override and re-rendered as an extra column.
 */
final class zcObserverIemsOrdersList extends base
{
    private const IEMS_SHIPPING_MODULES = ['iemspickup', 'iemsdelivery'];

    public function __construct()
    {
        $GLOBALS['show_zone_info'] = false;

        $this->attach($this, [
            'NOTIFY_ADMIN_ORDERS_SEARCH_PARMS',
            'NOTIFY_ADMIN_ORDERS_LIST_EXTRA_COLUMN_HEADING',
            'NOTIFY_ADMIN_ORDERS_LIST_EXTRA_COLUMN_DATA',
        ]);
    }

    public function update(&$class, $eventID, $p1, &$p2, &$p3, &$p4, &$p5, &$p6, &$p7)
    {
        switch ($eventID) {
            case 'NOTIFY_ADMIN_ORDERS_SEARCH_PARMS':
                $p4 = $this->addListFields((string)$p4);
                break;

            case 'NOTIFY_ADMIN_ORDERS_LIST_EXTRA_COLUMN_HEADING':
                $this->addHeading($p2);
                break;

            case 'NOTIFY_ADMIN_ORDERS_LIST_EXTRA_COLUMN_DATA':
                $this->addData(is_array($p2) ? $p2 : [], $p3);
                break;
        }
    }

    private function addListFields(string $newFields): string
    {
        $required = [
            'o.delivery_suburb',
            'o.shipping_module_code',
            'o.delivery_state',
            'o.delivery_country',
            'o.customers_state',
            'o.customers_country',
        ];
        foreach ($required as $field) {
            if (!preg_match('/(^|[\s,])' . preg_quote($field, '/') . '([\s,]|$)/', $newFields)) {
                $newFields .= ', ' . $field;
            }
        }

        return $newFields . ' ';
    }

    private function addHeading(mixed &$extraHeadings): void
    {
        if (!is_array($extraHeadings)) {
            $extraHeadings = [];
        }
        $extraHeadings[] = [
            'align' => 'left',
            'text' => defined('TABLE_HEADING_ZONE_INFO') ? TABLE_HEADING_ZONE_INFO : '',
        ];
    }

    /**
     * @param array<string, mixed> $fields
     */
    private function addData(array $fields, mixed &$extraData): void
    {
        if (!is_array($extraData)) {
            $extraData = [];
        }
        $extraData[] = [
            'align' => 'left',
            'text' => $this->formatZoneCell($fields),
        ];
    }

    /**
     * @param array<string, mixed> $fields
     */
    public function formatZoneCell(array $fields): string
    {
        $isIemsShipping = in_array(
            $fields['shipping_module_code'] ?? '',
            self::IEMS_SHIPPING_MODULES,
            true
        );

        $iemsParts = preg_split(
            '/\s+/',
            trim((string)($fields['delivery_suburb'] ?? '')),
            3
        );

        if ($isIemsShipping && count($iemsParts) === 3) {
            return zen_output_string_protected($iemsParts[0] . ' ' . $iemsParts[1])
                . '<br>'
                . zen_output_string_protected($iemsParts[2]);
        }

        if (!empty($fields['delivery_country'])) {
            return zen_output_string_protected((string)($fields['delivery_state'] ?? ''))
                . '<br>'
                . zen_output_string_protected((string)$fields['delivery_country']);
        }

        return zen_output_string_protected((string)($fields['customers_state'] ?? ''))
            . '<br>'
            . zen_output_string_protected((string)($fields['customers_country'] ?? ''));
    }
}
