<?php

declare(strict_types=1);

require_once __DIR__ . '/IemsPaymentEligibilityService.php';

abstract class IemsAgencyPaymentModule extends base
{
    protected int $_check;
    public string $code;
    public string $description;
    public bool $enabled = false;
    public int $order_status = 0;
    public $sort_order;
    public string $title;

    private string $statusKey;
    private string $sortOrderKey;
    private string $orderStatusKey;
    private string $requiredMode;
    private string $configurationTitle;
    private string $eligibilityError;

    public function __construct(
        string $code,
        string $title,
        string $description,
        string $statusKey,
        string $sortOrderKey,
        string $orderStatusKey,
        string $requiredMode,
        string $configurationTitle,
        string $eligibilityError,
        bool $uninstalling = false
    ) {
        $this->code = $code;
        $this->statusKey = $statusKey;
        $this->sortOrderKey = $sortOrderKey;
        $this->orderStatusKey = $orderStatusKey;
        $this->requiredMode = $requiredMode;
        $this->configurationTitle = $configurationTitle;
        $this->eligibilityError = $eligibilityError;
        if ($uninstalling) {
            return;
        }

        $this->title = $title;
        $this->description = $description;
        $this->sort_order = defined($sortOrderKey) ? (int)constant($sortOrderKey) : null;
        if ($this->sort_order === null) {
            return;
        }

        $this->enabled = defined($statusKey) && constant($statusKey) === 'True';
        if (defined($orderStatusKey) && (int)constant($orderStatusKey) > 0) {
            $this->order_status = (int)constant($orderStatusKey);
        }
        $this->update_status();
    }

    public function update_status(): void
    {
        if (IS_ADMIN_FLAG === true) {
            return;
        }

        $this->enabled = $this->enabled && $this->isCustomerEligible();
        if (!$this->enabled && ($_SESSION['payment'] ?? null) === $this->code) {
            $this->rejectSelectedMethod();
        }
    }

    public function javascript_validation(): bool
    {
        return false;
    }

    /**
     * @return array{id: string, module: string}
     */
    public function selection(): array
    {
        return [
            'id' => $this->code,
            'module' => $this->title,
        ];
    }

    public function pre_confirmation_check(): bool
    {
        $this->assertStillEligible();

        return false;
    }

    public function confirmation(): bool
    {
        return false;
    }

    public function process_button(): bool
    {
        return false;
    }

    public function before_process(): bool
    {
        $this->assertStillEligible();

        return false;
    }

    public function after_process(): bool
    {
        return false;
    }

    public function get_error(): bool
    {
        return false;
    }

    public function check(): int
    {
        global $db;

        if (!isset($this->_check)) {
            $result = $db->Execute(
                "SELECT configuration_value
                   FROM " . TABLE_CONFIGURATION . "
                  WHERE configuration_key = '" . $this->statusKey . "'"
            );
            $this->_check = $result->RecordCount();
        }

        return $this->_check;
    }

    public function install(): void
    {
        global $db;

        $db->Execute(
            "INSERT INTO " . TABLE_CONFIGURATION . "
                (configuration_title, configuration_key, configuration_value,
                 configuration_description, configuration_group_id, sort_order,
                 set_function, date_added)
             VALUES
                ('Enable " . $this->configurationTitle . "',
                 '" . $this->statusKey . "',
                 'True',
                 'Offer " . $this->configurationTitle . " to customers whose current valid active IEMS agency selects it?',
                 6, 1, 'zen_cfg_select_option(array(''True'', ''False''),', NOW())"
        );
        $db->Execute(
            "INSERT INTO " . TABLE_CONFIGURATION . "
                (configuration_title, configuration_key, configuration_value,
                 configuration_description, configuration_group_id, sort_order,
                 set_function, use_function, date_added)
             VALUES
                ('Set Order Status',
                 '" . $this->orderStatusKey . "',
                 '0',
                 'Set the status of orders using this payment method.',
                 6, 2, 'zen_cfg_pull_down_order_statuses(', 'zen_get_order_status_name', NOW())"
        );
        $db->Execute(
            "INSERT INTO " . TABLE_CONFIGURATION . "
                (configuration_title, configuration_key, configuration_value,
                 configuration_description, configuration_group_id, sort_order, date_added)
             VALUES
                ('Sort Order',
                 '" . $this->sortOrderKey . "',
                 '0',
                 'Sort order of display. Lowest is displayed first.',
                 6, 3, NOW())"
        );
    }

    public function remove(): void
    {
        global $db;

        $db->Execute(
            "DELETE FROM " . TABLE_CONFIGURATION . "
              WHERE configuration_key IN ('" . implode("', '", $this->keys()) . "')"
        );
    }

    /**
     * @return string[]
     */
    public function keys(): array
    {
        return [
            $this->statusKey,
            $this->orderStatusKey,
            $this->sortOrderKey,
        ];
    }

    private function isCustomerEligible(): bool
    {
        if (!function_exists('zen_in_guest_checkout') || zen_in_guest_checkout()) {
            return false;
        }

        $customerId = $_SESSION['customer_id'] ?? null;
        if (!is_int($customerId) && !(is_string($customerId) && ctype_digit($customerId))) {
            return false;
        }

        return (new IemsPaymentEligibilityService())->isEligible((int)$customerId, $this->requiredMode);
    }

    private function assertStillEligible(): void
    {
        if ($this->enabled && $this->isCustomerEligible()) {
            return;
        }

        $this->rejectSelectedMethod();
        zen_redirect(zen_href_link(FILENAME_CHECKOUT_PAYMENT, '', 'SSL', true, false));
    }

    private function rejectSelectedMethod(): void
    {
        global $messageStack;

        if (($_SESSION['payment'] ?? null) === $this->code) {
            unset($_SESSION['payment']);
        }
        $messageStack->add_session('checkout_payment', $this->eligibilityError, 'error');

        $currentPage = $GLOBALS['current_page_base'] ?? '';
        if ($currentPage !== FILENAME_CHECKOUT_PAYMENT) {
            zen_redirect(zen_href_link(FILENAME_CHECKOUT_PAYMENT, '', 'SSL', true, false));
        }
    }
}
