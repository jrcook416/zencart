<?php
/**
 * jscript_addr_pulldowns_zca_bootstrap
 *
 * BOOTSTRAP v3.4.0
 *
 * handles pulldown menu dependencies for state/country selection
 *
 * @package page
 * @copyright Copyright 2003-2018 Zen Cart Development Team
 * @copyright Portions Copyright 2003 osCommerce
 * @license http://www.zen-cart.com/license/2_0.txt GNU Public License V2.0
 * @version $Id: Scott C Wilson Sat Oct 27 01:31:20 2018 -0400 Modified in v1.5.6 $
 */
// -----
// zc158 introduces a common jQuery handler for the dropdown states' selection based
// on the country chosen.  When running on a zc158 (or later) 'core', use that handler instead
// of the legacy one provided by the Bootstrap template for previous Zen Cart versions.
//
if (zen_get_zcversion() >= '1.5.8') {
    return;
}

$zca_address_pages = [
    FILENAME_CREATE_ACCOUNT,
    FILENAME_LOGIN,
    FILENAME_SHOPPING_CART,
    FILENAME_CHECKOUT_PAYMENT_ADDRESS,
    FILENAME_CHECKOUT_SHIPPING_ADDRESS,
    FILENAME_ADDRESS_BOOK_PROCESS,
];
if (defined('FILENAME_NO_ACCOUNT')) {
   $zca_address_pages[] = FILENAME_NO_ACCOUNT;
}
if (ACCOUNT_STATE !== 'true' || ACCOUNT_STATE_DRAW_INITIAL_DROPDOWN !== 'true' || !isset($_GET['main_page']) || !in_array($_GET['main_page'], $zca_address_pages)) {
    return;
}
?>
<script>
jQuery(window).on('load', function() {
    <?php echo zca_js_zone_list('c2z'); ?>
    var textPleaseSelect = '<?php echo BOOTSTRAP_PLEASE_SELECT; ?>';

    // -----
    // Initialize the display for the dropdown vs. hand-entry of the state fields.  If the initially-selected
    // country doesn't have zones, the dropdown will contain only 1 element ('Type a choice below ...').
    //
    initializeStateZones = function() 
    {
        if (jQuery('#stateZone > option').length == 1) {
            jQuery('#stateZone, #zoneLabel, #zoneLabel+span, #stBreak').hide();
        } else {
            jQuery('#state, #stBreak, #stateLabel').hide();
            jQuery('#stateZone, #zoneLabel, #zoneLabel+span').show();
        }
    }
    initializeStateZones();

    // -----
    // Monitor the address block for changes to the selected country.
    //
    jQuery(document).on('change', '#country', function(event) {
        updateCountryZones(jQuery('#country option:selected').val());
    });

    // -----
    // This function provides the processing needed when a country has been changed.  It makes
    // use of the c2z (countries-to-zones) array, built and provided by the jscript_main.php's
    // processing.  The value for "textPleaseSelect" is set there, too.
    //
    // Note: When the selected country has zones, the 'state' input is reset to an empty
    // string.  Otherwise, that previous value is displayed as part of the current selection!
    //
    function updateCountryZones(selected_country)
    {
        var countryHasZones = false;
        var countryZones = '<option selected="selected" value="0">' + textPleaseSelect + '</option>';
        jQuery.each(JSON.parse(c2z), function(country_id, country_zones) {
            if (selected_country == country_id) {
                countryHasZones = true;
                jQuery.each(country_zones, function(zone_id, zone_name) {
                    countryZones += "<option value='" + zone_id + "'>" + zone_name + "</option>";
                });
            }
        });
        jQuery('#state').val('');
        if (countryHasZones) {
            jQuery('#stateZone').html(countryZones);
            jQuery('#stateZone, #zoneLabel, #zoneLabel+span').show();
            jQuery('#state, #stBreak, #stateLabel').hide();
        } else {
            jQuery('#state').removeAttr('disabled').addClass('form-control');
            jQuery('#stateZone, #zoneLabel, #zoneLabel+span').hide();
            jQuery('#state, #stateLabel').show();
        }
    }
});
</script>
