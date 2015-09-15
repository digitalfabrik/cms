var WPML_core = WPML_core || {};

jQuery(document).ready(function(){
    jQuery(document).delegate('#icl_menu_language', 'change', WPML_core.wp_nav_language_change);
    
});

WPML_core.wp_nav_language_change = function() {
    var thiss = jQuery(this);
    thiss.attr('disabled', 'disabled');
    var trid = jQuery('#icl_nav_menu_trid').val();
    data = {icl_wp_nav_menu_ajax:'translation_of', lang:jQuery(this).val(), trid:trid};
    jQuery.ajax({
        type: 'POST',
        data: data,
        url: location.href,
        success: function(res){
            jQuery('#icl_translation_of_wrap').html(res);
            thiss.removeAttr('disabled');
        }
    });
}

WPML_core.wp_nav_align_inputs = function() {
    var inputs = ['#menu-name', '#icl_menu_language', '#icl_menu_translation_of'];
    
    var right_max = 0;
    for (var i = 0; i < 3; i++) {
        var element = jQuery(inputs[i])
        if (element.length) {
            var position = jQuery(inputs[i]).offset().left;
            if (position > right_max) {
                right_max = position;
            }
        }
    }
    
    for ( i= 0; i < 3; i++) {
        var element = jQuery(inputs[i])
        if (element.length) {
            var position = jQuery(inputs[i]).offset().left;
            jQuery(inputs[i]).css('margin-left', right_max - position);
        }
    }
}