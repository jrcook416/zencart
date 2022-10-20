<?php
if (!defined('IS_ADMIN_FLAG') || IS_ADMIN_FLAG !== true) {
    die ('Illegal Access');
}
define('NOTES_LABEL', 'Notes:');
define('NOTES_CREATE_CATEGORY_FIRST', 'Create the category first, then notes may be added.'); 
define('NOTES_CREATE_PRODUCT_FIRST', 'Create the product first, then notes may be added');
