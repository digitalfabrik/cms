jQuery(function($) {
    
    $('#ure_other_roles_access_button').button({
        label: ure_data_other_roles_access.other_roles
    }).on('click', (function(event) {
        event.preventDefault();
        ure_other_roles_access_dialog_prepare();
    }));

});


function ure_other_roles_access_dialog_prepare() {

    jQuery(function($) {
        if ( ure_data_other_roles_access.not_block_local_admin==1 && $('#user_role').val()==='administrator' ) {
            alert( ure_data_other_roles_access.not_applicable_to_admin );
            return;
        }
        if (!$('#edit_users').is(':checked')) {
            alert(ure_data_other_roles_access.edit_users_required);
            return;
        }
        $.ajax({
            url: ajaxurl,
            type: 'POST',
            dataType: 'html',
            data: {
                action: 'ure_ajax',
                sub_action: 'get_roles_list',
                current_role: ure_current_role,
                network_admin: ure_data.network_admin,
                wp_nonce: ure_data.wp_nonce
            },
            success: function(response) {
                var data = $.parseJSON(response);
                if (typeof data.result !== 'undefined') {
                    if (data.result === 'success') {                    
                        ure_other_roles_access_dialog(data);
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
                alert("Ajax failure\n" + exception);
            },
            async: true
        });    
    });
}


function ure_other_roles_access_dialog(data) {
    jQuery(function($) {      
        $('#ure_other_roles_access_dialog').dialog({                   
            dialogClass: 'wp-dialog',           
            modal: true,
            autoOpen: true, 
            closeOnEscape: true,      
            width: 550,
            height: 500,
            resizable: false,
            title: ure_data_other_roles_access.dialog_title +' for "'+ ure_current_role +'"',
            'buttons'       : {
            'Update': function () {                                  
                    var form = $('#ure_other_roles_access_form');
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
      $('#dialog-update-button').html(ure_ui_button_text(ure_data_other_roles_access.update_button));
      $('.ui-dialog-buttonpane button:contains("Cancel")').attr("id", "dialog-cancel-button");
      $('#dialog-cancel-button').html(ure_ui_button_text(ure_data.cancel));
      
      $('#ure_other_roles_access_container').html(data.html);
      $('#ure_other_roles_select_all').on('click', ure_other_roles_auto_select );
    });                                
    
}


function ure_other_roles_auto_select(event) {
    jQuery(function($) {
        if (event.shiftKey) {
            $('.ure-cb-column').each(function () {   // reverse selection                
                $(this).prop('checked', !$(this).prop('checked'));
            });
        } else {    // switch On/Off all checkboxes
            $('.ure-cb-column').prop('checked', $('#ure_other_roles_select_all').prop('checked'));
        }
    });
}