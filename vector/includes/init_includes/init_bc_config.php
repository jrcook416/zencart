<?php
/**
 * ZCA Bootstrap Colors
 *
 * @copyright Copyright 2003-2005 Zen Cart Development Team
 * @copyright Portions Copyright 2003 osCommerce
 * @license http://www.zen-cart.com/license/2_0.txt GNU Public License V2.0
 * @version $Id: init_bc_config.php
 * BOOTSTRAP v3.1.3
 */

// -----
// Wait until an admin is logged in before installing or updating ...
//
if (!isset($_SESSION['admin_id'])) {
    return;
}

// -----
// Determine the configuration-group id to use for the plugin's settings, creating that
// group if it's not currently present.
//
$bc_menu_title = 'ZCA Bootstrap Colors';
$configuration = $db->Execute(
    "SELECT configuration_group_id 
       FROM " . TABLE_CONFIGURATION_GROUP . " 
      WHERE configuration_group_title = '$bc_menu_title' 
      LIMIT 1"
);
if ($configuration->EOF) {
    $db->Execute(
        "INSERT INTO " . TABLE_CONFIGURATION_GROUP . " 
            (configuration_group_title, configuration_group_description, sort_order, visible) 
         VALUES 
            ('$bc_menu_title', '$bc_menu_title', 1, 1);"
    );
    $bccid = $db->Insert_ID(); 
    $db->Execute(
        "UPDATE " . TABLE_CONFIGURATION_GROUP . " 
            SET sort_order = $bccid 
          WHERE configuration_group_id = $bccid
          LIMIT 1"
    );
} else {
    $bccid = $configuration->fields['configuration_group_id'];
}

// -----
// If this is an initial install, add the various (original) settings.
//
if (!defined('ZCA_BODY_TEXT_COLOR')) {
    // -----
    // NOTE: No 'use_function' or 'set_function' needed for any of these human-enterable settings!
    //
    //-- ADD VALUES TO ZCA BOOTSTRAP COLORS CONFIGURATION GROUP (Admin > Configuration > ZCA Bootstrap Colors Configuration) --
    /* ZCA Bootstrap Template BODY Colors */
    $db->Execute(
        "INSERT INTO " . TABLE_CONFIGURATION . "
            (configuration_title, configuration_key, configuration_value, configuration_description, configuration_group_id, sort_order, date_added)
         VALUES
            ('Body Text Color', 'ZCA_BODY_TEXT_COLOR', '#000000', 'Default:#000000', " . $bccid . ", 1, now()),
            ('Body Background Color', 'ZCA_BODY_BACKGROUND_COLOR', '#ffffff', 'Default:#ffffff', " .$bccid . ", 2, now()),
            ('<b>Body Breadcrumbs Background Color</b>', 'ZCA_BODY_BREADCRUMBS_BACKGROUND_COLOR', '#cccccc', 'Default:#cccccc', " . $bccid . ", 3, now()),
            ('<b>Body Breadcrumbs Text Color</b>', 'ZCA_BODY_BREADCRUMBS_TEXT_COLOR', '#000000', 'Default:000000', " . $bccid . ", 4, now()),
            ('<b>Body Breadcrumbs Link Color</b>', 'ZCA_BODY_BREADCRUMBS_LINK_COLOR', '#33b5e5', 'Default:33b5e5', " . $bccid . ", 5, now()),
            ('<b>Body Breadcrumbs Link Color on Hover</b>', 'ZCA_BODY_BREADCRUMBS_LINK_COLOR_HOVER', '#0099CC', 'Default:#0099CC', " . $bccid . ", 6, now()),
            ('Body Products Base Price', 'ZCA_BODY_PRODUCTS_BASE_COLOR', '#000000', 'Default:#000000', " . $bccid . ", 7, now()),
            ('Body Products Normal Price', 'ZCA_BODY_PRODUCTS_NORMAL_COLOR', '#000000', 'Default:#000000', " . $bccid . ", 8, now()),
            ('Body Products Special Price', 'ZCA_BODY_PRODUCTS_SPECIAL_COLOR', '#ff0000', 'Default:#ff0000', " . $bccid . ", 9, now()),
            ('Body Products Price Discount Price', 'ZCA_BODY_PRODUCTS_DISCOUNT_COLOR', '#ff0000', 'Default:#ff0000', " . $bccid . ", 10, now()),
            ('Body Products Sale Price', 'ZCA_BODY_PRODUCTS_SALE_COLOR', '#ff0000', 'Default:#ff0000', " . $bccid . ", 11, now()),
            ('Body Products Free Price', 'ZCA_BODY_PRODUCTS_FREE_COLOR', '#0000ff', 'Default:#0000ff', " . $bccid . ", 12, now()),
            ('<b>Body Form Placeholder</b>', 'ZCA_BODY_PLACEHOLDER', '#ff0000', 'Default:#ff0000', " . $bccid . ", 13, now())"
    );

    /* ZCA Bootstrap Template LINKS & BUTTONS Colors */
    $db->Execute(
        "INSERT INTO " . TABLE_CONFIGURATION . "
            (configuration_title, configuration_key, configuration_value, configuration_description, configuration_group_id, sort_order, date_added)
         VALUES
            ('A Link Color', 'ZCA_BUTTON_LINK_COLOR', '#05a5cb', 'Default:#05a5cb', " . $bccid . ", 14, now()),
            ('A Link Color on Hover', 'ZCA_BUTTON_LINK_COLOR_HOVER', '#0099CC', 'Default:#0099CC', " . $bccid . ", 15, now()),
            ('<b>Button Text Color</b>', 'ZCA_BUTTON_TEXT_COLOR', '#ffffff', 'Default:#ffffff', " . $bccid . ", 16, now()),
            ('<b>Button Text Color on Hover</b>', 'ZCA_BUTTON_TEXT_COLOR_HOVER', '#cccccc', 'Default:#cccccc', " . $bccid . ", 17, now()),
            ('<b>Button Color</b>', 'ZCA_BUTTON_COLOR', '#33b5e5', 'Default:#33b5e5', " . $bccid . ", 18, now()),
            ('<b>Button Color on Hover</b>', 'ZCA_BUTTON_COLOR_HOVER', '#0099CC', 'Default:#0099CC', " . $bccid . ", 19, now()),
            ('<b>Button Border Color</b>', 'ZCA_BUTTON_BORDER_COLOR', '#33b5e5', 'Default:#33b5e5', " . $bccid . ", 20, now()),
            ('<b>Button Border Color on Hover</b>', 'ZCA_BUTTON_BORDER_COLOR_HOVER', '#0099CC', 'Default:#0099CC', " . $bccid . ", 21, now()),
            ('Pagination Button Text Color', 'ZCA_BUTTON_PAGEINATION_TEXT_COLOR', '#000000', 'Default:#000000', " . $bccid . ", 22, now()),
            ('Pagination Button Text Color on Hover', 'ZCA_BUTTON_PAGEINATION_TEXT_COLOR_HOVER', '#ffffff', 'Default:#ffffff', " . $bccid . ", 23, now()),
            ('Pagination Button Color', 'ZCA_BUTTON_PAGEINATION_COLOR', '#cccccc', 'Default:#cccccc', " . $bccid . ", 24, now()),
            ('Pagination Button Color on Hover', 'ZCA_BUTTON_PAGEINATION_COLOR_HOVER', '#0099CC', 'Default:#0099CC', " . $bccid . ", 25, now()),
            ('Pagination Button Border Color', 'ZCA_BUTTON_PAGEINATION_BORDER_COLOR', '#cccccc', 'Default:#cccccc', " . $bccid . ", 26, now()),
            ('Pagination Button Border Color on Hover', 'ZCA_BUTTON_PAGEINATION_BORDER_COLOR_HOVER', '#0099CC', 'Default:#0099CC', " . $bccid . ", 27, now()),
            ('Pagination Active Button Text Color', 'ZCA_BUTTON_PAGEINATION_ACTIVE_TEXT_COLOR', '#ffffff', 'Default:#ffffff', " . $bccid . ", 28, now()),
            ('Pagination Active Button Color', 'ZCA_BUTTON_PAGEINATION_ACTIVE_COLOR', '#33b5e5', 'Default:#33b5e5', " . $bccid . ", 29, now())"
    );

    /* ZCA Bootstrap Template HEADER Colors */
    $db->Execute(
        "INSERT INTO " . TABLE_CONFIGURATION . "
            (configuration_title, configuration_key, configuration_value, configuration_description, configuration_group_id, sort_order, date_added)
         VALUES
            ('<b>Header Wrapper Background Color</b>', 'ZCA_HEADER_WRAPPER_BACKGROUND_COLOR', '#ffffff', 'Default:#ffffff', " . $bccid . ", 30, now()),
            ('Header Tagline Text Color', 'ZCA_HEADER_TAGLINE_TEXT_COLOR', '#000000', 'Default:#000000', " . $bccid . ", 31, now()),
            ('<b>Header Nav Bar Color</b>', 'ZCA_HEADER_NAV_BAR_BACKGROUND_COLOR', '#333333', 'Default:#333333', " . $bccid . ", 32, now()),
            ('<b>Header Nav Bar Link Color</b>', 'ZCA_HEADER_NAVBAR_LINK_COLOR', '#ffffff', 'Default:#ffffff', " . $bccid . ", 33, now()),
            ('<b>Header Nav Bar Link Color on Hover</b>', 'ZCA_HEADER_NAVBAR_LINK_COLOR_HOVER', '#cccccc', 'Default:#cccccc', " . $bccid . ", 34, now()),
            ('<b>Header Nav Bar Button Text Color</b>', 'ZCA_HEADER_NAVBAR_BUTTON_TEXT_COLOR', '#ffffff', 'Default:#ffffff', " . $bccid . ", 35, now()),
            ('<b>Header Nav Bar Button Text Color on Hover</b>', 'ZCA_HEADER_NAVBAR_BUTTON_TEXT_COLOR_HOVER', '#cccccc', 'Default:#cccccc', " . $bccid . ", 36, now()),
            ('<b>Header Nav Bar Button Color</b>', 'ZCA_HEADER_NAVBAR_BUTTON_COLOR', '#343a40', 'Default:#343a40', " . $bccid . ", 37, now()),
            ('<b>Header Nav Bar Button Color on Hover</b>', 'ZCA_HEADER_NAVBAR_BUTTON_COLOR_HOVER', '#919aa1', 'Default:#919aa1', " . $bccid . ", 38, now()),
            ('<b>Header Nav Bar Button Border Color</b>', 'ZCA_HEADER_NAVBAR_BUTTON_BORDER_COLOR', '#343a40', 'Default:#343a40', " . $bccid . ", 39, now()),
            ('<b>Header Nav Bar Border Color on Hover</b>', 'ZCA_HEADER_NAVBAR_BUTTON_BORDER_COLOR_HOVER', '#919aa1', 'Default:#919aa1', " . $bccid . ", 40, now()),
            ('Header Category Tabs Text Color', 'ZCA_HEADER_TABS_TEXT_COLOR', '#ffffff', 'Default:#ffffff', " . $bccid . ", 41, now()),
            ('Header Category Tabs Text Color on Hover', 'ZCA_HEADER_TABS_TEXT_COLOR_HOVER', '#cccccc', 'Default:#cccccc', " . $bccid . ", 42, now()),
            ('Header Category Tabs Background Color', 'ZCA_HEADER_TABS_COLOR', '#33b5e5', 'Default:#33b5e5', " . $bccid . ", 43, now()),
            ('Header Category Tabs Background Color on Hover', 'ZCA_HEADER_TABS_COLOR_HOVER', '#0099CC', 'Default:#0099CC', " . $bccid . ", 44, now()),
            ('<b>Header EZ-Page Bar Background Color</b>', 'ZCA_HEADER_EZPAGE_BACKGROUND_COLOR', '#464646', 'Default:#464646', " . $bccid . ", 45, now()),
            ('<b>Header EZ-Page Bar Link Color</b>', 'ZCA_HEADER_EZPAGE_LINK_COLOR', '#ffffff', 'Default:#ffffff', " . $bccid . ", 46, now()),
            ('<b>Header EZ-Page Bar Link Color on Hover</b>', 'ZCA_HEADER_EZPAGE_LINK_COLOR_HOVER', '#cccccc', 'Default:#cccccc', " . $bccid . ", 47, now())"
    );

    /* ZCA Bootstrap Template FOOTER Colors */
    $db->Execute(
        "INSERT INTO " . TABLE_CONFIGURATION . "
            (configuration_title, configuration_key, configuration_value, configuration_description, configuration_group_id, sort_order, date_added)
         VALUES
            ('Footer Wrapper Text Color', 'ZCA_FOOTER_WRAPPER_TEXT_COLOR', '#000000', 'Default:#000000', " . $bccid . ", 48, now()),
            ('Footer Wrapper Background Color', 'ZCA_FOOTER_WRAPPER_BACKGROUND_COLOR', '#ffffff', 'Default:#ffffff', " . $bccid . ", 49, now()),
            ('<b>Footer EZ-Page Bar Background Color</b>', 'ZCA_FOOTER_EZPAGE_BACKGROUND_COLOR', '#464646', 'Default:#464646', " . $bccid . ", 50, now()),
            ('<b>Footer EZ-Page Bar Link Color</b>', 'ZCA_FOOTER_EZPAGE_LINK_COLOR', '#ffffff', 'Default:#ffffff', " . $bccid . ", 51, now()),
            ('<b>Footer EZ-Page Bar Link Color on Hover</b>', 'ZCA_FOOTER_EZPAGE_LINK_COLOR_HOVER', '#cccccc', 'Default:#cccccc', " . $bccid . ", 52, now())"
    );

    /* ZCA Bootstrap Template SIDEBOXES Colors */
    $db->Execute(
        "INSERT INTO " . TABLE_CONFIGURATION . "
            (configuration_title, configuration_key, configuration_value, configuration_description, configuration_group_id, sort_order, date_added)
         VALUES
            ('Sidebox Background Color', 'ZCA_SIDEBOX_BACKGROUND_COLOR', '#ffffff', 'Default:#ffffff', " . $bccid . ", 53, now()),
            ('Sidebox Text Color', 'ZCA_SIDEBOX_TEXT_COLOR', '#000000', 'Default:#000000', " . $bccid . ", 54, now()),
            ('Sidebox Link Color', 'ZCA_SIDEBOX_LINK_COLOR', '#33b5e5', 'Default:#33b5e5', " . $bccid . ", 55, now()),
            ('Sidebox Link Color on Hover', 'ZCA_SIDEBOX_LINK_COLOR_HOVER', '#0099CC', 'Default:#0099CC', " . $bccid . ", 56, now()),
            ('Sidebox Link Background Color', 'ZCA_SIDEBOX_LINK_BACKGROUND_COLOR', '#ffffff', 'Default:#ffffff', " . $bccid . ", 57, now()),
            ('Sidebox Link Background Color on Hover', 'ZCA_SIDEBOX_LINK_BACKGROUND_COLOR_HOVER', '#efefef', 'Default:#ffffff', " . $bccid . ", 58, now()),
            ('<b>Sidebox Product Card Background Color</b>', 'ZCA_SIDEBOX_CARD_BACKGROUND_COLOR', '#ffffff', 'Default:#ffffff', " . $bccid . ", 59, now()),
            ('<b>Sidebox Product Card Background Color on Hover</b>', 'ZCA_SIDEBOX_CARD_BACKGROUND_COLOR_HOVER', '#efefef', 'Default:#efefef', " . $bccid . ", 60, now()),
            ('Sidebox Header Background Color', 'ZCA_SIDEBOX_HEADER_BACKGROUND_COLOR', '#333333', 'Default:#333333', " . $bccid . ", 61, now()),
            ('Sidebox Header Text Color', 'ZCA_SIDEBOX_HEADER_TEXT_COLOR', '#ffffff', 'Default:#ffffff', " . $bccid . ", 62, now()),
            ('Sidebox Header Link Color', 'ZCA_SIDEBOX_HEADER_LINK_COLOR', '#ffffff', 'Default:#ffffff', " . $bccid . ", 63, now()),
            ('Sidebox Header Link Color on Hover', 'ZCA_SIDEBOX_HEADER_LINK_COLOR_HOVER', '#cccccc', 'Default:#cccccc', " . $bccid . ", 64, now()),
            ('<b>Sidebox Category Counts Color</b>', 'ZCA_SIDEBOX_COUNTS_COLOR', '#ffffff', 'Default:#ffffff', " . $bccid . ", 65, now()),
            ('<b>Sidebox Category Counts Background Color</b>', 'ZCA_SIDEBOX_COUNTS_BACKGROUND_COLOR', '#33b5e5', 'Default:33b5e5', " . $bccid . ", 66, now())"
    );

    /* ZCA Bootstrap Template CENTERBOXES Colors */
    $db->Execute(
        "INSERT INTO " . TABLE_CONFIGURATION . "
            (configuration_title, configuration_key, configuration_value, configuration_description, configuration_group_id, sort_order, date_added)
         VALUES
            ('Centerbox Background Color', 'ZCA_CENTERBOX_BACKGROUND_COLOR', '#ffffff', 'Default:#ffffff', " . $bccid . ", 67, now()),
            ('Centerbox Text Color', 'ZCA_CENTERBOX_TEXT_COLOR', '#000000', 'Default:#000000', " . $bccid . ", 68, now()),
            ('<b>Centerbox Product Card Background Color</b>', 'ZCA_CENTERBOX_CARD_BACKGROUND_COLOR', '#ffffff', 'Default:#ffffff', " . $bccid . ", 69, now()),
            ('<b>Centerbox Product Card Background Color on Hover</b>', 'ZCA_CENTERBOX_CARD_BACKGROUND_COLOR_HOVER', '#efefef', 'Default:#efefef', " . $bccid . ", 70, now()),
            ('Centerbox Header Background Color', 'ZCA_CENTERBOX_HEADER_BACKGROUND_COLOR', '#333333', 'Default:#333333', " . $bccid . ", 71, now()),
            ('Centerbox Header Text Color', 'ZCA_CENTERBOX_HEADER_TEXT_COLOR', '#ffffff', 'Default:#ffffff', " . $bccid . ", 72, now())"
    );

    $messageStack->add('ZCA Bootstrap Colors install completed!', 'success');

    //-- Admin Menu for Wishlist Tools Menu
    zen_deregister_admin_pages('toolsZCABootstrapColors');
    zen_register_admin_page('toolsZCABootstrapColors', 'BOX_TOOLS_ZCA_BOOTSTRAP_COLORS', 'FILENAME_ZCA_BOOTSTRAP_COLORS', '', 'tools', 'Y');
}

// -----
// Additional selections added for Bootstrap 3.1.2
//
$zca_colors_updated = false;
if (!defined('ZCA_ADD_TO_CART_BACKGROUND_COLOR')) {
    $db->Execute(
        "INSERT INTO " . TABLE_CONFIGURATION . "
            (configuration_title, configuration_key, configuration_value, configuration_description, configuration_group_id, sort_order, date_added)
         VALUES
            ('Add-to-Cart Card Background Color', 'ZCA_ADD_TO_CART_BACKGROUND_COLOR', '#00C851', 'Default: #00C851', " . $bccid . ", 100, now()),
            ('Add-to-Cart Card Text Color', 'ZCA_ADD_TO_CART_TEXT_COLOR', '#fff', 'Default: #fff', " . $bccid . ", 101, now()),
            ('Add-to-Cart Card Border Color', 'ZCA_ADD_TO_CART_BORDER_COLOR', '#00C851', 'Default: #00C851', " . $bccid . ", 102, now())"
    );
    $zca_colors_updated = true;
}
if (!defined('ZCA_BUTTON_IN_CART_BACKGROUND_COLOR')) {
    $db->Execute(
        "INSERT INTO " . TABLE_CONFIGURATION . "
            (configuration_title, configuration_key, configuration_value, configuration_description, configuration_group_id, sort_order, date_added)
         VALUES
            ('Add-to-Cart Button Background Color', 'ZCA_BUTTON_IN_CART_BACKGROUND_COLOR', '#00C851', 'Default: #00C851', " . $bccid . ", 120, now()),
            ('Add-to-Cart Button Text Color', 'ZCA_BUTTON_IN_CART_TEXT_COLOR', '#fff', 'Default: #fff', " . $bccid . ", 121, now()),
            ('Add-to-Cart Button Background Color on Hover', 'ZCA_BUTTON_IN_CART_BACKGROUND_COLOR_HOVER', '#007E33', 'Default: #007E33', " . $bccid . ", 122, now()),
            ('Add-to-Cart Button Text Color on Hover', 'ZCA_BUTTON_IN_CART_TEXT_COLOR_HOVER', '#fff', 'Default: #fff', " . $bccid . ", 123, now()),
            ('Add-Selected Button Background Color', 'ZCA_BUTTON_ADD_SELECTED_BACKGROUND_COLOR', '#00C851', 'Default: #00C851', " . $bccid . ", 120, now()),
            ('Add-Selected Button Text Color', 'ZCA_BUTTON_ADD_SELECTED_TEXT_COLOR', '#fff', 'Default: #fff', " . $bccid . ", 121, now()),
            ('Add-Selected Button Background Color on Hover', 'ZCA_BUTTON_ADD_SELECTED_BACKGROUND_COLOR_HOVER', '#007E33', 'Default: #007E33', " . $bccid . ", 122, now()),
            ('Add-Selected Button Text Color on Hover', 'ZCA_BUTTON_ADD_SELECTED_TEXT_COLOR_HOVER', '#fff', 'Default: #fff', " . $bccid . ", 123, now())"
    );
    $zca_colors_updated = true;
}

// -----
// Correcting duplication of sort-orders in update applied above.
//
$bc_check = $db->ExecuteNoCache(
    "SELECT sort_order
       FROM " . TABLE_CONFIGURATION . "
      WHERE configuration_key = 'ZCA_BUTTON_ADD_SELECTED_BACKGROUND_COLOR'
        AND sort_order = 120
      LIMIT 1"
);
if (!$bc_check->EOF) {
    $db->Execute("UPDATE " . TABLE_CONFIGURATION . " SET sort_order = 130 WHERE configuration_key = 'ZCA_BUTTON_ADD_SELECTED_BACKGROUND_COLOR' LIMIT 1");
    $db->Execute("UPDATE " . TABLE_CONFIGURATION . " SET sort_order = 131 WHERE configuration_key = 'ZCA_BUTTON_ADD_SELECTED_TEXT_COLOR' LIMIT 1");
    $db->Execute("UPDATE " . TABLE_CONFIGURATION . " SET sort_order = 132 WHERE configuration_key = 'ZCA_BUTTON_ADD_SELECTED_BACKGROUND_COLOR_HOVER' LIMIT 1");
    $db->Execute("UPDATE " . TABLE_CONFIGURATION . " SET sort_order = 133 WHERE configuration_key = 'ZCA_BUTTON_ADD_SELECTED_TEXT_COLOR_HOVER' LIMIT 1");
}

if ($zca_colors_updated) {
    $messageStack->add('Additional ZCA Bootstrap Colors successfully added.', 'success');
}
