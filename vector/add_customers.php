<?php
/**
 * @copyright Copyright 2003-2010 Zen Cart Development Team
 * @copyright Portions Copyright 2003 osCommerce
 * @license http://www.zen-cart.com/license/2_0.txt GNU Public License V2.0
 *
 * add_customers module modified by Garden 2012-07-20
 * www.inzencart.cz Czech forum for ZenCart
 * Modified for Zen Cart 1.5.0, v1.5.1, lat9 2013-05-16
 * Modified for Zen Cart 1.5.5, lat9 2015-12-24
 * Modified for Zen Cart 1.5.7+, lat9, 2021-12-06
 */
require 'includes/application_top.php';

require DIR_WS_CLASSES . 'currencies.php';
$currencies = new currencies();

$error = false;
$errors = [];
$processed = false;

require DIR_WS_CLASSES . 'addCustomers.php';
$acfa = new addCustomers();

$theFormats = $acfa->getDateFormats();

// -----
// Initialize $cInfo array for single-entry form.
//
$default_customer = [
    'customers_authorization' => '0',
    'customers_gender' => 'm',
    'customers_firstname' => '',
    'customers_lastname' => '',
    'customers_email_address' => '',
    'entry_company' => '',
    'entry_street_address' => '',
    'entry_suburb' => '',
    'entry_postcode' => '',
    'entry_city' => '',
    'entry_state' => '',
    'entry_zone_id' => STORE_ZONE,
    'entry_country_id' => STORE_COUNTRY,
    'customers_telephone' => '',
    'customers_fax' => '',
    'customers_email_format' => (ACCOUNT_EMAIL_PREFERENCE === '0') ? 'TEXT' : 'HTML',
    'customers_newsletter' => '0',
    'customers_referral' => '',
    'customers_group_pricing' => '0',
];
$cInfo = new objectInfo($default_customer);
unset($default_customer);

// -----
// Determine what to do next, based on the posted 'action'.
//
$action = (isset($_POST['action'])) ? $_POST['action'] : false;
if ($action !== false) {
    switch ($action) {
        // -----
        // Adding a single-customer via the associated form.  The input is validated and,
        // if found to be OK, subsequently written to the database.
        //
        case 'add_single':
            list($cInfo, $errors) = $acfa->validateCustomer($_POST, 'date_format_s');
            if (count($errors) === 0) {
                $customerName = $acfa->insertCustomer($_POST);
                $messageStack->add_session(sprintf(MESSAGE_CUSTOMER_OK, $customerName), 'success');
                zen_redirect(zen_href_link(FILENAME_ADD_CUSTOMERS));
            }
            break;

        // -----
        // Adding multiple customers, via an uploaded .csv/.txt file.
        //
        case 'add_multiple':
            $errors = $acfa->checkFileUpload();
            if (count($errors) === 0) {
                $messageStack->add_session(sprintf(MESSAGE_CUSTOMERS_OK, $acfa->getCustomersInserted()), 'success');
                zen_redirect(zen_href_link(FILENAME_ADD_CUSTOMERS));
            }
            break;

        // -----
        // Resending the customer's welcome-email, optionally resetting their password.
        //
        case 'resend_email':
            if (!isset($_POST['resend_id'])) {
                $errors[] = ERROR_NO_CUSTOMER_SELECTED;
            } else {
                $custInfo = $db->Execute(
                    "SELECT customers_gender, customers_firstname, customers_lastname, customers_email_address
                       FROM " . TABLE_CUSTOMERS . "
                      WHERE customers_id = " . (int)$_POST['resend_id'] . "
                      LIMIT 1"
                );
                if ($custInfo->EOF) {
                    $errors[] = ERROR_NO_CUSTOMER_SELECTED;
                } else {
                    $thePassword = false;
                    if (isset($_POST['reset_pw']) && $_POST['reset_pw'] === '1') {
                        $thePassword = zen_create_PADSS_password(((int)ENTRY_PASSWORD_MIN_LENGTH > 0) ? (int)ENTRY_PASSWORD_MIN_LENGTH : 5);
                        $db->Execute(
                            "UPDATE " . TABLE_CUSTOMERS . "
                                SET customers_password = '" . zen_encrypt_password($thePassword) . "'
                              WHERE customers_id = " . (int)$_POST['resend_id'] . "
                              LIMIT 1"
                        );
                    }
                    $acfa->sendWelcomeEmail($custInfo->fields['customers_gender'], $custInfo->fields['customers_firstname'], $custInfo->fields['customers_lastname'], $custInfo->fields['customers_email_address'], $thePassword);
                    $messageStack->add_session(CUSTOMER_EMAIL_RESENT . ' ' . $custInfo->fields['customers_firstname'] . ' ' . $custInfo->fields['customers_lastname'] . ' (' . $custInfo->fields['customers_email_address'] . ')', 'success');
                    zen_redirect(zen_href_link(FILENAME_ADD_CUSTOMERS));
                }
            }
            break;

        // -----
        // Anything else, nothing to be done.
        //
        default:
            break;
    }
}
?>
<!doctype html>
<html <?php echo HTML_PARAMS; ?>>
<head>
<?php require DIR_WS_INCLUDES . 'admin_html_head.php'; ?>
</head>

<body>
<?php
require DIR_WS_INCLUDES . 'header.php';

$infoDivContents = '';
if (count($errors) > 0) {
    $infoDivContents = '<div class="errorDiv"><p class="errorBold">' . ERROR_CUSTOMER_ERROR_1 . (($action == 'insert_multiple' && !empty($_FILES['bulk_upload']['name'])) ? (' (' . $_FILES['bulk_upload']['name'] . ')') : '') . ':' . '</p><ul>';

    foreach ($errors as $line_no => $error) {
        if (is_array($error)) {
            $infoDivContents .= '<div class="error">' . sprintf(ERROR_ON_LINE, $line_no) . '</div><ul>';
            foreach ($error as $err) {
                $infoDivContents .= '<li class="error">' . $err . '</li>';
            }
            $infoDivContents .= '</ul>';
        } else {
            $infoDivContents .= '<li class="error">' . $error . '</li>';
        }
    }
    $infoDivContents .= '</ul></div>';
}

$insert_mode = (isset($_POST['insert_mode'])) ? $_POST['insert_mode'] : 'file';
if ($insert_mode !== 'file' && $insert_mode !== 'part') {
    $insert_mode = 'file';
}

if (isset($_POST['date_format_m'])) {
    $selectedDateFormat_m = (int)$_POST['date_format_m'];
} else {
    $currentDateFormat_m = str_replace('%m', 'MM', str_replace('%d', 'DD', str_replace('%Y', 'YYYY', DATE_FORMAT_SHORT)));
    $selectedDateFormat_m = array_search($currentDateFormat_m, $theFormats);
}
if (isset($_POST['date_format_s'])) {
    $selectedDateFormat_s = (int)$_POST['date_format_s'];
} else {
    $currentDateFormat_s = str_replace('%m', 'MM', str_replace('%d', 'DD', str_replace('%Y', 'YYYY', DATE_FORMAT_SHORT)));
    $selectedDateFormat_s = array_search($currentDateFormat_s, $theFormats);
}

for ($i = 0, $n = count($theFormats), $dateFormats = []; $i < $n; $i++) {
    $dateFormats[$i]['id'] = $i;
    $dateFormats[$i]['text'] = $theFormats[$i];
}

$resendID = (isset($_POST['resend_id'])) ? $_POST['resend_id'] : 0;
?>
    <div class="container-fluid">
        <h1><?php echo HEADING_TITLE; ?></h1>
        <div class="col-md-4">
<?php
// -----
// Add multiple customers via uploaded .csv/.txt form rendering.
//
if ($action == 'add_multiple') {
    echo $infoDivContents;
}
?>
            <?php echo zen_draw_form('customers', FILENAME_ADD_CUSTOMERS, '', 'post', 'enctype="multipart/form-data" class="form-horizontal"') .
                       zen_hide_session_id() .
                       zen_draw_hidden_field('action', 'add_multiple'); ?>
            <div class="row formAreaTitle"><?php echo CUSTOMERS_BULK_UPLOAD; ?></div>
            <div class="pull-right noprint">
                <a href="<?php echo HTTP_SERVER . DIR_WS_CATALOG; ?>add_customers_formatting_csv.html" rel="noopener" target="_blank" class="btn btn-sm btn-default btn-help" role="button" title="Help">
                    <i class="fa fa-question fa-lg" aria-hidden="true"></i>
                </a>
            </div>
            <div class="formArea">
                <div class="form-group">
                    <?php echo zen_draw_label(CUSTOMERS_FILE_IMPORT, 'bulk_upload', 'class="col-sm-3 control-label"'); ?>
                    <div class="col-sm-9 col-md-6"><?php echo zen_draw_file_field('bulk_upload', true, 'class="form-control"'); ?></div>
                </div>
                <div class="form-group">
                    <?php echo zen_draw_label(CUSTOMERS_INSERT_MODE, 'insert_mode', 'class="col-sm-3 control-label"'); ?>
                    <div class="col-sm-9 col-md-6">
                        <label class="radio-inline"><?php echo zen_draw_radio_field('insert_mode', 'part', $insert_mode === 'part') . CUSTOMERS_INSERT_MODE_VALID; ?></label>
                        <label class="radio-inline"><?php echo zen_draw_radio_field('insert_mode', 'file', $insert_mode === 'file') . CUSTOMERS_INSERT_MODE_FILE; ?></label>
                    </div>
                </div>
                <div class="form-group">
                    <?php echo zen_draw_label(DATE_FORMAT_CHOOSE_MULTI, 'date_format_m', 'class="col-sm-3 control-label"'); ?>
                    <div class="col-sm-9 col-md-6">
                        <?php echo zen_draw_pull_down_menu('date_format_m', $dateFormats, $selectedDateFormat_m); ?>
                    </div>
                </div>
                <div class="row text-right">
                    <button name="add_customers_in_bulk" type="submit" class="btn btn-primary"><?php echo IMAGE_UPLOAD; ?></button>
                </div>
            </div>
            <?php echo '</form>'; ?>
<?php
// -----
// Resend-email form rendering.
//
if ($action == 'resend_email') {
    echo $infoDivContents;
}
?>
            <?php echo zen_draw_form('resend', FILENAME_ADD_CUSTOMERS, '', 'post', 'enctype="multipart/form-data" class="form-horizontal"') .
                  zen_hide_session_id() .
                  zen_draw_hidden_field('action', 'resend_email'); ?>
            <div class="row formAreaTitle"><?php echo RESEND_WELCOME_EMAIL; ?></div>
            <div class="formArea">
                <div class="form-group">
                    <?php echo zen_draw_label(TEXT_CHOOSE_CUSTOMER, 'resend_id', 'class="col-sm-4 control-label"'); ?>
                    <div class="col-sm-8 col-md-6"><?php echo zen_draw_pull_down_menu('resend_id', $acfa->createCustomerDropdown(), $resendID); ?></div>
                </div>
                <div class="form-group">
                    <?php echo zen_draw_label(TEXT_RESET_PASSWORD, 'reset_pw', 'class="col-sm-4 control-label"'); ?>
                    <div class="col-sm-8 col-md-6">
                        <?php echo zen_draw_checkbox_field('reset_pw', '1', isset($_POST['reset_pw']), 'class="form-control"'); ?>
                    </div>
                </div>
                <div class="row text-right">
                    <button name="resend" type="submit" class="btn btn-primary"><?php echo BUTTON_RESEND; ?></button>
                </div>
            </div>
            <?php echo '</form>'; ?>
        </div>

        <div class="col-md-8">
<?php
// -----
// Add single-customer form rendering.
//
if ($action === 'add_single') {
    echo $infoDivContents;
}
?>
            <?php echo zen_draw_form('customers_1', FILENAME_ADD_CUSTOMERS, '', 'post', 'class="form-horizontal"') .
                       zen_hide_session_id() .
                       zen_draw_hidden_field('action', 'add_single'); ?>

            <div class="row formAreaTitle"><?php echo CUSTOMERS_SINGLE_ENTRY; ?></div>
            <div class="formArea">
                <div class="row formAreaTitle"><?php echo CATEGORY_PERSONAL; ?></div>
<?php
$customers_authorization_array = [
    ['id' => '0', 'text' => CUSTOMERS_AUTHORIZATION_0],
    ['id' => '1', 'text' => CUSTOMERS_AUTHORIZATION_1],
    ['id' => '2', 'text' => CUSTOMERS_AUTHORIZATION_2],
    ['id' => '3', 'text' => CUSTOMERS_AUTHORIZATION_3],
];
?>
                <div class="formArea">
                    <div class="form-group">
                        <?php echo zen_draw_label(CUSTOMERS_AUTHORIZATION, 'customers_authorization', 'class="col-sm-3 control-label"'); ?>
                        <div class="col-sm-9 col-md-6">
                            <?php echo zen_draw_pull_down_menu('customers_authorization', $customers_authorization_array, $cInfo->customers_authorization, 'class="form-control"'); ?>
                        </div>
                    </div>
<?php
if (ACCOUNT_GENDER === 'true') {
?>
                    <div class="form-group">
                        <?php echo zen_draw_label(ENTRY_GENDER, 'customers_gender', 'class="col-sm-3 control-label"'); ?>
                        <div class="col-sm-9 col-md-6">
                            <label class="radio-inline"><?php echo zen_draw_radio_field('customers_gender', 'm', $cInfo->customers_gender === 'm') . MALE; ?></label>
                            <label class="radio-inline"><?php echo zen_draw_radio_field('customers_gender', 'f', $cInfo->customers_gender === 'f') . FEMALE; ?></label>
                        </div>
                    </div>
<?php
}
?>
                    <div class="form-group">
                        <?php echo zen_draw_label(ENTRY_FIRST_NAME, 'customers_firstname', 'class="col-sm-3 control-label"'); ?>
                        <div class="col-sm-9 col-md-6">
                            <?php echo zen_draw_input_field('customers_firstname', htmlspecialchars($cInfo->customers_firstname, ENT_COMPAT, CHARSET, TRUE), zen_set_field_length(TABLE_CUSTOMERS, 'customers_firstname', 50) . ' class="form-control"', true); ?>
                        </div>
                    </div>
                    <div class="form-group">
                        <?php echo zen_draw_label(ENTRY_LAST_NAME, 'customers_lastname', 'class="col-sm-3 control-label"'); ?>
                        <div class="col-sm-9 col-md-6">
                            <?php echo zen_draw_input_field('customers_lastname', htmlspecialchars($cInfo->customers_lastname, ENT_COMPAT, CHARSET, TRUE), zen_set_field_length(TABLE_CUSTOMERS, 'customers_lastname', 50) . ' class="form-control"', true); ?>
                        </div>
                    </div>
<?php
if (ACCOUNT_DOB === 'true') {
?>
                    <div class="form-group">
                        <?php echo zen_draw_label(DATE_FORMAT_CHOOSE_SINGLE, 'date_format_s', 'class="col-sm-3 control-label"'); ?>
                        <div class="col-sm-9 col-md-6">
                            <?php echo zen_draw_pull_down_menu('date_format_s', $dateFormats, $selectedDateFormat_s, 'class="form-control"'); ?>
                        </div>
                    </div>
                    <div class="form-group">
                        <?php echo zen_draw_label(ENTRY_DATE_OF_BIRTH, 'customers_dob', 'class="col-sm-3 control-label"'); ?>
                        <div class="col-sm-9 col-md-6">
                            <?php echo zen_draw_input_field('customers_dob', ((empty($cInfo->customers_dob) || $cInfo->customers_dob <= '0001-01-01' || $cInfo->customers_dob == '0001-01-01 00:00:00') ? '' : zen_date_short($cInfo->customers_dob)), 'maxlength="10" class="form-control"', true); ?>
                        </div>
                    </div>

<?php
}
?>
                    <div class="form-group">
                        <?php echo zen_draw_label(ENTRY_EMAIL_ADDRESS, 'customers_email_address', 'class="col-sm-3 control-label"'); ?>
                        <div class="col-sm-9 col-md-6">
                            <?php echo zen_draw_input_field('customers_email_address', htmlspecialchars($cInfo->customers_email_address, ENT_COMPAT, CHARSET, TRUE), zen_set_field_length(TABLE_CUSTOMERS, 'customers_email_address', 50) . ' class="form-control"', true); ?>
                        </div>
                    </div>
                </div>
<?php
if (ACCOUNT_COMPANY === 'true') {
?>
                <div class="row formAreaTitle"><?php echo CATEGORY_COMPANY; ?></div>
                <div class="formArea">
                    <div class="form-group">
                        <?php echo zen_draw_label(ENTRY_COMPANY, 'customers_email_address', 'class="col-sm-3 control-label"'); ?>
                        <div class="col-sm-9 col-md-6">
                            <?php echo zen_draw_input_field('entry_company', htmlspecialchars($cInfo->entry_company, ENT_COMPAT, CHARSET, TRUE), zen_set_field_length(TABLE_ADDRESS_BOOK, 'entry_company', 50) . ' class="form-control"'); ?>
                        </div>
                    </div>
                </div>
<?php
}
?>
                <div class="row formAreaTitle"><?php echo CATEGORY_ADDRESS; ?></div>
                <div class="formArea">
                    <div class="form-group">
                        <?php echo zen_draw_label(ENTRY_STREET_ADDRESS, 'entry_street_address', 'class="col-sm-3 control-label"'); ?>
                        <div class="col-sm-9 col-md-6">
                            <?php echo zen_draw_input_field('entry_street_address', htmlspecialchars($cInfo->entry_street_address, ENT_COMPAT, CHARSET, TRUE), zen_set_field_length(TABLE_ADDRESS_BOOK, 'entry_street_address', 50) . ' class="form-control"', true); ?>
                        </div>
                    </div>
<?php
if (ACCOUNT_SUBURB === 'true') {
?>
                    <div class="form-group">
                        <?php echo zen_draw_label(ENTRY_SUBURB, 'suburb', 'class="col-sm-3 control-label"'); ?>
                        <div class="col-sm-9 col-md-6">
                            <?php echo zen_draw_input_field('entry_suburb', htmlspecialchars($cInfo->entry_suburb, ENT_COMPAT, CHARSET, TRUE), zen_set_field_length(TABLE_ADDRESS_BOOK, 'entry_suburb', 50) . ' class="form-control"'); ?>
                        </div>
                    </div>
<?php
}
?>
                    <div class="form-group">
                        <?php echo zen_draw_label(ENTRY_POST_CODE, 'entry_postcode', 'class="col-sm-3 control-label"'); ?>
                        <div class="col-sm-9 col-md-6">
                            <?php echo zen_draw_input_field('entry_postcode', htmlspecialchars($cInfo->entry_postcode, ENT_COMPAT, CHARSET, TRUE), zen_set_field_length(TABLE_ADDRESS_BOOK, 'entry_postcode', 10) . ' class="form-control"', true); ?>
                        </div>
                    </div>
                    <div class="form-group">
                        <?php echo zen_draw_label(ENTRY_CITY, 'entry_city', 'class="col-sm-3 control-label"'); ?>
                        <div class="col-sm-9 col-md-6">
                            <?php echo zen_draw_input_field('entry_city', htmlspecialchars($cInfo->entry_city, ENT_COMPAT, CHARSET, TRUE), zen_set_field_length(TABLE_ADDRESS_BOOK, 'entry_city', 50) . ' class="form-control"', true); ?>
                        </div>
                    </div>
<?php
if (ACCOUNT_STATE === 'true') {
?>
                    <div class="form-group">
                        <?php echo zen_draw_label(ENTRY_STATE, 'entry_state', 'class="col-sm-3 control-label"'); ?>
                        <div class="col-sm-9 col-md-6">
                            <?php echo zen_draw_input_field('entry_state', htmlspecialchars(zen_get_zone_name($cInfo->entry_country_id, $cInfo->entry_zone_id, $cInfo->entry_state), ENT_COMPAT, CHARSET, TRUE), 'class="form-control"'); ?>
                        </div>
                    </div>
<?php
}
?>
                    <div class="form-group">
                        <?php echo zen_draw_label(ENTRY_COUNTRY, 'entry_country_id', 'class="col-sm-3 control-label"'); ?>
                        <div class="col-sm-9 col-md-6">
                            <?php echo zen_draw_pull_down_menu('entry_country_id', zen_get_countries(), $cInfo->entry_country_id, 'class="form-control"'); ?>
                        </div>
                    </div>
                </div>

                <div class="row formAreaTitle"><?php echo CATEGORY_CONTACT; ?></div>
                <div class="formArea">
                    <div class="form-group">
                        <?php echo zen_draw_label(ENTRY_TELEPHONE_NUMBER, 'customers_telephone', 'class="col-sm-3 control-label"'); ?>
                        <div class="col-sm-9 col-md-6">
                            <?php echo zen_draw_input_field('customers_telephone', htmlspecialchars($cInfo->customers_telephone, ENT_COMPAT, CHARSET, TRUE), zen_set_field_length(TABLE_CUSTOMERS, 'customers_telephone', 15) . ' class="form-control"', true); ?>
                        </div>
                    </div>
<?php
if (ACCOUNT_FAX_NUMBER === 'true') {
?>
                    <div class="form-group">
                        <?php echo zen_draw_label(ENTRY_FAX_NUMBER, 'customers_fax', 'class="col-sm-3 control-label"'); ?>
                        <div class="col-sm-9 col-md-6">
                            <?php echo zen_draw_input_field('customers_fax', htmlspecialchars($cInfo->customers_fax, ENT_COMPAT, CHARSET, TRUE), zen_set_field_length(TABLE_CUSTOMERS, 'customers_fax', 15) . ' class="form-control"'); ?>
                        </div>
                    </div>
<?php
}
?>
                </div>
                
                <div class="row formAreaTitle"><?php echo CATEGORY_OPTIONS; ?></div>
                <div class="formArea">
                    <div class="form-group">
                        <?php echo zen_draw_label(ENTRY_EMAIL_PREFERENCE, 'customers_email_format', 'class="col-sm-3 control-label"'); ?>
                        <div class="col-sm-9 col-md-6">
                            <label class="radio-inline"><?php echo zen_draw_radio_field('customers_email_format', 'HTML', $cInfo->customers_email_format !== 'TEXT') . ENTRY_EMAIL_HTML_DISPLAY; ?></label>
                            <label class="radio-inline"><?php echo zen_draw_radio_field('customers_email_format', 'TEXT', $cInfo->customers_email_format === 'TEXT') . ENTRY_EMAIL_TEXT_DISPLAY; ?></label>
                        </div>
                    </div>
                    <div class="form-group">
                        <?php echo zen_draw_label(ENTRY_NEWSLETTER, 'customers_newsletter', 'class="col-sm-3 control-label"'); ?>
                        <div class="col-sm-9 col-md-6">
<?php
$newsletter_array = [
    ['id' => '1', 'text' => ENTRY_NEWSLETTER_YES],
    ['id' => '0', 'text' => ENTRY_NEWSLETTER_NO],
];
$newsletter =  ((empty($cInfo) && ACCOUNT_NEWSLETTER_STATUS === '2') || (isset($cInfo) && $cInfo->customers_newsletter === '1')) ? '1' : '0';
echo zen_draw_pull_down_menu('customers_newsletter', $newsletter_array, $newsletter, 'class="form-control"');
?>
                        </div>
                    </div>
                    <div class="form-group">
                        <?php echo zen_draw_label(ENTRY_PRICING_GROUP, 'customers_group_pricing', 'class="col-sm-3 control-label"'); ?>
                        <div class="col-sm-9 col-md-6">
<?php
$group_array_query = $db->Execute(
    "SELECT group_id, group_name, group_percentage
      FROM " . TABLE_GROUP_PRICING
);
$group_array[] = array('id' => 0, 'text' => TEXT_NONE);
foreach ($group_array_query as $item) {
    $group_array[] = ['id' => $item['group_id'], 'text' => $item['group_name'] . '&nbsp;' . $item['group_percentage'] . '%'];
}
echo zen_draw_pull_down_menu('customers_group_pricing', $group_array, $cInfo->customers_group_pricing, 'class="form-control"');
?>
                        </div>
                    </div>
<?php
if (CUSTOMERS_REFERRAL_STATUS === '2') {
?>
                    <div class="form-group">
                        <?php echo zen_draw_label(CUSTOMERS_REFERRAL, 'customers_referral', 'class="col-sm-3 control-label"'); ?>
                        <div class="col-sm-9 col-md-6">
                            <?php echo zen_draw_input_field('customers_referral', htmlspecialchars($cInfo->customers_referral, ENT_COMPAT, CHARSET, TRUE), zen_set_field_length(TABLE_CUSTOMERS, 'customers_referral', 15) . ' class="form-control"'); ?>
                        </div>
                    </div>
<?php
}
?>
                    <div class="form-group">
                        <?php echo zen_draw_label(ENTRY_EMAIL, 'send_welcome', 'class="col-sm-3 control-label"'); ?>
                        <div class="col-sm-9 col-md-6">
                            <?php echo zen_draw_checkbox_field('send_welcome', '1', isset($_POST['send_welcome']), 'class="form-control"'); ?>
                        </div>
                    </div>
                </div>
                <div class="row"><?php echo zen_draw_separator('pixel_trans.gif', '1', '10'); ?></div>
                <div class="row text-right">
                    <button name="insert" type="submit" class="btn btn-primary"><?php echo IMAGE_INSERT; ?></button>
                </div>
            </div>
            <?php echo '</form>'; ?>
        </div>
    </div>
<?php
require DIR_WS_INCLUDES . 'footer.php';
?>
</body>
</html>
<?php
require DIR_WS_INCLUDES . 'application_bottom.php';
