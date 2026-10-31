<?php
/**
 * IEMS Legacy Bridge: language strings for the Shipping Export page.
 * Wording is carried over from the 1.5.6c Shipping Export v1.3.2 module.
 */
$define = [
    'HEADING_SHIPPING_EXPORT_TITLE' => 'Export Shipping &amp; Order Information',
    'TEXT_SHIPPING_EXPORT_INSTRUCTIONS' => 'Export order shipping information to CSV for the inventory control system. By default only orders that have not already been exported are included, and they are marked as exported afterwards (unless Test Mode is on).',
    'HEADING_ADDITIONAL_FIELDS_TITLE' => 'Additional Fields and Options',
    'HEADING_CUSTOM_DATE_TITLE' => 'Custom Date Range',
    'HEADING_PREVIOUS_EXPORTS_TITLE' => 'Previous Exports Inclusion',
    'HEADING_AUTO_EMAIL_TITLE' => 'Automatic Email Options',
    'HEADING_UPDATE_STATUS_TITLE' => 'Update Order Status on Export',
    'HEADING_STATUS_FILTER_TITLE' => 'Order Status Export Options',

    'TEXT_CUSTOM_DATE' => 'Optional. Leave both fields blank to export all orders since the last export (the default). Fill both in to export a specific date range.',
    'TEXT_PREVIOUS_EXPORTS' => 'By default the export file includes only orders that have not already been exported. Tick the box below to include previously exported orders as well. Combine this with the date range for more flexibility.',
    'TEXT_PREVIOUS_EXPORTS_FIELD' => 'Include orders already downloaded in export.',
    'TEXT_RUNIN_TEST' => 'Test mode exports without marking orders as "exported", so they can be exported again.',
    'TEXT_RUNIN_TEST_FIELD' => 'Run In Test Mode',
    'TEXT_ADDITIONAL_FIELDS' => '<strong>Select additional fields</strong> to be added to the export. Additional fields are exported in the order listed.',
    'TEXT_FILE_LAYOUT' => '<strong>Select File Layout to Export</strong>',
    'TEXT_FILE_LAYOUT_OPR_FIELD' => '1 Order per row',
    'TEXT_FILE_LAYOUT_PPR_FIELD' => '1 Product per row',
    'TEXT_CHECK_UNCHECK_ALL' => 'Check / Uncheck All',
    'TEXT_SPLIT_NAME_FIELD' => 'Export First and Last Name into Separate Fields.',
    'TEXT_HEADER_ROW_FIELD' => 'Include Header Row In Export',
    'TEXT_EMAIL_EXPORT_FORMAT' => 'Export file format type: ',
    'TEXT_SHIPPING_METHOD_FIELD' => 'Shipping Method',
    'TEXT_SHIPPING_TOTAL_FIELD' => 'Shipping Total',
    'TEXT_PHONE_NUMBER_FIELD' => 'Phone Number',
    'TEXT_ORDER_TOTAL_FIELD' => 'Order Total',
    'TEXT_ORDER_DATE_FIELD' => 'Order Date',
    'TEXT_ORDER_COMMENTS_FIELD' => '1st Order Comment / Note',
    'TEXT_PRODUCT_DETAILS_FIELD' => 'Full Product Details',
    'TEXT_TAX_AMOUNT_FIELD' => 'Order Tax Amount',
    'TEXT_SUBTOTAL_FIELD' => 'Order Subtotal',
    'TEXT_DISCOUNT_FIELD' => 'Order Discount',
    'TEXT_PAYMENT_METHOD_FIELD' => 'Payment Method',
    'TEXT_ORDER_STATUS_FIELD' => 'Order Status',
    'TEXT_ISO_COUNTRY2_FIELD' => 'ISO Country Code (2 Character)',
    'TEXT_ISO_COUNTRY3_FIELD' => 'ISO Country Code (3 Character)',

    'TEXT_SAVE_AND_EMAIL_FIELD' => 'Save file to server and automatically email to supplier. (If not saving to server you will be prompted to download the file.)',
    'TEXT_SUPPLIER_EMAIL_FIELD' => 'Suppliers Email Address:',
    'TEXT_EMAIL_SUBJECT_FIELD' => 'Email Subject Line:',
    'TEXT_UPDATE_STATUS' => '(If this is set, the order status updates to what you select here after a successful export.)',
    'TEXT_SET_STATUS_AFTER_EXPORT_FIELD' => 'Set Order Status After Export to',
    'TEXT_ANY_ORDER_STATUS_FIELD' => 'Any Order Status',
    'TEXT_ASSIGNED_ORDER_STATUS_FIELD' => 'Assigned Order Status (select below)',
    'TEXT_START_DATE_FIELD' => 'Start Date:',
    'TEXT_END_DATE_FIELD' => 'End Date (inclusive):',
    'TEXT_EXPORT_BUTTON' => 'Export',

    'TEXT_NO_NEW_ORDERS' => 'There are no new orders to export.',
    'TEXT_VIEW_ORDER' => 'View',
    'TEXT_DISPLAY_NUMBER_OF_ORDERS' => 'Displaying <b>%d</b> to <b>%d</b> (of <b>%d</b> orders not yet exported)',
    'TEXT_EXPORT_SUCCESS' => 'Export saved to the server and emailed.',
    'ERROR_EXPORT_DIR_NOT_WRITABLE' => 'Export directory %s does not exist or is not writable.',

    'EMAIL_EXPORT_SUBJECT' => '%s orders for processing.',
    'EMAIL_EXPORT_BODY' => 'Attached please find the most recent set of orders from %s. If you have any questions please contact us.',
];

return $define;
