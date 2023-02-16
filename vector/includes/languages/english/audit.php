<?php
// Language file for Audit

define('HEADING_TITLE', 'Audit');
define('PRODUCT_HEADING', 'Product/Category'); 
define('ISSUE_HEADING', 'Issue'); 
define('FIELDS_HEADING', 'Key Fields'); 

define('MCAT_NOT_PRESENT', 'Master Category does not exist.'); 
define('PRODUCT_NOT_PRESENT_P2C', 'Product not present in Products to Categories table.'); 
define('PRODUCT_MCAT_PAIR_NOT_PRESENT_P2C', 'Product,Master Categories ID pair not present in Products to Categories table - Master category wrong?'); 
define('PRODUCT_MCAT_0','Product master category id is 0.'); 
define('PRODUCT_MCAT_SAFE','Product master category id is the SAFE category.'); 
define('PRODUCT_TYPE_0','Product type is 0.'); 
define('CAT_EMPTY','Category Empty'); 
define('TEXT_ADD_MCAT_PAIR','Add Product,Master Category Pair'); 
define('TEXT_SET_TYPE','Set product type'); 
define('TEXT_MOVE_PROD','Move Product'); 
define('TEXT_DELETE_CAT','Delete Category'); 
define('TEXT_SET_MCAT_WARNING','Warning: Will remove product from any linked categories');
define('FIX_OPTION_ADD_PAIR','add_pair'); 
define('FIX_OPTION_MOVE_PROD','move_prod'); 
define('FIX_OPTION_SET_TYPE','set_type'); 
define('FIX_OPTION_SET_MCAT','set_mcat'); 
define('FIX_OPTION_DEL_CAT','del_cat'); 

define('CAT_PRODS_CATS','Category contains products and categories'); 
define('WRONG_PRODS_COUNT','Number of incorrectly placed products: %d');
define('USE_ADMIN_PHPMYADMIN','Use Zen Admin and phpMyAdmin to fix - if products appear in Zen Admin, use the Move button; if they do not, delete entries from products_to_categories table.'); 

define('NO_ISSUES_FOUND', 'Hooray! No issues found!'); 

define('KEY_PRID','prid'); 
define('KEY_MCAT','mcat'); 
define('KEY_CAT','cat'); 

define('FIELD_PRODUCTS_ID','Products ID'); 
define('FIELD_MASTER_CATEGORIES_ID','Master Categories ID'); 
define('FIELD_CATEGORIES_ID','Categories ID'); 

