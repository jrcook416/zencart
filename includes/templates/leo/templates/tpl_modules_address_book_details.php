<?php
/**
 * Module Template
 *
 * Displays address-book details/selection
 *
 * @copyright Copyright 2003-2022 Zen Cart Development Team
 * @copyright Portions Copyright 2003 osCommerce
 * @license http://www.zen-cart.com/license/2_0.txt GNU Public License V2.0
 * @version $Id: DrByte 2021 Jun 14 Modified in v1.5.8-alpha $
 */
?>
<?php
// IEMS: a new entry starts from the customer's name and the store's default
// delivery address (Configuration > IEMS Legacy Bridge), as in 1.5.6c.
if (!isset($entry) || !is_object($entry)) {
    $entry = new stdClass();
}
if (!isset($entry->fields) || !is_array($entry->fields)) {
    $entry->fields = [];
}
$iemsDefaults = [
    'entry_firstname' => $_SESSION['customer_first_name'] ?? '',
    'entry_lastname' => $_SESSION['customer_last_name'] ?? '',
    'entry_company' => defined('IEMS_LEGACY_DEFAULT_COMPANY') ? IEMS_LEGACY_DEFAULT_COMPANY : '',
    'entry_street_address' => defined('IEMS_LEGACY_DEFAULT_STREET') ? IEMS_LEGACY_DEFAULT_STREET : '',
    'entry_city' => defined('IEMS_LEGACY_DEFAULT_CITY') ? IEMS_LEGACY_DEFAULT_CITY : '',
    'entry_postcode' => defined('IEMS_LEGACY_DEFAULT_POSTCODE') ? IEMS_LEGACY_DEFAULT_POSTCODE : '',
];
$iemsNewEntry = !isset($_GET['edit']);
foreach ($iemsDefaults as $iemsField => $iemsValue) {
    if (!isset($entry->fields[$iemsField]) || ($iemsNewEntry && (string)$entry->fields[$iemsField] === '')) {
        $entry->fields[$iemsField] = $iemsValue;
    }
}
foreach (['entry_gender', 'entry_suburb', 'entry_state', 'entry_country_id', 'entry_zone_id'] as $iemsField) {
    if (!isset($entry->fields[$iemsField])) {
        $entry->fields[$iemsField] = '';
    }
}
?>
<fieldset>
<legend><?php echo HEADING_TITLE; ?></legend>
<div class="alert forward"><?php echo FORM_REQUIRED_INFORMATION; ?></div>
<br class="clearBoth">

<?php
  if (ACCOUNT_GENDER == 'true') {
    if (isset($gender)) {
      $male = ($gender == 'm') ? true : false;
      $female = ($gender == 'f') ? true : false;
    } else {
      $male = ($entry->fields['entry_gender'] == 'm') ? true : false;
      $female = ($entry->fields['entry_gender'] == 'f') ? true : false;
    }
?>
<?php echo zen_draw_radio_field('gender', 'm', $male, 'id="gender-male"') . '<label class="radioButtonLabel" for="gender-male">' . MALE . '</label>' . zen_draw_radio_field('gender', 'f', $female, 'id="gender-female"') . '<label class="radioButtonLabel" for="gender-female">' . FEMALE . '</label>' . (!empty(ENTRY_GENDER_TEXT) ? '<span class="alert">' . ENTRY_GENDER_TEXT . '</span>': ''); ?>
<br class="clearBoth">

<?php
  }
?>
<label class="inputLabel" for="firstname"><?php echo ENTRY_FIRST_NAME; ?></label>
<?php echo zen_draw_input_field('firstname', $entry->fields['entry_firstname'], zen_set_field_length(TABLE_CUSTOMERS, 'customers_firstname', '40') . ' id="firstname" placeholder="' . ENTRY_FIRST_NAME_TEXT . '"' . ((int)ENTRY_FIRST_NAME_MIN_LENGTH > 0 ? ' required' : '')); ?>
<br class="clearBoth">

<label class="inputLabel" for="lastname"><?php echo ENTRY_LAST_NAME; ?></label>
<?php echo zen_draw_input_field('lastname', $entry->fields['entry_lastname'], zen_set_field_length(TABLE_CUSTOMERS, 'customers_lastname', '40') . ' id="lastname" placeholder="' . ENTRY_LAST_NAME_TEXT . '"' . ((int)ENTRY_LAST_NAME_MIN_LENGTH > 0 ? ' required' : '')); ?>
<br class="clearBoth">

<?php
  if (ACCOUNT_COMPANY == 'true') {
?>
<label class="inputLabel" for="company"><?php echo ENTRY_COMPANY; ?></label>
<?php echo zen_draw_input_field('company', $entry->fields['entry_company'], zen_set_field_length(TABLE_ADDRESS_BOOK, 'entry_company', '40') . ' id="company" autocomplete="organization" placeholder="' . ENTRY_COMPANY_TEXT . '"' . (ACCOUNT_COMPANY == 'true' && (int)ENTRY_COMPANY_MIN_LENGTH != 0 ? ' required' : '')); ?>
<br class="clearBoth">
<?php
  }
?>

<label class="inputLabel" for="street-address"><?php echo ENTRY_STREET_ADDRESS; ?></label>
<?php echo zen_draw_input_field('street_address', $entry->fields['entry_street_address'], zen_set_field_length(TABLE_ADDRESS_BOOK, 'entry_street_address', '40') . ' id="street-address" placeholder="' . ENTRY_STREET_ADDRESS_TEXT . '"' . ((int)ENTRY_STREET_ADDRESS_MIN_LENGTH > 0 ? ' required' : '')); ?>
<br class="clearBoth">
<?php
  if (ACCOUNT_SUBURB == 'true') {
?>
<label class="inputLabel" for="suburb"><span class="iemsUnitLabel"><?php echo ENTRY_SUBURB; ?></span></label>
<?php
if (class_exists('\\Zencart\\Plugins\\Catalog\\IemsLegacyBridge\\LegacyUnits')) {
    echo \Zencart\Plugins\Catalog\IemsLegacyBridge\LegacyUnits::pullDown('suburb', (string)($_POST['suburb'] ?? $entry->fields['entry_suburb']), 'id="suburb" class="iemsUnitSelect"');
} else {
    echo zen_draw_input_field('suburb', (string)($_POST['suburb'] ?? $entry->fields['entry_suburb']), zen_set_field_length(TABLE_ADDRESS_BOOK, 'entry_suburb', '40') . ' id="suburb"');
}
?>
<br class="clearBoth">
<?php
  }
?>

<label class="inputLabel" for="city"><?php echo ENTRY_CITY; ?></label>
<?php echo zen_draw_input_field('city', $entry->fields['entry_city'], zen_set_field_length(TABLE_ADDRESS_BOOK, 'entry_city', '40') . ' id="city" placeholder="' . ENTRY_CITY_TEXT . '"' . ((int)ENTRY_CITY_MIN_LENGTH > 0 ? ' required' : '')); ?>
<br class="clearBoth">

<?php
  if (ACCOUNT_STATE == 'true') {
    if ($flag_show_pulldown_states == true) {
?>
<label class="inputLabel" for="stateZone" id="zoneLabel"><?php echo ENTRY_STATE; ?></label>
<?php
      echo zen_draw_pull_down_menu('zone_id', zen_prepare_country_zones_pull_down($selected_country), $zone_id, 'id="stateZone"');
      if (!empty(ENTRY_STATE_TEXT)) echo '&nbsp;<span class="alert">' . ENTRY_STATE_TEXT . '</span>';
    }
?>

<?php if ($flag_show_pulldown_states == true) { ?>
<br class="clearBoth" id="stBreak">
<?php } ?>
<label class="inputLabel" for="state" id="stateLabel"><?php echo $state_field_label; ?></label>
<?php
    echo zen_draw_input_field('state', zen_get_zone_name((int)$entry->fields['entry_country_id'], (int)$entry->fields['entry_zone_id'], $entry->fields['entry_state']), zen_set_field_length(TABLE_ADDRESS_BOOK, 'entry_state', '40') . ' id="state" placeholder="' . ENTRY_STATE_TEXT . '"');

    if ($flag_show_pulldown_states == false) {
      echo zen_draw_hidden_field('zone_id', $zone_name, ' ');
    }
?>
<br class="clearBoth">
<?php
  }
?>

<label class="inputLabel" for="postcode"><?php echo ENTRY_POST_CODE; ?></label>
<?php echo zen_draw_input_field('postcode', $entry->fields['entry_postcode'], zen_set_field_length(TABLE_ADDRESS_BOOK, 'entry_postcode', '40') . ' id="postcode" placeholder="' . ENTRY_POST_CODE_TEXT . '"' . ((int)ENTRY_POSTCODE_MIN_LENGTH > 0 ? ' required' : '')); ?>
<br class="clearBoth">

<label class="inputLabel" for="country"><?php echo ENTRY_COUNTRY; ?></label>
<?php echo zen_get_country_list('zone_country_id', $entry->fields['entry_country_id'], 'id="country"' . ($flag_show_pulldown_states == true ? ' onchange="update_zone(this.form);"' : '')); ?>
<br class="clearBoth">

<?php
  if ((isset($_GET['edit']) && ($_SESSION['customer_default_address_id'] != $_GET['edit'])) || (isset($_GET['edit']) == false) ) {
?>
<?php echo zen_draw_checkbox_field('primary', 'on', false, 'id="primary"') . ' <label class="checkboxLabel" for="primary">' . SET_AS_PRIMARY . '</label>'; ?>
<?php
  }
?>
</fieldset>
