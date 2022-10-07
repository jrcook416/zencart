<?php 
if (!defined('IS_ADMIN_FLAG')) {
    die('Illegal Access');
} 

//----
// Register the New Tools tool into the admin menu structure.
//
// NOTES:  
// 1) Once this file has run once and you see the Tools > New Tool link in the admin
// menu structure, it is safe to delete this file (unless you have other functions that
// are initialized in the file).
// 2) If you have multiple items to add to the admin-level menus, then you should 
// register each of the pages here, just make sure that the "page key" is unique or 
// a debug-log will be generated!
//
if (function_exists('zen_register_admin_page')) {
    if (!zen_page_key_exists('toolsEditUnits')) {
        zen_register_admin_page(
            'toolsEditUnits', 
            'BOX_TOOLS_EDIT_UNITS', 
            'FILENAME_EDIT_UNITS',
            '' , 
            'tools', 
            'Y'
        );

        // Optionally display a message here, using the $messageStack
        $messageStack->add_session(BOX_TOOLS_EDIT_UNITS . ' installed', 'success');
    }    
}