<?php

function px_gcm_register() {
	if (!isset($_GET["gcm_register_id"])) {
		return;
	}

	global $wpdb;
	$gcm_regid = $_GET["gcm_register_id"];
	$px_table_name = $wpdb->prefix . 'gcm_users';
	$query = "SELECT gcm_regid FROM $px_table_name WHERE gcm_regid='$gcm_regid'";
	$result = $wpdb->get_results($query);

	if ($result) {
		echo "You're already registered";
	} else {
		$query = "INSERT INTO $px_table_name (gcm_regid, created_at) VALUES ('$gcm_regid', 'NOW()')";
		if ($wpdb->query($query) === false) {
			throw new RuntimeException("Could not insert into GCM registration table $px_table_name: "
				. $wpdb->last_error);
		}
		echo "You are now registered";
	}
	exit;
}
