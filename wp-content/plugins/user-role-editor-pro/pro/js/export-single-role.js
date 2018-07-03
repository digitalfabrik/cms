
jQuery(function() {

    jQuery("#ure_export_roles_button").button({
        label: ure_data_export.export_roles
    }).click(function(event) {
        event.preventDefault();
        jQuery.ure_postGo( ure_data.page_url, 
                      { action: 'export-roles', 
                        current_role: ure_current_role,  
                        ure_nonce: ure_data.wp_nonce
                       });
    });

});
