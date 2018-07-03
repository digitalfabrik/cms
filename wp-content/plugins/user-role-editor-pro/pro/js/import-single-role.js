// change color of apply to all check box - for multi-site setup only - overrides the same function from the standard URE
function ure_applyToAllOnClick(cb) {
  el = document.getElementById('ure_apply_to_all_div');
  el_1 = document.getElementById('ure_import_to_all_div');
  if (cb.checked) {
    el.style.color = '#FF0000';
    el_1.style.color = '#FF0000';
    document.getElementById('ure_import_to_all').checked = true;
  } else {
    el.style.color = '#000000';
    el_1.style.color = '#000000';
    document.getElementById('ure_import_to_all').checked = false;
  }
}


// change color of apply to all check box - for multi-site setup only - overrides the same function from the standard URE
function ure_importToAllOnClick(cb) {
  el = document.getElementById('ure_import_to_all_div');
  if (cb.checked) {
    el.style.color = '#FF0000';
  } else {
    el.style.color = '#000000';
  }
}


function ure_import_roles_dialog() {
    jQuery(function ($) {
        $info = $('#ure_import_roles_dialog');
        $info.dialog({
            dialogClass: 'wp-dialog',
            modal: true,
            autoOpen: true,
            closeOnEscape: true,
            width: 550,
            height: 400,
            resizable: false,
            title: ure_data_import.import_roles_title,
            'buttons': {
                'Import': function () {
                    var file_name = $('#roles_file').val();
                    if (file_name == '') {
                        alert(ure_data_import.select_file_with_roles);
                        return false;
                    }                    
                    var form = $('#ure_import_roles_form');
                    form.attr('action', ure_data.page_url);
                    $("<input type='hidden'>")
                            .attr("name", 'ure_nonce')
                            .attr("value", ure_data.wp_nonce)
                            .appendTo(form);
                    form.submit();
                    $(this).dialog('close');
                },
                'Cancel': function () {
                    $(this).dialog('close');
                    return false;
                }
            }
        });
        $('.ui-dialog-buttonpane button:contains("Import")').attr("id", "dialog-import-roles-button");
        $('#dialog-import-roles-button').html(ure_ui_button_text(ure_data_import.import_roles));
        $('.ui-dialog-buttonpane button:contains("Cancel")').attr("id", "dialog-cancel-button");
        $('#dialog-cancel-button').html(ure_ui_button_text(ure_data.cancel));
    });                                    
}


URE_Import_Role = {    
    status_refresh: function() {
        var markup = '<strong>'+ ure_data_import.prev_site +'</strong>: '+ 
                     ure_data_import.message +'<br><br>';
        if (ure_data_import.next_site!=='Done') {
            markup += ure_data_import.importing_to +': <strong> '+ ure_data_import.next_site +' ...</strong>  '+
                      jQuery('#ure_ajax_import').html();
        }    
        jQuery('#ure_import_roles_status_container').html(markup);
        
    },
    
    update_site: function() {
        if (ure_data_import.sites.length==0) {
            setTimeout(function() { jQuery('#ure_import_roles_status_dialog').dialog('close'); }, 2000); // delay for a second            
            return;
        }        
        var site_id = ure_data_import.sites.shift();
        var addons = JSON.stringify(ure_data_import.addons);
        jQuery.ajax({
            url: ajaxurl,
            type: 'POST',
            dataType: 'html',
            data: {
                action: 'ure_ajax',
                sub_action: 'import_role_to_site',
                site_id: site_id,
                next_site_id: ure_data_import.sites[0],
                addons: addons,
                user_role: ure_data_import.user_role,
                wp_nonce: ure_data.wp_nonce
            },
            success: function (response) {
                var data = jQuery.parseJSON(response);
                if (typeof data.result !== 'undefined') {
                    if (data.result === 'success') {
                        ure_data_import.prev_site = ure_data_import.next_site;
                        ure_data_import.next_site = data.next_site;
                        ure_data_import.message = data.message;
                        URE_Import_Role.status_refresh();
                        URE_Import_Role.update_site();
                    } else if (data.result === 'error') {                        
                        alert(data.message);
                        jQuery('#ure_import_roles_status_dialog').dialog('close');
                    } else {                        
                        alert('Wrong response: ' + response)
                        jQuery('#ure_import_roles_status_dialog').dialog('close');
                    }
                } else {                    
                    alert('Wrong response: ' + response);
                    jQuery('#ure_import_roles_status_dialog').dialog('close');
                }
            },
            error: function (XMLHttpRequest, textStatus, exception) {
                alert("Ajax failure\n" + XMLHttpRequest.statusText);
            },
            async: true
        });
    },
    
    show_status_window: function() {
        jQuery('#ure_import_roles_status_dialog').dialog({
            dialogClass: 'wp-dialog',
            modal: true,
            autoOpen: true,
            closeOnEscape: true,
            width: 550,
            height: 200,
            resizable: false,
            title: ure_data_import.import_roles_title,
            'buttons': {
                'Cancel': function () {
                    jQuery(this).dialog('close');
                    return false;
                }
            }
        });
        this.status_refresh();  
        this.update_site();
    }
};   // end of URE_Import_Role class


jQuery(function() {

    jQuery("#ure_import_roles_button").button({
        label: ure_data_import.import_roles
    }).click(function(event) {
        event.preventDefault();
        ure_import_roles_dialog();
    });

    if (ure_data_import.action==='import-role-next-site') {
        URE_Import_Role.show_status_window();
    }

});
