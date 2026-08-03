<?php
/**
 * Tests the Edit Orders zen_config compatibility helpers.
 */

declare(strict_types=1);

namespace Tests\Unit\testsPlugins;

use ReflectionClass;
use Tests\Support\zcUnitTestCase;

class EditOrdersZenConfigTest extends zcUnitTestCase
{
    /**
     * @runInSeparateProcess
     */
    public function testAdminHelperReturnsConstantsAndExactDefaults(): void
    {
        require DIR_FS_CATALOG . 'zc_plugins/EditOrders/v5.0.4/admin/includes/functions/extra_functions/edit_orders_functions.php';

        define('EDIT_ORDERS_ZEN_CONFIG_TEST_VALUE', 'configured');

        $this->assertSame('configured', zen_config('EDIT_ORDERS_ZEN_CONFIG_TEST_VALUE', 'default'));
        $this->assertMissingKeyDefaults(fn (mixed $default): mixed => zen_config('EDIT_ORDERS_ZEN_CONFIG_MISSING', $default));
    }

    /**
     * @runInSeparateProcess
     */
    public function testOrderTotalFallbackHelpersReturnConstantsAndExactDefaults(): void
    {
        require DIR_FS_CATALOG . 'zc_plugins/EditOrders/v5.0.4/catalog/includes/modules/order_total/ot_misc_cost.php';
        require DIR_FS_CATALOG . 'zc_plugins/EditOrders/v5.0.4/catalog/includes/modules/order_total/ot_onetime_discount.php';

        define('EDIT_ORDERS_ZEN_CONFIG_TEST_VALUE', 'configured');

        foreach (['ot_misc_cost', 'ot_onetime_discount'] as $className) {
            $reflection = new ReflectionClass($className);
            $module = $reflection->newInstanceWithoutConstructor();
            $zenConfig = $reflection->getMethod('zenConfig');
            $zenConfig->setAccessible(true);

            $this->assertSame('configured', $zenConfig->invoke($module, 'EDIT_ORDERS_ZEN_CONFIG_TEST_VALUE', 'default'));
            $this->assertMissingKeyDefaults(
                fn (mixed $default): mixed => $zenConfig->invoke($module, 'EDIT_ORDERS_ZEN_CONFIG_MISSING', $default)
            );
        }
    }

    private function assertMissingKeyDefaults(callable $getConfig): void
    {
        foreach ([null, false, 0, '', 'default'] as $default) {
            $this->assertSame($default, $getConfig($default));
        }
    }
}
