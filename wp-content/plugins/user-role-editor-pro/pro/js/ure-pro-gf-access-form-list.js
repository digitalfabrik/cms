/* 
 * User Role Editor Pro
 * Hide from user Gravity Form to which he has no access
 * 
 */

jQuery(document).ready(function($){
    
  $('tbody.user-list tr.author-self').each(function() { //loop over each row
      if (this.cells[1].className=='column-id') {
          var index = 1;
      } else {
          var index = 2;
      }
      var form_id = this.cells[index].innerHTML;
      var found = false;
      for (i=0; i<ure_data_gf_access.allowed_forms_list.length; i++) {
          if (ure_data_gf_access.allowed_forms_list[i]==form_id) {
              found = true;
              break;
          }
      }
      if (!found) {
          $(this).hide();
      }
  });
  
});