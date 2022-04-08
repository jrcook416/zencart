<?php
// -----
// Part of the "Add Customers from Admin" plugin.
//
/**
 * @copyright Copyright 2003-2010 Zen Cart Development Team
 * @copyright Portions Copyright 2003 osCommerce
 * @license http://www.zen-cart.com/license/2_0.txt GNU Public License V2.0
 *
 * add_customers module modified by Garden 2012-07-20
 * www.inzencart.cz Czech forum for ZenCart
 * Modified for Zen Cart 1.5.0, lat9 2012-08-31
 * Modified for Zen Cart 1.5.7, lat9 2021-12-06, converting to a PHP class
 *
 */
class addCustomers extends base
{
    public function __construct()
    {
        $this->headers = [];
        $this->sendWelcomeEmailAddressCouponError = false;

        $this->dateFormats = [
            'YYYY/MM/DD',
            'MM/DD/YYYY',
            'YYYY-MM-DD',
            'MM-DD-YYYY',
            'YYYY/DD/MM',
            'DD/MM/YYYY',
            'YYYY-DD-MM',
            'DD-MM-YYYY'
        ];

        $this->customers_inserted = 0;
    }

    // -----
    // Helper methods to retrieve the class-based variables.
    //
    public function getDateFormats()
    {
        return $this->dateFormats;
    }
    public function getCustomersInserted()
    {
        return $this->customers_inserted;
    }

    // -----
    // Called by the main script when the admin has submitted a bulk-import file.
    //
    public function checkFileUpload()
    {
        global $db;

        $errors = [];
        $to_insert = [];
        $line_num = 0;

        // -----
        // No file uploaded?  Nothing to be done ...
        //
        $files = (isset($_FILES['bulk_upload'])) ? $_FILES['bulk_upload'] : [];
        if (empty($files['name'])) {
            $errors[] = ERROR_NO_UPLOAD_FILE;
        } elseif ($files['error'] != 0) {
            $errors[] = sprintf(ERROR_FILE_UPLOAD, $files['error']);
        } else {
            $extension = pathinfo($files['name'],  PATHINFO_EXTENSION);
            $allowed_extensions = ['TXT', 'CSV'];
            if (empty($extension) || !in_array(strtoupper($extension), $allowed_extensions)) {
                $errors[] = sprintf(ERROR_BAD_FILE_EXTENSION, $extension) . implode(', ', $allowed_extensions);
            } else {
                $filepath = DIR_FS_BACKUP . $files['name'];

                if (move_uploaded_file($files['tmp_name'], $filepath) === false) {
                    $errors[] = ERROR_CANT_MOVE_FILE;
                } else {
                    chmod($filepath, 0775);
                    ini_set('auto_detect_line_endings', true);
                    $lines = file($filepath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);

                    $valid_headers = explode(', ', 'email, first_name, last_name, dob, gender, company, street_address, suburb, city, state, postcode, country, telephone, fax, newsletter, send_welcome, zone_id, customers_group_pricing');

                    $found_header = false;
                    foreach ($lines as $line) {
                        if (empty($line)) {
                            continue;
                        }
                        $line_num++;
                        $line = explode(',', $line);

                        // Process header row
                        if ($found_header === false) {
                            $found_header = true;
                            foreach ($line as $header_position => $header_label) {
                                $header_label = strtolower(trim($header_label));
                                if (in_array($header_label, $valid_headers)) {
                                    $this->headers[$header_label] = $header_position;
                                }
                            }
                        // Process data row
                        } elseif (count($this->headers) === 0) {
                            $errors[] = ERROR_BAD_FILE_HEADER;
                            break;
                        } else {
                            $country = trim(strtoupper($this->getValue('country', $line)));

                            if ($country === 'UK') {
                                $country = 'GB';
                            }
                            $country_query = $db->Execute(
                                "SELECT countries_id
                                   FROM " . TABLE_COUNTRIES . "
                                  WHERE countries_iso_code_2 = '" . zen_db_input($country) . "'
                                  LIMIT 1"
                            );
                            $country_id = (!$country_query->EOF) ? $country_query->fields['countries_id'] : false;

                            $gender = strtolower($this->getValue('gender', $line));
                            $gender = substr($gender, 0, 1);

                            //dynamic type
                            $state = $this->getValue('state', $line);
                            $zone_id = $this->getValue('zone_id', $line);
                            if ($country_id !== false) {
                                $zones = $db->Execute(
                                    "SELECT zone_id, zone_name, zone_code
                                       FROM " . TABLE_ZONES . "
                                      WHERE zone_country_id = " . $country_id . "
                                        AND zone_name = '" . zen_db_input($state) . "'
                                         OR zone_code = '" . zen_db_input($state) . "'
                                         OR zone_id = " . (int)$zone_id . "
                                      LIMIT 1"
                                );
                            }
                            // Country doesn't have zones (or country isn't valid), use state/province name
                            if ($country_id !== false && $zones->EOF) {
                                $zone_id = 0;
                            // Country has zones, use the zone_id
                            } else {
                                $state = '';
                                $zone_id = $zones->fields['zone_id'];
                            }
                            $values = [
                                'customers_gender' => $gender,
                                'customers_firstname' => $this->getValue('first_name', $line),
                                'customers_lastname' => $this->getValue('last_name', $line),
                                'customers_dob' => $this->getValue('dob', $line),
                                'customers_email_address' => $this->getValue('email', $line),
                                'entry_company' => $this->getValue('company', $line),
                                'entry_street_address' => $this->getValue('street_address', $line),
                                'entry_suburb' => $this->getValue('suburb', $line),
                                'entry_city' => $this->getValue('city', $line),
                                'entry_state' => $state,
                                'entry_postcode' => $this->getValue('postcode', $line),
                                'entry_country_id' => $country_id,
                                'entry_zone_id' => $zone_id,
                                'customers_telephone' => $this->getValue('telephone', $line),
                                'customers_fax' => $this->getValue('fax', $line),
                                'customers_newsletter' => ($this->getValue('newsletter', $line) === '1') ? '1' : '0',
                                'send_welcome' => ($this->getValue('send_welcome', $line) === '1') ? '1' : '0',
                                'customers_authorization' => '0',
                                'customers_group_pricing' => ($this->getValue('customers_group_pricing', $line) === false) ? '0' : $this->getValue('customers_group_pricing', $line),
                                'customers_referral' => '',
                                'customers_email_format' => (ACCOUNT_EMAIL_PREFERENCE === '1' ? 'HTML' : 'TEXT'),
                            ];

                            list($notused, $validation_errors) = $this->validateCustomer($values, 'date_format_m');

                            if (!empty($validation_errors)) {
                                $errors[$line_num] = $validation_errors;
                            } else {
                                if ($_POST['insert_mode'] === 'part') {
                                    $this->insertCustomer($values);
                                } else {
                                    $to_insert[] = $values;
                                }
                            }
                        }
                    }

                    if (count($errors) === 0) {
                        if ($found_header === false) {
                            $errors[] = ERROR_BAD_FILE_HEADER;
                        } elseif ($line_num === 0) {
                            $errors[] = ERROR_NO_RECORDS;
                        } elseif ($_POST['insert_mode'] === 'file') {
                            foreach ($to_insert as $values) {
                                $this->insertCustomer($values);
                            }
                        }
                    }

                    unlink($filepath);
                }
            }
        }
        return $errors;
    }

    protected function getValue($field_name, $line)
    {
        return (isset($this->headers[$field_name])) ? $line[$this->headers[$field_name]] : false;
    }

    public function insertCustomer($info)
    {
        global $db, $messageStack;

        $this->customers_inserted++;
      
        $customers_password = zen_create_PADSS_password(((int)ENTRY_PASSWORD_MIN_LENGTH > 0) ? (int)ENTRY_PASSWORD_MIN_LENGTH : 5);

        $customers_firstname = (isset($info['customers_firstname'])) ? zen_db_prepare_input(zen_sanitize_string($info['customers_firstname'])) : '';
        $customers_lastname = (isset($info['customers_lastname'])) ? zen_db_prepare_input(zen_sanitize_string($info['customers_lastname'])) : '';
        if (!isset($info['customers_email_address'])) {
            trigger_error("insertCustomer, missing email address: " . var_export($info, true), E_USER_ERROR);
            session_write_close();
            exit();
        }
        $customers_email_address = zen_db_prepare_input($info['customers_email_address']);
        $customers_telephone = (isset($info['customers_telephone'])) ? zen_db_prepare_input($info['customers_telephone']) : '';
        $customers_fax = (isset($info['customers_fax'])) ? zen_db_prepare_input($info['customers_fax']) : '';
        $customers_newsletter = (isset($info['customers_newsletter'])) ? zen_db_prepare_input($info['customers_newsletter']) : '0';
        $customers_group_pricing = (isset($info['customers_group_pricing'])) ? (int)$info['customers_group_pricing'] : '0';
        $customers_email_format = (isset($info['customers_email_format'])) ? zen_db_prepare_input($info['customers_email_format']) : ((ACCOUNT_EMAIL_PREFERENCE === '1') ? 'HTML' : 'TEXT');
        $customers_gender = (isset($info['customers_gender'])) ? zen_db_prepare_input($info['customers_gender']) : '';
        $customers_dob = (isset($info['customers_dob'])) ? zen_db_prepare_input($info['customers_dob']) : '0001-01-01 00:00:00';

        $customers_authorization = (isset($info['customers_authorization'])) ? (int)$info['customers_authorization'] : '0';
        $customers_referral= (isset($info['customers_referral'])) ? zen_db_prepare_input($info['customers_referral']) : '';

        $send_welcome = (isset($info['send_welcome']) && $info['send_welcome'] === '1');

        if (CUSTOMERS_APPROVAL_AUTHORIZATION === '2' && $customers_authorization === '1') {
            $customers_authorization = '2';
            $messageStack->add_session(ERROR_CUSTOMER_APPROVAL_CORRECTION2, 'caution');
        }

        if (CUSTOMERS_APPROVAL_AUTHORIZATION === '1' && $customers_authorization === '2') {
            $customers_authorization = '1';
            $messageStack->add_session(ERROR_CUSTOMER_APPROVAL_CORRECTION1, 'caution');
        }

        $entry_street_address = (isset($info['entry_street_address'])) ? zen_db_prepare_input($info['entry_street_address']) : '';
        $entry_suburb = (isset($info['entry_suburb'])) ? zen_db_prepare_input($info['entry_suburb']) : '';
        $entry_postcode = (isset($info['entry_postcode'])) ? zen_db_prepare_input($info['entry_postcode']) : '';
        $entry_city = (isset($info['entry_city'])) ? zen_db_prepare_input($info['entry_city']) : '';
        $entry_country_id = (isset($info['entry_country_id'])) ? (int)$info['entry_country_id'] : 0;

        $entry_company = (isset($info['entry_company'])) ? zen_db_prepare_input($info['entry_company']) : '';
        $entry_state = (isset($info['entry_state'])) ? zen_db_prepare_input($info['entry_state']) : '';

        $entry_zone_id = (isset($info['entry_zone_id'])) ? (int)$info['entry_zone_id'] : 0;

        $sql_data_array = [
            'customers_firstname'     => $customers_firstname,
            'customers_lastname'      => $customers_lastname,
            'customers_email_address' => $customers_email_address,
            'customers_telephone'     => $customers_telephone,
            'customers_fax'           => $customers_fax,
            'customers_group_pricing' => $customers_group_pricing,
            'customers_newsletter'    => $customers_newsletter,
            'customers_email_format'  => $customers_email_format,
            'customers_authorization' => $customers_authorization,
            'customers_password'      => zen_encrypt_password($customers_password),
        ];

        if (ACCOUNT_GENDER === 'true') {
            $sql_data_array['customers_gender'] = $customers_gender;
        }
        if (ACCOUNT_DOB === 'true') {
            $sql_data_array['customers_dob'] = $customers_dob;
        }
        if (CUSTOMERS_REFERRAL_STATUS === '2' && $customers_referral !== '') {
            $sql_data_array['customers_referral'] = $customers_referral;
        }
      
        zen_db_perform(TABLE_CUSTOMERS, $sql_data_array);
        $customer_id = $db->Insert_ID();

        $sql_data_array = [
           'customers_id'         => $customer_id,
           'entry_firstname'      => $customers_firstname,
           'entry_lastname'       => $customers_lastname,
           'entry_street_address' => $entry_street_address,
           'entry_postcode'       => $entry_postcode,
           'entry_city'           => $entry_city,
           'entry_country_id'     => $entry_country_id,
       ];

        if (ACCOUNT_COMPANY === 'true') {
            $sql_data_array['entry_company'] = $entry_company;
        }
        if (ACCOUNT_SUBURB === 'true') {
            $sql_data_array['entry_suburb'] = $entry_suburb;
        }
        if (ACCOUNT_STATE === 'true') {
            if ($entry_zone_id > 0) {
                $sql_data_array['entry_zone_id'] = $entry_zone_id;
                $sql_data_array['entry_state'] = '';
            } else {
                $sql_data_array['entry_zone_id'] = '0';
                $sql_data_array['entry_state'] = $entry_state;
            }
        }
        zen_db_perform(TABLE_ADDRESS_BOOK, $sql_data_array);
        $address_id = $db->Insert_ID();

        $db->Execute(
            "UPDATE " . TABLE_CUSTOMERS . "
                SET customers_default_address_id = " . $address_id . "
              WHERE customers_id = " . $customer_id . "
              LIMIT 1"
        );
        $db->Execute(
            "INSERT INTO " . TABLE_CUSTOMERS_INFO . "
                (customers_info_id, customers_info_number_of_logons, customers_info_date_account_created)
             VALUES
                (" . $customer_id . ", 0, now())"
        );

        // build the message content
        if ($send_welcome === true) {
            $this->sendWelcomeEmail($customers_gender, $customers_firstname, $customers_lastname, $customers_email_address, $customers_password);
        }
        return $customers_firstname . ' ' . $customers_lastname;
    }

    function sendWelcomeEmail($customers_gender, $customers_firstname, $customers_lastname, $customers_email_address, $customers_password)
    {
        global $db, $currencies, $messageStack;
      
        $name = $customers_firstname . ' ' . $customers_lastname;

        if (ACCOUNT_GENDER === 'true') {
            if ($customers_gender == 'm') {
                $email_text = sprintf(EMAIL_GREET_MR, $customers_lastname);
            } else {
                $email_text = sprintf(EMAIL_GREET_MS, $customers_lastname);
            }
        } else {
            $email_text = sprintf(EMAIL_GREET_NONE, $customers_firstname);
        }

        $html_msg['EMAIL_GREETING'] = str_replace('\n', '', $email_text);
        $html_msg['EMAIL_FIRST_NAME'] = $customers_firstname;
        $html_msg['EMAIL_LAST_NAME']  = $customers_lastname;

        // initial welcome
        $email_text .=  EMAIL_WELCOME;
        $html_msg['EMAIL_WELCOME'] = str_replace('\n', '', EMAIL_WELCOME);

        if (NEW_SIGNUP_DISCOUNT_COUPON !== '' && NEW_SIGNUP_DISCOUNT_COUPON != '0') {
            $coupon_id = (int)NEW_SIGNUP_DISCOUNT_COUPON;
            $coupon = $db->Execute(
                "SELECT c.coupon_code, cd.coupon_description
                   FROM " . TABLE_COUPONS . " c
                        LEFT JOIN " . TABLE_COUPONS_DESCRIPTION . " cd
                            ON cd.coupon_id = c.coupon_id
                           AND cd.language_id = " . $_SESSION['languages_id'] . "
                  WHERE c.coupon_id = " . $coupon_id . "
                  LIMIT 1"
            );

            if ($coupon->EOF) {
                if ($this->sendWelcomeEmailAddressCouponError === false) {
                    $this->sendWelcomeEmailAddressCouponError = true;
                    $messageStack->add_session(sprintf(ERROR_MISSING_CREATE_ACCOUNT_COUPON, $coupon_id), 'error');
                }
            } else {
                $db->Execute(
                    "INSERT INTO " . TABLE_COUPON_EMAIL_TRACK . "
                        (coupon_id, customer_id_sent, sent_firstname, emailed_to, date_sent)
                     VALUES
                        ('" . $coupon_id . "', '0', 'Admin', '" . $customers_email_address . "', now())"
                );

                $coupon_description = $coupon->fields['coupon_description'];
                $email_text .= 
                    "\n" . EMAIL_COUPON_INCENTIVE_HEADER .
                    (!empty($coupon_description) ? $coupon_description . "\n\n" : '') .
                    strip_tags(sprintf(EMAIL_COUPON_REDEEM, ' ' . $coupon->fields['coupon_code'])) .
                    EMAIL_SEPARATOR;
                $html_msg['COUPON_TEXT_VOUCHER_IS'] = EMAIL_COUPON_INCENTIVE_HEADER ;
                $html_msg['COUPON_DESCRIPTION'] = (!empty($coupon_description) ? '<strong>' . $coupon_description . '</strong>' : '');
                $html_msg['COUPON_TEXT_TO_REDEEM']  = str_replace("\n", '', sprintf(EMAIL_COUPON_REDEEM, ''));
                $html_msg['COUPON_CODE']  = $coupon->fields['coupon_code'];
            }
        }

        if (NEW_SIGNUP_GIFT_VOUCHER_AMOUNT > 0) {
            $coupon_code = create_coupon_code();
            $db->Execute(
                "INSERT INTO " . TABLE_COUPONS . "
                    (coupon_code, coupon_type, coupon_amount, date_created)
                 VALUES
                    ('" . $coupon_code . "', 'G', '" . NEW_SIGNUP_GIFT_VOUCHER_AMOUNT . "', now())"
            );
            $insert_id = $db->Insert_ID();
            $db->Execute(
                "INSERT INTO " . TABLE_COUPON_EMAIL_TRACK . "
                    (coupon_id, customer_id_sent, sent_firstname, emailed_to, date_sent)
                 VALUES
                    ($insert_id , '0', 'Admin', '" . $customers_email_address . "', now())"
            );

            // if on, add in GV explanation
            $email_text .= "\n\n" . sprintf(EMAIL_GV_INCENTIVE_HEADER, $currencies->format(NEW_SIGNUP_GIFT_VOUCHER_AMOUNT)) .
            sprintf(EMAIL_GV_REDEEM, $coupon_code) .
                EMAIL_GV_LINK .
                zen_catalog_href_link(FILENAME_GV_REDEEM, 'gv_no=' . $coupon_code, 'NONSSL', false) . "\n\n" .
                EMAIL_GV_LINK_OTHER .
                EMAIL_SEPARATOR;
            $html_msg['GV_WORTH'] = str_replace('\n', '', sprintf(EMAIL_GV_INCENTIVE_HEADER, $currencies->format(NEW_SIGNUP_GIFT_VOUCHER_AMOUNT)) );
            $html_msg['GV_REDEEM'] = str_replace('\n', '', str_replace('\n\n', '<br>', sprintf(EMAIL_GV_REDEEM, '<strong>' . $coupon_code . '</strong>')));
            $html_msg['GV_CODE_NUM'] = $coupon_code;
            $html_msg['GV_CODE_URL'] = str_replace('\n', '', EMAIL_GV_LINK . '<a href="' . zen_catalog_href_link(FILENAME_GV_REDEEM, 'gv_no=' . $coupon_code, 'NONSSL', false) . '">' . TEXT_GV_NAME . ': ' . $coupon_code . '</a>');
            $html_msg['GV_LINK_OTHER'] = EMAIL_GV_LINK_OTHER;
        }

        // -----
        // Add in regular email welcome text.
        //
        $email_text .= "\n\n" . EMAIL_TEXT_1 . (($customers_password !== false) ? sprintf(EMAIL_TEXT_2, $customers_password) : '') . EMAIL_TEXT_3 . EMAIL_CONTACT . EMAIL_GV_CLOSURE;

        $html_msg['EMAIL_MESSAGE_HTML'] = str_replace('\n', '', EMAIL_TEXT_1 . (($customers_password !== false) ? sprintf(EMAIL_TEXT_2, $customers_password) : '') . EMAIL_TEXT_3);
        $html_msg['EMAIL_CONTACT_OWNER'] = str_replace('\n', '', EMAIL_CONTACT);
        $html_msg['EMAIL_CLOSURE'] = nl2br(EMAIL_GV_CLOSURE);

        // include create-account-specific disclaimer
        $email_text .= "\n\n" . sprintf(EMAIL_DISCLAIMER_NEW_CUSTOMER, STORE_OWNER_EMAIL_ADDRESS). "\n\n";
        $html_msg['EMAIL_DISCLAIMER'] = sprintf(EMAIL_DISCLAIMER_NEW_CUSTOMER, '<a href="mailto:' . STORE_OWNER_EMAIL_ADDRESS . '">'. STORE_OWNER_EMAIL_ADDRESS .' </a>');

        // send welcome email
        zen_mail($name, $customers_email_address, EMAIL_SUBJECT, $email_text, STORE_NAME, EMAIL_FROM, $html_msg, 'welcome');

        // send additional emails
        if (SEND_EXTRA_CREATE_ACCOUNT_EMAILS_TO_STATUS === '1' && SEND_EXTRA_CREATE_ACCOUNT_EMAILS_TO !== '') {
            $extra_info = email_collect_extra_info($name, $customers_email_address, $customers_firstname . ' ' . $customers_lastname , $customers_email_address);
            $admin_html_msg['EXTRA_INFO'] = $extra_info['HTML'];
            if ($customers_password !== false) {
                $email_text = str_replace($customers_password, 'xxxx', $email_text);
                $html_msg['EMAIL_MESSAGE_HTML'] = str_replace($customers_password, 'xxxx', $html_msg['EMAIL_MESSAGE_HTML']);
            }
            zen_mail('', SEND_EXTRA_CREATE_ACCOUNT_EMAILS_TO, '[ACCOUNT CREATED BY ADMINISTRATOR]' . ' ' . EMAIL_SUBJECT, $email_text . $extra_info['TEXT'], STORE_NAME, EMAIL_FROM, $html_msg, 'welcome_extra');
        }
    }

    public function validateCustomer($info, $dateFormatName)
    {
        global $db, $messageStack;

        $errors = [];
        $cInfo = [];

        $customers_firstname = (isset($info['customers_firstname'])) ? zen_db_prepare_input($info['customers_firstname']) : '';
        $customers_lastname = (isset($info['customers_lastname'])) ? zen_db_prepare_input($info['customers_lastname']) : '';
        $customers_email_address = (isset($info['customers_email_address'])) ? zen_db_prepare_input($info['customers_email_address']) : '';
        $customers_telephone = (isset($info['customers_telephone'])) ? zen_db_prepare_input($info['customers_telephone']) : '';
        $customers_fax = (isset($info['customers_fax'])) ? zen_db_prepare_input($info['customers_fax']) : '';
        $customers_group_pricing = (isset($info['customers_group_pricing'])) ? (int)$info['customers_group_pricing'] : 0;
        $customers_gender = (isset($info['customers_gender'])) ? zen_db_prepare_input($info['customers_gender']) : '';
        $customers_dob = (isset($info['customers_dob'])) ? zen_db_prepare_input($info['customers_dob']) : '';
        $customers_authorization = (isset($info['customers_authorization'])) ? (int)$info['customers_authorization'] : 0;

        if (CUSTOMERS_APPROVAL_AUTHORIZATION === '2' && $customers_authorization === '1') {
            $customers_authorization = '2';
            $messageStack->add_session(ERROR_CUSTOMER_APPROVAL_CORRECTION2, 'caution');
        } elseif (CUSTOMERS_APPROVAL_AUTHORIZATION === '1' && $customers_authorization === '2') {
            $customers_authorization = '1';
            $messageStack->add_session(ERROR_CUSTOMER_APPROVAL_CORRECTION1, 'caution');
        }

        $entry_street_address = (isset($info['entry_street_address'])) ? zen_db_prepare_input($info['entry_street_address']) : '';
        $entry_suburb = (isset($info['entry_suburb'])) ? zen_db_prepare_input($info['entry_suburb']) : '';
        $entry_postcode = (isset($info['entry_postcode'])) ? zen_db_prepare_input($info['entry_postcode']) : '';
        $entry_city = (isset($info['entry_city'])) ? zen_db_prepare_input($info['entry_city']) : '';
        $entry_country_id = (isset($info['entry_country_id'])) ? (int)$info['entry_country_id'] : 0;

        $entry_company = (isset($info['entry_company'])) ? zen_db_prepare_input($info['entry_company']) : '';
        $entry_state = (isset($info['entry_state'])) ? zen_db_prepare_input($info['entry_state']) : '';
        $entry_zone_id = (isset($info['entry_zone_id'])) ? (int)$info['entry_zone_id'] : 0;

        if (ACCOUNT_GENDER === 'true') {
            if ($customers_gender !== 'm' && $customers_gender !== 'f') {
                $errors[] = ERROR_GENDER . "'$customers_gender'";
            }
        }

        if (strlen($customers_firstname) < ENTRY_FIRST_NAME_MIN_LENGTH) {
            $errors[] = ERROR_FIRST_NAME . " ($customers_firstname)";
        }

        if (strlen($customers_lastname) < ENTRY_LAST_NAME_MIN_LENGTH) {
            $errors[] = ERROR_LAST_NAME . " ($customers_lastname)";
        }

        $dob_account = ACCOUNT_DOB;
        if (ACCOUNT_DOB !== 'true') {
            $customers_dob = '0001-01-01 00:00:00';
        } else {
            $dobFormat = $this->dateFormats[(isset($_POST[$dateFormatName])) ? (int)$_POST[$dateFormatName] : 0];
            $dobError  = sprintf(ERROR_DOB_INVALID, $dobFormat);
            if (ENTRY_DOB_MIN_LENGTH > 0 && strlen($customers_dob) < ENTRY_DOB_MIN_LENGTH) {
                $errors[] = $dobError . " ($customers_dob)";
            } else {
                $month = (int)substr($customers_dob, strpos($dobFormat, 'MM'), 2);
                $day = (int)substr($customers_dob, strpos($dobFormat, 'DD'), 2);
                $year = (int)substr($customers_dob, strpos($dobFormat, 'YYYY'), 4);

                if (!checkdate($month, $day, $year)) {
                    $errors[] = $dobError . " ($customers_dob)";
                } else {
                    $info['customers_dob'] = date('Y-m-d H:i:s', mktime(0, 0, 0, $month, $day, $year));
                }
            }
        }

        if (strlen($customers_email_address) < ENTRY_EMAIL_ADDRESS_MIN_LENGTH) {
            $errors[] = ERROR_EMAIL_LENGTH . " ($customers_email_address)";
        } elseif (!zen_validate_email($customers_email_address)) {
            $errors[] = ERROR_EMAIL_INVALID . " ($customers_email_address)";
        } else {
            $check_email = $db->Execute(
                "SELECT customers_id
                   FROM " . TABLE_CUSTOMERS . "
                  WHERE customers_email_address = '" . zen_db_input($customers_email_address) . "'
                  LIMIT 1"
            );
            if (!$check_email->EOF) {
                $errors[] = sprintf(ERROR_EMAIL_ADDRESS_ERROR_EXISTS, $customers_email_address);
            }
        }

        if (ACCOUNT_COMPANY === 'true' && ENTRY_COMPANY_MIN_LENGTH > 0) {
            if (strlen($entry_company) < ENTRY_COMPANY_MIN_LENGTH) {
                $errors[] = sprintf(ERROR_COMPANY, ENTRY_COMPANY_MIN_LENGTH) . " ($entry_company)";
            }
        }

        if (strlen($entry_street_address) < ENTRY_STREET_ADDRESS_MIN_LENGTH) {
            $errors[] = ERROR_STREET_ADDRESS . " ($entry_street_address)";
        }

        if (strlen($entry_city) < ENTRY_CITY_MIN_LENGTH) {
            $errors[] = ERROR_CITY . " ($entry_city)";
        }

        $error_country = false;
        if (empty($entry_country_id)) {
            $error_country = true;
            $errors[] = ERROR_COUNTRY;
        }

        if (ACCOUNT_STATE === 'true' && $error_country === false) {
            $check_value = $db->Execute(
                "SELECT zone_id
                   FROM " . TABLE_ZONES . "
                  WHERE zone_country_id = " . (int)$entry_country_id . "
                  LIMIT 1"
            );
            if (!$check_value->EOF) {
                $zone_query = $db->Execute(
                    "SELECT zone_id
                       FROM " . TABLE_ZONES . "
                      WHERE zone_country_id = " . (int)$entry_country_id . "
                        AND (
                            zone_id = " . (int)$entry_zone_id . " OR
                            zone_code = '" . zen_db_input($entry_state) . "' OR
                            zone_name = '" . zen_db_input($entry_state) . "'
                        )
                      LIMIT 1"
                );
                if ($zone_query->EOF) {
                    $errors[] = ERROR_SELECT_STATE . " ($entry_state)";
                } else {
                    $info['entry_zone_id'] = $zone_query->fields['zone_id'];
                }
            } elseif ($entry_state === '') {
                $errors[] = ERROR_STATE_REQUIRED . " ($entry_state)";
            }
        }

        if (strlen($entry_postcode) < ENTRY_POSTCODE_MIN_LENGTH) {
            $errors[] = ERROR_POSTCODE . " ($entry_postcode)";
        } elseif ($error_country === false) {
            $errMsg = $this->postcode_validate($entry_postcode, $entry_country_id);
            if ($errMsg !== false) {
                $errors[] = $errMsg;
            }
        }

        //means that a telephone is not required but if it is given then it is subject to validation
        if (strlen($customers_telephone) < ENTRY_TELEPHONE_MIN_LENGTH) {
            $errors[] = ERROR_TELEPHONE . " ($customers_telephone)";
        } else {
            if (($errMsg = $this->validatePhone($customers_telephone)) !== false) {
                $errors[] = $errMsg;
            }
        }

        if ($customers_group_pricing !== 0) {
            $cgp = $db->Execute(
                "SELECT group_name
                   FROM " . TABLE_GROUP_PRICING . "
                  WHERE group_id = " . $customers_group_pricing . "
                  LIMIT 1"
            );
            if ($cgp->EOF) {
                $errors[] = sprintf(ERROR_UNKNOWN_GROUP_PRICING, $customers_group_pricing);
            }
        }

        if (count($errors)) {
            $cInfo = new objectInfo($info);
        }

        // -----
        // Return a simple array suitable for retrieval via list.
        //
        return [$cInfo, $errors];
    }

    // -----
    // Validate the phone number supplied.
    //
    protected function validatePhone($telephone)
    {
        // -----
        // Bypass the world phone prefix if it's the first character in the phone number.
        //
        $start_pos = 0;
        if (ENTRY_PHONE_NO_DELIM_WORLD !== false && strpos($telephone, ENTRY_PHONE_NO_DELIM_WORLD) === 0) {
            $start_pos = 1;
        }

        // -----
        // Remove all the delimiter characters, the remaining telephone should contain only digits (0-9).
        //
        $telephone = str_replace(str_split(ENTRY_PHONE_NO_DELIMS), '', $telephone);

        for ($i = $start_pos, $errorMessage = false, $num_digits = 0, $telephone_len = strlen($telephone); $i < $telephone_len && !$errorMessage; $i++) {
            if ($telephone[$i] < '0' || $telephone[$i] > '9') {
                $errorMessage = sprintf(ENTRY_PHONE_NO_CHAR_ERROR, $telephone[$i]);
            } else {
                $num_digits++;
            }
        }

        if ($errorMessage !== false) {
            if ($num_digits < ENTRY_PHONE_NO_MIN_DIGITS) {
                $errorMessage = ENTRY_PHONE_NO_MIN_ERROR;
            } elseif ($num_digits > ENTRY_PHONE_NO_MAX_DIGITS) {
                $errorMessage = ENTRY_PHONE_NO_MAX_ERROR;
            }
        }
        return $errorMessage;
    }

    /*----
    ** Validate a country-specific zip/postcode; the country code supplied is the country's numeric code.
    ** Returns false if no error, otherwise an error message string.
    ** If the postcode validates, the postcode might be updated with a re-formatted version of the value (e.g. uppercased).
    */
    protected function postcode_validate(&$postcode, $country_id)
    {
        $formats = [
            38 => '#(^[ABCEGHJ-NPRSTVXY]\d[ABCEGHJ-NPRSTV-Z] {0,1}\d[ABCEGHJ-NPRSTV-Z]\d$)#i',
            222 => '#(((^[BEGLMNS][1-9]\d?)|(^W[2-9])|(^(A[BL]|B[ABDHLNRST]|C[ABFHMORTVW]|D[ADEGHLNTY]|E[HNX]|F[KY]|G[LUY]|H[ADGPRSUX]|I[GMPV]|JE|K[ATWY]|L[ADELNSU]|M[EKL]|N[EGNPRW]|O[LX]|P[AEHLOR]|R[GHM]|S[AEGKL-PRSTWY]|T[ADFNQRSW]|UB|W[ADFNRSV]|YO|ZE)\d\d?)|(^W1[A-HJKSTUW0-9])|(((^WC[1-2])|(^EC[1-4])|(^SW1))[ABEHMNPRVWXY]))(\s*)?([0-9][ABD-HJLNP-UW-Z]{2}))$|(^GIR\s?0AA$)#i',
            223 => '#(^\d{5}$)|(^\d{5}-\d{4}$)#i',
        ];

        $errorMessage = false;

        if (array_key_exists($country_id, $formats)) {
            $temp = strtoupper($postcode);
            if (preg_match($formats[$country_id], $temp, $matches) == 0) {
                $errorMessage = sprintf(ENTRY_POSTCODE_NOT_VALID, $postcode, zen_get_country_name($country_id));
            } else {
                $postcode = $temp;
            }
        }
        return $errorMessage;
    }

    public function createCustomerDropdown()
    {
        global $db;
        $customers = [
            ['id' => '0', 'text' => TEXT_PLEASE_CHOOSE],
        ];

        $and_clause = (defined('CHECKOUT_ONE_GUEST_CUSTOMER_ID')) ? (' AND c.customers_id != ' . CHECKOUT_ONE_GUEST_CUSTOMER_ID) : '';
        $customersRecords = $db->Execute(
            "SELECT c.customers_id, c.customers_firstname, c.customers_lastname, c.customers_email_address
               FROM " . TABLE_CUSTOMERS . " c
                    LEFT JOIN " . TABLE_CUSTOMERS_INFO . " ci
                        ON ci.customers_info_id = c.customers_id
              WHERE ci.customers_info_date_of_last_logon IS NULL
              $and_clause
              ORDER BY c.customers_firstname, c.customers_lastname, c.customers_email_address"
        );
        foreach ($customersRecords as $next_customer) {
            $customers[] = [
                'id' => $next_customer['customers_id'],
                'text' => $next_customer['customers_firstname'] . ' ' . $next_customer['customers_lastname'] . ' (' . $next_customer['customers_email_address'] . ')'
            ];
        }
        return $customers;
    }
}
