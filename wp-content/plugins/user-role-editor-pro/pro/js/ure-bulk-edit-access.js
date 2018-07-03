/**
 * Javascript support stuff for bulk helpers to setup posts/pages edit access restrictions
 * 
 */

jQuery(document).ready(function () {
    jQuery('<option>').val('edit_access').text(ure_bulk_edit_access_data.action_title).appendTo("select[name='action']");
    jQuery('<option>').val('edit_access').text(ure_bulk_edit_access_data.action_title).appendTo("select[name='action2']");
    var original_do_action = jQuery('#doaction, #doaction2').click(function (e) {
        var control = jQuery(this).attr("id").substr(2);
        if ("edit_access" === jQuery('select[name="' + control + '"]').val()) {
            e.preventDefault();
            ure_bulk_post_edit_access_helper_dialog();
        } else {
            original_do_action;
        }
    });

});


function ure_bulk_post_edit_access_helper_dialog() {
    jQuery('#ure_bulk_post_edit_access_dialog').dialog({
        dialogClass: 'wp-dialog',           
        modal: true,
        autoOpen: true, 
        closeOnEscape: true,      
        width: 500,
        height: 350,
        resizable: false,
        title: ure_bulk_edit_access_data.dialog_title,
        'buttons'       : {
            'Apply': function () {              
                ure_update_post_edit_access_data();
                              
            },
            Cancel: function() {
                jQuery(this).dialog('close');
                return false;
            }
          }
      });  
    jQuery('.ui-dialog-buttonpane button:contains("Apply")').attr("id", "dialog-apply-button");
    jQuery('#dialog-apply-button').html('<span class="ui-button-text">'+ ure_bulk_edit_access_data.apply +'</span>');

    var post_id_arr = Array();
    jQuery('input[name=post\\[\\]]:checked').each(function() {
        post_id_arr.push(this.value);
    });
    var post_id_str = '';
    if (post_id_arr.length>0) {
        post_id_str = post_id_arr.join();
        jQuery('#ure_posts').html(post_id_str);
    }
          
}    


function ure_update_post_edit_access_data() {
    var user_ids = jQuery('#ure_users').val();
    if (user_ids.length==0) {
        alert(ure_bulk_edit_access_data.provide_user_ids);
        return;
    }
    var what_todo = jQuery('input[name=ure_what_todo]:radio:checked').val();
    var posts_restriction_type = jQuery('input[name=ure_posts_restriction_type]:radio:checked').val();
    var post_ids = jQuery('#ure_posts').val();
    
    jQuery.ajax({
        url: ajaxurl,
        type: 'POST',
        dataType: 'html',
        data: {
            action: 'ure_ajax',
            sub_action: 'set_users_edit_restrictions',
            wp_nonce: ure_bulk_edit_access_data.wp_nonce,
            what_todo: what_todo,
            posts_restriction_type: posts_restriction_type,
            post_ids: post_ids,
            user_ids: user_ids
        },
        success: function(response) {
            var data = jQuery.parseJSON(response);
            if (typeof data.result !== 'undefined') {
                if (data.result === 'success') {                    
                    jQuery('#ure_bulk_post_edit_access_dialog').dialog('close');
                    jQuery('h2').after('<div id="message" class="updated below-h2">Editor restrictions updated.</div>');
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
