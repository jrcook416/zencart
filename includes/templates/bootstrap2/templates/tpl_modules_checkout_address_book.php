<?php
/**
 * tpl_modules_checkout_address_book.php
 * 
 * BOOTSTRAP v3.4.0
 *
 * @package templateSystem
 * @copyright Copyright 2003-2009 Zen Cart Development Team
 * @copyright Portions Copyright 2003 osCommerce
 * @license http://www.zen-cart.com/license/2_0.txt GNU Public License V2.0
 * @version $Id: tpl_modules_checkout_address_book.php 13799 2009-07-08 02:08:33Z drbyte $
 */
?>
<?php
/**
 * require code to get address book details
 */
require DIR_WS_MODULES . zen_get_module_directory('checkout_address_book.php');
foreach ($addresses as $address) {
    $address_book_id = $address['address_book_id'];
    $selected = ($address_book_id === $_SESSION['sendto']);
    if ($current_page_base === FILENAME_CHECKOUT_PAYMENT_ADDRESS) {
        $selected = ($address_book_id === $_SESSION['billto']);
    }

    if ($selected === true) {
        $primary_border = ' border-primary';
        $primary_background = ' bg-primary text-white';
        $primary_address = BOOTSTRAP_CURRENT_ADDRESS;
    } else {
        $primary_border = '';
        $primary_background = '';
        $primary_address = '';
    }
?>
<!--bof address book single entries card-->
<div id="addressBookSingleEntryId<?php echo $address_book_id; ?>-card" class="card mb-3 <?php echo $primary_border; ?>">
    <h4 id="addressBookSingleEntryId<?php echo $address_book_id; ?>-card-header" class="card-header <?php echo $primary_background ; ?>">
        <div class="custom-control custom-radio custom-control-inline">
            <?php echo zen_draw_radio_field('address', $address_book_id, $selected, 'id="name-' . $address_book_id . '"'); ?>

            <label for="name-<?php echo $address_book_id; ?>" class="custom-control-label"><?php echo zen_output_string_protected($address['firstname'] . ' ' . $address['lastname']) . $primary_address; ?></label>
        </div>
    </h4>
<?php
    // -----
    // The zc158 version of the checkout_new_address module now returns an
    // associative array of pre-parsed information instead of a collection of
    // database fields.  Determine what values to use when formatting previously-registered
    // addresses.
    //
    $address_details = (zen_get_zcversion() >= '1.5.8') ? $address['address'] : $address->fields;
?>
    <div id="addressBookSingleEntryId<?php echo $address_book_id; ?>-card-body" class="card-body p-3">
        <address><?php echo zen_address_format(zen_get_address_format_id($address['country_id']), $address_details, true, ' ', '<br>'); ?></address>
    </div>
</div>
<!--eof address book single entry card-->
<?php
}
