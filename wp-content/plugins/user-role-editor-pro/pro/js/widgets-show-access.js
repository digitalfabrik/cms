jQuery(function() {

    jQuery(':button').each(function(i, obj) { 
        var obj_id = obj.id;
        var pos = obj_id.indexOf('_ure_access');
        if (pos>0) {
            var widget_id = obj_id.substr(0, pos);
            jQuery('#'+ obj_id).button({
                label: ure_data_widgets_show_access.access
            }).click(function(event) {
                    event.preventDefault();
                    ure_widgets_show_access_dialog_prepare(widget_id);
            });
        }
    });
    
});


function ure_widgets_show_access_dialog_prepare(widget_id) {
    jQuery.ajax({
        url: ajaxurl,
        type: 'POST',
        dataType: 'html',
        data: {
            action: 'ure_ajax',
            sub_action: 'get_show_access_data_for_widget',
            widget_id: widget_id,
            wp_nonce: ure_data_widgets_show_access.wp_nonce
        },
        success: function(response) {
            var data = jQuery.parseJSON(response);
            if (typeof data.result !== 'undefined') {
                if (data.result === 'success') {                    
                    ure_widgets_show_access_dialog(data);
                } else if (data.result === 'failure' || data.result === 'error') {
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


function ure_ui_button_text(caption) {
    var wrapper = '<span class="ui-button-text">' + caption + '</span>';

    return wrapper;
}


function ure_widgets_show_access_dialog(data) {
    jQuery(function($) {      
        $('#ure_widgets_show_access_dialog').dialog({
            dialogClass: 'wp-dialog',           
            modal: true,
            autoOpen: true, 
            closeOnEscape: true,      
            width: 500,
            height: 500,
            resizable: false,
            title: ure_data_widgets_show_access.dialog_title +' "'+ data.widget_title +'" ',
            'buttons'       : {
            'Update': function () {                                  
                    var form = $('#ure_widgets_show_access_form');
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
      $('#dialog-update-button').html(ure_ui_button_text(ure_data_widgets_show_access.update_button));
      $('.ui-dialog-buttonpane button:contains("Cancel")').attr("id", "dialog-cancel-button");
      $('#dialog-cancel-button').html(ure_ui_button_text(ure_data_widgets_show_access.cancel));      
      $('#ure_widgets_show_access_container').html(data.html);
      $('#ure_widgets_show_access_select_all').click(ure_widgets_show_access_auto_select);
    });                                
    
}


function ure_widgets_show_access_auto_select(event) {
    jQuery(function($) {
        if (event.shiftKey) {
            $('.ure-cb-column').each(function () {   // reverse selection
                $(this).prop('checked', !$(this).prop('checked'));
            });
        } else {    // switch On/Off all checkboxes
            $('.ure-cb-column').prop('checked', $('#ure_widgets_show_access_select_all').prop('checked'));

        }
    });
}