<?php
/**
 * Page Template
 *
 * Loaded automatically by index.php?main_page=checkout_shipping_adresss.
 * Allows customer to change the shipping address.
 *
 * @copyright Copyright 2003-2023 Zen Cart Development Team
 * @copyright Portions Copyright 2003 osCommerce
 * @license http://www.zen-cart.com/license/2_0.txt GNU Public License V2.0
 * @version $Id: Steve 2023 Feb 23 Modified in v1.5.8a $
 */
?>
<div class="centerColumn" id="checkoutShipAddressDefault">

<h1 id="checkoutShipAddressDefaultHeading"><?php echo HEADING_TITLE; ?></h1>

<?php if ($messageStack->size('checkout_address') > 0) echo $messageStack->output('checkout_address'); ?>

<?php
  if ($process == false || $error == true) {
?>

<h2 id="checkoutShipAddressDefaultAddress"><?php echo TITLE_SHIPPING_ADDRESS; ?></h2>
    <div class="instructions group">
     <address class="back"><?php echo zen_address_label($_SESSION['customer_id'], $_SESSION['sendto'], true, ' ', '<br>'); ?></address>
<?php echo TEXT_CREATE_NEW_SHIPPING_ADDRESS; ?></div>


<?php
// IEMS: as in 1.5.6c, every order shows the new-address form (pre-filled
// from the current address) so the unit is picked for each order. The 1.5.6c
// page also listed saved addresses inside this form, but the always-filled
// new-address fields took priority, so that list never had any effect.
       echo zen_draw_form('checkout_address', zen_href_link(FILENAME_CHECKOUT_SHIPPING_ADDRESS, '', 'SSL'), 'post', 'class="group"');
/**
 * require template to display new address form
 */
  require($template->get_template_dir('tpl_modules_checkout_new_address.php', DIR_WS_TEMPLATE, $current_page_base,'templates'). '/' . 'tpl_modules_checkout_new_address.php');
?>
<div class="buttonRow forward"><?php echo zen_draw_hidden_field('action', 'submit') . zen_image_submit(BUTTON_IMAGE_CONTINUE, BUTTON_CONTINUE_ALT); ?></div>
</form>
<?php
  }

?>

<div class="buttonRow back"><?php echo '<strong>' . TITLE_CONTINUE_CHECKOUT_PROCEDURE . '</strong>' . '<br>' . TEXT_CONTINUE_CHECKOUT_PROCEDURE; ?></div>

<div class="buttonRow back clearBoth"><?php echo zen_back_link() . zen_image_button(BUTTON_IMAGE_BACK, BUTTON_BACK_ALT) . '</a>'; ?></div>
</div>
