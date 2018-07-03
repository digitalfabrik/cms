jQuery(function() {

    jQuery("#ure_admin_menu_access_button").button({
        label: ure_data_admin_menu_access.admin_menu
    }).click(function(event) {
        event.preventDefault();
        ure_admin_menu_access_dialog_prepare();
    });        

});



function ure_admin_menu_access_dialog_prepare() {
    
    jQuery.ajax({
        url: ajaxurl,
        type: 'POST',
        dataType: 'html',
        data: {
            action: 'ure_ajax',
            sub_action: 'get_admin_menu',
            current_role: ure_current_role,
            network_admin: ure_data.network_admin,
            wp_nonce: ure_data.wp_nonce
        },
        success: function(response) {
            var data = jQuery.parseJSON(response);
            if (typeof data.result !== 'undefined') {
                if (data.result === 'success') {                    
                    ure_admin_menu_access_dialog(data);
                } else if (data.result === 'failure') {
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
    
}


function ure_admin_menu_access_dialog(data) {
    jQuery(function($) {      
        $('#ure_admin_menu_access_dialog').dialog({                   
            dialogClass: 'wp-dialog',           
            modal: true,
            autoOpen: true, 
            closeOnEscape: true,      
            width: 760,
            height: 700,
            resizable: false,
            title: ure_data_admin_menu_access.dialog_title +' for "'+ ure_current_role +'"',
            'buttons'       : {
            'Update': function () {                                  
                    var form = $('#ure_admin_menu_access_form');                    
                    form.submit();
                    $(this).dialog('close');
            },
            'Cancel': function() {
                $(this).dialog('close');
                return false;
            }
          }
      });    
      
      $('.ui-dialog-buttonpane button:contains("Update")').attr("id", "dialog-update-button");
      $('#dialog-update-button').html(ure_ui_button_text(ure_data_admin_menu_access.update_button));
      $('.ui-dialog-buttonpane button:contains("Cancel")').attr("id", "dialog-cancel-button");
      $('#dialog-cancel-button').html(ure_ui_button_text(ure_data.cancel));
      
      $('#ure_admin_menu_access_container').html(data.html);
      $('#ure_admin_menu_select_all').click(ure_admin_menu_auto_select);
    });                                
    
}


function ure_admin_menu_auto_select(event) {
    jQuery(function($) {
        if (event.shiftKey) {
            $('.ure-cb-column').each(function () {   // reverse selection
                $(this).prop('checked', !$(this).prop('checked'));
            });
        } else {    // switch On/Off all checkboxes
            $('.ure-cb-column').prop('checked', $('#ure_admin_menu_select_all').prop('checked'));

        }
    });
}