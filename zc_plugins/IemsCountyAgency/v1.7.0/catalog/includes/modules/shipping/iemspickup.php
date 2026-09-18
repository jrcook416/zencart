<?php

declare(strict_types=1);

require_once dirname(__DIR__, 2) . '/classes/IemsShippingEligibilityService.php';
require_once dirname(__DIR__, 2) . '/classes/IemsShippingAddressService.php';

class iemspickup extends ZenShipping
{
    public function __construct(bool $uninstalling = false)
    {
        $this->code = 'iemspickup';
        if ($uninstalling) {
            return;
        }

        $this->title = MODULE_SHIPPING_IEMSPICKUP_TEXT_TITLE;
        $this->description = MODULE_SHIPPING_IEMSPICKUP_TEXT_DESCRIPTION;
        if (!(new IemsShippingAddressService())->isPickupConfigured()) {
            $this->description .= '<br><strong>' . MODULE_SHIPPING_IEMSPICKUP_TEXT_MISCONFIGURED . '</strong>';
        }
        $this->sort_order = defined('MODULE_SHIPPING_IEMSPICKUP_SORT_ORDER')
            ? (int)MODULE_SHIPPING_IEMSPICKUP_SORT_ORDER
            : null;
        if ($this->sort_order === null) {
            return;
        }

        $this->icon = '';
        $this->tax_class = '0';
        $this->tax_basis = 'Shipping';
        $this->enabled = MODULE_SHIPPING_IEMSPICKUP_STATUS === 'True';
        $this->update_status();
    }

    public function update_status(): void
    {
        if (!$this->enabled || IS_ADMIN_FLAG === true) {
            return;
        }

        $this->enabled = $this->isCustomerEligible()
            && (new IemsShippingAddressService())->isPickupConfigured();
    }

    public function quote($method = ''): array
    {
        $this->enabled = MODULE_SHIPPING_IEMSPICKUP_STATUS === 'True'
            && $this->isCustomerEligible()
            && (new IemsShippingAddressService())->isPickupConfigured();
        $methods = [];
        if ($this->enabled) {
            $methods[] = [
                'id' => $this->code,
                'title' => MODULE_SHIPPING_IEMSPICKUP_TEXT_WAY,
                'cost' => 0.00,
            ];
        }

        $this->quotes = [
            'id' => $this->code,
            'module' => MODULE_SHIPPING_IEMSPICKUP_TEXT_TITLE,
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
                  WHERE configuration_key = 'MODULE_SHIPPING_IEMSPICKUP_STATUS'"
            );
            $this->_check = $result->RecordCount();
        }

        return $this->_check;
    }

    public function install(): void
    {
        global $db;

        $db->Execute(
            "DELETE FROM " . TABLE_CONFIGURATION . "
              WHERE configuration_key IN (
                'MODULE_SHIPPING_IEMS_PICKUP_STATUS',
                'MODULE_SHIPPING_IEMS_PICKUP_SORT_ORDER'
              )"
        );
        $db->Execute(
            "INSERT INTO " . TABLE_CONFIGURATION . "
                (configuration_title, configuration_key, configuration_value,
                 configuration_description, configuration_group_id, sort_order,
                 set_function, date_added)
             VALUES
                ('Enable Pickup at IEMS Logistics',
                 'MODULE_SHIPPING_IEMSPICKUP_STATUS',
                 'True',
                 'Offer zero-cost pickup to signed-in customers with a valid active IEMS affiliation?',
                 6, 0, 'zen_cfg_select_option(array(''True'', ''False''),', NOW())"
        );
        $db->Execute(
            "INSERT INTO " . TABLE_CONFIGURATION . "
                (configuration_title, configuration_key, configuration_value,
                 configuration_description, configuration_group_id, sort_order, date_added)
             VALUES
                ('Sort Order',
                 'MODULE_SHIPPING_IEMSPICKUP_SORT_ORDER',
                 '0',
                 'Sort order of display.',
                 6, 0, NOW())"
        );
        $db->Execute(
            "INSERT INTO " . TABLE_CONFIGURATION . "
                (configuration_title, configuration_key, configuration_value,
                 configuration_description, configuration_group_id, sort_order, date_added)
             VALUES
                ('Pickup Recipient / Location',
                 'MODULE_SHIPPING_IEMSPICKUP_RECIPIENT',
                 'IEMS Logistics',
                 'Required recipient or location name written to the order delivery address.',
                 6, 10, NOW()),
                ('Pickup Company',
                 'MODULE_SHIPPING_IEMSPICKUP_COMPANY',
                 '',
                 'Optional company written to the order delivery address.',
                 6, 20, NOW()),
                ('Pickup Street Address',
                 'MODULE_SHIPPING_IEMSPICKUP_STREET_ADDRESS',
                 '',
                 'Required street address. State and country are fixed to Indiana, United States.',
                 6, 30, NOW()),
                ('Pickup City',
                 'MODULE_SHIPPING_IEMSPICKUP_CITY',
                 '',
                 'Required city. State and country are fixed to Indiana, United States.',
                 6, 40, NOW()),
                ('Pickup Postcode',
                 'MODULE_SHIPPING_IEMSPICKUP_POSTCODE',
                 '',
                 'Required postcode. State and country are fixed to Indiana, United States.',
                 6, 50, NOW())"
        );
    }

    public function keys(): array
    {
        return [
            'MODULE_SHIPPING_IEMSPICKUP_STATUS',
            'MODULE_SHIPPING_IEMSPICKUP_RECIPIENT',
            'MODULE_SHIPPING_IEMSPICKUP_COMPANY',
            'MODULE_SHIPPING_IEMSPICKUP_STREET_ADDRESS',
            'MODULE_SHIPPING_IEMSPICKUP_CITY',
            'MODULE_SHIPPING_IEMSPICKUP_POSTCODE',
            'MODULE_SHIPPING_IEMSPICKUP_SORT_ORDER',
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

        return (new IemsShippingEligibilityService())->isPickupEligible((int)$customerId);
    }
}
