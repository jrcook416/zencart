<?php

/**
 * Admin side of the IEMS Legacy Bridge: the "Units of Measure" picker on the
 * product edit page, saved to products.products_price_uom, and a Unit column
 * (orders.delivery_suburb) on the Orders list. Replaces the 1.5.6c core edits
 * to admin collect_info.php, update_product.php and orders.php.
 */
class zcObserverIemsLegacyBridgeAdmin extends base
{
    public function __construct()
    {
        $this->attach($this, [
            'NOTIFY_ADMIN_PRODUCT_COLLECT_INFO_EXTRA_INPUTS',
            'NOTIFY_MODULES_UPDATE_PRODUCT_END',
            'NOTIFY_ADMIN_ORDERS_SEARCH_PARMS',
            'NOTIFY_ADMIN_ORDERS_LIST_EXTRA_COLUMN_HEADING',
            'NOTIFY_ADMIN_ORDERS_LIST_EXTRA_COLUMN_DATA',
        ]);
    }

    public function update(&$class, $eventID, $p1, &$p2, &$p3, &$p4, &$p5, &$p6, &$p7)
    {
        switch ($eventID) {
            case 'NOTIFY_ADMIN_PRODUCT_COLLECT_INFO_EXTRA_INPUTS':
                $this->addUomInput($p1, $p2);
                break;
            case 'NOTIFY_MODULES_UPDATE_PRODUCT_END':
                $this->saveUom($p1);
                break;
            case 'NOTIFY_ADMIN_ORDERS_SEARCH_PARMS':
                $this->addUnitField($p4);
                break;
            case 'NOTIFY_ADMIN_ORDERS_LIST_EXTRA_COLUMN_HEADING':
                $this->addUnitHeading($p2);
                break;
            case 'NOTIFY_ADMIN_ORDERS_LIST_EXTRA_COLUMN_DATA':
                $this->addUnitData($p2, $p3);
                break;
        }
    }

    // -------------------------------------------------------------------------
    // Orders list: 1.5.6c showed the unit under the customer's name.
    // -------------------------------------------------------------------------

    private function addUnitField(&$newFields): void
    {
        $newFields = (string)$newFields;
        if (!preg_match('/(^|[\s,])o\.delivery_suburb([\s,]|$)/', $newFields)) {
            $newFields .= ', o.delivery_suburb ';
        }
    }

    private function addUnitHeading(&$extraHeadings): void
    {
        if (!is_array($extraHeadings)) {
            $extraHeadings = [];
        }
        $extraHeadings[] = ['align' => 'left', 'text' => TABLE_HEADING_IEMS_LEGACY_UNIT];
    }

    private function addUnitData($fields, &$extraData): void
    {
        if (!is_array($extraData)) {
            $extraData = [];
        }
        $unit = is_array($fields) ? (string)($fields['delivery_suburb'] ?? '') : '';
        $extraData[] = ['align' => 'left', 'text' => zen_output_string_protected($unit)];
    }

    private function addUomInput($pInfo, &$extraInputs): void
    {
        if (!$this->hasUomColumn()) {
            return;
        }
        $options = [['id' => '', 'text' => '']];
        foreach ($this->uomOptions() as $uom) {
            $options[] = ['id' => $uom, 'text' => $uom];
        }
        $current = (string)($pInfo->products_uom ?? $pInfo->products_price_uom ?? '');

        $extraInputs[] = [
            'label' => [
                'text' => TEXT_IEMS_LEGACY_PRODUCTS_UOM,
                'field_name' => 'products_uom',
            ],
            'input' => zen_draw_pull_down_menu('products_uom', $options, $current, 'id="products_uom" class="form-control"'),
        ];
    }

    private function saveUom(array $info): void
    {
        global $db;

        if (!isset($_POST['products_uom']) || !$this->hasUomColumn()) {
            return;
        }
        $productsId = (int)($info['products_id'] ?? 0);
        if ($productsId <= 0) {
            return;
        }
        $sql = "UPDATE " . TABLE_PRODUCTS . "
                   SET products_price_uom = :uom
                 WHERE products_id = :productsId";
        $sql = $db->bindVars($sql, ':uom', zen_db_prepare_input($_POST['products_uom']), 'string');
        $sql = $db->bindVars($sql, ':productsId', $productsId, 'integer');
        $db->Execute($sql);
    }

    /**
     * @return array<int, string>
     */
    private function uomOptions(): array
    {
        global $db;

        $check = $db->Execute("SHOW TABLES LIKE 'uom'");
        if ($check->EOF) {
            return [];
        }
        $values = [];
        foreach ($db->Execute('SELECT `uom` FROM `uom` ORDER BY `uom`') as $row) {
            $values[] = (string)$row['uom'];
        }
        return $values;
    }

    private function hasUomColumn(): bool
    {
        global $db;

        $result = $db->Execute("SHOW COLUMNS FROM " . TABLE_PRODUCTS . " LIKE 'products_price_uom'");
        return !$result->EOF;
    }
}
