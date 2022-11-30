<?php
// -----
// Loop through the to-be-displayed productArray, changing any submit-type image in
// its 'buttonUpdate' elements to contain a font-awesome glyph instead.
//
// That regex "magic" says find the first '<input type="image"{...}/>', replace it with the
// button and then copy anything else (like the 'hidden' input that follows).
//
// BOOTSTRAP 3.1.5.
//
if (!(function_exists('zca_bootstrap_active') && zca_bootstrap_active())) {
    return;
}

if (!isset($productArray) || !is_array($productArray)) {
    $productArray = array(); 
}
for ($i = 0, $n = count($productArray); $i < $n; $i++) {
    $productArray[$i]['buttonUpdate'] = preg_replace(
        '/(<input type="image".*?\/>)(.*)/', '<button type="submit" class="btn" aria-label="' . ICON_UPDATE_ALT . '" title="' . ICON_UPDATE_ALT . '"><i aria-hidden="true" class="fas fa-sync-alt"></i></button>$2',
        $productArray[$i]['buttonUpdate']
    );  
}
