/* 
 * User Role Editor Pro WordPress plugin
 * Plugins access user level
 * Author: Vladimir Garagulya
 * email: support@role-editor.com
 * 
 */


var ure_plugins_access = {
        
    selection_dialog_prepare: function() {
        var user_id = jQuery('#ure_user_id').val();
        var plugins = jQuery('#ure_plugins_access_list').val();
        jQuery.ajax({
            url: ajaxurl,
            type: 'POST',
            dataType: 'html',
            data: {
                action: 'ure_ajax',
                sub_action: 'get_plugins_access_data_for_user',
                user_id: user_id,
                plugins: plugins,
                wp_nonce: ure_data_plugins_access.wp_nonce
            },
            success: function(response) {
                var data = jQuery.parseJSON(response);
                if (typeof data.result !== 'undefined') {
                    if (data.result === 'success') {                    
                        ure_plugins_access.selection_dialog_show(data);
                    } else if (data.result === 'error') {
                        alert(data.message);
                    } else {
                        alert('Wrong response: ' + response)
                    }
                } else {
                    alert('Wrong response: ' + response)
                }
            },
            error: function(XMLHttpRequest, textStatus, exception) {
                alert("Ajax failure\n" + XMLHttpRequest.statusText);
            },
            async: true
        });
    },
    
    selection_dialog_title: function() {
        var model = jQuery('input[name=ure_plugins_access_model]:checked').val();  
        var what = '';
        var dialog_title = ure_data_plugins_access.allow_manage;
        if (model==1) {
            what = ure_data_plugins_access.selected;
        } else {
            what = ure_data_plugins_access.not_selected;
        }
        dialog_title += ' '+ what; 
        
        return dialog_title;
    },
    
    save_user_selection: function() {
        var selected_ids = [];
        var selected_titles = [];
        
        jQuery('#ure_plugins_access_table input:checked').each(function() {
            var cb_id = jQuery(this).attr('id');
            var plugin_id = jQuery('#'+ cb_id +'-id').html();
            selected_ids.push(plugin_id);
            var plugin_title = jQuery('#'+ cb_id +'-title').html();
            selected_titles.push(plugin_title);
        });
        
        var raw_data = selected_ids.join(',');
        jQuery('#ure_plugins_access_list').val(raw_data);
        var data_to_show = selected_titles.join("\n");
        jQuery('#ure_show_plugins_access_list').html(data_to_show);
    },
    
    auto_select: function(event) {
        if (event.shiftKey) {
            jQuery('.ure-cb-column').each(function () {   // reverse selection
                jQuery(this).prop('checked', !jQuery(this).prop('checked'));
            });
        } else {    // switch On/Off all checkboxes
            jQuery('.ure-cb-column').prop('checked', jQuery('#ure_plugins_access_select_all').prop('checked'));

        }
    },
    
    selection_dialog_show: function(data) {
        
        dialog_title = ure_plugins_access.selection_dialog_title();
        jQuery('#ure_plugins_access_dialog').dialog({
            dialogClass: 'wp-dialog',           
            modal: true,
            autoOpen: true, 
            closeOnEscape: true,      
            width: 680,
            height: 470,
            resizable: false,
            title: dialog_title,
            'buttons'       : {
            'Update': function () {                                  
                ure_plugins_access.save_user_selection();
                jQuery(this).dialog('close');
            },
            'Cancel': function() {
                jQuery(this).dialog('close');
                return false;
            }
          }
      });
      jQuery('.ui-dialog-buttonpane button:contains("Update")').attr("id", "dialog-update-button");
      jQuery('#dialog-update-button').html(ure_data_plugins_access.update);
      jQuery('.ui-dialog-buttonpane button:contains("Cancel")').attr("id", "dialog-cancel-button");
      jQuery('#dialog-cancel-button').html(ure_data_plugins_access.cancel);
      jQuery('#ure_plugins_access_dialog_content').html(data.html);
      jQuery('#ure_plugins_access_select_all').click(this.auto_select);
    }
};


jQuery(document).ready(function(){
    if (jQuery('#ure_plugins_access_list').length==0) {
        return;
    }
    jQuery("#ure_edit_allowed_plugins").button().click(function(event) {
        event.preventDefault();
        ure_plugins_access.selection_dialog_prepare();                
    });              
            
});    


function ure_update_linked_controls_plugins() {
    var data_value = jQuery('#ure_select_allowed_plugins').multipleSelect('getSelects');
    var to_save = '';
    for (i=0; i<data_value.length; i++) {
        if (to_save!=='') {
            to_save = to_save + ', ';
        }
        to_save = to_save + data_value[i];
    }
    jQuery('#ure_allow_plugins').val(to_save);
    
    var data_text = jQuery('#ure_select_allowed_plugins').multipleSelect('getSelects', 'text');
    var to_show = '';
    for (i=0; i<data_text.length; i++) {        
        if (to_show!=='') {
            to_show = to_show + '\n';
        }
        to_show = to_show + data_text[i];
    }    
    jQuery('#show_allowed_plugins').val(to_show);    
}
