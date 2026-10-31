<?php

use Zencart\Plugins\Catalog\IemsLegacyBridge\LegacyUnits;

/**
 * Storefront side of the IEMS Legacy Bridge.
 *
 * Replaces the 1.5.6c core edits:
 *  - "00 PLEASE PICK YOUR UNIT" checks in create_account, checkout_new_address,
 *    address_book_process and checkout_shipping;
 *  - unit-of-measure text after the displayed price (functions_prices.php);
 *  - " (uom)" appended to cart product names (shopping_cart.php), which is what
 *    ends up in orders_products.products_name and the Shipping Export feed.
 */
class zcObserverIemsLegacyBridge extends base
{
    /** @var array<int, string> */
    private array $uomCache = [];
    private ?bool $uomColumnExists = null;

    public function __construct()
    {
        $this->attach($this, [
            'NOTIFY_CREATE_ACCOUNT_VALIDATION_CHECK',
            'NOTIFY_MODULE_CHECKOUT_NEW_ADDRESS_VALIDATION',
            'NOTIFY_ADDRESS_BOOK_PROCESS_VALIDATION',
            'NOTIFY_HEADER_START_CHECKOUT_SHIPPING',
            'NOTIFY_ZEN_GET_PRODUCTS_DISPLAY_PRICE_FREE_OR_CALL',
            'NOTIFIER_CART_GET_PRODUCTS_END',
        ]);
    }

    public function update(&$class, $eventID, $p1, &$p2, &$p3, &$p4, &$p5, &$p6, &$p7)
    {
        switch ($eventID) {
            case 'NOTIFY_CREATE_ACCOUNT_VALIDATION_CHECK':
                $this->validatePostedUnit('create_account', $p2);
                break;
            case 'NOTIFY_MODULE_CHECKOUT_NEW_ADDRESS_VALIDATION':
                $this->validatePostedUnit('checkout_address', $p2);
                break;
            case 'NOTIFY_ADDRESS_BOOK_PROCESS_VALIDATION':
                $this->validatePostedUnit('addressbook', $p2);
                break;
            case 'NOTIFY_HEADER_START_CHECKOUT_SHIPPING':
                $this->guardShippingAddressUnit();
                break;
            case 'NOTIFY_ZEN_GET_PRODUCTS_DISPLAY_PRICE_FREE_OR_CALL':
                $this->appendUomToDisplayPrice($p1, $p2, $p3, $p4);
                break;
            case 'NOTIFIER_CART_GET_PRODUCTS_END':
                $this->appendUomToCartNames($p2);
                break;
        }
    }

    // -------------------------------------------------------------------------
    // Units
    // -------------------------------------------------------------------------

    private function validatePostedUnit(string $stack, &$error): void
    {
        global $messageStack;

        if (!defined('ACCOUNT_SUBURB') || ACCOUNT_SUBURB !== 'true') {
            return;
        }
        $suburb = (string)zen_db_prepare_input($_POST['suburb'] ?? '');
        if ($suburb === '' || LegacyUnits::isPlaceholder($suburb)) {
            $error = true;
            $messageStack->add($stack, ENTRY_IEMS_LEGACY_UNIT_ERROR);
        }
    }

    /**
     * Legacy checkout_shipping redirected back to the shipping-address page when
     * the delivery address still carried the placeholder unit.
     */
    private function guardShippingAddressUnit(): void
    {
        global $db, $messageStack;

        if (empty($_SESSION['customer_id'])) {
            return;
        }
        $addressId = (int)(!empty($_SESSION['sendto']) ? $_SESSION['sendto'] : ($_SESSION['customer_default_address_id'] ?? 0));
        if ($addressId <= 0) {
            return;
        }
        $sql = "SELECT entry_suburb
                  FROM " . TABLE_ADDRESS_BOOK . "
                 WHERE address_book_id = :addressId
                   AND customers_id = :customerId
                 LIMIT 1";
        $sql = $db->bindVars($sql, ':addressId', $addressId, 'integer');
        $sql = $db->bindVars($sql, ':customerId', $_SESSION['customer_id'], 'integer');
        $result = $db->Execute($sql);
        if (!$result->EOF && LegacyUnits::isPlaceholder((string)$result->fields['entry_suburb'])) {
            $messageStack->add_session('checkout_address', ENTRY_IEMS_LEGACY_UNIT_ERROR, 'error');
            zen_redirect(zen_href_link(FILENAME_CHECKOUT_SHIPPING_ADDRESS, '', 'SSL'));
        }
    }

    // -------------------------------------------------------------------------
    // Unit of measure
    // -------------------------------------------------------------------------

    /**
     * Legacy output was: $final_display_price . ' ' . $uom . $free_tag . $call_tag.
     * Taking over the free/call tags lets the uom sit in exactly that position.
     */
    private function appendUomToDisplayPrice(array $info, &$tags_handled, &$free_tag, &$call_tag): void
    {
        if (!$this->hasUomColumn()) {
            return;
        }
        $tags_handled = true;

        $free_tag = ' ' . $this->uomFor((int)$info['product_id']);
        if ($info['product_is_free'] === '1') {
            $free_tag .= '<br>';
            if (OTHER_IMAGE_PRICE_IS_FREE_ON === '0') {
                $free_tag .= PRODUCTS_PRICE_IS_FREE_TEXT;
            } else {
                $free_tag .= zen_image(DIR_WS_TEMPLATE_IMAGES . OTHER_IMAGE_PRICE_IS_FREE, PRODUCTS_PRICE_IS_FREE_TEXT);
            }
        }

        $call_tag = '';
        if ($info['product_is_call'] === '1') {
            $call_tag = '<br>';
            if (PRODUCTS_PRICE_IS_CALL_IMAGE_ON === '0') {
                $call_tag .= PRODUCTS_PRICE_IS_CALL_FOR_PRICE_TEXT;
            } else {
                $call_tag .= zen_image(DIR_WS_TEMPLATE_IMAGES . OTHER_IMAGE_CALL_FOR_PRICE, PRODUCTS_PRICE_IS_CALL_FOR_PRICE_TEXT);
            }
        }
    }

    /**
     * Legacy cart names were always "name (uom)", even with an empty uom, and
     * that text is copied into orders_products.products_name.
     */
    private function appendUomToCartNames(&$products): void
    {
        if (!is_array($products) || !$this->hasUomColumn()) {
            return;
        }
        foreach ($products as $index => $product) {
            $uom = $this->uomFor((int)$product['id']);
            $products[$index]['name'] = $product['name'] . ' (' . $uom . ')';
            $products[$index]['uom'] = $uom;
        }
    }

    private function uomFor(int $productId): string
    {
        global $db;

        if (!isset($this->uomCache[$productId])) {
            $result = $db->Execute(
                'SELECT products_price_uom FROM ' . TABLE_PRODUCTS . ' WHERE products_id = ' . $productId . ' LIMIT 1'
            );
            $this->uomCache[$productId] = $result->EOF ? '' : (string)$result->fields['products_price_uom'];
        }
        return $this->uomCache[$productId];
    }

    private function hasUomColumn(): bool
    {
        global $db;

        if ($this->uomColumnExists === null) {
            $result = $db->Execute("SHOW COLUMNS FROM " . TABLE_PRODUCTS . " LIKE 'products_price_uom'");
            $this->uomColumnExists = !$result->EOF;
        }
        return $this->uomColumnExists;
    }
}
