<?php
unistall();

function unistall(){
  delete_option('gcm_setting');
  global $wpdb;
  $table_name = $wpdb->prefix .'gcm_users';
  $wpdb->query("DROP TABLE IF EXISTS $table_name");
}
?>