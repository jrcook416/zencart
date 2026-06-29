<?php
/**
 * Page Template
 *
 * Loaded automatically by index.php?main_page=create_account.
 * Displays Create Account form.
 *
 * @copyright Copyright 2003-2022 Zen Cart Development Team
 * @copyright Portions Copyright 2003 osCommerce
 * @license http://www.zen-cart.com/license/2_0.txt GNU Public License V2.0
 * @version $Id: DrByte 2021 Jun 14 Modified in v1.5.8-alpha $
 * Edited for the IEMS Zencart 3.0.0 template 2026-06-27 Jeremiah Cook
 */
?>

<?php if ($messageStack->size('create_account') > 0) echo $messageStack->output('create_account'); ?>
<div class="alert forward"><?php echo FORM_REQUIRED_INFORMATION; ?></div>
<br class="clearBoth">

<?php
  if (zen_config('DISPLAY_PRIVACY_CONDITIONS') === 'true') {
?>
<fieldset>
<legend><?php echo TABLE_HEADING_PRIVACY_CONDITIONS; ?></legend>
<div class="information"><?php echo TEXT_PRIVACY_CONDITIONS_DESCRIPTION;?></div>
<?php echo zen_draw_checkbox_field('privacy_conditions', '1', false, 'id="privacy"');?>
<label class="checkboxLabel" for="privacy"><?php echo TEXT_PRIVACY_CONDITIONS_CONFIRM;?></label>
</fieldset>
<?php
  }
/**
MODIFICATION - Adding an Ajax handler here to take care of changes in county and unit script.
**/
?>
<script>
jQuery(document).ready(function($) {
    alert("AJAX loaded.");
    // Listen for the change event on the county dropdown
    $('#county').on('change', function() {
        var countyId = $(this).val();
        alert("County ID is reported as " + countyId);
        
        // Instantly reset the unit dropdown to a loading state
        $('#unit').html('<option value="">Loading units...</option>');

        if (countyId !== '') {
            // Trigger Zen Cart AJAX request to our custom page handler
            $.ajax({
                url: 'index.php?main_page=get_units_ajax',
                type: 'POST',
                data: { 
                    county_id: countyId,
                    securityToken: '<?php echo $_SESSION['securityToken']; ?>' // Standard security precaution
                },
                dataType: 'json',
                success: function(response) {
                    var options = '<option value="">Select a Unit</option>';
                    
                    if (response.success && response.data.length > 0) {
                        // Loop through returned JSON objects and build HTML strings
                        $.each(response.data, function(index, unit) {
                            options += '<option value="' + unit.id + '">' + unit.name + '</option>';
                        });
                    } else {
                        options = '<option value="">No units found for this county</option>';
                    }
                    $('#unit').html(options);
                },
                error: function() {
                    $('#unit').html('<option value="">Error retrieving units</option>');
                }
            });
        } else {
            // Reset to default if no county is chosen
            $('#unit').html('<option value="">Select a Unit</option>');
        }
    });
});
</script>

<?php
/**
MODIFICATION - County and Unit identification are moving here in the IEMS specific template for work downstream.
Removing the if statement for the Unit box to enable the code globally. 
**/
/**
IEMS Edited Code Block -- Beginning
**/      
unit_lookup();
county_lookup();
    ?>
<label class="inputLabel" for="county"><?php echo ENTRY_COUNTY; ?></label>
<?php 
echo zen_draw_pull_down_menu('county', $county_array, 'entry_county', 'id="county"', 'required');?>
<br class="clearBoth"> 
<label class="inputLabel" for="unit"><?php echo ENTRY_UNIT; ?></label>
<?php
echo zen_draw_pull_down_menu('unit', '', 'entry_unit','id="unit"', 'required');
/**
IEMS Edited Code Block -- Ending
END OF MODIFICATION
**/
?>
<br class="clearBoth">
<?php
  if (zen_config('ACCOUNT_COMPANY') === 'true') {
?>
<fieldset>
<legend><?php echo CATEGORY_COMPANY; ?></legend>
<label class="inputLabel" for="company"><?php echo ENTRY_COMPANY; ?></label>
<?php echo zen_draw_input_field('company', '', zen_set_field_length(TABLE_ADDRESS_BOOK, 'entry_company', '40') . ' id="company" autocomplete="organization" placeholder="' . ENTRY_COMPANY_TEXT . '"'. (zen_config('ACCOUNT_COMPANY') === 'true' && (int)zen_config('ENTRY_COMPANY_MIN_LENGTH') != 0 ? ' required' : '')); ?>
</fieldset>
<?php
  }
?>

<fieldset>
<legend><?php echo TABLE_HEADING_ADDRESS_DETAILS; ?></legend>
<?php
  if (zen_config('ACCOUNT_GENDER') === 'true') {
?>
<?php echo zen_draw_radio_field('gender', 'm', '', 'id="gender-male"') . '<label class="radioButtonLabel" for="gender-male">' . MALE . '</label>' . zen_draw_radio_field('gender', 'f', '', 'id="gender-female"') . '<label class="radioButtonLabel" for="gender-female">' . FEMALE . '</label>' . (!empty(ENTRY_GENDER_TEXT) ? '<span class="alert">' . ENTRY_GENDER_TEXT . '</span>': ''); ?>
<br class="clearBoth">
<?php
  }
?>

<label class="inputLabel" for="firstname"><?php echo ENTRY_FIRST_NAME; ?></label>
<?php echo zen_draw_input_field('firstname', '', zen_set_field_length(TABLE_CUSTOMERS, 'customers_firstname', '40') . ' id="firstname" placeholder="' . ENTRY_FIRST_NAME_TEXT . '"' . ((int)zen_config('ENTRY_FIRST_NAME_MIN_LENGTH') > 0 ? ' required' : '')); ?>
<br class="clearBoth">

<label class="inputLabel" for="lastname"><?php echo ENTRY_LAST_NAME; ?></label>
<?php echo zen_draw_input_field('lastname', '', zen_set_field_length(TABLE_CUSTOMERS, 'customers_lastname', '40') . ' id="lastname" placeholder="' . ENTRY_LAST_NAME_TEXT . '"'. ((int)zen_config('ENTRY_LAST_NAME_MIN_LENGTH') > 0 ? ' required' : '')); ?>
<br class="clearBoth">
<label class="inputLabel" for="country"><?php echo ENTRY_COUNTRY; ?></label>
<?php echo zen_get_country_list('zone_country_id', $selected_country, 'id="country" disabled="disabled" ' . ($flag_show_pulldown_states == true ? 'onchange="update_zone(this.form);"' : '')) . (!empty(ENTRY_COUNTRY_TEXT) ? '<span class="alert">' . ENTRY_COUNTRY_TEXT . '</span>' : ''); ?>
<br class="clearBoth">
<br>

<label class="inputLabel" for="street-address"><?php echo ENTRY_STREET_ADDRESS; ?></label>
  <?php echo zen_draw_input_field('street_address', '', zen_set_field_length(TABLE_ADDRESS_BOOK, 'entry_street_address', '40') . ' id="street-address" disabled="disabled" placeholder="' . ENTRY_STREET_ADDRESS_TEXT . '"'. ((int)zen_config('ENTRY_STREET_ADDRESS_MIN_LENGTH') > 0 ? 'disabled' : '')); ?>
<br class="clearBoth">

<?php echo zen_draw_input_field($antiSpamFieldName, '', ' size="40" id="CAAS" style="visibility:hidden; display:none;" autocomplete="off"'); ?>
  
<label class="inputLabel" for="city"><?php echo ENTRY_CITY; ?></label>
<?php echo zen_draw_input_field('city', '', zen_set_field_length(TABLE_ADDRESS_BOOK, 'entry_city', '40') . ' id="city" disabled="disabled" placeholder="' . ENTRY_CITY_TEXT . '"'. ((int)zen_config('ENTRY_CITY_MIN_LENGTH') > 0 ? ' disabled' : '')); ?>
<br class="clearBoth">

<?php
  if (zen_config('ACCOUNT_STATE') === 'true') {
    if ($flag_show_pulldown_states == true) {
?>
<label class="inputLabel" for="stateZone" id="zoneLabel"><?php echo ENTRY_STATE; ?></label>
<?php
      echo zen_draw_pull_down_menu('zone_id', zen_prepare_country_zones_pull_down($selected_country), $zone_id, 'id="stateZone" disabled="disabled"', '');
      echo '<span class="alert">' . ((!empty(ENTRY_STATE_TEXT) && (int)zen_config('ENTRY_STATE_MIN_LENGTH') > 0) ? ENTRY_STATE_TEXT : 'disabled') . '</span>';
    }
?>

<?php if ($flag_show_pulldown_states == true) { ?>
<br class="clearBoth" id="stBreak">
<?php } ?>
<label class="inputLabel" for="state" id="stateLabel"><?php echo $state_field_label; ?></label>
<?php
    echo zen_draw_input_field('state', '', zen_set_field_length(TABLE_ADDRESS_BOOK, 'entry_state', '40') . ' id="state" disabled="disabled"' . ((int)zen_config('ENTRY_STATE_MIN_LENGTH') > 0 ? ' placeholder="' . ENTRY_STATE_TEXT . '"' : 'disabled'));
    if ($flag_show_pulldown_states == false) {
      echo zen_draw_hidden_field('zone_id', $zone_name, ' ');
    }
?>
<br class="clearBoth">
<?php
  }
?>

<label class="inputLabel" for="postcode"><?php echo ENTRY_POST_CODE; ?></label>
<?php echo zen_draw_input_field('postcode', '', zen_set_field_length(TABLE_ADDRESS_BOOK, 'entry_postcode', '40') . ' id="postcode" disabled="disabled" placeholder="' . ENTRY_POST_CODE_TEXT . '"' . ((int)zen_config('ENTRY_POSTCODE_MIN_LENGTH') > 0 ? ' disabled' : '')
); ?>
<br class="clearBoth">

</fieldset>

<fieldset>
<legend><?php echo TABLE_HEADING_PHONE_FAX_DETAILS; ?></legend>
<label class="inputLabel" for="telephone"><?php echo ENTRY_TELEPHONE_NUMBER; ?></label>
<?php echo zen_draw_input_field('telephone', '', zen_set_field_length(TABLE_CUSTOMERS, 'customers_telephone', '40') . ' id="telephone" placeholder="' . ENTRY_TELEPHONE_NUMBER_TEXT . '"' . ((int)zen_config('ENTRY_TELEPHONE_MIN_LENGTH') > 0 ? ' required' : ''), 'tel'); ?>

<?php
  if (zen_config('ACCOUNT_FAX_NUMBER') === 'true') {
?>
<br class="clearBoth">
<label class="inputLabel" for="fax"><?php echo ENTRY_FAX_NUMBER; ?></label>
<?php echo zen_draw_input_field('fax', '', zen_set_field_length(TABLE_CUSTOMERS, 'customers_fax', '32') . ' id="fax" disabled="disabled" placeholder="' . ENTRY_FAX_NUMBER_TEXT . '"', 'tel'); ?>
<?php
  }
?>
</fieldset>

<?php
  if (zen_config('ACCOUNT_DOB') === 'true') {
?>
<fieldset>
<legend><?php echo TABLE_HEADING_DATE_OF_BIRTH; ?></legend>
<label class="inputLabel" for="dob"><?php echo ENTRY_DATE_OF_BIRTH; ?></label>
<?php echo zen_draw_input_field('dob','', zen_set_field_length(TABLE_CUSTOMERS, 'customers_dob', '20') . ' id="dob" disabled="disabled" placeholder="' . ENTRY_DATE_OF_BIRTH_TEXT . '"' . (zen_config('ACCOUNT_DOB') === 'true' && (int)zen_config('ENTRY_DOB_MIN_LENGTH') != 0 ? ' required' : '')); ?>
<br class="clearBoth">
</fieldset>
<?php
  }
?>

<fieldset>
<legend><?php echo TABLE_HEADING_LOGIN_DETAILS; ?></legend>
<label class="inputLabel" for="email-address"><?php echo ENTRY_EMAIL_ADDRESS; ?></label>
<?php echo zen_draw_input_field('email_address', '', zen_set_field_length(TABLE_CUSTOMERS, 'customers_email_address', '40') . ' id="email-address" autocomplete="username" placeholder="' . ENTRY_EMAIL_ADDRESS_TEXT . '"' . ((int)zen_config('ENTRY_EMAIL_ADDRESS_MIN_LENGTH') > 0 ? ' required' : ''), 'email'); ?>
<br class="clearBoth">

<?php
  if ($display_nick_field == true) {
?>
<label class="inputLabel" for="nickname"><?php echo ENTRY_NICK; ?></label>
<?php echo zen_draw_input_field('nick','', zen_set_field_length(TABLE_CUSTOMERS, 'customers_nick', '32') . ' id="nickname" disabled="disabled" placeholder="' . ENTRY_NICK_TEXT . '"'); ?>
<br class="clearBoth">
<?php
  }
?>

<label class="inputLabel" for="password-new"><?php echo ENTRY_PASSWORD; ?></label>
<?php echo zen_draw_password_field('password', '', zen_set_field_length(TABLE_CUSTOMERS, 'customers_password', '20') . ' id="password-new" autocomplete="new-password" placeholder="' . ENTRY_PASSWORD_TEXT . '"'. ((int)zen_config('ENTRY_PASSWORD_MIN_LENGTH') > 0 ? ' required' : '')); ?>
<br class="clearBoth">

<label class="inputLabel" for="password-confirm"><?php echo ENTRY_PASSWORD_CONFIRMATION; ?></label>
<?php echo zen_draw_password_field('confirmation', '', zen_set_field_length(TABLE_CUSTOMERS, 'customers_password', '20') . ' id="password-confirm" autocomplete="new-password" placeholder="' . ENTRY_PASSWORD_CONFIRMATION_TEXT . '"'. ((int)zen_config('ENTRY_PASSWORD_MIN_LENGTH') > 0 ? ' required' : '')); ?>
<br class="clearBoth">
</fieldset>

<fieldset>
<legend><?php echo ENTRY_EMAIL_PREFERENCE; ?></legend>
<?php
  if ((int)zen_config('ACCOUNT_NEWSLETTER_STATUS') != 0) {
?>
<?php echo zen_draw_checkbox_field('newsletter', '1', $newsletter, 'id="newsletter-checkbox" disabled="disabled"') . '<label class="checkboxLabel" for="newsletter-checkbox">' . ENTRY_NEWSLETTER . '</label>' . (!empty(ENTRY_NEWSLETTER_TEXT) ? '<span class="alert">' . ENTRY_NEWSLETTER_TEXT . '</span>': ''); ?>
<br class="clearBoth">
<?php } ?>

<?php echo zen_draw_radio_field('email_format', 'HTML', ($email_format == 'HTML' ? true : false),'id="email-format-html" disabled="disabled"') . '<label class="radioButtonLabel" for="email-format-html">' . ENTRY_EMAIL_HTML_DISPLAY . '</label>' .  zen_draw_radio_field('email_format', 'TEXT', ($email_format == 'TEXT' ? true : false), 'id="email-format-text"') . '<label class="radioButtonLabel" for="email-format-text">' . ENTRY_EMAIL_TEXT_DISPLAY . '</label>'; ?>
<br class="clearBoth">
</fieldset>

<?php
  if ((int)zen_config('CUSTOMERS_REFERRAL_STATUS') === 2) {
?>
<fieldset>

<legend><?php echo TABLE_HEADING_REFERRAL_DETAILS; ?></legend>
<label class="inputLabel" for="customers_referral"><?php echo ENTRY_CUSTOMERS_REFERRAL; ?></label>
<?php echo zen_draw_input_field('customers_referral', '', zen_set_field_length(TABLE_CUSTOMERS, 'customers_referral', '15') . ' id="customers_referral" disabled="disabled"'); ?>
<br class="clearBoth">
</fieldset>
<?php } ?>
