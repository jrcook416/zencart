<?php
// -----
// Template formatting for the Bootstrap template's AJAX search feature.  Required by the
// template's AJAX search script (/includes/classes/ajax/zcAjaxBootstrapSearch.php).
//
// Bootstrap v3.4.0.
//
// -----
// zc158 redefines the 'advanced_search_result' page as simply 'search_result'.  If that
// new page's definition is present, the search result will be sent there for viewing;
// otherwise, it'll be sent to the legacy page.
//
$search_result_page = (defined('FILENAME_SEARCH_RESULT')) ? FILENAME_SEARCH_RESULT : FILENAME_ADVANCED_SEARCH_RESULT;

foreach ($products_search as $next) {
?>
<div class="sugg col-md-6">
    <div class="sugg-content">
        <a href="<?php echo $next['link'] ?>">
            <div class="sugg-img"><?php echo $next['image']; ?></div>
            <div class="sugg-name"><?php echo $next['name']; ?></div>
            <div class="sugg-model"><?php echo $next['model']; ?></div>
            <div class="sugg-brand"><?php echo $next['brand']; ?></div>
            <div class="sugg-price"><?php echo $next['price']; ?></div>
        </a>
    </div>
</div>
<?php
}
?>
<div class="row col-12">
    <div class="col-12 d-flex justify-content-center"><?php echo sprintf(TEXT_AJAX_SEARCH_RESULTS, $search_results_count); ?>&nbsp;<a class="btn btn-default sugg-button" role="button" href="<?php echo zen_href_link($search_result_page); ?>"><?php echo TEXT_AJAX_SEARCH_VIEW_ALL; ?></a></div>
</div>
