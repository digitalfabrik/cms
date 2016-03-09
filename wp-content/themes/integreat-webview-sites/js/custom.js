$(document).ready(function() {

    liveSearch();

    searchContentsHeight();

});

function liveSearch() {
    $('.search').on('input',function(e) {
        // current search input text
        var searchText = $('.search input').val().toLowerCase();

        if( searchText != null && searchText != '' ) {
            // iterate through all sites/languages and hide all that do not match search text
            $('#searchContent > div').each(function() {
                var searchContentName = $(this).data('search').toLowerCase();

                if( searchContentName.indexOf(searchText) >= 0 ) {
                    $(this).show();
                } else {
                    $(this).hide();
                }
            });
        } else {
            $('#searchContent > div').each(function() {
                $(this).show();
            });
        }
    });
}

function searchContentsHeight() {
    var elem = $('#searchContent > div > a');
    elem.height(0.7 * elem.width());
}