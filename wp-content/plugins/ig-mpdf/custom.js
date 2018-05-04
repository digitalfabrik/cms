jQuery(document).ready(function () {
    // check/uncheck all pages
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
    // open pdf in new tab if at least one page is selected
    jQuery('#ig-mpdf-submit').click(function () {
        jQuery('.pages .page').each(function() {
            if(jQuery(this).find('input').prop('checked')) {
                jQuery("#ig-mpdf-form").prop("target", '_blank');
                return true;
            }
        });
    });
});