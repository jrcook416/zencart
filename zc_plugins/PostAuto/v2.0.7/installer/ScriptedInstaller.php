<?php
use Zencart\PluginSupport\ScriptedInstaller as ScriptedInstallBase;

class ScriptedInstaller extends ScriptedInstallBase
{
    protected array $data_country = ['at', 'au', 'ch', 'de', 'es', 'fr', 'it', 'jp', 'us'];

    protected function tableconstants()
    {
        // Create a table constant for each new table
        foreach ($this->data_country as $cod) {
            if (!defined('TABLE_ZONES_TO_POST_CODE_' . strtoupper($cod))) {
                define('TABLE_ZONES_TO_POST_CODE_' . strtoupper($cod), DB_PREFIX . 'zones_to_post_code_' . $cod);
            }
        }
        unset($cod);
    }

    protected function executeInstall()
    {
        zen_set_time_limit(240);

        global $sniffer;

        // This can be commented out after first installation or if you have never installed a non-encapsulated version of this plugin.
        $this->removeOldNonencapsulatedPostauto();

        $this->tableconstants(); // Create each constant of the database tables for the post codes data.

        foreach ($this->data_country as $v) {
            if ($sniffer->table_exists(constant('TABLE_ZONES_TO_POST_CODE_' . strtoupper($v)), 'zone_country_id') !== true) {
                @$result = include 'sql/install/zones_to_post_code_' . $v . '.php';
            }
        }
        unset($v);
    }

    // Note: This (https://github.com/zencart/zencart/pull/6498) Zen Cart PR must
    // be present in the base code or a PHP Fatal error is generated due to the
    // function signature difference.
    //
    protected function executeUpgrade($oldVersion)
    {
        if ($this->version < '2.0.1') {
            $this->tableconstants();
            include_once 'sql/upgrade/zones_to_post_code_jp_update2025-03.php';
        }
    }

    protected function executeUninstall()
    {
        $this->tableconstants();
        // To keep postal codes database for a country from being deleted when uninstalling, comment the corresponding line
        $this->executeInstallerSql("DROP TABLE IF EXISTS " . TABLE_ZONES_TO_POST_CODE_AT);
        $this->executeInstallerSql("DROP TABLE IF EXISTS " . TABLE_ZONES_TO_POST_CODE_AU);
        $this->executeInstallerSql("DROP TABLE IF EXISTS " . TABLE_ZONES_TO_POST_CODE_CH);
        $this->executeInstallerSql("DROP TABLE IF EXISTS " . TABLE_ZONES_TO_POST_CODE_DE);
        $this->executeInstallerSql("DROP TABLE IF EXISTS " . TABLE_ZONES_TO_POST_CODE_ES);
        $this->executeInstallerSql("DROP TABLE IF EXISTS " . TABLE_ZONES_TO_POST_CODE_FR);
        $this->executeInstallerSql("DROP TABLE IF EXISTS " . TABLE_ZONES_TO_POST_CODE_IT);
        $this->executeInstallerSql("DROP TABLE IF EXISTS " . TABLE_ZONES_TO_POST_CODE_JP);
        $this->executeInstallerSql("DROP TABLE IF EXISTS " . TABLE_ZONES_TO_POST_CODE_US);

    }

    protected function removeOldNonencapsulatedPostauto(): void
    {
        $filesToDelete = [
            DIR_FS_CATALOG . 'includes/classes/ajax/zcAjaxPostcodeQuery.php',
            DIR_FS_CATALOG . 'includes/modules/pages/address_book_process/jscript_postcode.js',
            DIR_FS_CATALOG . 'includes/modules/pages/checkout_shipping_address/jscript_postcode.js',
            DIR_FS_CATALOG . 'includes/modules/pages/login/jscript_postcode.js',
            DIR_FS_CATALOG . 'includes/templates/responsive_classic/css/stylesheet_jquery.powertip.min.css',
            DIR_FS_CATALOG . 'includes/templates/responsive_classic/jscript/jquery.powertip.min.js',
        ];
        $isOld = false;

        foreach ($filesToDelete as $nextFile) {
            if (file_exists($nextFile)) {
                $result = unlink($nextFile);
                if (!$result && file_exists($nextFile)) {
                    $this->errorContainer->addError(
                        0,
                        sprintf(ERROR_UNABLE_TO_DELETE_FILE, $nextFile),
                        false,
                        // this str_replace has to do DIR_FS_ADMIN before CATALOG because catalog is contained within admin, so results are wrong.
                        // also, '[admin_directory]' is used to obfuscate the admin dir name, in case the user copy/pastes output to a public forum for help.
                        sprintf(ERROR_UNABLE_TO_DELETE_FILE, str_replace([DIR_FS_ADMIN, DIR_FS_CATALOG], ['[admin_directory]/', ''], $nextFile))
                    );
                } else {
                    $isOld = true;
                }

            }
        }
        if ($isOld !== true) {
            $this->executeUninstall();
        }
    }
}
