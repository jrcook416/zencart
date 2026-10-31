<?php
/**
 * Export Shipping/Order Information (inventory feed).
 *
 * Port of the 1.5.6c admin/shipping_export.php (Export Shipping and Order
 * Information v1.3.2 by Eric Leuenberger, with IEMS edits) to Zen Cart 2.2.2.
 *
 * The export file must stay byte-for-byte compatible with the 1.5.6c output,
 * because the inventory system imports it. Column order, header text, quoting,
 * line endings and value formatting are deliberately unchanged. Differences
 * from the legacy page are limited to:
 *  - the per-store order ID prefix and file name prefix come from
 *    Configuration > IEMS Legacy Bridge instead of being hard-coded;
 *  - PHP 8 / MySQL 8 fixes (undefined POST keys, "GROUP BY ... ASC");
 *  - request values are cast/escaped before use in SQL;
 *  - the spiffyCal date picker is replaced by HTML date inputs (same
 *    yyyy-mm-dd value format).
 *
 * @copyright Copyright 2009, Eric Leuenberger http://www.zencartoptimization.com
 * @copyright Portions Copyright 2003-2006 Zen Cart Development Team
 * @license http://www.zen-cart.com/license/2_0.txt GNU Public License V2.0
 */

require 'includes/application_top.php';

if (!zen_is_superuser() && !check_page(FILENAME_SHIPPING_EXPORT, [])) {
    zen_record_admin_activity(
        'Attempted access to unauthorized page [shipping_export]. Redirected to DENIED page instead.',
        'notice'
    );
    zen_redirect(zen_href_link(FILENAME_DENIED, '', 'SSL'));
}

include DIR_FS_CATALOG . DIR_WS_CLASSES . 'order.php';

/**
 * Legacy checkboxes were tested with "$_POST['x'] == 1".
 */
function iems_export_flag(string $name): bool
{
    return isset($_POST[$name]) && (string)$_POST[$name] === '1';
}

function iems_export_date(string $name): string
{
    $value = trim((string)($_POST[$name] ?? ''));
    return preg_match('/^\d{4}-\d{2}-\d{2}$/', $value) ? $value : '';
}

$order_prefix = defined('IEMS_LEGACY_EXPORT_ORDER_PREFIX') ? (string)IEMS_LEGACY_EXPORT_ORDER_PREFIX : '';
$file_prefix = defined('IEMS_LEGACY_EXPORT_FILE_PREFIX') && IEMS_LEGACY_EXPORT_FILE_PREFIX !== '' ? (string)IEMS_LEGACY_EXPORT_FILE_PREFIX : 'Orders';
$export_dir_setting = trim(defined('IEMS_LEGACY_EXPORT_DIR') && IEMS_LEGACY_EXPORT_DIR !== '' ? (string)IEMS_LEGACY_EXPORT_DIR : 'images/uploads/');
// An absolute path (e.g. /home/<acct>/exports/) keeps saved exports out of the web root.
$export_dir = (str_starts_with($export_dir_setting, '/') ? rtrim($export_dir_setting, '/') : DIR_FS_CATALOG . trim($export_dir_setting, '/')) . '/';
// Downloads also leave an archive copy, but only in a folder outside the store,
// so customer data is never left where the web server can serve it.
$archive_downloads = str_starts_with($export_dir_setting, '/')
    && !str_starts_with(realpath($export_dir) ?: $export_dir, rtrim(realpath(DIR_FS_CATALOG) ?: DIR_FS_CATALOG, '/') . '/');
$default_email = defined('IEMS_LEGACY_EXPORT_EMAIL') ? (string)IEMS_LEGACY_EXPORT_EMAIL : '';

/** Set Available Export Formats **/
$available_export_formats = [
    0 => ['id' => '0', 'text' => 'CSV'],
    1 => ['id' => '1', 'text' => 'TXT'],
];

$save_to_file_checked = iems_export_flag('savetofile') ? 1 : 0;
$post_format = (int)($_POST['format'] ?? 1);
if (!isset($available_export_formats[$post_format])) {
    $post_format = 1;
}
$format = $available_export_formats[$post_format]['text'];

if ($format === 'CSV') {
    $file_extension = '.csv';
    $FIELDSTART = '"';
    $FIELDEND = '"';
    $FIELDSEPARATOR = ',';
    $LINESTART = '';
    $LINEBREAK = "\n";
    $ATTRIBSEPARATOR = ' | ';
} else {
    $file_extension = '.txt';
    $FIELDSTART = '';
    $FIELDEND = '';
    $FIELDSEPARATOR = "\t";
    $LINESTART = '';
    $LINEBREAK = "\n";
    $ATTRIBSEPARATOR = ' | ';
}

$file = basename((string)($_POST['filename'] ?? ($file_prefix . date('mdy-Hi') . $file_extension)));
$to_email_address = (string)($_POST['auto_email_supplier'] ?? $default_email);
$email_subject = (string)($_POST['auto_email_subject'] ?? sprintf(EMAIL_EXPORT_SUBJECT, STORE_NAME));
$success_message = '';

if (isset($_POST['download_csv'])) {
    $start_date = '';
    $end_date = '';
    $post_start = iems_export_date('start_date');
    $post_end = iems_export_date('end_date');
    if ($post_start !== '') {
        $start_date = $post_start . ' 00:00';
    }
    if ($post_end !== '') {
        $end_date = $post_end . ' 23:59';
    }
    $date_range = ($post_start !== '' && $post_end !== '');

    $include_downloaded = iems_export_flag('dload_include');
    $status_filter = ((int)($_POST['status_target'] ?? 0) === 2);
    $filter_status_id = (int)($_POST['order_status'] ?? 0);
    $iso2 = iems_export_flag('iso_country2_code');
    $iso3 = iems_export_flag('iso_country3_code');
    $one_product_per_row = ((int)($_POST['filelayout'] ?? 0) === 2);

    $order_filters = '';
    if (!$include_downloaded) {
        $order_filters .= " AND downloaded_ship='no'";
    }
    if ($status_filter) {
        $order_filters .= " AND o.orders_status = '" . $filter_status_id . "'";
    }
    if ($date_range) {
        $order_filters .= " AND date_purchased BETWEEN '" . $start_date . "' AND '" . $end_date . "'";
    }

    $max_products = 0;
    if ($one_product_per_row) {
        $order_info = "SELECT o.orders_id, customers_email_address, delivery_name, delivery_company, delivery_street_address, delivery_suburb, delivery_city, delivery_postcode, delivery_state, delivery_country, shipping_method, customers_telephone, order_total, op.products_model, products_name, op.products_price, final_price, op.products_quantity, date_purchased, ot.value, orders_products_id, order_tax, o.orders_status, o.payment_method";
        if ($iso2) {
            $order_info .= ", cc.countries_iso_code_2";
        }
        if ($iso3) {
            $order_info .= ", cc.countries_iso_code_3";
        }
        $order_info .= " FROM (" . TABLE_ORDERS . " o LEFT JOIN " . TABLE_ORDERS_PRODUCTS . " op ON o.orders_id = op.orders_id), " . TABLE_ORDERS_TOTAL . " ot";
        if ($iso2 || $iso3) {
            $order_info .= ", " . TABLE_COUNTRIES . " cc";
        }
        $order_info .= " WHERE o.orders_id = ot.orders_id ";
        if ($iso2 || $iso3) {
            $order_info .= " AND cc.countries_name = o.delivery_country ";
        }
        $order_info .= "AND ot.class = 'ot_shipping'" . $order_filters . " ORDER BY orders_id ASC";
    } else {
        $order_info = "SELECT o.orders_id, customers_email_address, delivery_name, delivery_company, delivery_street_address, delivery_suburb, delivery_city, delivery_postcode, delivery_state, delivery_country, shipping_method, customers_telephone, order_total, date_purchased, ot.value, comments, order_tax, o.orders_status, o.payment_method";
        if ($iso2) {
            $order_info .= ", cc.countries_iso_code_2";
        }
        if ($iso3) {
            $order_info .= ", cc.countries_iso_code_3";
        }
        $order_info .= " FROM " . TABLE_ORDERS . " o, " . TABLE_ORDERS_STATUS_HISTORY . " os, " . TABLE_ORDERS_TOTAL . " ot";
        if ($iso2 || $iso3) {
            $order_info .= ", " . TABLE_COUNTRIES . " cc";
        }
        $order_info .= " WHERE o.orders_id = ot.orders_id AND ot.class = 'ot_shipping' ";
        if ($iso2 || $iso3) {
            $order_info .= " AND cc.countries_name = o.delivery_country ";
        }
        $order_info .= "AND o.orders_id = os.orders_id" . $order_filters . " GROUP BY o.orders_id ORDER BY o.orders_id ASC";

        $max_num_products = "SELECT COUNT(*) AS max_num_of_products
            FROM (" . TABLE_ORDERS . " o LEFT JOIN " . TABLE_ORDERS_PRODUCTS . " op ON o.orders_id = op.orders_id), " . TABLE_ORDERS_TOTAL . " ot
            WHERE o.orders_id = ot.orders_id
            AND ot.class = 'ot_shipping'" . $order_filters . "
            GROUP BY o.orders_id
            ORDER BY max_num_of_products DESC
            LIMIT 1";
        $max_num_products_result = $db->Execute($max_num_products);
        $max_products = $max_num_products_result->EOF ? 0 : (int)$max_num_products_result->fields['max_num_of_products'];
    }

    $order_details = $db->Execute($order_info);

    /****************** Header row ******************/
    $str_header = "Order ID,Customer Email";
    if (iems_export_flag('split_name')) {
        $str_header .= ",First Name,Last Name";
    } else {
        $str_header .= ",Delivery Name";
    }
    $str_header .= ",Company,Delivery Street,Delivery Suburb,Delivery City,Delivery State,Delivery Post Code,Delivery Country,Ship Dest Type";
    if (iems_export_flag('shipmethod')) {
        $str_header .= ",Shipping Method";
    }
    if (iems_export_flag('shiptotal')) {
        $str_header .= ",Shipping Total";
    }
    if (iems_export_flag('customers_telephone')) {
        $str_header .= ",Customers Telephone";
    }
    if (iems_export_flag('order_total')) {
        $str_header .= ",Order Total";
    }
    if (iems_export_flag('date_purchased')) {
        $str_header .= ",Order Date";
    }
    if (iems_export_flag('order_comments')) {
        $str_header .= ",Order Notes";
    }
    if (iems_export_flag('order_tax')) {
        $str_header .= ",Order Tax";
    }
    if (iems_export_flag('order_subtotal')) {
        $str_header .= ",Order Subtotal";
    }
    if (iems_export_flag('order_discount')) {
        $str_header .= ",Order Discount";
    }
    if (iems_export_flag('payment_method')) {
        $str_header .= ",Payment Method";
    }
    if (iems_export_flag('orders_status_export')) {
        $str_header .= ",Order Status";
    }
    if ($iso2) {
        $str_header .= ",ISO Country Code 2";
    }
    if ($iso3) {
        $str_header .= ",ISO Country Code 3";
    }
    if (iems_export_flag('product_details')) {
        if ($one_product_per_row) {
            $str_header .= ",Product Qty,Products Price,Product Name,Product Model,Product Attributes";
        } else {
            for ($i = 0; $i < $max_products; $i++) {
                $str_header .= ",Product " . $i . " Qty";
                $str_header .= ",Product " . $i . " Price";
                $str_header .= ",Product " . $i . " Name";
                $str_header .= ",Product " . $i . " Model";
                $str_header .= ",Product " . $i . " Attributes";
            }
        }
    }
    $str_header .= "\n";

    /****************** Rows ******************/
    $str_full_export = '';
    $set_status_after_export = iems_export_flag('status_setting');
    $status_after_export = (int)($_POST['order_status_setting'] ?? 0);

    while (!$order_details->EOF) {
        $row = $order_details->fields;
        $orders_id = (int)$row['orders_id'];

        $str_export = $FIELDSTART . $order_prefix . $row['orders_id'] . $FIELDEND . $FIELDSEPARATOR . $FIELDSTART . $row['customers_email_address'] . $FIELDEND;
        if (iems_export_flag('split_name')) {
            // Same split as 1.5.6c: first word, then the third word (or the
            // second when there are only two).
            $name_parts = preg_split("/[\s,]+/", (string)$row['delivery_name']);
            $first = $name_parts[0] ?? null;
            $middle = $name_parts[1] ?? null;
            $last = $name_parts[2] ?? null;
            if (!$last) {
                $last = $middle;
            }
            $str_export .= $FIELDSEPARATOR . $FIELDSTART . $first . $FIELDEND . $FIELDSEPARATOR . $FIELDSTART . $last . $FIELDEND;
        } else {
            $str_export .= $FIELDSEPARATOR . $FIELDSTART . $row['delivery_name'] . $FIELDEND;
        }

        $dest_type = ($row['delivery_company'] == '') ? 'Residential' : 'Commercial';

        $str_export .= $FIELDSEPARATOR . $FIELDSTART . $row['delivery_company'] . $FIELDEND . $FIELDSEPARATOR . $FIELDSTART . $row['delivery_street_address'] . $FIELDEND .
            $FIELDSEPARATOR . $FIELDSTART . $row['delivery_suburb'] . $FIELDEND . $FIELDSEPARATOR . $FIELDSTART . $row['delivery_city'] . $FIELDEND .
            $FIELDSEPARATOR . $FIELDSTART . $row['delivery_state'] . $FIELDEND . $FIELDSEPARATOR . $FIELDSTART . $row['delivery_postcode'] . $FIELDEND .
            $FIELDSEPARATOR . $FIELDSTART . $row['delivery_country'] . $FIELDEND .
            $FIELDSEPARATOR . $FIELDSTART . $dest_type . $FIELDEND;

        if (iems_export_flag('shipmethod')) {
            $str_export .= $FIELDSEPARATOR . $FIELDSTART . $row['shipping_method'] . $FIELDEND;
        }
        if (iems_export_flag('shiptotal')) {
            $str_export .= $FIELDSEPARATOR . $FIELDSTART . $row['value'] . $FIELDEND;
        }
        if (iems_export_flag('customers_telephone')) {
            $str_export .= $FIELDSEPARATOR . $FIELDSTART . $row['customers_telephone'] . $FIELDEND;
        }
        if (iems_export_flag('order_total')) {
            $str_export .= $FIELDSEPARATOR . $FIELDSTART . $row['order_total'] . $FIELDEND;
        }
        if (iems_export_flag('date_purchased')) {
            $str_export .= $FIELDSEPARATOR . $FIELDSTART . $row['date_purchased'] . $FIELDEND;
        }
        if (iems_export_flag('order_comments')) {
            if ($one_product_per_row) {
                $orders_comments = $db->Execute(
                    "SELECT comments FROM " . TABLE_ORDERS_STATUS_HISTORY . "
                      WHERE orders_id = " . $orders_id . "
                      ORDER BY orders_status_history_id ASC
                      LIMIT 1"
                );
                $comment = $orders_comments->EOF ? '' : (string)$orders_comments->fields['comments'];
            } else {
                $comment = (string)$row['comments'];
            }
            $str_safequotes = str_replace('"', "'", $comment);
            $str_export .= $FIELDSEPARATOR . $FIELDSTART . str_replace(["\r\n", "\r", "\n"], " ", $str_safequotes) . $FIELDEND;
        }
        if (iems_export_flag('order_tax')) {
            $str_export .= $FIELDSEPARATOR . $FIELDSTART . $row['order_tax'] . $FIELDEND;
        }
        if (iems_export_flag('order_subtotal')) {
            $orders_subtotal = $db->Execute(
                "SELECT value FROM " . TABLE_ORDERS_TOTAL . "
                  WHERE orders_id = " . $orders_id . " AND class = 'ot_subtotal'
                  ORDER BY orders_total_id ASC
                  LIMIT 1"
            );
            $str_export .= $FIELDSEPARATOR . $FIELDSTART . ($orders_subtotal->EOF ? '' : $orders_subtotal->fields['value']) . $FIELDEND;
        }
        if (iems_export_flag('order_discount')) {
            $orders_discount = $db->Execute(
                "SELECT value FROM " . TABLE_ORDERS_TOTAL . "
                  WHERE orders_id = " . $orders_id . " AND class = 'ot_coupon'
                  ORDER BY orders_total_id ASC
                  LIMIT 1"
            );
            $str_export .= $FIELDSEPARATOR . $FIELDSTART . ($orders_discount->EOF ? '' : $orders_discount->fields['value']) . $FIELDEND;
        }
        if (iems_export_flag('payment_method')) {
            $str_export .= $FIELDSEPARATOR . $FIELDSTART . $row['payment_method'] . $FIELDEND;
        }
        if (iems_export_flag('orders_status_export')) {
            // Legacy looked the name up without a language filter, so the first
            // matching row wins; ordering by language_id keeps that deterministic.
            $orders_status = $db->Execute(
                "SELECT orders_status_name FROM " . TABLE_ORDERS_STATUS . "
                  WHERE orders_status_id = " . (int)$row['orders_status'] . "
                  ORDER BY language_id ASC
                  LIMIT 1"
            );
            $str_export .= $FIELDSEPARATOR . $FIELDSTART . ($orders_status->EOF ? '' : $orders_status->fields['orders_status_name']) . $FIELDEND;
        }
        if ($iso2) {
            $str_export .= $FIELDSEPARATOR . $FIELDSTART . $row['countries_iso_code_2'] . $FIELDEND;
        }
        if ($iso3) {
            $str_export .= $FIELDSEPARATOR . $FIELDSTART . $row['countries_iso_code_3'] . $FIELDEND;
        }

        if (iems_export_flag('product_details')) {
            if ($one_product_per_row) {
                $str_export .= $FIELDSEPARATOR . $FIELDSTART . $row['products_quantity'] . $FIELDEND;
                $str_export .= $FIELDSEPARATOR . $FIELDSTART . $row['final_price'] . $FIELDEND;
                $str_export .= $FIELDSEPARATOR . $FIELDSTART . $row['products_name'] . $FIELDEND;
                $str_export .= $FIELDSEPARATOR . $FIELDSTART . $row['products_model'] . $FIELDEND . $FIELDSEPARATOR;

                $attributes = $db->Execute(
                    "SELECT products_options, products_options_values
                       FROM " . TABLE_ORDERS_PRODUCTS_ATTRIBUTES . "
                      WHERE orders_id = " . $orders_id . "
                        AND orders_products_id = " . (int)$row['orders_products_id']
                );
                if (!$attributes->EOF) {
                    $str_export .= $FIELDSTART;
                    while (!$attributes->EOF) {
                        $str_safequotes = str_replace('"', "'", (string)$attributes->fields['products_options_values']);
                        $str_export .= $attributes->fields['products_options'] . ': ' . str_replace(["\r\n", "\r", "\n"], " ", $str_safequotes) . $ATTRIBSEPARATOR;
                        $attributes->MoveNext();
                    }
                    $str_export .= $FIELDEND;
                }
            } else {
                $order = new order($orders_id);
                for ($i = 0, $n = count($order->products); $i < $n; $i++) {
                    $str_export .= $FIELDSEPARATOR . $FIELDSTART . $order->products[$i]['qty'] . $FIELDEND;
                    $str_export .= $FIELDSEPARATOR . $FIELDSTART . $order->products[$i]['final_price'] . $FIELDEND;
                    $str_export .= $FIELDSEPARATOR . $FIELDSTART . $order->products[$i]['name'] . $FIELDEND;
                    $str_export .= $FIELDSEPARATOR . $FIELDSTART . $order->products[$i]['model'] . $FIELDEND . $FIELDSEPARATOR;
                    if (!empty($order->products[$i]['attributes'])) {
                        $str_export .= $FIELDSTART;
                        foreach ($order->products[$i]['attributes'] as $attribute) {
                            $str_safequotes = str_replace('"', "'", (string)$attribute['value']);
                            $str_export .= $attribute['option'] . ': ' . str_replace(["\r\n", "\r", "\n"], " ", $str_safequotes) . $ATTRIBSEPARATOR;
                        }
                        $str_export .= $FIELDEND;
                    }
                }
            }
        }

        $str_export .= "\n";
        $str_full_export .= $str_export;

        if ($set_status_after_export) {
            $db->Execute(
                "UPDATE " . TABLE_ORDERS . " SET orders_status = " . $status_after_export . " WHERE orders_id = " . $orders_id
            );
        }
        $order_details->MoveNext();
    }

    /****************** Mark exported orders ******************/
    $mark_exported = function () use ($db, $date_range, $start_date, $end_date): void {
        if (iems_export_flag('export_test')) {
            return;
        }
        $orders_update_query = "UPDATE " . TABLE_ORDERS . " SET downloaded_ship='yes' WHERE downloaded_ship='no'";
        if ($date_range) {
            $orders_update_query .= " AND date_purchased BETWEEN '" . $start_date . "' AND '" . $end_date . "'";
        }
        $db->Execute($orders_update_query);
    };

    if ($save_to_file_checked === 1) {
        if (!is_dir($export_dir) || !is_writable($export_dir)) {
            $messageStack->add_session(sprintf(ERROR_EXPORT_DIR_NOT_WRITABLE, $export_dir), 'error');
            zen_redirect(zen_href_link(FILENAME_SHIPPING_EXPORT));
        }
        $f = fopen($export_dir . $file, 'w+');
        if (iems_export_flag('include_header_row')) {
            fwrite($f, $str_header);
        }
        fwrite($f, $str_full_export);
        fclose($f);

        zen_mail('Supplier Name', $to_email_address, $email_subject, sprintf(EMAIL_EXPORT_BODY, STORE_NAME), STORE_NAME, EMAIL_FROM, [], 'default', $export_dir . $file);
        $success_message = TEXT_EXPORT_SUCCESS;
        $mark_exported();
    } else {
        header('Content-type: application/csv');
        header('Content-disposition: attachment; filename=' . $file);
        if (iems_export_flag('include_header_row')) {
            echo $str_header;
        }
        echo $str_full_export;
        if ($archive_downloads && is_dir($export_dir) && is_writable($export_dir)) {
            file_put_contents(
                $export_dir . $file,
                (iems_export_flag('include_header_row') ? $str_header : '') . $str_full_export
            );
        }
        $mark_exported();
        exit;
    }
}

// Order status dropdowns
$status_array = [];
$status_table = [];
$orders_status = $db->Execute(
    "SELECT orders_status_id, orders_status_name
       FROM " . TABLE_ORDERS_STATUS . "
      WHERE language_id = " . (int)$_SESSION['languages_id'] . "
      ORDER BY orders_status_id ASC"
);
while (!$orders_status->EOF) {
    $status_array[] = [
        'id' => $orders_status->fields['orders_status_id'],
        'text' => $orders_status->fields['orders_status_name'] . ' [' . $orders_status->fields['orders_status_id'] . ']',
    ];
    $status_table[$orders_status->fields['orders_status_id']] = $orders_status->fields['orders_status_name'] . ' [' . $orders_status->fields['orders_status_id'] . ']';
    $orders_status->MoveNext();
}

$current_page = (int)($_GET['page'] ?? 1);
$pending_query = "SELECT o.orders_id, customers_email_address, delivery_name, delivery_company, delivery_street_address, delivery_suburb, delivery_city, delivery_postcode, delivery_state, orders_status
                    FROM " . TABLE_ORDERS . " o
                   WHERE downloaded_ship='no'
                   ORDER BY orders_id ASC";
$pending_rows = 0;
$order_pages = new splitPageResults($current_page, MAX_DISPLAY_SEARCH_RESULTS_REPORTS, $pending_query, $pending_rows);
$pending_orders = $db->Execute($pending_query);
?>
<!doctype html>
<html <?php echo HTML_PARAMS; ?>>
<head>
    <?php require DIR_WS_INCLUDES . 'admin_html_head.php'; ?>
    <script>
        var iemsExportChecked = false;
        function iemsExportToggleAll() {
            var form = document.getElementById('download_csv');
            iemsExportChecked = !iemsExportChecked;
            for (var i = 0; i < form.elements.length; i++) {
                if (form.elements[i].type === 'checkbox') {
                    form.elements[i].checked = iemsExportChecked;
                }
            }
        }
    </script>
</head>
<body>
<?php require DIR_WS_INCLUDES . 'header.php'; ?>
<div class="container-fluid">
    <h1><?php echo HEADING_SHIPPING_EXPORT_TITLE; ?></h1>
    <?php if ($success_message !== '') { ?>
        <div class="alert alert-success"><?php echo $success_message; ?></div>
    <?php } ?>
    <div class="row">
        <div class="col-md-9">
            <p><?php echo TEXT_SHIPPING_EXPORT_INSTRUCTIONS; ?></p>
            <table class="table table-condensed table-striped">
                <thead>
                <tr class="dataTableHeadingRow">
                    <th class="dataTableHeadingContent">Order<br>ID</th>
                    <th class="dataTableHeadingContent">Email</th>
                    <th class="dataTableHeadingContent">Customer<br>Name</th>
                    <th class="dataTableHeadingContent">Company</th>
                    <th class="dataTableHeadingContent">Delivery<br>Street</th>
                    <th class="dataTableHeadingContent">Delivery<br>Suburb</th>
                    <th class="dataTableHeadingContent">Delivery<br>City</th>
                    <th class="dataTableHeadingContent">Post<br>Code</th>
                    <th class="dataTableHeadingContent">State</th>
                    <th class="dataTableHeadingContent">Order<br>Status</th>
                    <th class="dataTableHeadingContent">&nbsp;</th>
                </tr>
                </thead>
                <tbody>
                <?php if ($pending_orders->EOF) { ?>
                    <tr class="dataTableRow">
                        <td class="dataTableContent text-center" colspan="11"><b><?php echo TEXT_NO_NEW_ORDERS; ?></b></td>
                    </tr>
                <?php } ?>
                <?php foreach ($pending_orders as $pending) { ?>
                    <tr class="dataTableRow">
                        <td class="dataTableContent text-right"><?php echo (int)$pending['orders_id']; ?></td>
                        <td class="dataTableContent"><?php echo zen_output_string_protected((string)$pending['customers_email_address']); ?></td>
                        <td class="dataTableContent"><?php echo zen_output_string_protected((string)$pending['delivery_name']); ?></td>
                        <td class="dataTableContent"><?php echo zen_output_string_protected((string)$pending['delivery_company']); ?></td>
                        <td class="dataTableContent"><?php echo zen_output_string_protected((string)$pending['delivery_street_address']); ?></td>
                        <td class="dataTableContent"><?php echo zen_output_string_protected((string)$pending['delivery_suburb']); ?></td>
                        <td class="dataTableContent"><?php echo zen_output_string_protected((string)$pending['delivery_city']); ?></td>
                        <td class="dataTableContent"><?php echo zen_output_string_protected((string)$pending['delivery_postcode']); ?></td>
                        <td class="dataTableContent"><?php echo zen_output_string_protected((string)$pending['delivery_state']); ?></td>
                        <td class="dataTableContent"><?php echo $status_table[$pending['orders_status']] ?? ''; ?></td>
                        <td class="dataTableContent">
                            <a href="<?php echo zen_href_link(FILENAME_ORDERS, 'oID=' . (int)$pending['orders_id'] . '&action=edit'); ?>"><?php echo TEXT_VIEW_ORDER; ?></a>
                        </td>
                    </tr>
                <?php } ?>
                </tbody>
            </table>
            <div class="row">
                <div class="col-sm-6"><?php echo $order_pages->display_count($pending_rows, MAX_DISPLAY_SEARCH_RESULTS_REPORTS, $current_page, TEXT_DISPLAY_NUMBER_OF_ORDERS); ?></div>
                <div class="col-sm-6 text-right"><?php echo $order_pages->display_links($pending_rows, MAX_DISPLAY_SEARCH_RESULTS_REPORTS, MAX_DISPLAY_PAGE_LINKS, $current_page); ?></div>
            </div>
        </div>

        <div class="col-md-3">
            <h4><?php echo HEADING_ADDITIONAL_FIELDS_TITLE; ?></h4>
            <p><?php echo TEXT_RUNIN_TEST; ?></p>
            <?php echo zen_draw_form('download_csv', FILENAME_SHIPPING_EXPORT, '', 'post', 'id="download_csv"'); ?>
            <input type="button" class="btn btn-default btn-sm" value="<?php echo TEXT_CHECK_UNCHECK_ALL; ?>" onclick="iemsExportToggleAll();"><br><br>
            <?php echo zen_draw_checkbox_field('export_test', '1', false); ?>&nbsp;<?php echo TEXT_RUNIN_TEST_FIELD; ?><br>
            <?php echo zen_draw_checkbox_field('split_name', '1', false); ?>&nbsp;<?php echo TEXT_SPLIT_NAME_FIELD; ?><br>
            <?php echo zen_draw_checkbox_field('include_header_row', '1', true); ?>&nbsp;<?php echo TEXT_HEADER_ROW_FIELD; ?><br><br>
            <?php echo TEXT_EMAIL_EXPORT_FORMAT; ?><?php echo zen_draw_pull_down_menu('format', $available_export_formats, '0'); ?>
            <hr>
            <strong><?php echo HEADING_AUTO_EMAIL_TITLE; ?></strong><br>
            <?php echo zen_draw_checkbox_field('savetofile', '1', false); ?>&nbsp;<?php echo TEXT_SAVE_AND_EMAIL_FIELD; ?><br>
            <?php echo TEXT_SUPPLIER_EMAIL_FIELD; ?>&nbsp;<?php echo zen_draw_input_field('auto_email_supplier', $default_email, 'class="form-control"'); ?>
            <?php echo TEXT_EMAIL_SUBJECT_FIELD; ?>&nbsp;<?php echo zen_draw_input_field('auto_email_subject', sprintf(EMAIL_EXPORT_SUBJECT, STORE_NAME), 'class="form-control"'); ?>
            <hr>
            <strong><?php echo HEADING_UPDATE_STATUS_TITLE; ?></strong><br><?php echo TEXT_UPDATE_STATUS; ?><br>
            <?php echo zen_draw_checkbox_field('status_setting', '1', true); ?>&nbsp;<?php echo TEXT_SET_STATUS_AFTER_EXPORT_FIELD; ?>
            <?php echo zen_draw_pull_down_menu('order_status_setting', $status_array, (string)($_POST['order_status_setting'] ?? ''), 'id="order_status_setting" class="form-control"'); ?>
            <hr>
            <strong><?php echo HEADING_STATUS_FILTER_TITLE; ?></strong><br>
            <?php echo zen_draw_radio_field('status_target', '1', false); ?>&nbsp;<?php echo TEXT_ANY_ORDER_STATUS_FIELD; ?><br>
            <?php echo zen_draw_radio_field('status_target', '2', true); ?>&nbsp;<?php echo TEXT_ASSIGNED_ORDER_STATUS_FIELD; ?><br>
            <?php echo zen_draw_pull_down_menu('order_status', $status_array, (string)($_POST['order_status'] ?? ''), 'id="order_status" class="form-control"'); ?>
            <hr>
            <strong><?php echo HEADING_PREVIOUS_EXPORTS_TITLE; ?></strong><br><?php echo TEXT_PREVIOUS_EXPORTS; ?><br>
            <?php echo zen_draw_checkbox_field('dload_include', '1', false); ?>&nbsp;<?php echo TEXT_PREVIOUS_EXPORTS_FIELD; ?>
            <hr>
            <?php echo TEXT_FILE_LAYOUT; ?>
            <?php echo zen_draw_radio_field('filelayout', '1', false); ?>&nbsp;<?php echo TEXT_FILE_LAYOUT_OPR_FIELD; ?><br>
            <?php echo zen_draw_radio_field('filelayout', '2', true); ?>&nbsp;<?php echo TEXT_FILE_LAYOUT_PPR_FIELD; ?><br>
            <hr>
            <?php echo TEXT_ADDITIONAL_FIELDS; ?>
            <?php echo zen_draw_checkbox_field('shipmethod', '1', false); ?>&nbsp;<?php echo TEXT_SHIPPING_METHOD_FIELD; ?><br>
            <?php echo zen_draw_checkbox_field('shiptotal', '1', false); ?>&nbsp;<?php echo TEXT_SHIPPING_TOTAL_FIELD; ?><br>
            <?php echo zen_draw_checkbox_field('customers_telephone', '1', false); ?>&nbsp;<?php echo TEXT_PHONE_NUMBER_FIELD; ?><br>
            <?php echo zen_draw_checkbox_field('order_total', '1', false); ?>&nbsp;<?php echo TEXT_ORDER_TOTAL_FIELD; ?><br>
            <?php echo zen_draw_checkbox_field('date_purchased', '1', true); ?>&nbsp;<?php echo TEXT_ORDER_DATE_FIELD; ?><br>
            <?php echo zen_draw_checkbox_field('order_comments', '1', false); ?>&nbsp;<?php echo TEXT_ORDER_COMMENTS_FIELD; ?><br>
            <?php echo zen_draw_checkbox_field('order_tax', '1', false); ?>&nbsp;<?php echo TEXT_TAX_AMOUNT_FIELD; ?><br>
            <?php echo zen_draw_checkbox_field('order_subtotal', '1', false); ?>&nbsp;<?php echo TEXT_SUBTOTAL_FIELD; ?><br>
            <?php echo zen_draw_checkbox_field('order_discount', '1', false); ?>&nbsp;<?php echo TEXT_DISCOUNT_FIELD; ?><br>
            <?php echo zen_draw_checkbox_field('payment_method', '1', false); ?>&nbsp;<?php echo TEXT_PAYMENT_METHOD_FIELD; ?><br>
            <?php echo zen_draw_checkbox_field('orders_status_export', '1', true); ?>&nbsp;<?php echo TEXT_ORDER_STATUS_FIELD; ?><br>
            <?php echo zen_draw_checkbox_field('iso_country2_code', '1', false); ?>&nbsp;<?php echo TEXT_ISO_COUNTRY2_FIELD; ?><br>
            <?php echo zen_draw_checkbox_field('iso_country3_code', '1', false); ?>&nbsp;<?php echo TEXT_ISO_COUNTRY3_FIELD; ?><br>
            <hr>
            <?php echo zen_draw_checkbox_field('product_details', '1', true); ?>&nbsp;<?php echo TEXT_PRODUCT_DETAILS_FIELD; ?> <span class="text-danger"><strong>*</strong></span>
            <hr>
            <strong><?php echo HEADING_CUSTOM_DATE_TITLE; ?></strong><br><?php echo TEXT_CUSTOM_DATE; ?><br>
            <label for="start_date"><?php echo TEXT_START_DATE_FIELD; ?></label>
            <input type="date" name="start_date" id="start_date" class="form-control">
            <label for="end_date"><?php echo TEXT_END_DATE_FIELD; ?></label>
            <input type="date" name="end_date" id="end_date" class="form-control">
            <br>
            <input type="submit" name="download_csv" class="btn btn-primary" value="<?php echo TEXT_EXPORT_BUTTON; ?>">
            <?php echo '</form>'; ?>
        </div>
    </div>
</div>
<?php require DIR_WS_INCLUDES . 'footer.php'; ?>
</body>
</html>
<?php require DIR_WS_INCLUDES . 'application_bottom.php'; ?>
