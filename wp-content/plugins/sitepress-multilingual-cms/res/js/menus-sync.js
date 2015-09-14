var WPML_core = WPML_core || {};

jQuery(document).ready(function(){
   
   jQuery("#icl_msync_cancel").click(function(){
       location.href = location.href.replace(/#(.)$/, '');
   }); 
   
   jQuery('#icl_msync_confirm thead :checkbox').change(function(){
       var on = jQuery(this).attr('checked');
       if(on){
           jQuery('#icl_msync_confirm :checkbox').attr('checked', 'checked');           
           if(jQuery('#icl_msync_confirm tbody .check-column :checkbox').length){
                jQuery('#icl_msync_submit').removeAttr('disabled');
           }
       }else{
           jQuery('#icl_msync_confirm :checkbox').removeAttr('checked');
           if(!jQuery('input[name^="sync"]').length){
                jQuery('#icl_msync_submit').attr('disabled', 'disabled');
           }
       }
   })
   
   jQuery('#icl_msync_confirm tbody :checkbox').change(function(){
       
       if(jQuery(this).attr('readonly') == 'readonly'){
           if(jQuery(this).attr('checked')){
               jQuery(this).removeAttr('checked');
           }else{
               jQuery(this).attr('checked', 'checked');
           }
       };
       
       var checked = jQuery('#icl_msync_confirm tbody :checkbox:checked').length;

       if(checked){
           jQuery('#icl_msync_submit').removeAttr('disabled');                
       }else{
           jQuery('#icl_msync_submit').attr('disabled', 'disabled');
       }
       
       if(checked && jQuery('#icl_msync_confirm tbody :checkbox:checked').length == jQuery('#icl_msync_confirm tbody :checkbox').length){
           jQuery('#icl_msync_confirm thead :checkbox').attr('checked', 'checked');           
       }else{
           jQuery('#icl_msync_confirm thead :checkbox').removeAttr('checked');
       }
       
       WPML_core.icl_msync_validation();
       
   });
   
   jQuery('#icl_msync_submit').on( 'click', function() {
	  jQuery(this).attr('disabled', 'disabled');

      var total_menus = jQuery('input[name^=sync]:checked').length;
	  
	  var spinner = jQuery('<span class="spinner"></span>');
	  jQuery('#icl_msync_message').before(spinner);
	  spinner.css({display: 'inline-block', float: 'none', 'visibility': 'visible'});
	  
	  WPML_core.sync_menus(total_menus);
	  
   });
   
   var max_vars_warning = jQuery('#icl_msync_max_input_vars');
   if (max_vars_warning.length) {
      var menu_sync_check_box_count = jQuery('input[name^=sync]').length;
	  var max_vars_extra = 10; // Allow for a few other items as well. eg. nonce, etc
      if (menu_sync_check_box_count + max_vars_extra > max_vars_warning.data('max_input_vars')) {
		 var warning_text = max_vars_warning.html();
		 warning_text = warning_text.replace('!NUM!', menu_sync_check_box_count + max_vars_extra);
		 max_vars_warning.html(warning_text);
		 max_vars_warning.show();
	  }
   }
});

WPML_core.icl_msync_validation = function(){
    
    jQuery('#icl_msync_confirm tbody :checkbox').each(function(){
        var mnthis = jQuery(this);
        
        mnthis.removeAttr('readonly', 'readonly');
        
        if(jQuery(this).attr('name')=='menu_translation[]'){
            var spl = jQuery(this).val().split('#');
            var menu_id = spl[0];   
            
            jQuery('#icl_msync_confirm tbody :checkbox').each(function(){
                
                if(jQuery(this).val().search('newfrom-'+menu_id+'-') == 0 && jQuery(this).attr('checked')){
                    mnthis.attr('checked', 'checked');
                    mnthis.attr('readonly', 'readonly');
                }
            });
        }
    });
}

WPML_core.sync_menus = function (total_menus) {

   var data = 'action=icl_msync_confirm';
   data += '&_icl_nonce_menu_sync=' + jQuery('#_icl_nonce_menu_sync').val();
   
   var number_to_send = 50;

   var menus = jQuery('input[name^=sync]:checked:not(:disabled)');
   if (menus.length) {
	  
	  for ( var i = 0; i < Math.min( number_to_send, menus.length); i++ ) {
		 
	  	  data += '&' + jQuery(menus[i]).serialize();
		  
		  jQuery(menus[i]).attr('disabled', 'disabled');
	  }
   
	  var message = jQuery('#icl_msync_submit').data('message');
	  message = message.replace('%1', total_menus - menus.length);
	  message = message.replace('%2', total_menus);
	  
	  jQuery('#icl_msync_message').text(message);
	  
      jQuery.ajax({
		 url: ajaxurl,
		 type: "POST",
		 data: data,
		 success: function (response) {
			
			if (response == '1') {
			   WPML_core.sync_menus(total_menus);
			}
		 }
      });
   } else {
	  jQuery('#icl_msync_message').hide();
	  var message = jQuery('#icl_msync_submit').data('message-complete');
	  jQuery('#icl_msync_message').text(message);
	  jQuery('.spinner').remove();
	  jQuery('#icl_msync_message').fadeIn('slow');
   }
   
}
