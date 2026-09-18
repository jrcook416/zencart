<?php

declare(strict_types=1);

namespace Zencart\PluginSupport {
    class ScriptedInstaller
    {
        protected string $pluginDir;

        public function __construct(string $pluginDir)
        {
            $this->pluginDir = $pluginDir;
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

        protected function executeInstallerSql(string $sql): void
        {
            global $db;
            $db->apply($sql);
        }
    }
}

namespace {
    define('TABLE_CONFIGURATION', 'configuration');

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
        ];

        /** @var array<string, array{Type: string, Null: string, Default: mixed}> */
        public array $unitFields = [
            'delivery_city' => ['Type' => 'varchar(255)', 'Null' => 'NO', 'Default' => ''],
        ];

        /** @var string[] */
        public array $executed = [];

        public function Execute(string $sql): IemsInstallerFakeResult
        {
            if (preg_match("/SHOW COLUMNS FROM `iems_agencies` LIKE '([^']+)'/", $sql, $matches) === 1) {
                return $this->fieldResult($matches[1], $this->agencyFields);
            }
            if (preg_match("/SHOW COLUMNS FROM `iems_units` LIKE '([^']+)'/", $sql, $matches) === 1) {
                return $this->fieldResult($matches[1], $this->unitFields);
            }
            if (str_contains($sql, 'SELECT configuration_group_id')) {
                return new IemsInstallerFakeResult(['configuration_group_id' => 9]);
            }

            throw new RuntimeException('Unexpected direct installer query: ' . $sql);
        }

        public function apply(string $sql): void
        {
            $this->executed[] = $sql;
            if (preg_match('/(?:ADD|MODIFY) COLUMN `([^`]+)` ([a-z]+\\(\\d+\\)) (NOT NULL|NULL) DEFAULT ([^\\s]+)/i', $sql, $matches) !== 1) {
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
        return null;
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
    $upgrade->invoke($installer, 'v1.6.0');

    foreach (
        [
            'delivery_street_address' => ['varchar(128)', 'YES', null],
            'delivery_city' => ['varchar(128)', 'YES', null],
            'delivery_postcode' => ['varchar(64)', 'YES', null],
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
        count(array_filter(
            $db->executed,
            static fn (string $sql): bool => str_contains($sql, 'ALTER TABLE')
        )) === 4,
        'Malformed and missing definitions should require exactly four first-run ALTER statements.'
    );
    $assert(
        count(array_filter(
            $db->executed,
            static fn (string $sql): bool => str_contains($sql, 'NULLIF(TRIM(`delivery_street_address`)')
        )) === 1,
        'Installer must normalize blank address values before enforcing all-or-none readiness.'
    );

    $db->executed = [];
    $upgrade->invoke($installer, 'v1.7.0');
    $assert(
        array_filter(
            $db->executed,
            static fn (string $sql): bool => str_contains($sql, 'ALTER TABLE')
        ) === [],
        'A second installer run must not alter already-normalized definitions.'
    );

    echo "IEMS installer idempotence/malformed-schema harness passed.\n";
}
