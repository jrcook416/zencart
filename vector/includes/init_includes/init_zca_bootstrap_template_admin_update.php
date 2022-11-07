<?php
// -----
// Configuration update for the ZCAdditions' bootstrap template.  Required by
// init_zca_bootstrap_template_admin if the current template version isn't yet registered.
//
// The $cgi value contains the configuration_group_id associated with the template's configuration.
// settings.
//
// Bootstrap v3.2.0.
//
switch (true) {
    // -----
    // v3.2.0: Add settings for the Bootstrap template's AJAX search feature.  Update
    // description for 'Product Listing :: Columns Per Row' to indicate the preferred
    // values for the Bootstrap template.
    //
    case version_compare(ZCA_BOOTSTRAP_VERSION, '3.2.0', '<'):
        $db->Execute(
            "INSERT IGNORE INTO " . TABLE_CONFIGURATION . "
                (configuration_title, configuration_key, configuration_value, configuration_description, configuration_group_id, date_added, sort_order, use_function, set_function)
             VALUES
                ('Enable AJAX Search?', 'BS4_AJAX_SEARCH_ENABLE', 'false', 'Enable the template\'s AJAX search feature?', $cgi, now(), 1000, NULL, 'zen_cfg_select_option(array(\'true\', \'false\'),'),

                ('AJAX Search: Max Results', 'BS4_AJAX_SEARCH_RESULTS_PER_PAGE', '8', 'Identify the number of matching products to display in the AJAX search modal display.  Default: <b>8</b>.', $cgi, now(), 1005, NULL, NULL),

                ('AJAX Search: Image Width', 'BS4_AJAX_SEARCH_IMAGE_WIDTH', '50', 'Identify the width of a product\'s image displayed in the AJAX search modal.  Default: <b>50</b>.', $cgi, now(), 1010, NULL, NULL),

                ('AJAX Search: Image Height', 'BS4_AJAX_SEARCH_IMAGE_HEIGHT', '50', 'Identify the height of a product\'s image displayed in the AJAX search modal.  Default: <b>50</b>.', $cgi, now(), 1011, NULL, NULL),

                ('AJAX Search: Use minified script?', 'BS4_AJAX_SEARCH_USE_MINIMIZED_SCRIPT', 'true', 'Use the minimized version of the AJAX search script?', $cgi, now(), 1020, NULL, 'zen_cfg_select_option(array(\'true\', \'false\'),')"
        );
        $db->Execute(
            "UPDATE " . TABLE_CONFIGURATION . "
                SET configuration_description = 'Select the number of columns of products to show per row in the product listing.<br>Recommended: 3<br>1=[rows] mode.<br><br>For the <code>bootstrap</code> template, use 0 (fluid columns) or 1 (rows).<br>'
              WHERE configuration_key = 'PRODUCT_LISTING_COLUMNS_PER_ROW'
              LIMIT 1"
        );
    default:                                                    //- Fall through from above
        break;
}

$db->Execute(
    "UPDATE " . TABLE_CONFIGURATION . "
        SET configuration_value = '" . ZCA_BOOTSTRAP_CURRENT_VERSION . "',
            last_modified = now()
      WHERE configuration_key = 'ZCA_BOOTSTRAP_VERSION'
      LIMIT 1"
);

if (!zen_page_key_exists('configBootstrapTemplate')) {
    zen_register_admin_page('configBootstrapTemplate', 'BOX_ZCA_BOOTSTRAP', 'FILENAME_CONFIGURATION', "gID=$cgi", 'configuration', 'Y');
}
