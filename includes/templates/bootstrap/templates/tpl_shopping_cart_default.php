<?php
/**
 * Page Template
 *
 * BOOTSTRAP v3.3.0
 *
 * Loaded automatically by index.php?main_page=shopping_cart.<br />
 * Displays shopping-cart contents
 *
 * @copyright Copyright 2003-2020 Zen Cart Development Team
 * @copyright Portions Copyright 2003 osCommerce
 * @license http://www.zen-cart.com/license/2_0.txt GNU Public License V2.0
 * @version $Id: DrByte 2020 Oct 19 Modified in v1.5.7a $
 */
?>
<div id="shoppingCartDefault" class="centerColumn">
<?php
if ($flagHasCartContents) {
    if ($_SESSION['cart']->count_contents() > 0) {
?>
    <div id="shoppingCartDefault-helpLink" class="helpLink float-right p-3">
        <a data-toggle="modal" href="#cartHelpModal"><?php echo TEXT_CART_MODAL_HELP; ?></a>
    </div>

    <?php require $template->get_template_dir('tpl_info_shopping_cart.php', DIR_WS_TEMPLATE, $current_page_base, 'modalboxes') . '/tpl_info_shopping_cart.php'; ?>

    <div class="clearfix"></div>
<?php
    }
?>
    <h1 id="shoppingCartDefault-pageHeading" class="pageHeading"><?php echo HEADING_TITLE; ?></h1> 
  
    <?php if ($messageStack->size('shopping_cart') > 0) echo $messageStack->output('shopping_cart'); ?>

    <?php echo zen_draw_form('cart_quantity', zen_href_link(FILENAME_SHOPPING_CART, 'action=update_product', $request_type), 'post', 'id="shoppingCartForm"'); ?> 
    
    <div id="shoppingCartDefault-content" class="content">
<?php
/**
 * require the html_define for the shopping_cart page
 */
    require $define_page;
?>
    </div>

<?php 
    if (!empty($totalsDisplay)) { 
?>
    <div id="shoppingCartDefault-cartTotalsDisplay" class="cartTotalsDisplay text-center font-weight-bold p-3"><?php echo $totalsDisplay; ?></div>
<?php 
    }

    if ($flagAnyOutOfStock) {
        if (STOCK_ALLOW_CHECKOUT == 'true') {
?>
    <div class="alert alert-danger" role="alert"><?php echo OUT_OF_STOCK_CAN_CHECKOUT; ?></div>
<?php    
        } else { 
?>
    <div class="alert alert-danger" role="alert"><?php echo OUT_OF_STOCK_CANT_CHECKOUT; ?></div>
<?php    
        } //endif STOCK_ALLOW_CHECKOUT
    } //endif flagAnyOutOfStock 
?> 
    <div class="table-responsive">
        <table id="shoppingCartDefault-cartTableDisplay" class="cartTableDisplay table table-bordered table-striped">
            <tr>
                <th scope="col" id="cartTableDisplay-qtyHeading"><?php echo TABLE_HEADING_QUANTITY; ?></th>
                <th scope="col" class="d-none d-sm-table-cell" id="cartTableDisplay-qtyUpdateHeading">&nbsp;</th>
                <th scope="col" id="cartTableDisplay-productsHeading"><?php echo TABLE_HEADING_PRODUCTS; ?></th>
                <th scope="col" id="cartTableDisplay-priceHeading"><?php echo TABLE_HEADING_PRICE; ?></th>
                <th scope="col" id="cartTableDisplay-totalsHeading"><?php echo TABLE_HEADING_TOTAL; ?></th>
                <th scope="col" class="d-none d-sm-table-cell" id="cartTableDisplay-removeHeading">&nbsp;</th>
            </tr>
<?php
    foreach ($productArray as $product) {
?>
            <tr>
                <td class="qtyCell">
<?php
        if ($product['flagShowFixedQuantity']) {
            echo $product['showFixedQuantityAmount'] . '' . $product['flagStockCheck'] . '' . $product['showMinUnits'];
        } else {
            echo $product['quantityField'] . '' . $product['flagStockCheck'] . '' . $product['showMinUnits'];
        }
?>
                </td>
                <td class="qtyUpdateCell text-center d-none d-sm-table-cell"><?php echo (!empty($product['buttonUpdate'])) ? $product['buttonUpdate'] : ''; ?></td>
                <td class="productsCell">
                    <a href="<?php echo $product['linkProductsName']; ?>"><span class="d-none d-sm-block"><?php echo $product['productsImage']; ?></span><?php echo $product['productsName'] . '' . $product['flagStockCheck'] . ''; ?></a>

<?php
        echo $product['attributeHiddenField'];
        if (isset($product['attributes']) && is_array($product['attributes'])) {
?>
                    <div class="productsCell-attributes">
                        <ul>
<?php
            foreach ($product['attributes'] as $option => $value) {
?>
                            <li><?php echo $value['products_options_name'] . TEXT_OPTION_DIVIDER . nl2br($value['products_options_values_name']); ?></li>
<?php
            }
?>
                        </ul>
                    </div>
<?php
        }
?>
                </td>
                <td class="priceCell"><?php echo $product['productsPriceEach']; ?></td>
                <td class="totalsCell"><?php echo $product['productsPrice']; ?></td>
                <td class="removeCell text-center d-none d-sm-table-cell">
<?php
        if ($product['buttonDelete']) {
?>
                    <a href="<?php echo zen_href_link(FILENAME_SHOPPING_CART, 'action=remove_product&product_id=' . $product['id']); ?>" class="btn" aria-label="<?php echo ICON_TRASH_ALT; ?>" title="<?php echo ICON_TRASH_ALT; ?>"><i aria-hidden="true" class="fas fa-trash-alt"></i></a>
<?php
        }
        if ($product['checkBoxDelete'] ) {
            echo zen_draw_checkbox_field('cart_delete[]', $product['id'], false, 'aria-label="' . ARIA_DELETE_ITEM_FROM_CART . '"');
        }
?>
                </td>
            </tr>
<?php
    } // end foreach ($productArray as $product)
?>
            <tr>
                <td colspan="1">

<?php
    // show update cart button
    if (SHOW_SHOPPING_CART_UPDATE == 2 || SHOW_SHOPPING_CART_UPDATE == 3) {
?>
                    <div id="cartUpdate" class="text-center">
                        <button type="submit" class="btn" aria-label="<?php echo BUTTON_UPDATE_ALT; ?>"><i class="fas fa-sync-alt"></i></button>
                    </div>
<?php
    }
?>
                </td>
                <td colspan="5">
                    <div id="cartTotal" class="text-right font-weight-bold"><?php echo SUB_TITLE_SUB_TOTAL; ?> <?php echo $cartShowTotal; ?></div>
                </td>
            </tr>
        </table>
    </div> 

<!--bof shopping cart buttons-->
    <div id="shoppingCartDefault-btn-toolbar" class="btn-toolbar justify-content-between my-3" role="toolbar">
        <?php echo zen_back_link() . zen_image_button(BUTTON_IMAGE_CONTINUE_SHOPPING, BUTTON_CONTINUE_SHOPPING_ALT) . '</a>'; ?>
        <?php echo '<a href="' . zen_href_link(FILENAME_CHECKOUT_SHIPPING, '', 'SSL') . '">' . zen_image_button(BUTTON_IMAGE_CHECKOUT, BUTTON_CHECKOUT_ALT) . '</a>'; ?>
    </div>
<!--eof shopping cart buttons-->

    <?php echo '</form>'; ?>
<?php
    if (SHOW_SHIPPING_ESTIMATOR_BUTTON == '1') {
        // -----
        // Determine whether the modal should be shown on the page's initial rendering.  It will be if its
        // form was just posted.
        //
        if (isset($_POST['action']) && $_POST['action'] == 'submit') {
?>
    <script>
    jQuery(document).ready(function () {
        jQuery('#shippingEstimatorModal').modal('show');
    });
    </script>
<?php
        }
?>
    <div id="shoppingCartDefault-shoppingEstimator-btn-toolbar" class="btn-toolbar my-3" role="toolbar">
        <?php echo zen_image_button(BUTTON_IMAGE_SHIPPING_ESTIMATOR, BUTTON_SHIPPING_ESTIMATOR_ALT, 'data-toggle="modal" data-target="#shippingEstimatorModal"'); ?>
    </div>
<?php
        require $template->get_template_dir('tpl_shipping_estimator.php', DIR_WS_TEMPLATE, $current_page_base, 'modalboxes') . '/tpl_shipping_estimator.php';
    }
?>
<!-- ** BEGIN PAYPAL EXPRESS CHECKOUT ** -->
<?php  // the tpl_ec_button template only displays EC option if cart contents >0 and value >0
    if (defined('MODULE_PAYMENT_PAYPALWPP_STATUS') && MODULE_PAYMENT_PAYPALWPP_STATUS == 'True') {
        require DIR_FS_CATALOG . DIR_WS_MODULES . 'payment/paypal/tpl_ec_button.php';
    }
?>
<!-- ** END PAYPAL EXPRESS CHECKOUT ** -->

<?php
    if (SHOW_SHIPPING_ESTIMATOR_BUTTON == '2') {
/**
 * load the shipping estimator code if needed
 */
        require DIR_WS_MODULES . zen_get_module_directory('shipping_estimator.php');
    }
} else {
?>
    <h1 id="shoppingCartDefault-pageHeading" class="pageHeading"><?php echo TEXT_CART_EMPTY; ?></h1> 
<?php
    $show_display_shopping_cart_empty = $db->Execute(SQL_SHOW_SHOPPING_CART_EMPTY);

    while (!$show_display_shopping_cart_empty->EOF) {
        if ($show_display_shopping_cart_empty->fields['configuration_key'] == 'SHOW_SHOPPING_CART_EMPTY_FEATURED_PRODUCTS') {
            /**
             * display the Featured Products Center Box
             */
            require $template->get_template_dir('tpl_modules_featured_products.php', DIR_WS_TEMPLATE, $current_page_base, 'centerboxes') . '/tpl_modules_featured_products.php';
        }

        if ($show_display_shopping_cart_empty->fields['configuration_key'] == 'SHOW_SHOPPING_CART_EMPTY_SPECIALS_PRODUCTS') {
            /**
             * display the Special Products Center Box
             */
            require $template->get_template_dir('tpl_modules_specials_default.php', DIR_WS_TEMPLATE, $current_page_base, 'centerboxes') . '/tpl_modules_specials_default.php';
        }

        if ($show_display_shopping_cart_empty->fields['configuration_key'] == 'SHOW_SHOPPING_CART_EMPTY_NEW_PRODUCTS') {
            /**
             * display the New Products Center Box
             */
            require $template->get_template_dir('tpl_modules_whats_new.php', DIR_WS_TEMPLATE, $current_page_base, 'centerboxes') . '/tpl_modules_whats_new.php';
        }

        if ($show_display_shopping_cart_empty->fields['configuration_key'] == 'SHOW_SHOPPING_CART_EMPTY_UPCOMING') {
            require DIR_WS_MODULES . zen_get_module_directory('centerboxes/' . FILENAME_UPCOMING_PRODUCTS);
        }

        $show_display_shopping_cart_empty->MoveNext();
    } // !EOF
}
?>
</div>
