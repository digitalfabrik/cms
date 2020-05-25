/* 
 * User Role Editor Pro WordPress plugin
 * Author: Vladimir Garagulya
 * email: support@role-editor.com
 * 
 * Settings section support
 * 
 */

// Content View Restrictions add-on defaults section UI management
ure_cvr_defaults = {    
    
    // Show / Hide Content View Restriction default values section on add-on activation/deactivation
    refresh: function() {
        var checked = jQuery('input[name="activate_content_for_roles"]:checked').length;
        var trow = jQuery('#ure_content_view_defaults');
        if (checked>0) { 
            trow.show();
            var checked1 = jQuery('#content_view_show_access_error_message:checked').length;
            var cont_div = jQuery('#content_view_access_error_message_container');
            if (checked1>0) {
                cont_div.show();
            } else {
                cont_div.hide();
            }
        } else {
            trow.hide();
        }
    },
    
    show_message_div: function() {
        jQuery('#content_view_access_error_message_container').show();
        
    },
    
    hide_message_div: function() {
        jQuery('#content_view_access_error_message_container').hide();
        
    },
    
    toggle_container: function() {
        var link = jQuery('#content_view_restrictions_defaults_show');
        var container = jQuery('#content_view_restrictions_defaults_container');
        if (container.is(':visible')) {
            link.text('Show defaults...');
            jQuery('#cvr_defaults_visible').val(0);
        } else {
            link.text('Hide defaults...');            
            jQuery('#cvr_defaults_visible').val(1);
        }
        container.toggle();
    }
    
};

jQuery(function() {
    ure_cvr_defaults.refresh();
});


//--------------------------------------------
// Admin menu access URL parameters White List
ure_admin_menu_access_url_args = {
    selected_row_id: '',
    
    toggle_link: function () {
        jQuery('#admin_menu_access_url_args_link').toggle();
    },

    ui_button_text: function(caption) {
        var wrapper = '<span class="ui-button-text">' + caption + '</span>';

        return wrapper;
    },
    
    parse_url: function(url) {

        var parser = document.createElement("a");
        parser.href = url;

        // IE 8 and 9 dont load the attributes "protocol" and "host" in case the source URL
        // is just a pathname, that is, "/example" and not "http://domain.com/example".
        parser.href = parser.href;

        // IE 7 and 6 wont load "protocol" and "host" even with the above workaround,
        // so we take the protocol/host from window.location and place them manually
        if (parser.host === "") {
            var newProtocolAndHost = window.location.protocol + "//" + window.location.host;
            if (url.charAt(1) === "/") {
                parser.href = newProtocolAndHost + url;
            } else {
                // the regex gets everything up to the last "/"
                // /path/takesEverythingUpToAndIncludingTheLastForwardSlash/thisIsIgnored
                // "/" is inserted before because IE takes it of from pathname
                var currentFolder = ("/" + parser.pathname).match(/.*\//)[0];
                parser.href = newProtocolAndHost + currentFolder + url;
            }
        }

        var parsed_url = {};
        // copies all the properties to returning object        
        var properties = ['host', 'hostname', 'hash', 'href', 'port', 'protocol', 'search'];
        for (var i = 0, n = properties.length; i < n; i++) {
            parsed_url[properties[i]] = parser[properties[i]];
        }

        // pathname is special because IE takes the "/" of the starting of pathname
        parsed_url.pathname = (parser.pathname.charAt(0) !== "/" ? "/" : "") + parser.pathname;
        
        return parsed_url;
    },
    
    extract_args: function (query_string) {

        if (query_string.length==0) {
            return false;
        }

        var obj = {};        
        query_string = query_string.substring(1); // ignore '?' character in the 1st place of a query string
        var arr = query_string.split('&');
        for (var i = 0; i < arr.length; i++) {            
            var a = arr[i].split('=');
            // in case params look like: list[]=thing1&list[]=thing2
            var param_num = undefined;
            var param_name = a[0].replace(/\[\d*\]/, function (v) {
                param_num = v.slice(1, -1);
                return '';
            });
            // set parameter value (use '' if empty)
            var param_value = typeof (a[1]) === 'undefined' ? '' : a[1];            
            param_name = param_name.toLowerCase();
            param_value = param_value.toLowerCase();
            // if parameter name already exists
            if (obj[param_name]) {
                // convert value to array (if still string)
                if (typeof obj[param_name] === 'string') {
                    obj[param_name] = [obj[param_name]];
                }
                // if no array index number specified...
                if (typeof param_num === 'undefined') {
                    // put the value on the end of the array
                    obj[param_name].push(param_value);
                }
                // if array index number specified...
                else {
                    // put the value at that index number
                    obj[param_name][param_num] = param_value;
                }
            }
            // if param name doesn't exist yet, set it
            else {
                obj[param_name] = param_value;
            }
        }        

        return obj;
    },
    
    add_old_args: function(new_args) {
        var old_args = new Array();
        var old_value = jQuery('#allowed_args').val();
        if (old_value.length>0) {
            var old_args_0 = old_value.split(',');        
            var trimmed = '';
            for(var i=0; i<old_args_0.length; i++) {
                trimmed = jQuery.trim(old_args_0[i]);
                if (trimmed.length>0) {                    
                    old_args.push(trimmed);
                }
            }
            jQuery.merge(new_args, old_args);
        }
        
        return new_args;
    },
    
    array_unique: function(arr) {
        var seen = {};        
        var filtered = arr.filter(function(value) {
            if (seen[value]) {
                return false;
            }
            seen[value] = true;
            return value;
        });
        
        return filtered;
    },
    
    extract: function() {
        var url = jQuery('#url_to_parse').val();
        if (url.length==0) {
            return;
        }
        var parsed_url = this.parse_url(url);
        var arguments = this.extract_args(parsed_url.search);
        if (arguments.length==0) {
            return;
        }
        var new_args = Object.keys(arguments);                
        new_args = this.add_old_args(new_args);                    
        new_args = this.array_unique(new_args);
        new_args.sort();
        var args_list = new_args.join(', ');
        args_list = jQuery.trim(args_list);
        jQuery('#allowed_args').val(args_list);
        jQuery('#url_to_parse').val('');
        
    },
    
    update: function() {
      
        var allowed_args = jQuery('#allowed_args').val();
        var row = jQuery('#'+ this.selected_row_id);
        var cells = row.find('td');
        var base_url = cells[1].innerHTML;
        
        jQuery.ajax({
            url: ajaxurl,
            type: 'POST',
            dataType: 'html',
            data: {
                action: 'ure_ajax',
                sub_action: 'save_admin_menu_allowed_args',
                base_url: base_url,
                allowed_args: allowed_args,
                wp_nonce: ure_data.wp_nonce
            },
            success: function (response) {
                var data = jQuery.parseJSON(response);
                if ( (typeof data.result === 'undefined') || (data.result!=='success' && data.result!=='failure') ) {
                    alert('Wrong response: ' + response);
                    return;
                }
                if (data.result === 'success') {
                    cells[2].innerHTML = allowed_args;
                } else {    // failure
                    alert(data.message);
                }
            },
            error: function (XMLHttpRequest, textStatus, exception) {
                alert("Ajax failure\n" + XMLHttpRequest.statusText);
            },
            async: true
        });
        
    },

    show_main: function(data) {
        
        jQuery(function ($) {
            $('#admin_menu_allowed_args_dialog').dialog({
                dialogClass: 'wp-dialog',
                modal: true,
                autoOpen: true,
                closeOnEscape: true,
                width: 800,
                height: 670,
                resizable: false,
                title: ure_settings_data_pro.admin_menu_allowed_args_dialog_title,
                'buttons': {
                    'Close': function () {                        
                        $(this).dialog('close');
                    }
                }
            });

            ure_admin_menu_access_url_args.selected_row_id = '';
            $('.ui-dialog-buttonpane button:contains("Update")').attr("id", "dialog-update-button");
            $('#dialog-update-button').html(ure_admin_menu_access_url_args.ui_button_text(ure_settings_data_pro.close_button));
            $('#admin_menu_allowed_args_container').html(data.html);            
            
            $('#extract_args_button').button({
                label: ure_settings_data_pro.extract_button,
                disabled: true
            }).click(function(event) {
                event.preventDefault();
                ure_admin_menu_access_url_args.extract();
            });
          
            $('#update_allowed_args_button').button({
                label: ure_settings_data_pro.update_button,
                disabled: true
            }).click(function(event) {
                event.preventDefault();
                ure_admin_menu_access_url_args.update();
            });
            
            ure_admin_menu_access_url_args.table_rows_clickable();
        });
        
    },

    dialog_prepare: function () {
        jQuery.ajax({
            url: ajaxurl,
            type: 'POST',
            dataType: 'html',
            data: {
                action: 'ure_ajax',
                sub_action: 'get_admin_menu_allowed_args',
                wp_nonce: ure_data.wp_nonce
            },
            success: function (response) {
                var data = jQuery.parseJSON(response);
                if ( (typeof data.result === 'undefined') || (data.result!=='success' && data.result!=='failure') ) {
                    alert('Wrong response: ' + response);
                    return;
                }
                if (data.result === 'success') {
                    ure_admin_menu_access_url_args.show_main(data);
                } else {    // failure
                    alert(data.message);
                }
            },
            error: function (XMLHttpRequest, textStatus, exception) {
                alert("Ajax failure\n" + XMLHttpRequest.statusText);
            },
            async: true
        });
    },

    show: function () {
        this.dialog_prepare();
    },
    
    table_rows_clickable: function () {
        jQuery('#ure_admin_menu_access_table tr').click(function () {
            var selected = jQuery(this).hasClass('ure_table_row_selected');
            jQuery("#ure_admin_menu_access_table tr").removeClass('ure_table_row_selected');
            if (!selected) {
                jQuery(this).addClass("ure_table_row_selected");
            }            
            var cells = jQuery('#'+ this.id).find('td');
            jQuery('#base_url_label').html('<span style="font-weight: bold;">' + cells[1].innerHTML + '</span>');
            jQuery('#allowed_args').html(cells[2].innerHTML);
            
            if (ure_admin_menu_access_url_args.selected_row_id.length==0) {
                jQuery('#update_allowed_args_button').button('enable');
                jQuery('#extract_args_button').button('enable');
            }
            ure_admin_menu_access_url_args.selected_row_id = this.id;
        });
    }

};
// end of ure_admin_menu_access_url_args()


// Tools: Export roles to CSV
jQuery(document).ready(function() {   
        
    jQuery('#ure_export_roles_csv_button').button({
        label: ure_settings_data_pro.export_button
    }).click(function (event) {
        event.preventDefault();        
        jQuery('#ure_export_roles_csv_form').submit();
    });    
        
});