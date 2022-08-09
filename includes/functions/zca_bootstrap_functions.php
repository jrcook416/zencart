<?php
/**
 * @author ZCAdditions.com, ZCA Bootstrap Template
 *
 * BOOTSTRAP v3.1.6
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
    $countries = $GLOBALS['db']->Execute(
        "SELECT DISTINCT zone_country_id
           FROM " . TABLE_ZONES . "
                INNER JOIN " . TABLE_COUNTRIES . "
                    ON countries_id = zone_country_id
                   AND status = 1
       ORDER BY zone_country_id"
    );
    
    $c2z = array();
    while (!$countries->EOF) {
        $current_country_id = $countries->fields['zone_country_id'];
        $c2z[$current_country_id] = array();

        $states = $GLOBALS['db']->Execute(
            "SELECT zone_name, zone_id
               FROM " . TABLE_ZONES . "
              WHERE zone_country_id = $current_country_id
           ORDER BY zone_name"
        );
        while (!$states->EOF) {
            $c2z[$current_country_id][$states->fields['zone_id']] = $states->fields['zone_name'];
            $states->MoveNext();
        }
        $countries->MoveNext();
    }
    
    if (count($c2z) == 0) {
        $output_string = '';
    } else {
        $output_string = 'var ' . $varname . ' = \'' . addslashes(json_encode($c2z)) . '\';' . PHP_EOL;
    }
    return $output_string;
}

function zca_get_language_dir($language_filename)
{
    global $language_page_directory, $template_dir;

    $language_dir = (file_exists($language_page_directory . $template_dir . '/' . $language_filename)) ? "$template_dir/" : '';
    return $language_dir;
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
