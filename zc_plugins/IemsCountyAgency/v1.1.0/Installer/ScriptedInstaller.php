<?php

use Zencart\PluginSupport\ScriptedInstaller as ScriptedInstallBase;

class ScriptedInstaller extends ScriptedInstallBase
{
    protected function executeInstall()
    {
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
        $this->registerAdminPage($groupId);

        parent::executeInstall();
        return true;
    }

    protected function executeUpgrade($oldVersion)
    {
        $groupId = $this->insertConfigGroup();
        $this->insertLockToggle($groupId);
        $this->registerAdminPage($groupId);

        parent::executeUpgrade($oldVersion);
    }

    protected function executeUninstall()
    {
        zen_deregister_admin_pages(['configIemsSettings']);

        $this->executeInstallerSql("DELETE FROM configuration WHERE configuration_key = 'IEMS_ACCOUNT_EDIT_LOCK_ENABLED'");

        // Remove the IEMS config group only if it is now empty.
        $this->executeInstallerSql(
            "DELETE cg
               FROM configuration_group cg
               LEFT JOIN configuration c ON c.configuration_group_id = cg.configuration_group_id
              WHERE cg.configuration_group_title = 'IEMS Settings'
                AND c.configuration_id IS NULL"
        );

        $this->executeInstallerSql("DROP TABLE IF EXISTS `iems_customer_affiliations`");

        parent::executeUninstall();
    }

    // -------------------------------------------------------------------------
    // Helpers
    // -------------------------------------------------------------------------

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

    private function registerAdminPage(int $groupId): void
    {
        if ($groupId <= 0) {
            return;
        }

        // Remove any stale registration before re-inserting (handles upgrades cleanly).
        zen_deregister_admin_pages(['configIemsSettings']);

        zen_register_admin_page(
            'configIemsSettings',
            'BOX_CONFIGURATION_IEMS_SETTINGS',
            'FILENAME_CONFIGURATION',
            'gID=' . $groupId,
            'configuration',
            'Y'
        );
    }
}
