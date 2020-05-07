jQuery(function() {

    jQuery("#ure_nav_menus_access_button").button({
        label: ure_data_nav_menus_access.nav_menus
    }).click(function(event) {
        event.preventDefault();
        ure_nav_menus_access_dialog_prepare();
    });        

});



function ure_nav_menus_access_dialog_prepare() {
    
    if (!jQuery('#edit_theme_options').is(':checked')) {
        alert( ure_data_nav_menus_access.edit_theme_options_required );
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
        success: function(response) {
            var data = jQuery.parseJSON(response);
            if (typeof data.result !== 'undefined') {
                if (data.result === 'success') {                    
                    ure_nav_menus_access_dialog( data );
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


function ure_nav_menus_access_dialog(data) {
    jQuery(function($) {      
        $('#ure_nav_menus_access_dialog').dialog({
            dialogClass: 'wp-dialog',           
            modal: true,
            autoOpen: true, 
            closeOnEscape: true,      
            width: 500,
            height: 500,
            resizable: false,
            title: ure_data_nav_menus_access.dialog_title +' for "'+ ure_current_role +'"',
            'buttons'       : {
            'Update': function () {                                  
                    var form = $('#ure_nav_menus_access_form');                    
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
      $('#dialog-update-button').html(ure_ui_button_text(ure_data_nav_menus_access.update_button));
      $('.ui-dialog-buttonpane button:contains("Cancel")').attr("id", "dialog-cancel-button");
      $('#dialog-cancel-button').html(ure_ui_button_text(ure_data.cancel));
      
      $('#ure_nav_menus_access_container').html(data.html);

    });                                
    
}
