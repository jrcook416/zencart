<?php
/**
 * Page Template
 *
 * BOOTSTRAP v3.4.0
 *
 * Loaded automatically by index.php?main_page=advanced_search.<br />
 * Displays options fields upon which a product search will be run
 *
 * @copyright Copyright 2003-2020 Zen Cart Development Team
 * @copyright Portions Copyright 2003 osCommerce
 * @license http://www.zen-cart.com/license/2_0.txt GNU Public License V2.0
 * @version $Id: DrByte 2020 May 16 Modified in v1.5.7 $
 */
// -----
// zc158 redefines the 'advanced_search' page as simply 'search'.  Since v3.4.0 and later
// of the template currently supports both zc158 and the zc157 series, simply load the
// newly-named template from this legacy one.
//
require $template->get_template_dir('/tpl_search_default.php', DIR_WS_TEMPLATE, $current_page_base, 'templates') . '/tpl_search_default.php';
