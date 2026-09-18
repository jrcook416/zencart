<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/IemsShippingAddressService.php';

class zcObserverIemsCheckoutUnit extends base
{
    private IemsCheckoutUnitService $service;

    public function __construct()
    {
        $this->service = new IemsCheckoutUnitService();
        $this->attach($this, [
            'NOTIFY_HEADER_START_CHECKOUT_SHIPPING',
            'NOTIFY_HEADER_START_CHECKOUT_PAYMENT',
            'NOTIFY_HEADER_START_CHECKOUT_CONFIRMATION',
            'NOTIFY_HEADER_START_CHECKOUT_ONE',
            'NOTIFY_HEADER_START_CHECKOUT_ONE_CONFIRMATION',
            'NOTIFY_CHECKOUT_PROCESS_BEGIN',
            'NOTIFY_PAYMENT_PAYPALEC_BEFORE_SETEC',
            'NOTIFY_PAYPALWPP_BEFORE_DOEXPRESSCHECKOUT',
            'NOTIFY_PAYMENT_PAYPAL_CANCELLED_DURING_CHECKOUT',
            'NOTIFY_ORDER_CART_AFTER_ADDRESSES_SET',
            'NOTIFY_ORDER_CART_EXTERNAL_TAX_DURING_ORDER_CREATE',
            'NOTIFY_CHECKOUT_PROCESS_BEFORE_CART_RESET',
            'NOTIFY_HEADER_START_SHOPPING_CART',
            'NOTIFY_HEADER_START_LOGOFF',
        ]);
    }

    public function update(&$class, $eventID, $p1, &$p2, &$p3, &$p4, &$p5, &$p6, &$p7)
    {
        switch ($eventID) {
            case 'NOTIFY_HEADER_START_CHECKOUT_SHIPPING':
                $this->handleShippingPage();
                break;

            case 'NOTIFY_HEADER_START_CHECKOUT_PAYMENT':
                $this->handlePaymentPage();
                break;

            case 'NOTIFY_HEADER_START_CHECKOUT_CONFIRMATION':
                $this->capturePostedSelection('standard');
                $this->requireValidSelection(FILENAME_CHECKOUT_SHIPPING, 'checkout_shipping');
                $this->showConfirmation();
                break;

            case 'NOTIFY_HEADER_START_CHECKOUT_ONE':
                $this->prepareSelector();
                break;

            case 'NOTIFY_HEADER_START_CHECKOUT_ONE_CONFIRMATION':
                $this->capturePostedSelection('opc');
                $this->requireValidSelection(FILENAME_CHECKOUT_ONE, 'checkout_payment');
                $this->showConfirmation();
                break;

            case 'NOTIFY_CHECKOUT_PROCESS_BEGIN':
            case 'NOTIFY_PAYMENT_PAYPALEC_BEFORE_SETEC':
            case 'NOTIFY_PAYPALWPP_BEFORE_DOEXPRESSCHECKOUT':
                $this->requireValidSelection($this->getSelectionPage(), $this->getSelectionMessageStack());
                break;

            case 'NOTIFY_ORDER_CART_AFTER_ADDRESSES_SET':
                $this->setOrderSuburbs($class, $p2, $p3);
                break;

            case 'NOTIFY_ORDER_CART_EXTERNAL_TAX_DURING_ORDER_CREATE':
                $this->enforceOrderSelection($class);
                break;

            case 'NOTIFY_CHECKOUT_PROCESS_BEFORE_CART_RESET':
            case 'NOTIFY_PAYMENT_PAYPAL_CANCELLED_DURING_CHECKOUT':
            case 'NOTIFY_HEADER_START_SHOPPING_CART':
            case 'NOTIFY_HEADER_START_LOGOFF':
                $this->service->clearSelection();
                break;
        }
    }

    private function handleShippingPage(): void
    {
        if ($this->isPayPalExpressCancellation()) {
            $this->service->clearSelection();
        }

        if (($_POST['action'] ?? '') === 'process') {
            $previousSelection = $this->service->getValidatedSelection($this->customerId());
            $selection = $this->service->saveSelection(
                $this->customerId(),
                $_POST['iems_unit_id'] ?? null,
                'standard'
            );
            if (!$selection['valid']) {
                $this->redirectWithError(FILENAME_CHECKOUT_SHIPPING, 'checkout_shipping', $selection['error']);
            }
            if (!$this->isSameSelection($previousSelection, $selection)) {
                unset($_SESSION['shipping']);
                zen_redirect(zen_href_link(FILENAME_CHECKOUT_SHIPPING, '', 'SSL'));
            }
        }

        $this->prepareSelector();
    }

    private function handlePaymentPage(): void
    {
        if ($_SESSION['cart']->get_content_type() === 'virtual') {
            $this->prepareSelector();
            $GLOBALS['iems_checkout_unit_render_on_payment'] = true;
            return;
        }

        $this->requireValidSelection(FILENAME_CHECKOUT_SHIPPING, 'checkout_shipping');
    }

    private function capturePostedSelection(string $flow): void
    {
        if (!array_key_exists('iems_unit_id', $_POST)) {
            return;
        }

        $previousSelection = $this->service->getValidatedSelection($this->customerId());
        $selection = $this->service->saveSelection(
            $this->customerId(),
            $_POST['iems_unit_id'],
            $flow
        );
        if (!$selection['valid']) {
            $page = $flow === 'opc' ? FILENAME_CHECKOUT_ONE : FILENAME_CHECKOUT_PAYMENT;
            $stack = $flow === 'opc' ? 'checkout_payment' : 'checkout_payment';
            $this->redirectWithError($page, $stack, $selection['error']);
        }
        if (!$this->isSameSelection($previousSelection, $selection)) {
            unset($_SESSION['shipping']);
            $page = $flow === 'opc' ? FILENAME_CHECKOUT_ONE : $this->getSelectionPage();
            zen_redirect(zen_href_link($page, '', 'SSL'));
        }
    }

    private function prepareSelector(): void
    {
        $customerId = $this->customerId();
        $selection = $this->service->getValidatedSelection($customerId);
        $GLOBALS['iems_checkout_shipping_ready'] = $selection['valid'];
        $GLOBALS['iems_checkout_unit_selector_html'] = $this->renderSelector(
            $this->service->getCheckoutOptionsForCustomer($customerId),
            $selection['selection_value']
        );
    }

    private function requireValidSelection(string $redirectPage, string $messageStack): void
    {
        $selection = $this->service->getValidatedSelection($this->customerId());
        if (!$selection['valid']) {
            $this->redirectWithError($redirectPage, $messageStack, $selection['error']);
        }
    }

    private function showConfirmation(): void
    {
        global $messageStack;

        $selection = $this->service->getValidatedSelection($this->customerId());
        if ($selection['valid']) {
            $messageStack->add(
                'checkout_confirmation',
                sprintf(TEXT_IEMS_CHECKOUT_UNIT_CONFIRMATION, zen_output_string_protected($selection['label'])),
                'success'
            );
        }
    }

    private function setOrderSuburbs(object $order, &$taxCountryId, &$taxZoneId): void
    {
        $selection = $this->service->getValidatedSelection($this->customerId());
        if (!$selection['valid']) {
            return;
        }

        $this->applyOrderAddresses($order, $selection);
        if (
            $this->selectedShippingModuleIsIems()
            && $this->usesDeliveryAddressForProductTax($order)
        ) {
            $taxCountryId = (int)$order->delivery['country_id'];
            $taxZoneId = (int)$order->delivery['zone_id'];
        }
    }

    private function enforceOrderSelection(object $order): void
    {
        $selection = $this->service->getValidatedSelection($this->customerId());
        if (!$selection['valid']) {
            $this->redirectWithError(
                $this->getSelectionPage(),
                $this->getSelectionMessageStack(),
                $selection['error']
            );
        }

        $this->applyOrderAddresses($order, $selection);
    }

    /**
     * @param array<string, mixed> $selection
     */
    private function applyOrderAddresses(object $order, array $selection): void
    {
        $label = $selection['label'];
        $order->customer['suburb'] = $label;
        $order->delivery['suburb'] = $label;
        $order->billing['suburb'] = $label;

        $shippingModule = $this->selectedShippingModule();
        if (!in_array(
            $shippingModule,
            [IemsShippingAddressService::MODULE_PICKUP, IemsShippingAddressService::MODULE_DELIVERY],
            true
        )) {
            return;
        }

        $destination = (new IemsShippingAddressService())->getDestination(
            $this->customerId(),
            $shippingModule
        );
        if ($destination === null) {
            unset($_SESSION['shipping']);
            $error = $shippingModule === IemsShippingAddressService::MODULE_PICKUP
                ? 'pickup_configuration'
                : IemsCheckoutUnitService::ERROR_ADDRESS;
            $this->redirectWithError(
                $this->getSelectionPage(),
                $this->getSelectionMessageStack(),
                $error
            );
        }

        $order->delivery = $destination;
    }

    private function selectedShippingModuleIsIems(): bool
    {
        return in_array(
            $this->selectedShippingModule(),
            [IemsShippingAddressService::MODULE_PICKUP, IemsShippingAddressService::MODULE_DELIVERY],
            true
        );
    }

    private function usesDeliveryAddressForProductTax(object $order): bool
    {
        if (($order->content_type ?? '') === 'virtual') {
            return false;
        }
        if (STORE_PRODUCT_TAX_BASIS === 'Shipping') {
            return true;
        }

        return STORE_PRODUCT_TAX_BASIS === 'Store'
            && (int)($order->billing['zone_id'] ?? 0) !== (int)STORE_ZONE;
    }

    /**
     * @param array<int, array{id: int|string, text: string}> $options
     */
    private function renderSelector(array $options, string $selectedValue): string
    {
        $selectOptions = [['id' => '', 'text' => TEXT_IEMS_CHECKOUT_UNIT_PROMPT]];
        foreach ($options as $option) {
            $selectOptions[] = $option;
        }

        $select = zen_draw_pull_down_menu(
            'iems_unit_id',
            $selectOptions,
            $selectedValue,
            'id="iems_unit_id" required aria-required="true"'
        );

        $help = $options === []
            ? TEXT_IEMS_CHECKOUT_UNIT_UNAVAILABLE
            : TEXT_IEMS_CHECKOUT_UNIT_HELP . ' ' . TEXT_IEMS_CHECKOUT_UNIT_CONFIRM_HELP;

        return
            '<fieldset id="iemsCheckoutUnit" class="iems-checkout-unit">'
            . '<legend>' . zen_output_string_protected(HEADING_IEMS_CHECKOUT_UNIT) . '</legend>'
            . '<label for="iems_unit_id" class="inputLabel">'
            . zen_output_string_protected(ENTRY_IEMS_CHECKOUT_UNIT)
            . '</label>'
            . $select
            . '<div class="instructions">' . zen_output_string_protected($help) . '</div>'
            . '</fieldset>';
    }

    private function redirectWithError(string $page, string $messageStackName, string $error): never
    {
        global $messageStack;

        $messageStack->add_session($messageStackName, $this->errorMessage($error), 'error');
        zen_redirect(zen_href_link($page, '', 'SSL'));
    }

    private function errorMessage(string $error): string
    {
        return match ($error) {
            IemsCheckoutUnitService::ERROR_AFFILIATION => ERROR_IEMS_CHECKOUT_AFFILIATION,
            IemsCheckoutUnitService::ERROR_LABEL_LENGTH => ERROR_IEMS_CHECKOUT_UNIT_LABEL_LENGTH,
            IemsCheckoutUnitService::ERROR_CART => ERROR_IEMS_CHECKOUT_UNIT_STALE,
            IemsCheckoutUnitService::ERROR_FALLBACK => ERROR_IEMS_CHECKOUT_AGENCY_FALLBACK_INVALID,
            IemsCheckoutUnitService::ERROR_ADDRESS => ERROR_IEMS_CHECKOUT_DELIVERY_ADDRESS,
            'pickup_configuration' => ERROR_IEMS_CHECKOUT_PICKUP_CONFIGURATION,
            IemsCheckoutUnitService::ERROR_MALFORMED,
            IemsCheckoutUnitService::ERROR_UNIT => ERROR_IEMS_CHECKOUT_UNIT_INVALID,
            default => ERROR_IEMS_CHECKOUT_UNIT_REQUIRED,
        };
    }

    private function getSelectionPage(): string
    {
        if ($this->service->getSelectionFlow() === 'opc') {
            return FILENAME_CHECKOUT_ONE;
        }

        if ($_SESSION['cart']->get_content_type() === 'virtual') {
            return FILENAME_CHECKOUT_PAYMENT;
        }

        return FILENAME_CHECKOUT_SHIPPING;
    }

    private function getSelectionMessageStack(): string
    {
        if (
            $this->service->getSelectionFlow() === 'opc'
            || $_SESSION['cart']->get_content_type() === 'virtual'
        ) {
            return 'checkout_payment';
        }

        return 'checkout_shipping';
    }

    private function customerId(): int
    {
        return !empty($_SESSION['customer_id']) ? (int)$_SESSION['customer_id'] : 0;
    }

    /**
     * @param array<string, mixed> $first
     * @param array<string, mixed> $second
     */
    private function isSameSelection(array $first, array $second): bool
    {
        return $first['valid']
            && $second['valid']
            && $first['selection_type'] === $second['selection_type']
            && $first['reference_id'] === $second['reference_id'];
    }

    private function selectedShippingModule(): string
    {
        $shippingId = $_SESSION['shipping']['id'] ?? '';
        if (!is_string($shippingId) || !str_contains($shippingId, '_')) {
            return '';
        }

        return strstr($shippingId, '_', true) ?: '';
    }

    private function isPayPalExpressCancellation(): bool
    {
        foreach (['ec_cancel', 'amp;ec_cancel'] as $parameter) {
            $value = $_GET[$parameter] ?? null;
            if (is_scalar($value) && (string)$value === '1') {
                return true;
            }
        }

        return false;
    }
}
