<?php
// -----
// Part of the One-Page Checkout plugin, provided under GPL 2.0 license by lat9 (cindy@vinosdefrutastropicales.com).
// Copyright (C) 2017-2022, Vinos de Frutas Tropicales.  All rights reserved.
//
// Last updated: OPC v2.4.2/Bootstrap 3.4.0
//
// This template is loaded for the address_book page by /includes/modules/pages/address_book/main_template_vars.php
// when the One-Page Checkout plugin is installed, its "account registration" processing is enabled and the currently
// logged-in customer has completed account "registration" but has not (yet) provided their default address information.
//
// -----
// OPC v2.4.2, in support of zc158, has changed the 'TEXT_NO_ADDRESSES' definition from
// a 'simple' definition to one that requires insertion of the alt-text.
//
$text_no_addresses = TEXT_NO_ADDRESSES;
if (version_compare(CHECKOUT_ONE_MODULE_VERSION, '2.4.2', '>=')) {
    $text_no_addresses = sprintf(TEXT_NO_ADDRESSES, BUTTON_ADD_ADDRESS_ALT);
}
?>
<div class="centerColumn" id="addressBookDefault">
    <h1 id="addressBookDefaultHeading"><?php echo HEADING_TITLE; ?></h1>

    <div id="addressBookNoPrimary"><?php echo $text_no_addresses; ?></div>
    <div class="d-flex justify-content-around mt-3">
        <a href="<?php echo zen_href_link(FILENAME_ACCOUNT, '', 'SSL'); ?>">
            <?php echo zen_image_button(BUTTON_IMAGE_BACK, BUTTON_BACK_ALT); ?>
        </a>
        <a href="<?php echo zen_href_link(FILENAME_ADDRESS_BOOK_PROCESS, 'edit=' . $_SESSION['customer_default_address_id'], 'SSL'); ?>">
            <?php echo zen_image_button(BUTTON_IMAGE_ADD_ADDRESS, BUTTON_ADD_ADDRESS_ALT); ?>
        </a>
    </div>
</div>
