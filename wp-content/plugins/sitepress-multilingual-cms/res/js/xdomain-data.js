jQuery(document).ready(function () {
    jQuery('#lang_sel a, #lang_sel_footer a, .menu-item-language a').on('click', function (event) {
        event.preventDefault();
        var original_url = jQuery(this).attr('href');
        jQuery.ajax({
            url: icl_vars.ajax_url,
            type: 'post',
            dataType: 'json',
            async: false,
            data: {action: 'switching_language', from_language: icl_vars.current_language},
            success: function (ret) {
                if (ret.xdomain_data) {
                    var url_split = original_url.split('#');
                    var hash = '';
                    if (url_split.length > 1) {
                        hash = '#' + url_split[1];
                    }
                    var url = url_split[0];
                    var args_glue = url.indexOf('?') !== -1 ? '&' : '?';
                    url = original_url + args_glue + 'xdomain_data=' + ret.xdomain_data + hash;
                } else {
                    url = original_url;
                }

                location.href = url;
                return false;
            },
            error: function () {
                location.href = original_url;
                return false;
            }
        });
        return false;
    });
});