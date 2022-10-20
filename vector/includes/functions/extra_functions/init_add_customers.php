<?php
// -----
// Part of the "Add Customers from Admin" plugin.
//
if (!defined('IS_ADMIN_FLAG')) {
    die('Illegal Access');
} 

//----
// If the installation supports admin-page registration (i.e. v1.5.0 and later), then
// register the New Tools tool into the admin menu structure.
//
if (!zen_page_key_exists('customersAddCustomer')) {
    zen_register_admin_page('customersAddCustomer', 'BOX_CUSTOMERS_ADD_CUSTOMERS', 'FILENAME_ADD_CUSTOMERS','' , 'customers', 'Y');
}
