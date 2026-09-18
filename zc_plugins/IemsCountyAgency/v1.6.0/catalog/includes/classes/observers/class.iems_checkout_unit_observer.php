<?php

declare(strict_types=1);

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
                $this->setOrderSuburbs($class);
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
            $selection = $this->service->saveSelection(
                $this->customerId(),
                $_POST['iems_unit_id'] ?? null,
                'standard'
            );
            if (!$selection['valid']) {
                $this->redirectWithError(FILENAME_CHECKOUT_SHIPPING, 'checkout_shipping', $selection['error']);
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
    }

    private function prepareSelector(): void
    {
        $customerId = $this->customerId();
        $selection = $this->service->getValidatedSelection($customerId);
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

    private function setOrderSuburbs(object $order): void
    {
        $selection = $this->service->getValidatedSelection($this->customerId());
        if (!$selection['valid']) {
            return;
        }

        $this->applyOrderSuburbs($order, $selection['label']);
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

        $this->applyOrderSuburbs($order, $selection['label']);
    }

    private function applyOrderSuburbs(object $order, string $label): void
    {
        $order->customer['suburb'] = $label;
        $order->delivery['suburb'] = $label;
        $order->billing['suburb'] = $label;
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

        $help = $options === [] ? TEXT_IEMS_CHECKOUT_UNIT_UNAVAILABLE : TEXT_IEMS_CHECKOUT_UNIT_HELP;

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
