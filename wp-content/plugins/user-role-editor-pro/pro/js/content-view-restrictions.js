/* 
 * Content View Restrictions meta box for post editor
 * User Role Editor Pro WordPress plugin
 * Author: Vladimir Garagulya
 * Email: support@role-editor.com
 */


jQuery(document).ready(function(){
    ure_post_view_roles_button();
    
    ure_selected_roles_refresh();
    jQuery('input[name="ure_content_view_whom"]').click(function() {
        ure_selected_roles_refresh();
    });
    
    ure_post_access_error_action_select();
    jQuery('input[name="ure_post_access_error_action"]').click(function(){
        ure_post_access_error_action_select();
    });
    
});    


function ure_selected_roles_refresh() {
    var selected = jQuery('input[name="ure_content_view_whom"]:checked', '#post').val();
    if (selected==1 || selected==2) { // All users or Any user with role (logged in only)
        jQuery('#ure_selected_roles_container').hide();
    } else {    // show custom error message
        jQuery('#ure_selected_roles_container').show();
    }
}

function ure_post_access_error_action_select() {
    
    if (jQuery('input[name=ure_post_access_error_action]:checked', '#post').val()!=3) { // return 404 HTTP error or show global access error message
        jQuery('#ure_post_access_error_message_container').hide();
    } else {    // show custom error message
        jQuery('#ure_post_access_error_message_container').show();
    }

    if (jQuery('input[name=ure_post_access_error_action]:checked', '#post').val()!=4) { // return 404 HTTP error or show global access error message
        jQuery('#ure_view_access_error_url_container').hide();
    } else {    // show input field for access error URL
        jQuery('#ure_view_access_error_url_container').show();
    }

}


//----------------------------
// Post editor metabox support

function ure_post_view_roles_button() {
    if (jQuery('#edit_content_for_roles').length==0) {
        return;
    }
    
    jQuery("#edit_content_for_roles").button({
        label: ure_data_pro.edit_content_for_roles
    }).click(function (event) {
        event.preventDefault();
        jQuery('#edit_roles_list_dialog').dialog({
            dialogClass: 'wp-dialog',
            modal: true,
            autoOpen: true,
            closeOnEscape: true,
            width: 450,
            height: 400,
            resizable: false,
            title: ure_data_pro.edit_content_for_roles,
            'buttons': {
                'Save': function () {
                    ure_post_view_save_roles_list();
                    jQuery(this).dialog('close');
                },
                'Close': function () {
                    jQuery(this).dialog('close');
                    return false;
                }
            }
        });
    });
    jQuery('.ui-dialog-buttonpane button:contains("Save")').attr("id", "save-roles-list-button");
    jQuery('#save-roles-list-button').html(ure_data_pro.save_roles_list);
    jQuery('.ui-dialog-buttonpane button:contains("Cancel")').attr("id", "dialog-close-button");
    jQuery('#dialog-close-button').html(ure_data_pro.close);
    jQuery('#ure_roles_auto_select').click(ure_post_view_roles_auto_select);
}


function ure_post_view_save_roles_list() {
    
    var selected_roles = new Array();
    jQuery('#edit_roles_list_dialog_content input:checked').each(function() {
        if (jQuery(this).attr('id')!='ure_roles_auto_select') {
            selected_roles.push(jQuery(this).attr('name'));
        }
    });
        
    var to_save = '';
    for (i=0; i<selected_roles.length; i++) {
        if (to_save!=='') {
            to_save = to_save + ', ';
        }
        to_save = to_save + selected_roles[i];
    }
    jQuery('#ure_content_for_roles').html(to_save);
        
}


function ure_post_view_roles_auto_select(event) {
    jQuery(function($) {
        if (event.shiftKey) {
            $('.ure_role_cb').each(function () {   // reverse selection
                $(this).prop('checked', !$(this).prop('checked'));
            });
        } else {    // switch On/Off all checkboxes
            $('.ure_role_cb').prop('checked', $('#ure_roles_auto_select').prop('checked'));
        }
    });
}

// end of Post editor metabox support
//-----------------------------------
