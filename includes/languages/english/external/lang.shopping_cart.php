<?php
// IEMS: changes from the 1.5.6c includes/languages/english/external/shopping_cart.php (stock text left to 2.2.2).
$define = [
    'OUT_OF_STOCK_CANT_CHECKOUT' => 'Products marked with ' . STOCK_MARK_PRODUCT_OUT_OF_STOCK . ' may be out of stock or there are not enough in stock to fill your order.<br />Please change the quantity of products marked with (' . STOCK_MARK_PRODUCT_OUT_OF_STOCK . '). Thank you',
    'OUT_OF_STOCK_CAN_CHECKOUT' => 'Products marked with ' . STOCK_MARK_PRODUCT_OUT_OF_STOCK . ' may be out of stock.<br />Items not in stock will be placed on backorder.',
];

return $define;
