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
 *
 * Also makes the order's customer and billing addresses match the delivery address
 * (the unit chosen at the start of checkout).
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
            // One-Page Checkout skips checkout_shipping, so guard its pages too.
            'NOTIFY_HEADER_START_CHECKOUT_ONE',
            'NOTIFY_HEADER_START_CHECKOUT_ONE_CONFIRMATION',
            'NOTIFY_ZEN_GET_PRODUCTS_DISPLAY_PRICE_FREE_OR_CALL',
            'NOTIFIER_CART_GET_PRODUCTS_END',
            'NOTIFY_ORDER_CART_AFTER_ADDRESSES_SET',
            'NOTIFY_HEADER_START_CHECKOUT_PAYMENT',
            'NOTIFY_HEADER_START_CHECKOUT_CONFIRMATION',
            'NOTIFY_HEADER_START_CHECKOUT_PROCESS',
            'NOTIFY_HEADER_START_CHECKOUT_PAYMENT_ADDRESS',
            'NOTIFY_SEFU_INTERCEPT',
            'NOTIFY_ZEN_REDIRECT',
            'NOTIFY_HEADER_START_CHECKOUT_SUCCESS',
            'NOTIFY_CHECKOUT_PROCESS_BEFORE_CART_RESET',
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
            case 'NOTIFY_HEADER_START_CHECKOUT_ONE':
            case 'NOTIFY_HEADER_START_CHECKOUT_ONE_CONFIRMATION':
                $this->enforceShippingAddressStep();
                $this->guardShippingAddressUnit();
                break;
            case 'NOTIFY_ZEN_GET_PRODUCTS_DISPLAY_PRICE_FREE_OR_CALL':
                $this->appendUomToDisplayPrice($p1, $p2, $p3, $p4);
                break;
            case 'NOTIFIER_CART_GET_PRODUCTS_END':
                $this->appendUomToCartNames($p2);
                break;
            case 'NOTIFY_ORDER_CART_AFTER_ADDRESSES_SET':
                $this->matchOrderAddressesToDelivery($class);
                break;
            case 'NOTIFY_HEADER_START_CHECKOUT_PAYMENT':
            case 'NOTIFY_HEADER_START_CHECKOUT_CONFIRMATION':
            case 'NOTIFY_HEADER_START_CHECKOUT_PROCESS':
                $this->enforceShippingAddressStep();
                $this->syncBillingAddressWithDelivery();
                break;
            case 'NOTIFY_HEADER_START_CHECKOUT_PAYMENT_ADDRESS':
                // Billing is locked to the delivery unit; no separate billing address.
                zen_redirect(zen_href_link(FILENAME_CHECKOUT_PAYMENT, '', 'SSL'));
                break;
            case 'NOTIFY_SEFU_INTERCEPT':
                $this->pointHeaderCheckoutAtShippingAddress($p3, $p4);
                break;
            case 'NOTIFY_ZEN_REDIRECT':
                $this->markShippingAddressStepDone($p1);
                break;
            case 'NOTIFY_HEADER_START_CHECKOUT_SUCCESS':
            case 'NOTIFY_CHECKOUT_PROCESS_BEFORE_CART_RESET':
                unset($_SESSION['iems_shipping_address_step']);
                break;
        }
    }

    // -------------------------------------------------------------------------
    // Header "Checkout" link
    // -------------------------------------------------------------------------

    /**
     * 1.5.6c sent the header Checkout link to checkout_shipping_address, where the
     * unit is picked. Templates build it with zen_href_link(FILENAME_CHECKOUT_SHIPPING),
     * which has no hook of its own, so the notifier zen_href_link() raises for every
     * link is used: only a plain checkout_shipping link built by the header or mobile
     * menu template is changed. Cart buttons, breadcrumbs and redirects are untouched.
     * checkout_shipping_address sends a logged-out customer to login (and back to
     * itself afterwards) and an empty cart to the shopping cart, as checkout_shipping does.
     */
    private function pointHeaderCheckoutAtShippingAddress(&$page, $parameters): void
    {
        if (!defined('FILENAME_CHECKOUT_SHIPPING') || !defined('FILENAME_CHECKOUT_SHIPPING_ADDRESS')
            || $page !== FILENAME_CHECKOUT_SHIPPING || !empty($parameters)) {
            return;
        }
        foreach (debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 10) as $frame) {
            if (in_array(basename($frame['file'] ?? ''), ['tpl_header.php', 'tpl_modules_mobile_menu.php'], true)) {
                $page = FILENAME_CHECKOUT_SHIPPING_ADDRESS;
                return;
            }
        }
    }

    // -------------------------------------------------------------------------
    // checkout_shipping_address cannot be bypassed
    // -------------------------------------------------------------------------

    /**
     * The step counts as done when checkout_shipping_address submits successfully, which
     * the core module signals by redirecting on to checkout_shipping (or checkout_payment
     * for a virtual cart). The mark is tied to the customer and to the cart's internal
     * cartID, which core regenerates on every cart change, so any cart change, a
     * different login, a placed order (cleared explicitly) or a new session means the
     * step must be done again.
     */
    private function markShippingAddressStepDone($redirect): void
    {
        global $current_page_base;

        if (!is_array($redirect) || ($current_page_base ?? '') !== 'checkout_shipping_address'
            || empty($_SESSION['customer_id']) || !isset($_SESSION['cart'])) {
            return;
        }
        $url = html_entity_decode((string)($redirect['url'] ?? ''));
        if (!preg_match('/main_page=checkout_(shipping|payment)(&|$)/', $url)
            && !preg_match('#/checkout_(shipping|payment)(\?|$)#', $url)) {
            return;
        }
        $_SESSION['iems_shipping_address_step'] = [
            'customer' => (int)$_SESSION['customer_id'],
            'cart' => (string)($_SESSION['cart']->cartID ?? ''),
        ];
    }

    /**
     * Send later checkout steps back to checkout_shipping_address until it is done.
     * Logged-out customers and empty or virtual carts are left to core (login first,
     * which returns to the step they asked for and is then gated here; nothing to ship).
     * checkout_shipping_address itself is never gated, so there is no redirect loop.
     */
    public function enforceShippingAddressStep(): void
    {
        if (!$this->shippingAddressStepRequired()) {
            return;
        }
        zen_redirect(zen_href_link(FILENAME_CHECKOUT_SHIPPING_ADDRESS, '', 'SSL'));
    }

    public function shippingAddressStepRequired(): bool
    {
        if (empty($_SESSION['customer_id']) || !isset($_SESSION['cart']) || !is_object($_SESSION['cart'])
            || !defined('FILENAME_CHECKOUT_SHIPPING_ADDRESS')) {
            return false;
        }
        $cart = $_SESSION['cart'];
        if ($cart->count_contents() <= 0
            || (method_exists($cart, 'get_content_type') && $cart->get_content_type() === 'virtual')) {
            return false;
        }
        $step = $_SESSION['iems_shipping_address_step'] ?? null;
        return !is_array($step)
            || (int)($step['customer'] ?? 0) !== (int)$_SESSION['customer_id']
            || (string)($step['cart'] ?? '') !== (string)($cart->cartID ?? '');
    }

    // -------------------------------------------------------------------------
    // Order addresses
    // -------------------------------------------------------------------------
    /**
     * The delivery address carries the unit picked at the start of checkout. Copy it
     * over the order's billing and customer addresses so all three match; the customer's
     * telephone and email address (not part of an address) are kept.
     */
    public function matchOrderAddressesToDelivery(object $order): void
    {
        $delivery = $order->delivery ?? null;
        if (!is_array($delivery)
            || trim((string)($delivery['street_address'] ?? '')) === ''
            || LegacyUnits::isPlaceholder((string)($delivery['suburb'] ?? ''))) {
            return;
        }

        $order->billing = $delivery;

        $customer = is_array($order->customer ?? null) ? $order->customer : [];
        foreach (['telephone', 'email_address'] as $key) {
            if (array_key_exists($key, $customer)) {
                $delivery[$key] = $customer[$key];
            }
        }
        $order->customer = $delivery;
    }

    /**
     * The payment page prints the billing address from $_SESSION['billto'], so point it
     * at the delivery address (the unit) before the page, confirmation and order run.
     */
    public function syncBillingAddressWithDelivery(): void
    {
        if (empty($_SESSION['customer_id']) || empty($_SESSION['sendto'])) {
            return;
        }
        $_SESSION['billto'] = $_SESSION['sendto'];
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
     * the delivery address still carried the placeholder unit. The same check
     * runs on One-Page Checkout's pages, which bypass checkout_shipping.
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
