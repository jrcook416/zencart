<?php
/**
 * Page Template
 * 
 * BOOTSTRAP v3.4.0
 *
 * Loaded automatically by index.php?main_page=advanced_search_result.<br />
 * Displays results of advanced search
 *
 * @package templateSystem
 * @copyright Copyright 2003-2016 Zen Cart Development Team
 * @copyright Portions Copyright 2003 osCommerce
 * @license http://www.zen-cart.com/license/2_0.txt GNU Public License V2.0
 * @version $Id: Author: DrByte  Sun Dec 13 16:25:20 2015 -0500 Modified in v1.5.5 $
 */
// -----
// zc158 redefines the 'advanced_search_result' page as simply 'search_result'.  Since v3.4.0 and later
// of the template currently supports both zc158 and the zc157 series, simply load the
// newly-named template from this legacy one.
//
require $template->get_template_dir('/tpl_search_result_default.php', DIR_WS_TEMPLATE, $current_page_base, 'templates') . '/tpl_search_result_default.php';
