<?php
/**
 * Side Box Template: Searchbox for column header
 *
 * BOOTSTRAP v3.4.0
 *
 * @copyright Copyright 2003-2020 Zen Cart Development Team
 * @copyright Portions Copyright 2003 osCommerce
 * @license http://www.zen-cart.com/license/2_0.txt GNU Public License V2.0
 * @version $Id: DrByte 2020 May 16 Modified in v1.5.7 $
 */
// -----
// zc158 redefines the 'advanced_search_result' page as simply 'search_result.  If that
// new page's definition is present, the search result will be sent there for viewing;
// otherwise, it'll be sent to the legacy page.
//
$search_result_page = (defined('FILENAME_SEARCH_RESULT')) ? FILENAME_SEARCH_RESULT : FILENAME_ADVANCED_SEARCH_RESULT;

$content = '';
$content .= zen_draw_form('quick_find_header', zen_href_link($search_result_page, '', $request_type, false), 'get', 'class="form-inline"');
$content .= zen_draw_hidden_field('main_page', $search_result_page);
$content .= zen_draw_hidden_field('search_in_description', '1') . zen_hide_session_id();

$content .= '<div class="input-group">';
$content .= zen_draw_input_field('keyword', '', 'placeholder="' . HEADER_SEARCH_DEFAULT_TEXT . '" aria-label="' . HEADER_SEARCH_DEFAULT_TEXT . '" ');

$content .= '<div class="input-group-append">';
if (strtolower(IMAGE_USE_CSS_BUTTONS) == 'yes') {
    $content .= zen_image_submit(BUTTON_IMAGE_SEARCH, HEADER_SEARCH_BUTTON);
} else {
    $content .= '<input type="submit" value="' . HEADER_SEARCH_BUTTON . '" style="width: 60px" />';
}
$content .= '</div>';
$content .= '</div>';
$content .= '</form>';
