jQuery(function() {

    jQuery("#ure_meta_boxes_access_button").button({
        label: ure_data_meta_boxes_access.meta_boxes
    }).click(function(event) {
        event.preventDefault();
        ure_meta_boxes_access_dialog_prepare();
    });

});


function ure_meta_boxes_access_dialog_prepare() {
    jQuery.ajax({
        url: ajaxurl,
        type: 'POST',
        dataType: 'html',
        data: {
            action: 'ure_ajax',
            sub_action: 'get_meta_boxes_list',
            current_role: ure_current_role,
            network_admin: ure_data.network_admin,
            wp_nonce: ure_data.wp_nonce
        },
        success: function(response) {
            var data = jQuery.parseJSON(response);
            if (typeof data.result !== 'undefined') {
                if (data.result === 'success') {                    
                    ure_meta_boxes_access_dialog(data);
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
    
}


function ure_meta_boxes_access_dialog(data) {
    jQuery(function($) {      
        $('#ure_meta_boxes_access_dialog').dialog({                   
            dialogClass: 'wp-dialog',           
            modal: true,
            autoOpen: true, 
            closeOnEscape: true,      
            width: 650,
            height: 600,
            resizable: false,
            title: ure_data_meta_boxes_access.dialog_title +' for "'+ ure_current_role +'"',
            'buttons'       : {
            'Update': function () {                                  
                    var form = $('#ure_meta_boxes_access_form');
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
      $('#dialog-update-button').html(ure_ui_button_text(ure_data_meta_boxes_access.update_button));
      $('.ui-dialog-buttonpane button:contains("Cancel")').attr("id", "dialog-cancel-button");
      $('#dialog-cancel-button').html(ure_ui_button_text(ure_data.cancel));
      
      $('#ure_meta_boxes_access_container').html(data.html);
    });                                
    
}


function ure_meta_boxes_remove_from_list(meta_box_key) {
    jQuery('#remove_'+ meta_box_key).hide();
    jQuery('#ajax_'+ meta_box_key).show();
    jQuery.ajax({
        url: ajaxurl,
        type: 'POST',
        dataType: 'html',
        data: {
            action: 'ure_ajax',
            sub_action: 'remove_from_meta_boxes_list',
            mb_key: meta_box_key,
            wp_nonce: ure_data.wp_nonce
        },
        success: function(response) {
            var data = jQuery.parseJSON(response);
            if (typeof data.result !== 'undefined') {
                if (data.result === 'success') {                    
                    ure_meta_boxes_refresh_view(meta_box_key);
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
}


function ure_meta_boxes_refresh_view(meta_box_key) {

    jQuery('#tr_'+ meta_box_key).remove();
    
}