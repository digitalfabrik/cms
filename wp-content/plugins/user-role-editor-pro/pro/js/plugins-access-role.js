/* 
 * User Role Editor Pro WordPress plugin
 * Plugins access role level
 * Author: Vladimir Garagulya
 * email: support@role-editor.com
 * 
 */

jQuery(function() {
    if (jQuery('#ure_plugins_access_button').length==0) {
        return;
    }
    // "Posts Edit" button at User Role Editor dialog
    jQuery('#ure_plugins_access_button').button({
        label: ure_data_plugins_access.plugins_button
    }).click(function(event) {
        event.preventDefault();
        ure_plugins_access.dialog_prepare();
    });

});


var ure_plugins_access = {
    dialog_prepare: function() {
        if (!jQuery('#activate_plugins').is(':checked')) {
            alert(ure_data_plugins_access.activate_plugins_required);
            return;
        }
        jQuery.ajax({
            url: ajaxurl,
            type: 'POST',
            dataType: 'html',
            data: {
                action: 'ure_ajax',
                sub_action: 'get_plugins_access_data_for_role',
                current_role: ure_current_role,
                network_admin: ure_data.network_admin,
                wp_nonce: ure_data.wp_nonce
            },
            success: function(response) {
                var data = jQuery.parseJSON(response);
                if (typeof data.result !== 'undefined') {
                    if (data.result === 'success') {                    
                        ure_plugins_access.dialog_show(data);
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
    
    dialog_show: function(data) {
        jQuery('#ure_plugins_access_dialog').dialog({
            dialogClass: 'wp-dialog',
            modal: true,
            autoOpen: true,
            closeOnEscape: true,
            width: 680,
            height: 470,
            resizable: false,
            title: ure_data_plugins_access.dialog_title +' (' + ure_current_role + ')',
            'buttons': {
                'Update': function () {
                    var form = jQuery('#ure_plugins_access_form');
                    form.submit();
                    jQuery(this).dialog('close');
                },
                'Cancel': function () {
                    jQuery(this).dialog('close');
                    return false;
                }
            }
        });
        jQuery('.ui-dialog-buttonpane button:contains("Update")').attr("id", "dialog-update-button");
        jQuery('#dialog-update-button').html(ure_ui_button_text(ure_data_plugins_access.update_button));
        jQuery('.ui-dialog-buttonpane button:contains("Cancel")').attr("id", "dialog-cancel-button");
        jQuery('#dialog-cancel-button').html(ure_ui_button_text(ure_data.cancel));

        jQuery('#ure_plugins_access_container').html(data.html);
        jQuery('#ure_plugins_access_select_all').click(ure_plugins_access.auto_select);
    },
    
    auto_select: function() {
        if (event.shiftKey) {
            jQuery('.ure-cb-column').each(function () {   // reverse selection
                jQuery(this).prop('checked', !jQuery(this).prop('checked'));
            });
        } else {    // switch On/Off all checkboxes
            jQuery('.ure-cb-column').prop('checked', jQuery('#ure_plugins_access_select_all').prop('checked'));
        }
    }
}