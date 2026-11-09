<?php
// -----
// IEMS Legacy Bridge replacement for Edit Orders' EoProductPulldown (see
// ../../../psr4Autoload.php). Same class name and namespace as the stock
// EditOrders v5.0.4 class, which is only used by Edit Orders' "Add Product"
// search and category pickers. Unlike the stock class it lists every product:
// disabled products, and products with zero quantity when
// STOCK_ALLOW_CHECKOUT is 'false'.
//
namespace Zencart\Plugins\Admin\EditOrders;

if (!defined('IS_ADMIN_FLAG')) {
    die('Illegal Access');
}

class EoProductPulldown extends \productPulldown
{
    // Edit Orders asks for active products only; staff may add any product.
    public function onlyActive(bool $status)
    {
        return parent::onlyActive(false);
    }
}
