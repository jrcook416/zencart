<?php
/**
 * IEMS Legacy Bridge: lets staff add ANY product (disabled or out of stock) to
 * an order from the Edit Orders "Add Product" picker, without editing the stock
 * EditOrders plugin. Edit Orders builds that picker from
 * Zencart\Plugins\Admin\EditOrders\EoProductPulldown and hard-codes
 * onlyActive(true) plus a "quantity > 0" condition, and offers no hook for
 * either. Prepending this directory to the same namespace prefix makes the
 * autoloader find the bridge's replacement class first; the stock file is then
 * never loaded. Nothing else in that namespace is overridden.
 *
 * @var \Aura\Autoload\Loader $psr4Autoloader
 */
if (defined('IS_ADMIN_FLAG') && IS_ADMIN_FLAG === true) {
    $psr4Autoloader->addPrefix(
        'Zencart\\Plugins\\Admin\\EditOrders',
        __DIR__ . '/admin/includes/eo_overrides/',
        true
    );
}
