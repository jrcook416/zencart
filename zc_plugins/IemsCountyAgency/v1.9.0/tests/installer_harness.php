<?php

declare(strict_types=1);

namespace Zencart\PluginSupport {
    class ScriptedInstaller
    {
        protected string $pluginDir;
        protected \IemsInstallerErrors $errorContainer;

        public function __construct(string $pluginDir)
        {
            $this->pluginDir = $pluginDir;
            $this->errorContainer = new \IemsInstallerErrors();
        }

        protected function executeInstall()
        {
            return true;
        }

        protected function executeUpgrade($oldVersion)
        {
        }

        protected function executeUninstall()
        {
        }

        protected function executeInstallerSql(string $sql): bool
        {
            global $db;
            if ($db->failSqlContaining !== null && str_contains($sql, $db->failSqlContaining)) {
                $db->errors[] = 'Simulated migration SQL failure';
                return false;
            }
            $db->apply($sql);
            return true;
        }
    }
}

namespace {
    define('TABLE_CONFIGURATION', 'configuration');
    define('PLUGIN_INSTALL_SQL_FAILURE', 'SQL failure');

    final class IemsInstallerErrors
    {
        public function addError(int $number, string $message, bool $fatal, string $type): void
        {
            global $db;
            $db->errors[] = $message;
        }
    }

    final class IemsInstallerFakeResult
    {
        public bool $EOF;
        public array $fields;

        /** @param array<string, mixed>|null $row */
        public function __construct(?array $row)
        {
            $this->EOF = $row === null;
            $this->fields = $row ?? [];
        }
    }

    final class IemsInstallerFakeDb
    {
        /** @var array<string, array{Type: string, Null: string, Default: mixed}> */
        public array $agencyFields = [
            'delivery_enabled' => ['Type' => 'varchar(4)', 'Null' => 'YES', 'Default' => null],
            'payment_mode' => ['Type' => 'varchar(255)', 'Null' => 'YES', 'Default' => null],
        ];

        /** @var string[] */
        public array $paymentModes = ['invoice', 'invalid', 'iems_unit'];

        /** @var array<string, array{Type: string, Null: string, Default: mixed}> */
        public array $unitFields = [
            'delivery_city' => ['Type' => 'varchar(255)', 'Null' => 'NO', 'Default' => ''],
        ];

        /** @var string[] */
        public array $executed = [];

        public array $errors = [];
        public ?string $failSqlContaining = null;
        public array $configuration = [];
        public array $agencies = [
            ['county_number' => '049'],
            ['county_number' => '003'],
            ['county_number' => '49'],
            ['county_number' => '92'],
        ];
        public array $mileage = [null, null];

        public function Execute(string $sql): IemsInstallerFakeResult
        {
            if (str_starts_with(ltrim($sql), 'DELETE FROM')) {
                $this->apply($sql);
                return new IemsInstallerFakeResult(null);
            }
            if (preg_match("/SHOW COLUMNS FROM `iems_agencies` LIKE '([^']+)'/", $sql, $matches) === 1) {
                return $this->fieldResult($matches[1], $this->agencyFields);
            }
            if (preg_match("/SHOW COLUMNS FROM `iems_units` LIKE '([^']+)'/", $sql, $matches) === 1) {
                return $this->fieldResult($matches[1], $this->unitFields);
            }
            if (str_contains($sql, 'SELECT configuration_group_id')) {
                return new IemsInstallerFakeResult(['configuration_group_id' => 9]);
            }
            if (str_contains($sql, 'SELECT a.agency_ID')) {
                foreach ([
                    'c.county_number IS NULL',
                    'CHAR_LENGTH(c.county_number) NOT BETWEEN 1 AND 3',
                    "c.county_number REGEXP '[^0-9]'",
                    'CAST(c.county_number AS UNSIGNED) NOT BETWEEN 1 AND 92',
                ] as $guard) {
                    if (!str_contains($sql, $guard)) {
                        throw new RuntimeException('Migration preflight is missing county guard: ' . $guard);
                    }
                }
                foreach ($this->agencies as $id => $agency) {
                    $code = $agency['county_number'];
                    if (
                        !is_string($code)
                        || preg_match('/^[0-9]{1,3}$/D', $code) !== 1
                        || (int)$code < 1
                        || (int)$code > 92
                    ) {
                        return new IemsInstallerFakeResult(['agency_ID' => $id + 1]);
                    }
                }
                return new IemsInstallerFakeResult(null);
            }

            throw new RuntimeException('Unexpected direct installer query: ' . $sql);
        }

        public function apply(string $sql): void
        {
            $this->executed[] = $sql;
            if (str_contains($sql, 'SET a.shipping_category = CASE')) {
                if (!str_contains($sql, "WHEN c.county_number IN ('49', '049') THEN 'marion'")) {
                    throw new RuntimeException('Unexpected county migration predicate.');
                }
                foreach ($this->agencies as &$agency) {
                    $agency['shipping_category'] = in_array($agency['county_number'], ['49', '049'], true)
                        ? 'marion'
                        : 'out_of_county';
                }
                unset($agency);
            }
            if (preg_match(
                "/SELECT '([^']+)', '(MODULE_SHIPPING_IEMSDELIVERY_(?:FLAT_RATE|PER_MILE_RATE))', '([^']+)'/",
                $sql,
                $setting
            ) === 1) {
                if (!str_contains($sql, 'AND NOT EXISTS')) {
                    throw new RuntimeException('Rate backfill must guard existing keys without relying on a unique index.');
                }
                if (isset($this->configuration['MODULE_SHIPPING_IEMSDELIVERY_STATUS'])) {
                    $this->configuration[$setting[2]] ??= $setting[3];
                }
            }
            if (str_starts_with(ltrim($sql), 'DELETE FROM')) {
                preg_match_all("/'(MODULE_[A-Z_]+)'/", $sql, $keys);
                foreach ($keys[1] as $key) {
                    unset($this->configuration[$key]);
                }
            }
            if (str_contains($sql, 'SET `payment_mode` = CASE')) {
                $this->paymentModes = array_map(
                    static fn (string $mode): string => in_array($mode, ['invoice', 'iems_unit'], true)
                        ? $mode
                        : 'invoice',
                    $this->paymentModes
                );
            }
            if (preg_match('/(?:ADD|MODIFY) COLUMN `([^`]+)` ([a-z]+\\([^)]*\\)) (NOT NULL|NULL) DEFAULT ([^\\s]+)/i', $sql, $matches) !== 1) {
                return;
            }

            $fields = str_contains($sql, '`iems_agencies`')
                ? $this->agencyFields
                : $this->unitFields;
            $fields[$matches[1]] = [
                'Type' => strtolower($matches[2]),
                'Null' => $matches[3] === 'NULL' ? 'YES' : 'NO',
                'Default' => strtolower($matches[4]) === 'null' ? null : trim($matches[4], "'"),
            ];
            if (str_contains($sql, '`iems_agencies`')) {
                $this->agencyFields = $fields;
            } else {
                $this->unitFields = $fields;
            }
        }

        /**
         * @param array<string, array{Type: string, Null: string, Default: mixed}> $fields
         */
        private function fieldResult(string $column, array $fields): IemsInstallerFakeResult
        {
            if (!isset($fields[$column])) {
                return new IemsInstallerFakeResult(null);
            }

            return new IemsInstallerFakeResult([
                'Field' => $column,
                ...$fields[$column],
            ]);
        }
    }

    function zen_page_key_exists(string $key): bool
    {
        return true;
    }

    function zen_register_admin_page(...$arguments): void
    {
    }

    function zen_deregister_admin_pages(array $pages): void
    {
    }

    function zen_config(string $key): mixed
    {
        global $db;
        return $db->configuration[$key] ?? null;
    }

    function zen_define_default(string $key, mixed $value): void
    {
        if (!defined($key)) {
            define($key, $value);
        }
    }

    $assert = static function (bool $condition, string $message): void {
        if (!$condition) {
            throw new RuntimeException($message);
        }
    };

    $pluginRoot = dirname(__DIR__);
    require $pluginRoot . '/Installer/ScriptedInstaller.php';

    $db = new IemsInstallerFakeDb();
    $installer = new \ScriptedInstaller($pluginRoot);
    $upgrade = new ReflectionMethod($installer, 'executeUpgrade');
    $upgrade->invoke($installer, 'v1.7.0');

    foreach (
        [
            'delivery_street_address' => ['varchar(128)', 'YES', null],
            'delivery_city' => ['varchar(128)', 'YES', null],
            'delivery_postcode' => ['varchar(64)', 'YES', null],
            'one_way_miles' => ['decimal(7,2)', 'YES', null],
        ] as $column => [$type, $null, $default]
    ) {
        $assert(
            $db->unitFields[$column] === ['Type' => $type, 'Null' => $null, 'Default' => $default],
            'Installer did not normalize ' . $column . '.'
        );
    }
    $assert(
        $db->agencyFields['delivery_enabled'] === [
            'Type' => 'tinyint(1)',
            'Null' => 'NO',
            'Default' => '0',
        ],
        'Installer did not preserve v1.6 delivery-field normalization.'
    );
    $assert(
        $db->agencyFields['payment_mode'] === [
            'Type' => 'varchar(16)',
            'Null' => 'NO',
            'Default' => 'invoice',
        ],
        'Installer did not normalize the payment-mode field.'
    );
    $assert(
        $db->paymentModes === ['invoice', 'invoice', 'iems_unit'],
        'Installer did not preserve valid modes and default invalid existing values to invoice.'
    );
    $assert(
        count(array_filter(
            $db->executed,
            static fn (string $sql): bool => str_contains($sql, 'ALTER TABLE')
        )) === 8,
        'Legacy normalization and staged category migration should require exactly eight first-run ALTER statements.'
    );
    $assert(
        count(array_filter(
            $db->executed,
            static fn (string $sql): bool => str_contains($sql, 'NULLIF(TRIM(`delivery_street_address`)')
        )) === 1,
        'Installer must normalize blank address values before enforcing all-or-none readiness.'
    );
    $assert(
        $db->agencyFields['shipping_category'] === [
            'Type' => "enum('marion','iems','out_of_county')",
            'Null' => 'NO',
            'Default' => 'marion',
        ],
        'Shipping category must use the exact non-null enum and marion default.'
    );
    $assert(
        array_column($db->agencies, 'shipping_category') === ['marion', 'out_of_county', 'marion', 'out_of_county'],
        'Both legacy 49 and padded 049 county codes must migrate to marion.'
    );
    $assert($db->configuration === [], 'Uninstalled delivery must not gain configuration.');

    $db->executed = [];
    $db->agencies[0]['shipping_category'] = 'iems';
    $db->agencies[1]['shipping_category'] = 'marion';
    $db->mileage = ['0.00', '99999.99'];
    $savedAgencies = $db->agencies;
    $upgrade->invoke($installer, 'v1.8.0');
    $assert(
        array_filter(
            $db->executed,
            static fn (string $sql): bool => str_contains($sql, 'ALTER TABLE')
        ) === [],
        'A second installer run must not alter already-normalized definitions.'
    );
    $assert($db->agencies === $savedAgencies, 'Repeat upgrades must retain administrator-set categories.');
    $assert($db->mileage === ['0.00', '99999.99'], 'Repeat upgrades must retain valid mileage including range endpoints.');
    $assert(
        !array_filter($db->executed, static fn (string $sql): bool => str_contains($sql, 'SET a.shipping_category')),
        'County backfill must run only when the category column is first added.'
    );

    $db = new IemsInstallerFakeDb();
    unset($db->agencyFields['payment_mode']);
    $installer = new \ScriptedInstaller($pluginRoot);
    $upgrade = new ReflectionMethod($installer, 'executeUpgrade');
    $upgrade->invoke($installer, 'v1.7.0');
    $assert(
        $db->agencyFields['payment_mode'] === [
            'Type' => 'varchar(16)',
            'Null' => 'NO',
            'Default' => 'invoice',
        ],
        'A missing payment-mode field must be added with the invoice default.'
    );

    $install = new ReflectionMethod($installer, 'executeInstall');
    $db = new IemsInstallerFakeDb();
    $db->agencyFields = [];
    $db->unitFields = [];
    $assert($install->invoke($installer) === true, 'Fresh plugin installation should succeed on the foundation schema.');
    $assert(isset($db->agencyFields['shipping_category'], $db->unitFields['one_way_miles']), 'Install must add both shipping fields.');
    $assert($db->mileage === [null, null], 'Existing units start with unknown mileage, not zero.');
    $assert(
        count(array_filter($db->executed, static fn (string $sql): bool => str_contains($sql, 'CREATE TABLE IF NOT EXISTS'))) === 1,
        'Fresh installation must retain affiliation-table creation.'
    );

    $db->configuration = [
        'MODULE_SHIPPING_IEMSDELIVERY_STATUS' => 'False',
        'MODULE_SHIPPING_IEMSDELIVERY_SORT_ORDER' => '37',
        'MODULE_SHIPPING_IEMSDELIVERY_FLAT_RATE' => '44.95',
    ];
    $upgrade->invoke($installer, 'v1.8.0');
    $assert(
        $db->configuration === [
            'MODULE_SHIPPING_IEMSDELIVERY_STATUS' => 'False',
            'MODULE_SHIPPING_IEMSDELIVERY_SORT_ORDER' => '37',
            'MODULE_SHIPPING_IEMSDELIVERY_FLAT_RATE' => '44.95',
            'MODULE_SHIPPING_IEMSDELIVERY_PER_MILE_RATE' => '0.55',
        ],
        'Backfill must add only missing rates, including when the installed module is disabled.'
    );
    $db->configuration['MODULE_SHIPPING_IEMSDELIVERY_PER_MILE_RATE'] = '1.23';
    $upgrade->invoke($installer, 'v1.9.0');
    $assert($db->configuration['MODULE_SHIPPING_IEMSDELIVERY_PER_MILE_RATE'] === '1.23', 'Repeat upgrade must retain custom per-mile rates.');
    unset($db->configuration['MODULE_SHIPPING_IEMSDELIVERY_FLAT_RATE']);
    $upgrade->invoke($installer, 'v1.9.0');
    $assert($db->configuration['MODULE_SHIPPING_IEMSDELIVERY_FLAT_RATE'] === '35.00', 'Missing flat rate must receive the documented default.');

    $validAgencyFields = $db->agencyFields;
    $validUnitFields = $db->unitFields;
    foreach ([
        ['agencyFields', 'shipping_category', 'Field', 'shippingXcategory'],
        ['agencyFields', 'shipping_category', 'Type', 'varchar(16)'],
        ['agencyFields', 'shipping_category', 'Type', "enum('marion','iems','out_of_county','other')"],
        ['agencyFields', 'shipping_category', 'Type', "enum('MARION','IEMS','OUT_OF_COUNTY')"],
        ['agencyFields', 'shipping_category', 'Null', 'YES'],
        ['agencyFields', 'shipping_category', 'Default', 'iems'],
        ['agencyFields', 'shipping_category', 'Extra', 'STORED GENERATED'],
        ['unitFields', 'one_way_miles', 'Field', 'oneXwayXmiles'],
        ['unitFields', 'one_way_miles', 'Type', 'decimal(8,2)'],
        ['unitFields', 'one_way_miles', 'Type', 'decimal(7,3)'],
        ['unitFields', 'one_way_miles', 'Null', 'NO'],
        ['unitFields', 'one_way_miles', 'Default', '0.00'],
        ['unitFields', 'one_way_miles', 'Extra', 'VIRTUAL GENERATED'],
    ] as [$property, $column, $attribute, $value]) {
        $db->agencyFields = $validAgencyFields;
        $db->unitFields = $validUnitFields;
        $db->{$property}[$column][$attribute] = $value;
        $db->executed = [];
        $db->errors = [];
        $assert($upgrade->invoke($installer, 'v1.8.0') === false, 'Malformed new schema must reject upgrade.');
        $assert($install->invoke($installer) === false, 'Malformed new schema must reject install.');
        $assert(count($db->errors) === 2, 'Malformed schema must surface a Plugin Manager error.');
        $assert($db->executed === [], 'Malformed shipping schema must fail before modifying retained data.');
    }

    $db->agencyFields = $validAgencyFields;
    $db->unitFields = $validUnitFields;
    $db->executed = [];
    $db->agencies[0]['shipping_category'] = 'iems';
    $db->mileage = ['0.00', '99999.99'];
    $savedAgencies = $db->agencies;
    $root = dirname(__DIR__, 4);
    require_once $root . '/includes/classes/traits/NotifierManager.php';
    require_once $root . '/includes/classes/traits/ObserverManager.php';
    require_once $root . '/includes/classes/class.base.php';
    require_once $root . '/includes/classes/ZenShipping.php';
    $uninstall = new ReflectionMethod($installer, 'executeUninstall');
    $uninstall->invoke($installer);
    $assert($db->agencyFields === $validAgencyFields && $db->unitFields === $validUnitFields, 'Uninstall must preserve both schema definitions.');
    $assert($db->agencies === $savedAgencies && $db->mileage === ['0.00', '99999.99'], 'Uninstall must preserve categories and mileage.');
    $assert($db->configuration === [], 'Native delivery removal must clean up rate and module configuration.');
    $assert(
        !array_filter($db->executed, static fn (string $sql): bool => str_contains($sql, 'ALTER TABLE') || str_contains($sql, 'DROP TABLE')),
        'Uninstall must never remove retained IEMS schema.'
    );
    $uninstall->invoke($installer);
    $assert($install->invoke($installer) === true, 'Reinstall should succeed with retained fields.');
    $assert($db->agencies === $savedAgencies, 'Reinstall must not rerun the one-time county category migration.');
    $assert($db->configuration === [], 'Reinstall must not reinstall a removed delivery module.');

    foreach ([null, '', ' ', '49 ', "49\n", '0', '000', '93', '097', '0049', '999', '-49', '+49', '4.9', '4e1', '４９', 'xx'] as $invalidCode) {
        $db = new IemsInstallerFakeDb();
        $db->agencies[0]['county_number'] = $invalidCode;
        $assert($upgrade->invoke($installer, 'v1.8.0') === false, 'Invalid county must abort first category migration.');
        $assert($install->invoke($installer) === false, 'Invalid county must abort first category installation.');
        $assert(count($db->errors) === 2, 'County preflight must report a Plugin Manager error.');
        $assert($db->executed === [], 'Invalid/missing county must fail before adding or changing any schema/data.');
        $db->agencyFields = $validAgencyFields;
        $db->unitFields = $validUnitFields;
        $db->agencies[0]['shipping_category'] = 'iems';
        $assert($upgrade->invoke($installer, 'v1.9.0') === false, 'Invalid county must also abort upgrade with retained schema.');
        $assert($db->executed === [], 'County preflight must protect retained schema and settings.');
        $assert($db->agencies[0]['shipping_category'] === 'iems', 'Failed preflight must preserve a retained administrator category.');
    }
    foreach (['1', '01', '001', '3', '03', '003', '49', '049', '92', '092'] as $validCode) {
        $db = new IemsInstallerFakeDb();
        $db->agencies[0]['county_number'] = $validCode;
        $assert($install->invoke($installer) === true, 'A valid numeric county code must support fresh installation.');
        $assert(
            $db->agencies[0]['shipping_category'] === ((int)$validCode === 49 ? 'marion' : 'out_of_county'),
            'Migration must classify compatible county-code widths consistently.'
        );
        $assert($db->agencies[0]['county_number'] === $validCode, 'Category migration must not rewrite stored county codes.');
    }

    $db = new IemsInstallerFakeDb();
    $db->failSqlContaining = 'SET a.shipping_category = CASE';
    $assert($upgrade->invoke($installer, 'v1.8.0') === false, 'A failed category migration must stop the upgrade.');
    $assert(
        $db->agencyFields['shipping_category']['Default'] === 'out_of_county',
        'A partially migrated category column must not expose the valid production default.'
    );
    $db->failSqlContaining = null;
    $db->executed = [];
    $assert($upgrade->invoke($installer, 'v1.8.0') === false, 'Interrupted category migration must require deliberate repair.');
    $assert($db->executed === [], 'Retrying an interrupted migration must not overwrite retained category data.');

    echo "IEMS v1.9 installer create/upgrade/idempotence/retention/configuration/malformed-schema harness passed.\n";
}
