jQuery(document).ready(function () {
    checkAll();
});

// check/uncheck all pages
function checkAll() {
    jQuery('#check-all input').click(function () {
        if(jQuery(this).prop('checked')) {
            jQuery('.pages .page').each(function() {
                jQuery(this).find('input').prop('checked', 1);
            });
        } else {
            jQuery('.pages .page').each(function() {
                jQuery(this).find('input').prop('checked', 0);
            });
        }
    });
}