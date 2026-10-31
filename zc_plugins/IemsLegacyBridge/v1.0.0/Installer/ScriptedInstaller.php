<?php

declare(strict_types=1);

use Zencart\PluginSupport\ScriptedInstaller as ScriptedInstallBase;

class ScriptedInstaller extends ScriptedInstallBase
{
    private const CONFIG_GROUP_TITLE = 'IEMS Legacy Bridge';

    protected function executeInstall()
    {
        if (!$this->addDownloadedShipColumn()) {
            return false;
        }
        $groupId = $this->insertConfigGroup();
        if ($groupId <= 0) {
            return false;
        }
        $this->insertConfiguration($groupId);
        $this->registerAdminPages($groupId);

        parent::executeInstall();
        return true;
    }

    protected function executeUpgrade($oldVersion)
    {
        if (!$this->addDownloadedShipColumn()) {
            return false;
        }
        $groupId = $this->insertConfigGroup();
        $this->insertConfiguration($groupId);
        $this->registerAdminPages($groupId);

        parent::executeUpgrade($oldVersion);
        return true;
    }

    /**
     * Removes this plugin's settings and menu entries only. The legacy units,
     * uom and products_price_uom data, and orders.downloaded_ship, are store
     * data and are always kept.
     */
    protected function executeUninstall()
    {
        global $db;

        zen_deregister_admin_pages(['configIemsLegacyBridge', 'shipping_export']);
        $this->executeInstallerSql("DELETE FROM " . TABLE_CONFIGURATION . " WHERE configuration_key LIKE 'IEMS\_LEGACY\_%'");

        $group = $db->Execute(
            "SELECT configuration_group_id FROM " . TABLE_CONFIGURATION_GROUP . "
              WHERE configuration_group_title = '" . self::CONFIG_GROUP_TITLE . "' LIMIT 1"
        );
        if (!$group->EOF) {
            $this->executeInstallerSql(
                "DELETE FROM " . TABLE_CONFIGURATION_GROUP . " WHERE configuration_group_id = " . (int)$group->fields['configuration_group_id']
            );
        }

        parent::executeUninstall();
    }

    // -------------------------------------------------------------------------
    // Helpers
    // -------------------------------------------------------------------------

    /**
     * Shipping Export marks exported orders in orders.downloaded_ship. Every
     * legacy store already has it; if a database somehow does not, add it and
     * mark existing orders as already exported so the first export is not the
     * store's entire order history.
     */
    private function addDownloadedShipColumn(): bool
    {
        global $db;

        $check = $db->Execute("SHOW COLUMNS FROM " . TABLE_ORDERS . " LIKE 'downloaded_ship'");
        if (!$check->EOF) {
            return true;
        }
        return $this->executeInstallerSql(
            "ALTER TABLE " . TABLE_ORDERS . " ADD downloaded_ship ENUM('yes','no') NOT NULL DEFAULT 'no'"
        ) && $this->executeInstallerSql(
            "UPDATE " . TABLE_ORDERS . " SET downloaded_ship = 'yes'"
        );
    }

    private function insertConfigGroup(): int
    {
        global $db;

        $existing = $db->Execute(
            "SELECT configuration_group_id FROM " . TABLE_CONFIGURATION_GROUP . "
              WHERE configuration_group_title = '" . self::CONFIG_GROUP_TITLE . "' LIMIT 1"
        );
        if (!$existing->EOF) {
            return (int)$existing->fields['configuration_group_id'];
        }

        $this->executeInstallerSql(
            "INSERT INTO " . TABLE_CONFIGURATION_GROUP . "
                (configuration_group_title, configuration_group_description, sort_order, visible)
             VALUES
                ('" . self::CONFIG_GROUP_TITLE . "', 'Legacy 1.5.6c store behaviour: units, unit of measure and Shipping Export', 210, 1)"
        );
        $created = $db->Execute(
            "SELECT configuration_group_id FROM " . TABLE_CONFIGURATION_GROUP . "
              WHERE configuration_group_title = '" . self::CONFIG_GROUP_TITLE . "' LIMIT 1"
        );
        return $created->EOF ? 0 : (int)$created->fields['configuration_group_id'];
    }

    /**
     * Per-store defaults, keyed by the storefront template each legacy store
     * database already selects. Every store branch carries all five templates,
     * so the template is what identifies the store. Values only seed new
     * settings; existing values are never overwritten and every value can be
     * changed in Configuration > IEMS Legacy Bridge.
     *
     * LEO and IFD export prefixes are unconfirmed and must be checked against
     * the live shipping_export.php before cutover.
     */
    private const STORE_DEFAULTS = [
        'IEMS' => [
            'order_prefix' => 'IEMS-', 'file_prefix' => 'IEMS-Orders',
            'company' => 'Indianapolis EMS', 'street' => '3930 Georgetown Road', 'city' => 'Indianapolis', 'postcode' => '46254',
        ],
        'external' => [
            'order_prefix' => 'EXT-', 'file_prefix' => 'External-Orders',
            'company' => 'Indianapolis EMS', 'street' => '3930 Georgetown Road', 'city' => 'Indianapolis', 'postcode' => '46254',
        ],
        'wayne' => [
            'order_prefix' => 'WAY-', 'file_prefix' => 'Wayne-Orders',
            'company' => '49 WTFD', 'street' => '700 North High School Road', 'city' => 'Indianapolis', 'postcode' => '46214',
        ],
        'IFD' => [
            'order_prefix' => 'IFD-', 'file_prefix' => 'IFD-Orders',
            'company' => 'Indianapolis Fire Department', 'street' => '955 Fort Wayne Avenue', 'city' => 'Indianapolis', 'postcode' => '46202',
        ],
        'leo' => [
            'order_prefix' => 'LEO-', 'file_prefix' => 'LEO-Orders',
            'company' => 'Indianapolis EMS', 'street' => '3930 Georgetown Road', 'city' => 'Indianapolis', 'postcode' => '46254',
        ],
    ];

    private function storeDefaults(): array
    {
        global $db;

        $template = $db->Execute(
            "SELECT template_dir FROM " . TABLE_TEMPLATE_SELECT . " WHERE template_language = '0' LIMIT 1"
        );
        $dir = $template->EOF ? '' : (string)$template->fields['template_dir'];
        return self::STORE_DEFAULTS[$dir] ?? [
            'order_prefix' => '', 'file_prefix' => 'Orders', 'company' => '', 'street' => '', 'city' => '', 'postcode' => '',
        ];
    }

    private function insertConfiguration(int $groupId): void
    {
        if ($groupId <= 0) {
            return;
        }
        $store = $this->storeDefaults();

        $settings = [
            ['Unit placeholder text', 'IEMS_LEGACY_UNIT_PLACEHOLDER', '00 PLEASE PICK YOUR UNIT',
                'A unit containing this text is rejected at sign-up, address book and checkout (the legacy "please pick your unit" row).', 1],
            ['Hide units containing', 'IEMS_LEGACY_UNIT_EXCLUDE', 'reserve medic',
                'Units whose description contains this text are left out of the storefront dropdown. Blank shows every unit.', 2],
            ['Export order ID prefix', 'IEMS_LEGACY_EXPORT_ORDER_PREFIX', $store['order_prefix'],
                'Prefix written before each order number in the Shipping Export file, e.g. IEMS- or LEO-. Must match what the inventory system expects.', 3],
            ['Export file name prefix', 'IEMS_LEGACY_EXPORT_FILE_PREFIX', $store['file_prefix'],
                'Export file name before the date stamp, e.g. IEMS-Orders gives IEMS-Orders102326-1405.csv.', 4],
            ['Export supplier email', 'IEMS_LEGACY_EXPORT_EMAIL', '',
                'Default address for "save to server and email to supplier". Separate several addresses with commas.', 5],
            ['Export save folder', 'IEMS_LEGACY_EXPORT_DIR', 'images/uploads/',
                'Folder for saved export files: relative to the store root, or an absolute path such as /home/acct/exports/. The legacy default images/uploads/ is web-readable. With an absolute path outside the store, every downloaded export also keeps an archive copy there.', 6],
            ['Address default: company', 'IEMS_LEGACY_DEFAULT_COMPANY', $store['company'],
                'Pre-filled company on a new address book entry.', 10],
            ['Address default: street', 'IEMS_LEGACY_DEFAULT_STREET', $store['street'],
                'Pre-filled street address at sign-up and on a new address book entry.', 11],
            ['Address default: city', 'IEMS_LEGACY_DEFAULT_CITY', $store['city'],
                'Pre-filled city at sign-up and on a new address book entry.', 12],
            ['Address default: post code', 'IEMS_LEGACY_DEFAULT_POSTCODE', $store['postcode'],
                'Pre-filled post code at sign-up and on a new address book entry.', 13],
        ];

        foreach ($settings as [$title, $key, $value, $description, $sort]) {
            $this->executeInstallerSql(
                "INSERT IGNORE INTO " . TABLE_CONFIGURATION . "
                    (configuration_title, configuration_key, configuration_value, configuration_description,
                     configuration_group_id, sort_order, date_added)
                 VALUES
                    ('" . zen_db_input($title) . "', '" . $key . "', '" . zen_db_input($value) . "', '" . zen_db_input($description) . "',
                     " . $groupId . ", " . (int)$sort . ", NOW())"
            );
        }
    }

    private function registerAdminPages(int $groupId): void
    {
        if ($groupId > 0 && !zen_page_key_exists('configIemsLegacyBridge')) {
            zen_register_admin_page(
                'configIemsLegacyBridge',
                'BOX_CONFIGURATION_IEMS_LEGACY_BRIDGE',
                'FILENAME_CONFIGURATION',
                'gID=' . $groupId,
                'configuration',
                'Y'
            );
        }
        // The 1.5.6c Shipping Export install already registered this key under
        // Tools; it is reused as-is when present.
        if (!zen_page_key_exists('shipping_export')) {
            zen_register_admin_page(
                'shipping_export',
                'BOX_TOOLS_SHIPPING_EXPORT',
                'FILENAME_SHIPPING_EXPORT',
                '',
                'tools',
                'Y'
            );
        }
    }
}
