<?php

function px_gcm_register() {

  if (isset($_GET["regId"])) {

   global $wpdb;
   $gcm_regid = $_GET["regId"];
   $time = date("Y-m-d H:i:s");
   $px_table_name = $wpdb->prefix.'gcm_users';
   $sql = "SELECT gcm_regid FROM $px_table_name WHERE gcm_regid='$gcm_regid'";
   $result = $wpdb->get_results($sql);

   if (!$result) {
        $sql = "INSERT INTO $px_table_name (gcm_regid, created_at) VALUES ('$gcm_regid', '$time')";
        $q = $wpdb->query($sql);

        echo "Du bist jetzt registriert";

    } else {
      echo 'You\'re already registered';
    }
 }
}

?>