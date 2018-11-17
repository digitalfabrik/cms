<?php
add_filter('ure_attachments_show_full_list', 'show_full_list_of_attachments', 10, 1);
function show_full_list_of_attachments($show_all) {
 
   return true;
}
?>
