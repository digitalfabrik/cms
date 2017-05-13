<?php

function write_firebase_notification() {
	//send message if nonce is valid
	if ( wp_verify_nonce( $_POST['_wpnonce'], 'ig-fb-send-nonce' ) && current_user_can('publish_pages') ) {
		$languages = icl_get_languages();
		$items = array();
		foreach( $languages as $key => $value ) {
			$items[$key]  = array( 'title' => $_POST['pn-title_'.$key], 'message' => $_POST['pn-message_'.$key], 'lang' => $key, 'translate' => $_POST['pn-translate'], 'group' => $_POST['fbn_groups'] );
		}
		$myNotification = new FirebaseNotificationsService();
		$myNotification->translate_send_notifications( $items );
	}

	wp_enqueue_style( 'ig-fb-style-send', plugin_dir_url(__FILE__) . '/css/send.css' );
	wp_enqueue_script( 'ig-fb-js-send', plugin_dir_url(__FILE__) . '/js/send.js' );
	// display form
	echo write_firebase_notification_form();
}

function write_firebase_notification_form() {
	$header = "<h1>".get_admin_page_title()."</h1>
<form method='post'>
	".wp_nonce_field( 'ig-fb-send-nonce' )."
	<div class='notification-editor'>
		<div class='tabs'>
";

	if( get_site_option( 'fbn_force_network_settings' ) == '2' || ( get_site_option( 'fbn_force_network_settings' ) == '1' && get_blog_option( $blog_id, 'fbn_use_network_settings' ) == '1' ) ) {
		$groups = get_site_option( 'fbn_groups' );
	} else {
		$groups = get_blog_option( get_current_blog_id(), 'fbn_groups' );
	}

	$select = "<select name='fbn_groups'>";
	foreach( explode( ' ', $groups ) as $group ) {
		$select .= "<option>$group</option>";
	}
	$select .= "</select>";

	$footer = "
		</div>
	</div>
	<p>".__('Please fill in the title and message for the red underlined language. Optionally, translations can be filled in as well.', 'firebase-notifications')."</p>
	<p>".__('Languages without manual translation receive:', 'firebase-notifications')."</p>
	<fieldset>
		<ul>
			".( class_exists('TranslationService') ? "<li><input type='radio' id='at' name='pn-translate' value='at' checked='checked'><label for='at'> ".__('Automatic translation', 'firebase-notifications')."</label></li>":"")."
			<li><input type='radio' id='no' name='pn-translate' value='no' ".( !class_exists('TranslationService') ? " checked='checked'" : "")."><label for='no'> ".__('No message', 'firebase-notifications')."</label></li>
			<li><input type='radio' id='or' name='pn-translate' value='or'><label for='or'> ".__('Message in original language (marked red)', 'firebase-notifications')."</label></li>
		</ul>
	</fieldset>
	<p>".__('Groups', 'firebase-notifications').": $select</p>
	<button>".__('Send Notification', 'firebase-notifications')."</button>
</form>
";

	$tabs = "";
	$languages = icl_get_languages();
	foreach( $languages as $key => $value ) {
		$default = ($value['active'] == "1" ? "deflang" : $value['code'] );
		$tabs .= "
			<div id='".$default."'>
				<a href='#".$default."'>".$value['translated_name']."</a>
				<div>
					<table class='tabtable'>
						<tr><td>".__('Title', 'firebase-notifications')."</td><td><input name='pn-title_".$value['code']."' type='text' class='pn-title' maxlength='50'></td></tr>
						<tr><td>".__('Message', 'firebase-notifications')."</td><td><textarea name='pn-message_".$value['code']."' class='pn-message' maxlength='140'></textarea></td></tr>
					</table>
				</div>
			</div>
";
	}
	return $header.$tabs.$footer;
}

?>
