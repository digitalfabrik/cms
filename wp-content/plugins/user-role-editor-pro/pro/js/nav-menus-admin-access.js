var ure_nav_menus_admin_access = {
    update_success: function( data ) {
        
        jQuery('#ure_task_status').hide();
        if ( data.result=='success' ) {    
            ure_main.show_notice( data.message, 'success' );            
        } else {
            ure_main.show_notice( data.message, 'error' );
        }
    },    
    
    update: function() {
        
        var values = {};
        jQuery.each( jQuery('#ure_nav_menus_access_form').serializeArray(), function( i, field ) {
            values[field.name] = field.value;
        });
        jQuery('#ure_task_status').show();
        jQuery.ajax( {
            url: ajaxurl,
            type: 'POST',
            dataType: 'json',
            async: true,
            data: {
                action: 'ure_ajax',
                sub_action: 'nav_menus_admin_access_update',
                values: values,
                user_role_id: values['user_role'],
                network_admin: ure_data.network_admin,
                wp_nonce: ure_data.wp_nonce
            },
            success: ure_nav_menus_access.update_success,
            error: ure_main.ajax_error
        } );
                
    },
    dialog_show: function (data) {

        jQuery('#ure_nav_menus_access_dialog').dialog({
            dialogClass: 'wp-dialog',
            modal: true,
            autoOpen: true,
            closeOnEscape: true,
            width: 500,
            height: 500,
            resizable: false,
            title: ure_data_nav_menus_access.dialog_title + ' for "' + ure_current_role + '"',
            'buttons': {
                'Update': function () {
                    ure_nav_menus_admin_access.update();
                    jQuery(this).dialog('close');
                },
                'Cancel': function () {
                    jQuery(this).dialog('close');
                    return false;
                }
            }
        });

        jQuery('.ui-dialog-buttonpane button:contains("Update")').attr("id", "dialog-update-button");
        jQuery('#dialog-update-button').html(ure_ui_button_text(ure_data_nav_menus_access.update_button));
        jQuery('.ui-dialog-buttonpane button:contains("Cancel")').attr("id", "dialog-cancel-button");
        jQuery('#dialog-cancel-button').html(ure_ui_button_text(ure_data.cancel));

        jQuery('#ure_nav_menus_access_container').html(data.html);

    },
    dialog_prepare: function () {
        if (!jQuery('#edit_theme_options').is(':checked')) {
            alert(ure_data_nav_menus_access.edit_theme_options_required);
            return;
        }

        jQuery.ajax({
            url: ajaxurl,
            type: 'POST',
            dataType: 'html',
            data: {
                action: 'ure_ajax',
                sub_action: 'get_nav_menus',
                current_role: ure_current_role,
                network_admin: ure_data.network_admin,
                wp_nonce: ure_data.wp_nonce
            },
            success: function (response) {
                var data = jQuery.parseJSON(response);
                if (typeof data.result !== 'undefined') {
                    if (data.result === 'success') {
                        ure_nav_menus_admin_access.dialog_show( data );
                    } else if (data.result === 'failure') {
                        alert(data.message);
                    } else {
                        alert('Wrong response: ' + response)
                    }
                } else {
                    alert('Wrong response: ' + response)
                }
            },
            error: function (XMLHttpRequest, textStatus, exception) {
                alert("Ajax failure\n" + XMLHttpRequest.statusText);
            },
            async: true
        });
    }
}


jQuery(function() {

    jQuery("#ure_nav_menus_access_button").button({
        label: ure_data_nav_menus_access.nav_menus
    }).on('click', (function(event) {
        event.preventDefault();
        ure_nav_menus_admin_access.dialog_prepare();
    }));        

});
