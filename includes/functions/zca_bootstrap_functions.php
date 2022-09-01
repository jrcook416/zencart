<?php
/**
 * @author ZCAdditions.com, ZCA Bootstrap Template
 *
 * BOOTSTRAP v3.4.0
 *
*/
 
// -----
// This function returns a boolean value indicating whether (true) or not (false)
// the ZCA bootstrap template is the currently-active template.  The definition is
// present in the template's /includes/languages/english/extra_definitions/zca_bootstrap_id.php,
//
function zca_bootstrap_active()
{
    return (defined('IS_ZCA_BOOTSTRAP_TEMPLATE'));
}

function zca_js_zone_list($varname = 'c2z')
{
    global $db;

    $countries = $db->Execute(
        "SELECT DISTINCT zone_country_id
           FROM " . TABLE_ZONES . "
                INNER JOIN " . TABLE_COUNTRIES . "
                    ON countries_id = zone_country_id
                   AND status = 1
       ORDER BY zone_country_id"
    );

    $c2z = [];
    foreach ($countries as $country) {
        $current_country_id = $country['zone_country_id'];
        $c2z[$current_country_id] = [];

        $states = $db->Execute(
            "SELECT zone_name, zone_id
               FROM " . TABLE_ZONES . "
              WHERE zone_country_id = $current_country_id
           ORDER BY zone_name"
        );
        foreach ($states as $state) {
            $c2z[$current_country_id][$state['zone_id']] = $state['zone_name'];
        }
    }

    if (count($c2z) === 0) {
        $output_string = '';
    } else {
        $output_string = 'var ' . $varname . ' = \'' . addslashes(json_encode($c2z)) . '\';' . PHP_EOL;
    }
    return $output_string;
}

// -----
// Loads a language-file for the requested modal page.  Some of the "core" Zen Cart pop-up pages
// are replaced by modals for the Bootstrap template.
//
// NOTE: This function, introduced in v3.4.0, replaces the zca_get_language_dir function to enable
// a single template distribution to support both the zc157 series and its zc158+ follow-on.
//
function zca_load_language_for_modal($modal_pagename)
{
    if (zen_get_zcversion() >= '1.5.8') {
        global $languageLoader;

        $languageLoader->setCurrentPage($modal_pagename);
        $languageLoader->loadLanguageForView();
    } else {
        global $language_page_directory, $template_dir;

        $modal_language_filename = $modal_pagename . '.php';
        $language_dir = '';
        if (file_exists($language_page_directory . $template_dir . '/' . $modal_language_filename)) {
            $language_dir = "$template_dir/";
        }
        require $language_page_directory . $language_dir . $modal_language_filename;
    }
}

// -----
// Common function to get font-awesome version of the products' rating stars.
//
// $rating ... An integer value between 0 and 5.
// $size ..... A character string to identify the relative 'size' of the generated stars, one of the font-awesome size suffixes:
//             'xs', 'sm', 'lg', '2x', '3x', '5x', '7x' or '10x'.  Note that this value is unchecked!
//
function zca_get_rating_stars($rating, $size = '')
{
    $rating = (int)$rating;
    $rating = ($rating < 0) ? 0 : $rating;
    $rating = ($rating > 5) ? 5 : $rating;
    
    $rating_stars = '<span class="sr-only">' . $rating . ' ' . (($rating === 1) ? ARIA_REVIEW_STAR : ARIA_REVIEW_STARS) . '</span>';
    $size = ($size != '') ? " fa-$size" : '';
    for ($i = 1; $i <= 5; $i++) {
        $fa_class = ($i <= $rating) ? 'fas' : 'far';
        $rating_stars .= '<i class="' . $fa_class . ' fa-star' . $size . '"></i>';
    }
    return $rating_stars;
}

// -----
// A function to provide compatability for the template's use for 'strftime' formatted
// dates; that function is deprecated in PHP 8.1 and will be removed in a future version.
// zc158 has defined a class that can be used to provide that compatibility.
//
function zca_get_translated_month_name()
{
    if (zen_get_zcversion() >= '1.5.8') {
        global $zcDate;
        $month_name = $zcDate->output('%B');
    } else {
        $month_name = strftime('%B');
    }
    return $month_name;
}
