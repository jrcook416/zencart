<?php
// -----
// Initial configuration initialization for the ZCAdditions' bootstrap template.  Required by
// init_zca_bootstrap_template_admin if the current template version isn't yet registered.
//
// The $cgi value contains the configuration_group_id associated with the template's configuration.
// settings.
//
// If a version of the template (pre-v5.2.0) is already installed, move the configuration settings
// from the "Layout Settings" and "Product Info" configuration groups into the consolidated
// template-settings' group.  Otherwise, create those base configuration settings.
//
// BOOTSTRAP v3.6.3
//
// -----
// Start by inserting any of the 'base' configuration settings that are not yet present.
//
$db->Execute(
    "INSERT IGNORE INTO " . TABLE_CONFIGURATION . "
        (configuration_title, configuration_key, configuration_value, configuration_description, configuration_group_id, date_added, sort_order, use_function, set_function)
     VALUES
        ('Bootstrap Template Version', 'ZCA_BOOTSTRAP_VERSION', '0.0.0', 'Displays the template\'s current version.', $cgi, now(), 0, NULL, 'zen_cfg_read_only('),

        ('Responsive Left Column Width', 'SET_COLUMN_LEFT_LAYOUT', '3', 'Set Width of Left Column<br>Default is <b>3</b>, Total columns <b>12</b>.<br>Responsive Left, Center & Right Column Width must sum to 12', $cgi, now(), 200, NULL, 'zen_cfg_select_option([\'0\', \'1\', \'2\', \'3\', \'4\', \'5\', \'6\', \'7\', \'8\', \'9\', \'10\', \'11\', \'12\'],'),

        ('Responsive Center Column Width', 'SET_COLUMN_CENTER_LAYOUT', '6', 'Set Width of Center Column<br>Default is <b>6</b>, Total columns <b>12</b>.<br>Responsive Left, Center & Right Column Width must sum to 12', $cgi, now(), 201, NULL, 'zen_cfg_select_option([\'0\', \'1\', \'2\', \'3\', \'4\', \'5\', \'6\', \'7\', \'8\', \'9\', \'10\', \'11\', \'12\'],'),

        ('Responsive Right Column Width', 'SET_COLUMN_RIGHT_LAYOUT', '3', 'Set Width of Right Column<br>Default is <b>3</b>, Total columns <b>12</b>.<br>Responsive Left, Center & Right Column Width must sum to 12', $cgi, now(), 202, NULL, 'zen_cfg_select_option([\'0\', \'1\', \'2\', \'3\', \'4\', \'5\', \'6\', \'7\', \'8\', \'9\', \'10\', \'11\', \'12\'],'),

        ('<i>Bootstrap Banner Display</i> - Enable Header Position 1 Carousel Feature', 'ZCA_ACTIVATE_BANNER_ONE_CAROUSEL', 'false', 'Enable the Header Position 1 Banner Carousel.', $cgi, now(), 213, NULL, 'zen_cfg_select_option([\'true\', \'false\'],'),

        ('<i>Bootstrap Banner Display</i> - Enable Header Position 2 Carousel Feature', 'ZCA_ACTIVATE_BANNER_TWO_CAROUSEL', 'false', 'Enable the Header Position 2 Banner Carousel.', $cgi, now(), 214, NULL, 'zen_cfg_select_option([\'true\', \'false\'],'),

        ('<i>Bootstrap Banner Display</i> - Enable Header Position 3 Carousel Feature', 'ZCA_ACTIVATE_BANNER_THREE_CAROUSEL', 'false', 'Enable the Header Position 3 Banner Carousel.', $cgi, now(), 215, NULL, 'zen_cfg_select_option([\'true\', \'false\'],'),

        ('<i>Bootstrap Banner Display</i> - Enable Footer Position 1 Carousel Feature', 'ZCA_ACTIVATE_BANNER_FOUR_CAROUSEL', 'false', 'Enable the Footer Position 1 Banner Carousel.', $cgi, now(), 216, NULL, 'zen_cfg_select_option([\'true\', \'false\'],'),

        ('<i>Bootstrap Banner Display</i> - Enable Footer Position 2 Carousel Feature', 'ZCA_ACTIVATE_BANNER_FIVE_CAROUSEL', 'false', 'Enable the Footer Position 2 Banner Carousel.', $cgi, now(), 217, NULL, 'zen_cfg_select_option([\'true\', \'false\'],'),

        ('<i>Bootstrap Banner Display</i> - Enable Footer Position 3 Carousel Feature', 'ZCA_ACTIVATE_BANNER_SIX_CAROUSEL', 'false', 'Enable the Footer Position 3 Banner Carousel.', $cgi, now(), 218, NULL, 'zen_cfg_select_option([\'true\', \'false\'],'),

        ('Enable <em>Bootstrap</em> Modal Image Popups', 'PRODUCT_INFO_SHOW_BOOTSTRAP_MODAL_POPUPS', 'Yes', 'Should the ZCA <code>bootstrap</code> template display pop-up product images using its <em>modal</em> dialog? If your store uses an image-display plugin (like <b>Zen ColorBox</b>), set this value to <em>No</em>. Default: <b>Yes</b>', $cgi, now(), 300, NULL, 'zen_cfg_select_option([\'No\', \'Yes\'],'),

        ('Use Bootstrap Additional Image Carousel', 'PRODUCT_INFO_SHOW_BOOTSTRAP_MODAL_SLIDE', '0', 'Default is <b>0</b>, Opens images in an individual modal, <b>1</b> opens images in a single modal with carousel.', $cgi, now(), 301, NULL, 'zen_cfg_select_option([\'0\', \'1\'],'),

        ('Display the Manufacturer Box on Product Pages', 'PRODUCT_INFO_SHOW_MANUFACTURER_BOX', '1', 'Used by the ZCA Bootstrap template.  Default is <b>1</b>, Displays on Info Page, <b>0</b> Does not Display.', $cgi, now(), 302, NULL, 'zen_cfg_select_option([\'0\', \'1\'],'),

        ('Display the Notifications Box on Product Pages', 'PRODUCT_INFO_SHOW_NOTIFICATIONS_BOX', '1', 'Used by the ZCA Bootstrap template. Default is <b>1</b>, Displays on Info Page, <b>0</b> Does not Display.', $cgi, now(), 303, NULL, 'zen_cfg_select_option([\'0\', \'1\'],')"
);

// -----
// Set the previously-recorded template version for the remainder of the
// install/upgrade processing.
//
define('ZCA_BOOTSTRAP_VERSION', '0.0.0');

// -----
// If this was an upgrade from an older version, need to also fix-up the sort-orders
// of various template configuration settings.
//
$bootstrap_settings = [
    'SET_COLUMN_LEFT_LAYOUT' => 200,
    'SET_COLUMN_CENTER_LAYOUT' => 201,
    'SET_COLUMN_RIGHT_LAYOUT' => 202,
    'ZCA_ACTIVATE_BANNER_ONE_CAROUSEL' => 213,
    'ZCA_ACTIVATE_BANNER_TWO_CAROUSEL' => 214,
    'ZCA_ACTIVATE_BANNER_THREE_CAROUSEL' => 215,
    'ZCA_ACTIVATE_BANNER_FOUR_CAROUSEL' => 216,
    'ZCA_ACTIVATE_BANNER_FIVE_CAROUSEL' => 217,
    'ZCA_ACTIVATE_BANNER_SIX_CAROUSEL' => 218,
    'PRODUCT_INFO_SHOW_BOOTSTRAP_MODAL_POPUPS' => 300,
    'PRODUCT_INFO_SHOW_BOOTSTRAP_MODAL_SLIDE' => 301,
    'PRODUCT_INFO_SHOW_MANUFACTURER_BOX' => 302,
    'PRODUCT_INFO_SHOW_NOTIFICATIONS_BOX' => 303,
];
foreach ($bootstrap_settings as $key => $sort_order) {
    $db->Execute(
        "UPDATE " . TABLE_CONFIGURATION . "
            SET configuration_group_id = $cgi,
                sort_order = $sort_order
          WHERE configuration_key = '$key'
          LIMIT 1"
    );
}
