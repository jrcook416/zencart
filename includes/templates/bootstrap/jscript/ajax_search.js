// -----
// AJAX search for the Zen Cart Bootstrap template.
//
// Bootstrap v3.2.0.
//
jQuery(document).ready(function() {
    // -----
    // When a search-icon is clicked, display the search form.
    //
    jQuery('#search-icon, #mobile-search').on('click', function(event) {
        jQuery('#search-wrapper').modal();
    });

    jQuery('#search-wrapper').on('shown.bs.modal', function() {
        jQuery('#search-input').focus();
        jQuery('#search-input').trigger('focus');
    });

    // -----
    // When a 'keyup' (devices with keyboards) or 'touchend' (those without) condition 
    // is seen on the search-input, gather the current keywords, submit them to the 
    // AJAX handler and display the returned HTML in the search-content section.
    //
    jQuery('#search-input').on('keyup touchend', function(event) {
        // -----
        // If the "Enter/Go" key is pressed, send the customer to the advanced-search-results
        // page with the current keywords.  Replacing Safari's "smart quotes" with 'vanilla' quotes
        // for matching.
        //
        var keyword = this.value.replace(/”|“/g, '"');
        keyword.replace(/‘|’/g, "'");

        var separator = (jQuery('#search-page').val().indexOf('?') == -1) ? '?' : '&';
        var searchLink = jQuery('#search-page').val()+separator+'keyword='+keyword;
        if (event.keyCode == 13) {
            window.location.replace(searchLink);
        }

        zcJS.ajax({
            url: "ajax.php?act=ajaxBootstrapSearch&method=searchProducts",
            data: {
                keywords: keyword
            },
            cache: false,
            headers: { "cache-control": "no-cache" },
            error: function (jqXHR, textStatus, errorThrown) {
                if (textStatus == 'timeout') {
                    alert(ajaxTimeoutErrorMessage);
                }
            },
        }).done(function(response) {
            jQuery('#search-content').html(response.searchHtml);
            jQuery('#search-content .sugg-button').attr('href', searchLink);
        });
    });
});