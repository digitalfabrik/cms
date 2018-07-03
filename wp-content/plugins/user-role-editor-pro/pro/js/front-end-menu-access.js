/* 
 * Front end menu access support at menu editor page
 * User Role Editor Pro WordPress plugin
 * Author: Vladimir Garagulya
 * Email: support@role-editor.com
 */

var ure = {};

jQuery(document).ready(function(){    
    
    jQuery('.ure_show_to').click(function(event) {
        ure_selected_roles_refresh(event);
    });
    
    jQuery('.ure_edit_roles_list').click(function(event) {
        event.preventDefault();
        ure_edit_roles_list_dialog(event);
    });
    
    jQuery(document.body).append('<div id="ure_select_roles_dialog"></div>');
    
    ure.roles = Object.keys(ure_pro_front_end_menu_access_data.roles).map(function (key) { return key; });
    ure.roles_names = Object.keys(ure_pro_front_end_menu_access_data.roles).map(function (key) { return ure_pro_front_end_menu_access_data.roles[key]; });

    ure_selected_roles_refresh_all();
});    


function ure_show_edit_roles_list_dialog(el_number) {
    jQuery('#ure_select_roles_dialog').dialog({                   
            dialogClass: 'wp-dialog',           
            modal: true,
            autoOpen: true, 
            closeOnEscape: true,      
            width: 500,
            height: 500,
            resizable: false,
            title: ure_pro_front_end_menu_access_data.dialog_title,
            'buttons'       : {
            'Update': function () {                                  
                    ure_store_selected_roles(el_number);
                    jQuery(this).dialog('close');
            },
            'Cancel': function() {
                jQuery(this).dialog('close');
                return false;
            }
          }
      });
}


function ure_edit_roles_list_dialog(event) {
    
    var el_number = event.target.id.substr(20);  
    var roles_str = jQuery('#ure_roles_list_'+ el_number).html();
    var selected_roles = new Array();
    if (roles_str!='') {
        selected_roles = roles_str.split(',');
    }
    
    var content = '<div id="ure_roles_list_container">';
    var checked = '';
    for(i=0; i<ure.roles.length; i++) {
        if (jQuery.inArray(ure.roles[i], selected_roles) > -1) {
            checked = 'checked="checked"';
        } else {
            checked = '';
        }
        content += '<input type="checkbox" id="ure_cb_'+ i +'" value="'+ ure.roles[i] +'" class="ure_role_cb" '+ checked +'> <label for="ure_cb_'+ i +'">'+ ure.roles_names[i] +'</label><br>';
    }
    content += '</div>'
    
    jQuery('#ure_select_roles_dialog').html(content);
    
    ure_show_edit_roles_list_dialog(el_number);    
}



function ure_store_selected_roles(el_number) {
    var roles_list = new Array();
    jQuery('.ure_role_cb').each(function(ind, obj) {
        if (jQuery('#'+ obj.id).is(':checked')) {
            roles_list.push(jQuery('#'+ obj.id).val());
        }
    });
    
    if (roles_list.length>0) {
        roles_str = roles_list.join();
    } else {
        roles_str = '';
    }
    jQuery('#ure_roles_list_'+ el_number).html(roles_str);
}


function ure_show_hide_roles_list(el_number) {
    var with_roles1 = jQuery('#ure_show_to_logged_in_with_roles_'+ el_number).prop('checked');    
    var container_id = 'ure_selected_roles_container_'+ el_number;    
    if (with_roles1) {    // logged-in with selected roles
        jQuery('#'+ container_id).appendTo(jQuery('#ure_roles_container1_'+ el_number));
        jQuery('#'+ container_id).show();
    }
    
    var with_roles2 = jQuery('#ure_show_to_not_logged_in_and_with_roles_'+ el_number).prop('checked');    
    if (with_roles2) {    // not logged-in and logged-in users with selected roles
        jQuery('#'+ container_id).appendTo(jQuery('#ure_roles_container2_'+ el_number));
        jQuery('#'+ container_id).show();
    } 
    
    if (!with_roles1 && !with_roles2) {
        jQuery('#'+ container_id).hide();
    }
    
}


function ure_selected_roles_refresh(event) {
    
    var pos = event.target.id.lastIndexOf('_');
    var el_number = event.target.id.substr(pos + 1);
    ure_show_hide_roles_list(el_number);    
    
}


function ure_selected_roles_refresh_all() {
    var id_list = new Array();
    jQuery('.menu-item-data-db-id').each(function(ind, obj) {
        id_list.push(obj.value);
    });
    if (id_list.length==0) {
        return;
    }
    for(i=0; i<id_list.length; i++) {
        ure_show_hide_roles_list(id_list[i]);    
    }
    
}
