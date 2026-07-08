<?php
// -----
// Part of the "ZCA Bootstrap Template", v3.8.0 and later.
//
// Copyright (c) 2026 Vinos de Frutas Tropicales
//
// This script removes all files distributed by the template as well
// as the database elements.
//
// It's available to run for **only** admin's with superuser authority!
//
use App\Models\LayoutBox;
use Zencart\DbRepositories\LayoutBoxRepository;

require 'includes/application_top.php';

// -----
// If not an admin superuser or the template's not installed, redirect to the admin's home page.
//
if (!zen_is_superuser() || zen_config('ZCA_BOOTSTRAP_VERSION') === null) {
    zen_redirect(zen_href_link(FILENAME_DEFAULT));
}

// -----
// If the admin has confirmed the removal of "ZCA Bootstrap Template" ...
//
if (($_POST['action'] ?? null) === 'uninstall') {
    // -----
    // Build up a list of files to be unconditionally removed.
    //
    // NOTE: /includes/functions/extra_functions/zen_config.php and zen_add_filemtime.php
    // are **not** removed, since other plugins might have also provided these polyfills.
    //
    $files_to_remove = [
        'storefront' => [
            'auto_loaders/config.zca_bootstrap.php',
            'classes/ajax/zcAjaxBootstrapSearch.php',
            'classes/observers/ZcaBootstrapObserver.php',
            'classes/zca/zca_message_stack.php',
            'classes/zca/zca_site_map.php',
            'classes/zca/zca_split_page_results.php',
            'extra_datafiles/dist.site-specific-bootstrap-settings.php',
            'functions/zca_bootstrap_functions.php',
            'init_includes/init_zca_bootstrap.php',
            'languages/english/bootstrap/lang.account_history_info.php',
            'languages/english/bootstrap/account_history_info.php',
            'languages/english/extra_definitions/bootstrap/lang.zca_bootstrap_common.php',
            'languages/english/extra_definitions/bootstrap/lang.zca_bootstrap_id.php',
            'languages/english/extra_definitions/bootstrap/zca_bootstrap_common.php',
            'languages/english/extra_definitions/bootstrap/zca_bootstrap_id.php',
            'languages/bootstrap/lang.english.php',
            'languages/bootstrap/english.php',
            'modules/bootstrap/attributes.php',
            'modules/bootstrap/bootstrap_additional_images.php',
            'modules/bootstrap/bootstrap_slide_additional_images.php',
            'modules/bootstrap/categories_tabs.php',
            'modules/bootstrap/category_row.php',
            'modules/bootstrap/product_listing.php',
            'modules/bootstrap/centerboxes/also_purchased_products.php',
            'modules/bootstrap/centerboxes/featured_categories.php',
            'modules/bootstrap/centerboxes/featured_products.php',
            'modules/bootstrap/centerboxes/manufacturer_info.php',
            'modules/bootstrap/centerboxes/new_products.php',
            'modules/bootstrap/centerboxes/product_notifications.php',
            'modules/bootstrap/centerboxes/specials_index.php',
            'modules/bootstrap/centerboxes/upcoming_products.php',
            'modules/pages/account_history/header_php_account_history_zca_bootstrap.php',
            'modules/pages/featured_products/header_php_featured_products_zca_bootstrap.php',
            'modules/pages/page_not_found/header_php_page_not_found_zca_bootstrap.php',
            'modules/pages/products_all/header_php_products_all_zca_bootstrap.php',
            'modules/pages/products_new/header_php_products_new_zca_bootstrap.php',
            'modules/pages/product_reviews/header_php_product_reviews_zca_bootstrap.php',
            'modules/pages/product_reviews_info/header_php_product_reviews_info_zca_bootstrap.php',
            'modules/pages/product_reviews_write/header_php_products_reviews_write_zca_bootstrap.php',
            'modules/pages/shopping_cart/header_php_shopping_cart_zca_bootstrap.php',
            'modules/pages/shopping_cart/jscript_addr_pulldowns_bootstrap.php',
            'modules/pages/site_map/header_php_site_map_zca_bootstrap.php',
            'modules/pages/specials/header_php_specials_zca_bootstrap.php',
            'modules/sideboxes/bootstrap/information.php',
            'modules/sideboxes/bootstrap/more_information.php',
            'modules/sideboxes/bootstrap/search_header.php',
            'templates/bootstrap/template_info.php',
            'templates/bootstrap/centerboxes/tpl_modules_also_purchased_products.php',
            'templates/bootstrap/centerboxes/pl_modules_featured_categories.php',
            'templates/bootstrap/centerboxes/tpl_modules_featured_products.php',
            'templates/bootstrap/centerboxes/tpl_modules_manufacturer_info.php',
            'templates/bootstrap/centerboxes/tpl_modules_no_notifications.php',
            'templates/bootstrap/centerboxes/tpl_modules_specials_default.php',
            'templates/bootstrap/centerboxes/tpl_modules_upcoming_products.php',
            'templates/bootstrap/centerboxes/tpl_modules_whats_new.php',
            'templates/bootstrap/centerboxes/tpl_modules_yes_notifications.php',
            'templates/bootstrap/common/html_header.php',
            'templates/bootstrap/common/html_header_css_loader.php',
            'templates/bootstrap/common/html_header_js_loader.php',
            'templates/bootstrap/common/tpl_box_default_left.php',
            'templates/bootstrap/common/tpl_box_default_right.php',
            'templates/bootstrap/common/tpl_box_default_single.php',
            'templates/bootstrap/common/tpl_columnar_display.php',
            'templates/bootstrap/common/tpl_columnar_display_carousel.php',
            'templates/bootstrap/common/tpl_footer.php',
            'templates/bootstrap/common/tpl_header.php',
            'templates/bootstrap/common/tpl_main_page.php',
            'templates/bootstrap/common/tpl_offcanvas_menu.php',
            'templates/bootstrap/common/tpl_tabular_display.php',
            'templates/bootstrap/common/tpl_zca_banner_carousel.php',
            'templates/bootstrap/css/bootstrap_color_vars.php',
            'templates/bootstrap/css/checkout_one.css',
            'templates/bootstrap/css/checkout_one_confirmation.css',
            'templates/bootstrap/css/checkout_success.css',
            'templates/bootstrap/css/dist-site_specific_styles.php',
            'templates/bootstrap/css/print_stylesheet.css',
            'templates/bootstrap/css/stylesheet.css',
            'templates/bootstrap/css/stylesheet_360.css',
            'templates/bootstrap/css/stylesheet_361.css',
            'templates/bootstrap/css/stylesheet_364.css',
            'templates/bootstrap/css/stylesheet_365.css',
            'templates/bootstrap/css/stylesheet_373.css',
            'templates/bootstrap/css/stylesheet_374.css',
            'templates/bootstrap/css/stylesheet_378.css',
            'templates/bootstrap/css/stylesheet_ajax_search.css',
            'templates/bootstrap/css/stylesheet_bootstrap.carousel.css',
            'templates/bootstrap/css/stylesheet_bootstrap.lightbox.css',
            'templates/bootstrap/css/stylesheet_colors.css',
            'templates/bootstrap/css/stylesheet_zca_colors.css',
            'templates/bootstrap/css/stylesheet_zca_colors.php',
            'templates/bootstrap/images/ZCA_BOOTSTRAP_TEMPLATE.png',
            'templates/bootstrap/jscript/ajax_search.js',
            'templates/bootstrap/jscript/ajax_search.min.js',
            'templates/bootstrap/jscript/jscript_addr_pulldowns_zca_bootstrap.php',
            'templates/bootstrap/jscript/jscript_bs4_ajax_search.php',
            'templates/bootstrap/jscript/jscript_bs4_matching_heights.php',
            'templates/bootstrap/jscript/jscript_framework.php',
            'templates/bootstrap/jscript/jquery.matchHeight.js',
            'templates/bootstrap/jscript/jquery.matchHeight.min.js',
            'templates/bootstrap/jscript/jquery.min.js',
            'templates/bootstrap/jscript/jscript_sidebox_select_form.php',
            'templates/bootstrap/jscript/jscript_view_password.js',
            'templates/bootstrap/jscript/jscript_zca_bootstrap.js',
            'templates/bootstrap/modalboxes/tpl_ajax_search.php',
            'templates/bootstrap/modalboxes/tpl_attributes_qty_prices.php',
            'templates/bootstrap/modalboxes/tpl_bootstrap_images.php',
            'templates/bootstrap/modalboxes/tpl_coupon_help.php',
            'templates/bootstrap/modalboxes/tpl_cvv_help.php',
            'templates/bootstrap/modalboxes/tpl_image.php',
            'templates/bootstrap/modalboxes/tpl_image_additional.php',
            'templates/bootstrap/modalboxes/tpl_info_shopping_cart.php',
            'templates/bootstrap/modalboxes/tpl_search_help.php',
            'templates/bootstrap/modalboxes/tpl_shipping_estimator.php',
            'templates/bootstrap/sideboxes/tpl_ajax_search_header.php',
            'templates/bootstrap/sideboxes/tpl_best_sellers.php',
            'templates/bootstrap/sideboxes/tpl_brands.php',
            'templates/bootstrap/sideboxes/tpl_categories.php',
            'templates/bootstrap/sideboxes/tpl_document_categories.php',
            'templates/bootstrap/sideboxes/tpl_ezpages.php',
            'templates/bootstrap/sideboxes/tpl_featured.php',
            'templates/bootstrap/sideboxes/tpl_information.php',
            'templates/bootstrap/sideboxes/tpl_more_information.php',
            'templates/bootstrap/sideboxes/tpl_order_history.php',
            'templates/bootstrap/sideboxes/tpl_reviews_random.php',
            'templates/bootstrap/sideboxes/tpl_search.php',
            'templates/bootstrap/sideboxes/tpl_search_header.php',
            'templates/bootstrap/sideboxes/tpl_shopping_cart.php',
            'templates/bootstrap/sideboxes/tpl_specials.php',
            'templates/bootstrap/sideboxes/tpl_whats_new.php',
            'templates/bootstrap/templates/tpl_account_default.php',
            'templates/bootstrap/templates/tpl_account_edit_default.php',
            'templates/bootstrap/templates/tpl_account_history_default.php',
            'templates/bootstrap/templates/tpl_account_history_info_default.php',
            'templates/bootstrap/templates/tpl_account_newsletters_default.php',
            'templates/bootstrap/templates/tpl_account_notifications_default.php',
            'templates/bootstrap/templates/tpl_account_password_default.php',
            'templates/bootstrap/templates/tpl_address_book_default.php',
            'templates/bootstrap/templates/tpl_address_book_process_default.php',
            'templates/bootstrap/templates/tpl_address_book_register.php',
            'templates/bootstrap/templates/tpl_advanced_search_default.php',
            'templates/bootstrap/templates/tpl_advanced_search_results_default.php',
            'templates/bootstrap/templates/tpl_ajax_checkout_confirmation_default.php',
            'templates/bootstrap/templates/tpl_ajax_search_results.php',
            'templates/bootstrap/templates/tpl_ask_a_question_default.php',
            'templates/bootstrap/templates/tpl_brands_default.php',
            'templates/bootstrap/templates/tpl_checkout_confirmation_default.php',
            'templates/bootstrap/templates/tpl_checkout_one_confirmation_default.php',
            'templates/bootstrap/templates/tpl_checkout_one_default.php',
            'templates/bootstrap/templates/tpl_checkout_payment_address_default.php',
            'templates/bootstrap/templates/tpl_checkout_payment_default.php',
            'templates/bootstrap/templates/tpl_checkout_shipping_address_default.php',
            'templates/bootstrap/templates/tpl_checkout_shipping_default.php',
            'templates/bootstrap/templates/tpl_checkout_success_default.php',
            'templates/bootstrap/templates/tpl_checkout_success_guest.php',
            'templates/bootstrap/templates/tpl_conditions_default.php',
            'templates/bootstrap/templates/tpl_contact_us_default.php',
            'templates/bootstrap/templates/tpl_cookie_usage_default.php',
            'templates/bootstrap/templates/tpl_create_account_default.php',
            'templates/bootstrap/templates/tpl_create_account_register.php',
            'templates/bootstrap/templates/tpl_create_account_success_default.php',
            'templates/bootstrap/templates/tpl_create_account_success_register.php',
            'templates/bootstrap/templates/tpl_customers_authorization_default.php',
            'templates/bootstrap/templates/tpl_discount_coupon_default.php',
            'templates/bootstrap/templates/tpl_document_general_info_display.php',
            'templates/bootstrap/templates/tpl_document_product_info_display.php',
            'templates/bootstrap/templates/tpl_download_time_out_default.php',
            'templates/bootstrap/templates/tpl_down_for_maintenance_default.php',
            'templates/bootstrap/templates/tpl_ezpages_bar_footer.php',
            'templates/bootstrap/templates/tpl_ezpages_bar_header.php',
            'templates/bootstrap/templates/tpl_featured_products_default.php',
            'templates/bootstrap/templates/tpl_gv_faq_default.php',
            'templates/bootstrap/templates/tpl_gv_redeem_default.php',
            'templates/bootstrap/templates/tpl_gv_send_default.php',
            'templates/bootstrap/templates/tpl_index_categories.php',
            'templates/bootstrap/templates/tpl_index_default.php',
            'templates/bootstrap/templates/tpl_index_product_list.php',
            'templates/bootstrap/templates/tpl_index_slider.php',
            'templates/bootstrap/templates/tpl_login_default.php',
            'templates/bootstrap/templates/tpl_login_guest.php',
            'templates/bootstrap/templates/tpl_logoff_default.php',
            'templates/bootstrap/templates/tpl_modules_additional_images.php',
            'templates/bootstrap/templates/tpl_modules_address_book_details.php',
            'templates/bootstrap/templates/tpl_modules_attributes.php',
            'templates/bootstrap/templates/tpl_modules_categories_tabs.php',
            'templates/bootstrap/templates/tpl_modules_category_icon_display.php',
            'templates/bootstrap/templates/tpl_modules_category_row.php',
            'templates/bootstrap/templates/tpl_modules_checkout_address_book.php',
            'templates/bootstrap/templates/tpl_modules_common_address_format.php',
            'templates/bootstrap/templates/tpl_modules_create_account.php',
            'templates/bootstrap/templates/tpl_modules_downloads.php',
            'templates/bootstrap/templates/tpl_modules_listing_display_order.php',
            'templates/bootstrap/templates/tpl_modules_main_product_image.php',
            'templates/bootstrap/templates/tpl_modules_media_manager.php',
            'templates/bootstrap/templates/tpl_modules_opc_address_block.php',
            'templates/bootstrap/templates/tpl_modules_opc_billing_address.php',
            'templates/bootstrap/templates/tpl_modules_opc_comments.php',
            'templates/bootstrap/templates/tpl_modules_opc_conditions.php',
            'templates/bootstrap/templates/tpl_modules_opc_credit_selections.php',
            'templates/bootstrap/templates/tpl_modules_opc_customer_info.php',
            'templates/bootstrap/templates/tpl_modules_opc_instructions.php',
            'templates/bootstrap/templates/tpl_modules_opc_payment_choices.php',
            'templates/bootstrap/templates/tpl_modules_opc_shipping_address.php',
            'templates/bootstrap/templates/tpl_modules_opc_shipping_choices.php',
            'templates/bootstrap/templates/tpl_modules_opc_shopping_cart.php',
            'templates/bootstrap/templates/tpl_modules_opc_submit_block.php',
            'templates/bootstrap/templates/tpl_modules_order_totals.php',
            'templates/bootstrap/templates/tpl_modules_products_quantity_discounts.php',
            'templates/bootstrap/templates/tpl_modules_product_image.php',
            'templates/bootstrap/templates/tpl_modules_product_listing.php',
            'templates/bootstrap/templates/tpl_modules_send_or_spend.php',
            'templates/bootstrap/templates/tpl_modules_shipping_estimator.php',
            'templates/bootstrap/templates/tpl_order_status_default.php',
            'templates/bootstrap/templates/tpl_page_2_default.php',
            'templates/bootstrap/templates/tpl_page_3_default.php',
            'templates/bootstrap/templates/tpl_page_4_default.php',
            'templates/bootstrap/templates/tpl_page_default.php',
            'templates/bootstrap/templates/tpl_page_not_found_default.php',
            'templates/bootstrap/templates/tpl_password_forgotten_default.php',
            'templates/bootstrap/templates/tpl_password_reset_default.php',
            'templates/bootstrap/templates/tpl_privacy_default.php',
            'templates/bootstrap/templates/tpl_products_all_default.php',
            'templates/bootstrap/templates/tpl_products_new_default.php',
            'templates/bootstrap/templates/tpl_products_next_previous.php',
            'templates/bootstrap/templates/tpl_product_free_shipping_info_display.php',
            'templates/bootstrap/templates/pl_product_info_display.php',
            'templates/bootstrap/templates/tpl_product_info_display_details.php',
            'templates/bootstrap/templates/tpl_product_info_noproduct.php',
            'templates/bootstrap/templates/tpl_product_music_info_display.php',
            'templates/bootstrap/templates/tpl_product_music_info_display_details.php',
            'templates/bootstrap/templates/tpl_product_music_info_display_extra.php',
            'templates/bootstrap/templates/tpl_product_reviews_default.php',
            'templates/bootstrap/templates/tpl_product_reviews_info_default.php',
            'templates/bootstrap/templates/tpl_product_reviews_write_default.php',
            'templates/bootstrap/templates/tpl_reviews_default.php',
            'templates/bootstrap/templates/tpl_search_default.php',
            'templates/bootstrap/templates/tpl_search_result_default.php',
            'templates/bootstrap/templates/tpl_shippinginfo_default.php',
            'templates/bootstrap/templates/tpl_shopping_cart_default.php',
            'templates/bootstrap/templates/tpl_site_map_default.php',
            'templates/bootstrap/templates/tpl_specials_default.php',
            'templates/bootstrap/templates/tpl_ssl_check_default.php',
            'templates/bootstrap/templates/tpl_time_out_default.php',
            'templates/bootstrap/templates/tpl_unsubscribe_default.php',
        ],
       'admin_includes' => [
            'auto_loaders/config.bc.php',
            'auto_loaders/config/zca_bootstrap_admin.php',
            'css/colorpicker.css',
            'extra_datafiles/zca_bootstrap_colors.php',
            'init_includes/init_bc_config.php',
            'init_includesinit_bc_config_install_or_upgrade.php',
            'init_includesinit_zca_bootstrap_template_admin.php',
            'init_includesinit_zca_bootstrap_template_admin_install.php',
            'init_includesinit_zca_bootstrap_template_admin_update.php',
            'javascript/colorpicker.js',
            'languages/english/lang.zca_bootstrap_colors.php',
            'languages/english/lang.zca_bootstrap_uninstall.php',
            'languages/english/zca_bootstrap_colors.php',
            'languages/english/extra_definitions/lang.zca_bootstrap_colors.php',
            'languages/english/extra_definitions/lang.zca_bootstrap_messages.php',
            'languages/english/extra_definitions/zca_bootstrap_colors.php',
            'languages/english/extra_definitions/zca_bootstrap_messages.php',
        ],
        'admin_root' => [
            'zca_bootstrap_colors.php',
            'zca_bootstrap_uninstall.php',
        ],
    ];

    // -----
    // Remove those files ...
    //
    foreach ($files_to_remove as $key => $file_list) {
        switch ($key) {
            case 'storefront':
                $directory = DIR_FS_CATALOG . DIR_WS_INCLUDES;
                break;

            case 'admin_includes':
                $directory = DIR_FS_ADMIN . DIR_WS_INCLUDES;
                break;

            default:
                $directory = DIR_FS_ADMIN;
                break;
        }
        foreach ($file_list as $current_file) {
            if (is_file($directory . $current_file)) {
                unlink($directory . $current_file);
            }
        }
    }

    // -----
    // Remove the various pages from the admin's menus.
    //
    zen_deregister_admin_pages(['toolsZCABootstrapColors', 'extrasZCABootstrapUninstall', 'configBootstrapTemplate']);

    // -----
    // Remove the "Bootstrap Colors" configuration values.
    //
    $db->Execute(
        "DELETE FROM " . TABLE_CONFIGURATION . "
          WHERE configuration_key = 'ZCA_BOOTSTRAP_COLORS_VERSION'
             OR configuration_key LIKE 'ZCA\_BODY\_%'
             OR configuration_key LIKE 'ZCA\_BUTTON\_%'
             OR configuration_key LIKE 'ZCA\_HEADER\_%'
             OR configuration_key LIKE 'ZCA\_FOOTER\_%'
             OR configuration_key LIKE 'ZCA\_SIDEBOX\_%'
             OR configuration_key LIKE 'ZCA\_CENTERBOX\_%'
             OR configuration_key LIKE 'ZCA\_ADD\_TO\_CART\_%'
             OR configuration_key LIKE 'ZCA\_CHECKOUT\_%'
             OR configuration_key LIKE 'ZCA\_CAROUSEL\_%'
             OR configuration_key LIKE 'ZCA\_PRIMARY\_ADDRESS\_%'
             OR configuration_key LIKE 'ZCA\_SOLD\_OUT\_%'
             OR configuration_key LIKE 'ZCA\_ALERT\_INFO\_%'"
    );
    $db->Execute(
        "DELETE FROM " . TABLE_CONFIGURATION_GROUP . "
          WHERE configuration_group_title = 'ZCA Bootstrap Colors'
          LIMIT 1"
    );

    // -----
    // Remove the "Bootstrap Template" configuration values, if present.
    //
    $configurationGroupTitle = 'Bootstrap Template Settings';
    $configuration = $db->Execute("SELECT configuration_group_id FROM " . TABLE_CONFIGURATION_GROUP . " WHERE configuration_group_title = '$configurationGroupTitle' LIMIT 1");
    if (!$configuration->EOF) {
        $cgi = (int)$configuration->fields['configuration_group_id'];
        $db->Execute(
            "DELETE FROM " . TABLE_CONFIGURATION . "
              WHERE configuration_group_id = $cgi"
        );
        $db->Execute(
            "DELETE FROM " . TABLE_CONFIGURATION_GROUP . "
              WHERE configuration_group_id = $cgi
              LIMIT 1"
        );
    }

    // -----
    // Remove the 'bootstrap' template records from the 'layout_boxes'
    // table. Note that the class name and interfaces changed for zc22x
    // and later.
    //
    if (class_exists('\App\Models\LayoutBox')) {
        $model = new LayoutBox();
        $model->where('layout_template', 'bootstrap')->delete();
    } else {
        $model = new LayoutBoxRepository($db);
        $bootstrap_boxes = $model->getByTemplate('bootstrap');
        foreach ($bootstrap_boxes as $next_box) {
            $model->deleteByLayoutIdAndName((int)$next_box['layout_id'], $next_box['layout_box_name']);
        }
    }

    // -----
    // Set a message notifying the admin of the removal, note the change in the activity
    // log and redirect back to the admin dashboard.
    //
    $messageStack->add_session(TEXT_MESSAGE_ZCA_REMOVED, 'success');
    zen_record_admin_activity(TEXT_MESSAGE_ZCA_REMOVED, 'info');
    zen_redirect(zen_href_link(FILENAME_DEFAULT));
}
?>
<!doctype html>
<html <?= HTML_PARAMS ?>>
<head>
    <?php require DIR_WS_INCLUDES . 'admin_html_head.php'; ?>
</head>

<body>
    <?php require DIR_WS_INCLUDES . 'header.php'; ?>
    <div class="container-fluid">
        <h1><?= HEADING_TITLE ?></h1>
<?php
if ($template_dir === 'bootstrap') {
?>
        <div class="row">
            <div class="col-md-1"></div>
            <div class="col-md-10 messageStackAlert alert alert-danger text-center">
                <i class="fa-solid fa-2x fa-circle-exclamation"></i>
                <?= ERROR_BOOTSTRAP_IS_CURRENT ?>
            </div>
            <div class="col-md-1"></div>
        </div>
<?php
} else {
    // -----
    // Set up the next-action to be performed on form-submittal and the message to display on the
    // current page.  On initial entry, the admin is questioned as to whether to remove IH; on the
    // first form-submittal, the admin is asked to confirm their removal request and on the next
    // form-submittal, the file/configuration removal is actually performed.
    //
    if (!isset($_POST['action']) || $_POST['action'] !== 'confirm') {
        $next_action = 'confirm';
        $current_message = TEXT_ARE_YOU_SURE;
        $go_button_class = 'warning';
    } else {
        $next_action = 'uninstall';
        $current_message = TEXT_CONFIRMATION;
        $go_button_class = 'danger';
    }
?>
        <p><?= TEXT_INFORMATION ?></p>
        <p class="text-center"><?= $current_message ?></p>
        <?= zen_draw_form('remove', FILENAME_ZCA_BOOTSTRAP_UNINSTALL) . zen_draw_hidden_field('action', $next_action) ?>
            <div class="row text-center">
                <a href="<?= zen_href_link(FILENAME_DEFAULT) ?>" class="btn btn-default"><?= IMAGE_CANCEL ?></a>
                <input type="submit" class="btn btn-<?= $go_button_class ?>" value="<?= IMAGE_GO ?>">
            </div>
        <?= '</form>'; ?>
<?php
}
?>
    </div>
    <?php require DIR_WS_INCLUDES . 'footer.php'; ?>
</body>

</html>
<?php 
require DIR_WS_INCLUDES . 'application_bottom.php';
