/* 
 * User Role Editor WordPress plugin Pro
 * Available Themes list edit
 * Author: Vladimir Garagulya
 * email: vladimir@shinephp.com
 * 
 */

jQuery(document).ready(function(){
    if (jQuery('#ure_allow_themes').length==0) {
        return;
    }
    jQuery('#ure_select_allowed_themes').multipleSelect({
            filter: true,
            multiple: true,
            selectAll: false,
            multipleWidth: 600,
            maxHeight: 300,
            placeholder: "Select themes you permit to activate",
            onClick: function(view) {
                ure_update_linked_controls_themes();
            }
    });
      
    var allowed_themes = jQuery('#ure_allow_themes').val();
    var selected_themes = allowed_themes.split(',');
    jQuery('#ure_select_allowed_themes').multipleSelect('setSelects', selected_themes);
      
});    


function ure_update_linked_controls_themes() {
    var data_value = jQuery('#ure_select_allowed_themes').multipleSelect('getSelects');
    var to_save = '';
    for (i=0; i<data_value.length; i++) {
        if (to_save!=='') {
            to_save = to_save + ', ';
        }
        to_save = to_save + data_value[i];
    }
    jQuery('#ure_allow_themes').val(to_save);
    
    var data_text = jQuery('#ure_select_allowed_themes').multipleSelect('getSelects', 'text');
    var to_show = '';
    for (i=0; i<data_text.length; i++) {        
        if (to_show!=='') {
            to_show = to_show + '\n';
        }
        to_show = to_show + data_text[i];
    }    
    jQuery('#show_allowed_themes').val(to_show);
}
