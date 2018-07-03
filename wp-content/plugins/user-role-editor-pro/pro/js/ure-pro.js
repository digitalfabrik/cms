/* 
 * User Role Editor WordPress plugin Pro
 * Author: Vladimir Garagulya
 * email: support@role-editor.com
 * 
 */

jQuery(function() {
    if (jQuery('#ure_update_all_network').length==0) {
        return;
    }
    jQuery('#ure_update_all_network').button({
        label: ure_data_pro.update_network
    }).click(function(event) {
        event.preventDefault();
        show_update_network_dialog();
                
    });
});


function show_update_network_dialog() {
    jQuery('#ure_network_update_dialog').dialog({                   
        dialogClass: 'wp-dialog',           
        modal: true,
        autoOpen: true, 
        closeOnEscape: true,      
        width: 400,
        height: 300,
        resizable: false,
        title: ure_data_pro.update_network,
        'buttons'       : {
            'Update': function (event) {
                event.preventDefault();
                
                var apply_to_all = document.createElement("input");
                apply_to_all.setAttribute("type", "hidden");
                apply_to_all.setAttribute("id", "ure_apply_to_all");
                apply_to_all.setAttribute("name", "ure_apply_to_all");
                apply_to_all.setAttribute("value", '1');
                document.getElementById("ure_form").appendChild(apply_to_all);
                
                var obj = null;
                for (var i = 0; i<ure_data_pro.replicators.length; i++) {
                    var rid = ure_data_pro.replicators[i];
                    if (jQuery('#' + rid + '0').length > 0) {
                        var checked = jQuery('#' + rid + '0').is(':checked');
                        if (checked) {
                            obj = document.createElement('input');
                            obj.setAttribute('type', 'hidden');
                            obj.setAttribute('id', rid);
                            obj.setAttribute('name', rid);
                            obj.setAttribute('value', 1);
                            document.getElementById('ure_form').appendChild(obj);
                        }
                    }
                }   // for(...)
                
                jQuery('#ure_form').submit();
                jQuery(this).dialog('close');
            },
            Cancel: function() {
                jQuery(this).dialog('close');
                return false;
            }
          }
      });
}

