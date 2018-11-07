<?php
/**
 * Generate page for writing a push notification.
 * The result is written to the output buffer.
 */
function write_firebase_notification() {
	//send message if nonce is valid
	if ( wp_verify_nonce( $_POST['_wpnonce'], 'ig-fb-send-nonce' ) && current_user_can('publish_pages') ) {
		$languages = icl_get_languages();
		$items = array();
		foreach( $languages as $key => $value ) {
			$items[$key]  = array( 'title' => $_POST['pn-title_'.$key], 'message' => $_POST['pn-message_'.$key], 'lang' => $key, 'translate' => $_POST['pn-translate'], 'group' => $_POST['fbn_groups'] );
		}
		$fcm = new FirebaseNotificationsService();
		$fcm->translate_send_notifications( $items );
	}

	wp_enqueue_style( 'wp-ms-fcm-style', plugin_dir_url(__FILE__) . '../css/wp-ms-fcm.css' );
	wp_enqueue_script( 'wp-ms-fcm-js', plugin_dir_url(__FILE__) . '../js/send.js' );
	// display form
	require_once( __DIR__ . '/../templates/notification.php');
	echo write_firebase_notification_form();
}

/**
 * Generate list of sent messages for a language.
 * @param str $lang language of messages
 * @return str HTML code of messages list
 */
function fcm_sent_list_html( $lang ) {
	$fcmdb = New FirebaseNotificationsDatabase();
	$messages = $fcmdb->messages_by_language( $lang , $amount = 10 );
	$foo = "<table width='100%' style='border:1px solid #cccccc;'>";
	$foo .= "<tr><th>" . __('Status') . "</th><th>" . __("Date") . "</th><th>" . __("Title") . "</tr>";
	foreach( $messages as $message ){
		if ( $message['answer'] === null ) {
			$bullet_color = "#f00";
		} else {
			$bullet_color = "#0f0";
		}
		$foo .= "<tr><td style='color:$bullet_color;'>&#11044;</td><td>" . $message['timestamp'] . "</td><td>" . $message['request']['notification']['title'] . "</td></tr>";
	}
	$foo .= "</table>";
	return $foo;
}

?>