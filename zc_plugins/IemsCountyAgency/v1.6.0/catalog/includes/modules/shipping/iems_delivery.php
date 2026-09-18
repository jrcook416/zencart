<?php

declare(strict_types=1);

require_once dirname(__DIR__, 2) . '/classes/IemsShippingEligibilityService.php';

class iems_delivery extends ZenShipping
{
    public function __construct(bool $uninstalling = false)
    {
        $this->code = 'iems_delivery';
        if ($uninstalling) {
            return;
        }

        $this->title = MODULE_SHIPPING_IEMS_DELIVERY_TEXT_TITLE;
        $this->description = MODULE_SHIPPING_IEMS_DELIVERY_TEXT_DESCRIPTION;
        $this->sort_order = defined('MODULE_SHIPPING_IEMS_DELIVERY_SORT_ORDER')
            ? (int)MODULE_SHIPPING_IEMS_DELIVERY_SORT_ORDER
            : null;
        if ($this->sort_order === null) {
            return;
        }

        $this->icon = '';
        $this->tax_class = '0';
        $this->tax_basis = 'Shipping';
        $this->enabled = MODULE_SHIPPING_IEMS_DELIVERY_STATUS === 'True';
        $this->update_status();
    }

    public function update_status(): void
    {
        if (!$this->enabled || IS_ADMIN_FLAG === true) {
            return;
        }

        $this->enabled = $this->isCustomerEligible();
    }

    public function quote($method = ''): array
    {
        $this->enabled = MODULE_SHIPPING_IEMS_DELIVERY_STATUS === 'True'
            && $this->isCustomerEligible();
        $methods = [];
        if ($this->enabled) {
            $methods[] = [
                'id' => $this->code,
                'title' => MODULE_SHIPPING_IEMS_DELIVERY_TEXT_WAY,
                'cost' => 0.00,
            ];
        }

        $this->quotes = [
            'id' => $this->code,
            'module' => MODULE_SHIPPING_IEMS_DELIVERY_TEXT_TITLE,
            'methods' => $methods,
        ];

        return $this->quotes;
    }

    public function check()
    {
        global $db;

        if (!isset($this->_check)) {
            $result = $db->Execute(
                "SELECT configuration_value
                   FROM " . TABLE_CONFIGURATION . "
                  WHERE configuration_key = 'MODULE_SHIPPING_IEMS_DELIVERY_STATUS'"
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
                ('Enable Delivery to Location',
                 'MODULE_SHIPPING_IEMS_DELIVERY_STATUS',
                 'True',
                 'Offer zero-cost delivery to signed-in customers whose valid active IEMS agency enables delivery?',
                 6, 0, 'zen_cfg_select_option(array(''True'', ''False''),', NOW())"
        );
        $db->Execute(
            "INSERT INTO " . TABLE_CONFIGURATION . "
                (configuration_title, configuration_key, configuration_value,
                 configuration_description, configuration_group_id, sort_order, date_added)
             VALUES
                ('Sort Order',
                 'MODULE_SHIPPING_IEMS_DELIVERY_SORT_ORDER',
                 '10',
                 'Sort order of display.',
                 6, 0, NOW())"
        );
    }

    public function keys(): array
    {
        return [
            'MODULE_SHIPPING_IEMS_DELIVERY_STATUS',
            'MODULE_SHIPPING_IEMS_DELIVERY_SORT_ORDER',
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

        return (new IemsShippingEligibilityService())->isDeliveryEligible((int)$customerId);
    }
}
