<?php

use Zencart\PluginSupport\ScriptedInstaller as ScriptedInstallBase;

class ScriptedInstaller extends ScriptedInstallBase
{
    protected function executeInstall()
    {
        $this->addDeliveryEnabledColumn();

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

        $groupId = $this->insertConfigGroup();
        $this->insertLockToggle($groupId);
        $this->registerAdminPages($groupId);

        parent::executeUpgrade($oldVersion);
    }

    protected function executeUninstall()
    {
        if (zen_config('MODULE_SHIPPING_IEMS_PICKUP_STATUS') !== null) {
            require_once $this->pluginDir . '/catalog/includes/modules/shipping/iems_pickup.php';
            (new iems_pickup(uninstalling: true))->remove();
        }
        if (zen_config('MODULE_SHIPPING_IEMS_DELIVERY_STATUS') !== null) {
            require_once $this->pluginDir . '/catalog/includes/modules/shipping/iems_delivery.php';
            (new iems_delivery(uninstalling: true))->remove();
        }

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
