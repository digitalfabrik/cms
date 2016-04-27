jQuery(document).ready(function () {
    if (window.navigator.userAgent.indexOf('MSIE ') > -1 || window.navigator.userAgent.indexOf('Trident/') > -1) {
        jQuery('#icl_translation_pickup_mode').submit(icl_tm_set_pickup_method);
    } else {
        jQuery('#icl_translation_pickup_mode').on('submit', icl_tm_set_pickup_method);
    }

    function icl_tm_set_pickup_method(e) {
        e.preventDefault();

        var form = jQuery(this);
        var submitButton = form.find(':submit');

        submitButton.prop('disabled', true);
        var ajaxLoader = jQuery(icl_ajxloaderimg).insertBefore(submitButton);

        jQuery.ajax({
            type: "POST",
            url: icl_ajx_url,
            dataType: 'json',
            data: 'icl_ajx_action=set_pickup_mode&' + form.serialize(),
            success: function (msg) {
                if (!msg.error) {
                    var boxPopulation = new WpmlTpPollingPickupPopulateAction(jQuery, TranslationProxyPolling);
                    boxPopulation.run();
                }
            },
            complete: function () {
                ajaxLoader.remove();
                submitButton.prop('disabled', false);
            }
        });

        return false;
    }
});