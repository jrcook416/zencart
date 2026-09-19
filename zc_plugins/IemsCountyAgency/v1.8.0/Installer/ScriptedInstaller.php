<?php

use Zencart\PluginSupport\ScriptedInstaller as ScriptedInstallBase;

class ScriptedInstaller extends ScriptedInstallBase
{
    protected function executeInstall()
    {
        $this->addDeliveryEnabledColumn();
        $this->addPaymentModeColumn();
        $this->addUnitAddressColumns();
        $this->addPickupAddressConfiguration();

        $this->executeInstallerSql(
            "CREATE TABLE IF NOT EXISTS `iems_customer_affiliations` (
                `affiliation_ID` int(11) NOT NULL AUTO_INCREMENT,
                `customer_id` int(11) NOT NULL,
                `county_ID` int(11) NOT NULL,
                `agency_ID` int(11) NOT NULL,
                `date_added` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
                `last_modified` datetime NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
                PRIMARY KEY (`affiliation_ID`),
                UNIQUE KEY `idx_iems_customer_affiliations_customer` (`customer_id`),
                KEY `idx_iems_customer_affiliations_county` (`county_ID`),
                KEY `idx_iems_customer_affiliations_agency` (`agency_ID`),
                CONSTRAINT `fk_iems_customer_affiliations_county`
                    FOREIGN KEY (`county_ID`) REFERENCES `iems_counties` (`county_ID`)
                    ON DELETE RESTRICT ON UPDATE CASCADE,
                CONSTRAINT `fk_iems_customer_affiliations_agency`
                    FOREIGN KEY (`agency_ID`) REFERENCES `iems_agencies` (`agency_ID`)
                    ON DELETE RESTRICT ON UPDATE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8"
        );

        $groupId = $this->insertConfigGroup();
        $this->insertLockToggle($groupId);
        $this->registerAdminPages($groupId);

        parent::executeInstall();
        return true;
    }

    protected function executeUpgrade($oldVersion)
    {
        $this->addDeliveryEnabledColumn();
        $this->addPaymentModeColumn();
        $this->addUnitAddressColumns();
        $this->addPickupAddressConfiguration();

        $groupId = $this->insertConfigGroup();
        $this->insertLockToggle($groupId);
        $this->registerAdminPages($groupId);

        parent::executeUpgrade($oldVersion);
    }

    protected function executeUninstall()
    {
        if (zen_config('MODULE_PAYMENT_IEMSINVOICE_STATUS') !== null) {
            require_once $this->pluginDir . '/catalog/includes/modules/payment/iemsinvoice.php';
            (new iemsinvoice(uninstalling: true))->remove();
        }
        if (zen_config('MODULE_PAYMENT_IEMSUNIT_STATUS') !== null) {
            require_once $this->pluginDir . '/catalog/includes/modules/payment/iemsunit.php';
            (new iemsunit(uninstalling: true))->remove();
        }
        if (zen_config('MODULE_SHIPPING_IEMSPICKUP_STATUS') !== null) {
            require_once $this->pluginDir . '/catalog/includes/modules/shipping/iemspickup.php';
            (new iemspickup(uninstalling: true))->remove();
        }
        if (zen_config('MODULE_SHIPPING_IEMSDELIVERY_STATUS') !== null) {
            require_once $this->pluginDir . '/catalog/includes/modules/shipping/iemsdelivery.php';
            (new iemsdelivery(uninstalling: true))->remove();
        }
        $this->removeLegacyShippingConfiguration();

        zen_deregister_admin_pages(['configIemsSettings', 'customersIemsAgencies', 'customersIemsUnits']);

        parent::executeUninstall();
    }

    // -------------------------------------------------------------------------
    // Helpers
    // -------------------------------------------------------------------------

    private function addDeliveryEnabledColumn(): void
    {
        global $db;

        $field = $db->Execute(
            "SHOW COLUMNS FROM `iems_agencies` LIKE 'delivery_enabled'"
        );
        if (
            !$field->EOF
            && strtolower((string)($field->fields['Type'] ?? '')) === 'tinyint(1)'
            && ($field->fields['Null'] ?? null) === 'NO'
            && (string)($field->fields['Default'] ?? '') === '0'
        ) {
            return;
        }

        if ($field->EOF) {
            $this->executeInstallerSql(
                "ALTER TABLE `iems_agencies`
                    ADD COLUMN `delivery_enabled` tinyint(1) NOT NULL DEFAULT 0
                    AFTER `agency_name`"
            );
            return;
        }

        $this->executeInstallerSql(
            "UPDATE `iems_agencies`
                SET `delivery_enabled` = CASE
                    WHEN `delivery_enabled` = 1 THEN 1
                    ELSE 0
                END"
        );
        $this->executeInstallerSql(
            "ALTER TABLE `iems_agencies`
                MODIFY COLUMN `delivery_enabled` tinyint(1) NOT NULL DEFAULT 0"
        );
    }

    private function addPaymentModeColumn(): void
    {
        global $db;

        $field = $db->Execute(
            "SHOW COLUMNS FROM `iems_agencies` LIKE 'payment_mode'"
        );
        if ($field->EOF) {
            $this->executeInstallerSql(
                "ALTER TABLE `iems_agencies`
                    ADD COLUMN `payment_mode` varchar(16) NOT NULL DEFAULT 'invoice'
                    AFTER `delivery_enabled`"
            );
            return;
        }

        $this->executeInstallerSql(
            "UPDATE `iems_agencies`
                SET `payment_mode` = CASE
                    WHEN `payment_mode` IN ('invoice', 'iems_unit') THEN `payment_mode`
                    ELSE 'invoice'
                END"
        );
        if (
            strtolower((string)($field->fields['Type'] ?? '')) === 'varchar(16)'
            && ($field->fields['Null'] ?? null) === 'NO'
            && (string)($field->fields['Default'] ?? '') === 'invoice'
        ) {
            return;
        }

        $this->executeInstallerSql(
            "ALTER TABLE `iems_agencies`
                MODIFY COLUMN `payment_mode` varchar(16) NOT NULL DEFAULT 'invoice'"
        );
    }

    private function addUnitAddressColumns(): void
    {
        $this->normalizeUnitAddressColumn('delivery_street_address', 128, 'unit_name');
        $this->normalizeUnitAddressColumn('delivery_city', 128, 'delivery_street_address');
        $this->normalizeUnitAddressColumn('delivery_postcode', 64, 'delivery_city');

        $this->executeInstallerSql(
            "UPDATE `iems_units`
                SET `delivery_street_address` = NULLIF(TRIM(`delivery_street_address`), ''),
                    `delivery_city` = NULLIF(TRIM(`delivery_city`), ''),
                    `delivery_postcode` = NULLIF(TRIM(`delivery_postcode`), '')"
        );
        $this->executeInstallerSql(
            "UPDATE `iems_units`
                SET `delivery_street_address` = NULL,
                    `delivery_city` = NULL,
                    `delivery_postcode` = NULL
              WHERE (`delivery_street_address` IS NULL)
                 OR (`delivery_city` IS NULL)
                 OR (`delivery_postcode` IS NULL)"
        );
    }

    private function normalizeUnitAddressColumn(string $column, int $length, string $after): void
    {
        global $db;

        $field = $db->Execute(
            "SHOW COLUMNS FROM `iems_units` LIKE '" . $column . "'"
        );
        $expectedType = 'varchar(' . $length . ')';
        if (
            !$field->EOF
            && strtolower((string)($field->fields['Type'] ?? '')) === $expectedType
            && ($field->fields['Null'] ?? null) === 'YES'
            && ($field->fields['Default'] ?? null) === null
        ) {
            return;
        }

        if ($field->EOF) {
            $this->executeInstallerSql(
                "ALTER TABLE `iems_units`
                    ADD COLUMN `" . $column . "` varchar(" . $length . ") NULL DEFAULT NULL
                    AFTER `" . $after . "`"
            );
            return;
        }

        $this->executeInstallerSql(
            "UPDATE `iems_units`
                SET `" . $column . "` = CASE
                    WHEN CHAR_LENGTH(TRIM(`" . $column . "`)) > " . $length . " THEN NULL
                    ELSE NULLIF(TRIM(`" . $column . "`), '')
                END"
        );
        $this->executeInstallerSql(
            "ALTER TABLE `iems_units`
                MODIFY COLUMN `" . $column . "` varchar(" . $length . ") NULL DEFAULT NULL"
        );
    }

    private function removeLegacyShippingConfiguration(): void
    {
        $this->executeInstallerSql(
            "DELETE FROM " . TABLE_CONFIGURATION . "
              WHERE configuration_key IN (
                'MODULE_SHIPPING_IEMS_PICKUP_STATUS',
                'MODULE_SHIPPING_IEMS_PICKUP_SORT_ORDER',
                'MODULE_SHIPPING_IEMS_DELIVERY_STATUS',
                'MODULE_SHIPPING_IEMS_DELIVERY_SORT_ORDER'
              )"
        );
    }

    private function addPickupAddressConfiguration(): void
    {
        $settings = [
            [
                'Pickup Recipient / Location',
                'MODULE_SHIPPING_IEMSPICKUP_RECIPIENT',
                'IEMS Logistics',
                'Required recipient or location name written to the order delivery address.',
                10,
            ],
            [
                'Pickup Company',
                'MODULE_SHIPPING_IEMSPICKUP_COMPANY',
                '',
                'Optional company written to the order delivery address.',
                20,
            ],
            [
                'Pickup Street Address',
                'MODULE_SHIPPING_IEMSPICKUP_STREET_ADDRESS',
                '',
                'Required street address. State and country are fixed to Indiana, United States.',
                30,
            ],
            [
                'Pickup City',
                'MODULE_SHIPPING_IEMSPICKUP_CITY',
                '',
                'Required city. State and country are fixed to Indiana, United States.',
                40,
            ],
            [
                'Pickup Postcode',
                'MODULE_SHIPPING_IEMSPICKUP_POSTCODE',
                '',
                'Required postcode. State and country are fixed to Indiana, United States.',
                50,
            ],
        ];

        foreach ($settings as [$title, $key, $value, $description, $sortOrder]) {
            $this->executeInstallerSql(
                "INSERT IGNORE INTO " . TABLE_CONFIGURATION . "
                    (configuration_title, configuration_key, configuration_value,
                     configuration_description, configuration_group_id, sort_order, date_added)
                 SELECT
                    '" . $title . "', '" . $key . "', '" . $value . "',
                    '" . $description . "', 6, " . $sortOrder . ", NOW()
                  FROM " . TABLE_CONFIGURATION . "
                 WHERE configuration_key = 'MODULE_SHIPPING_IEMSPICKUP_STATUS'
                 LIMIT 1"
            );
        }
    }

    private function insertConfigGroup(): int
    {
        global $db;

        $this->executeInstallerSql(
            "INSERT IGNORE INTO configuration_group
                (configuration_group_title, configuration_group_description, sort_order, visible)
             VALUES
                ('IEMS Settings', 'Configuration for Indianapolis EMS custom features', 200, 1)"
        );

        $result  = $db->Execute(
            "SELECT configuration_group_id
               FROM configuration_group
              WHERE configuration_group_title = 'IEMS Settings'
              LIMIT 1"
        );

        return $result->EOF ? 0 : (int)$result->fields['configuration_group_id'];
    }

    private function insertLockToggle(int $groupId): void
    {
        if ($groupId <= 0) {
            return;
        }

        $this->executeInstallerSql(
            "INSERT IGNORE INTO configuration
                (configuration_title, configuration_key, configuration_value,
                 configuration_description, configuration_group_id,
                 sort_order, date_added, set_function)
             VALUES (
                'Lock Account Edit Fields',
                'IEMS_ACCOUNT_EDIT_LOCK_ENABLED',
                'true',
                'When enabled, customers may only edit their phone number on the account edit page. All other fields display as read-only.',
                " . $groupId . ",
                1,
                NOW(),
                'zen_cfg_select_option(array(''true'', ''false''),'
             )"
        );
    }

    private function registerAdminPages(int $groupId): void
    {
        if ($groupId <= 0) {
            return;
        }

        if (!zen_page_key_exists('configIemsSettings')) {
            zen_register_admin_page(
                'configIemsSettings',
                'BOX_CONFIGURATION_IEMS_SETTINGS',
                'FILENAME_CONFIGURATION',
                'gID=' . $groupId,
                'configuration',
                'Y'
            );
        }

        if (!zen_page_key_exists('customersIemsAgencies')) {
            zen_register_admin_page(
                'customersIemsAgencies',
                'BOX_CUSTOMERS_IEMS_AGENCIES',
                'FILENAME_IEMS_AGENCIES',
                '',
                'customers',
                'Y'
            );
        }

        if (!zen_page_key_exists('customersIemsUnits')) {
            zen_register_admin_page(
                'customersIemsUnits',
                'BOX_CUSTOMERS_IEMS_UNITS',
                'FILENAME_IEMS_UNITS',
                '',
                'customers',
                'Y'
            );
        }
    }
}
