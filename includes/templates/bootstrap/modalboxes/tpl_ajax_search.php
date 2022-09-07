<?php
// -----
// Part of the Bootstrap template for Zen Cart.  Included by /includes/templates/bootstrap/common/tpl_main_page.php.
//
// Bootstrap v3.4.0.
//
if (defined('BS4_AJAX_SEARCH_ENABLE') && BS4_AJAX_SEARCH_ENABLE === 'true') {
    // -----
    // zc158 redefines the 'advanced_search_result' page as simply 'search_result'.  If that
    // new page's definition is present, the search result will be sent there for viewing;
    // otherwise, it'll be sent to the legacy page.
    //
    $search_result_page = (defined('FILENAME_SEARCH_RESULT')) ? FILENAME_SEARCH_RESULT : FILENAME_ADVANCED_SEARCH_RESULT;
?>
    <div id="search-wrapper" class="modal fade" role="dialog" aria-labelledby="search-modal-title" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-body container-fluid">
                    <button type="button" class="close" data-dismiss="modal" aria-label="<?php echo TEXT_MODAL_CLOSE; ?>"><i class="fa fa-times"></i></button>
                    <h5 class="modal-title mb-1" id="search-modal-title"><?php echo TEXT_AJAX_SEARCH_TITLE; ?></h5>
                    <div class="form-group">
                        <form class="search-form">
                            <input type="text" id="search-input" class="form-control" placeholder="<?php echo TEXT_AJAX_SEARCH_PLACEHOLDER; ?>">
                            <input id="search-page" type="hidden" value="<?php echo zen_href_link($search_result_page); ?>">
                        </form>
                    </div>
                    <div id="search-content" class="row"></div>
                </div>
            </div>
        </div>
    </div>
<?php
}
