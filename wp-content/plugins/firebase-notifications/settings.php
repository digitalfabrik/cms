<?php

function firebase_notification_settings () {
	if ( wp_verify_nonce( $_POST['_wpnonce'], 'ig-fb-settings-nonce' ) && current_user_can('manage_options') ) {
		$blog_id = get_current_blog_id();
		update_blog_option( $blog_id, 'fbn_auth_key', $_POST['fbn_auth_key'] );
		update_blog_option( $blog_id, 'fbn_api_url', $_POST['fbn_api_url'] );
		update_blog_option( $blog_id, 'fbn_use_network_settings', $_POST['fbn_use_network_settings'] );
		update_blog_option( $blog_id, 'fbn_groups', $_POST['fbn_groups'] );
		echo "<div class='notice notice-success'><p>".__('Settings saved.', 'firebase-notifications')."</p></div>";
	}
	echo firebase_notification_settings_form();
}

function firebase_notification_settings_form() {
	$blog_id = get_current_blog_id();
	$settings['auth_key'] = get_blog_option( $blog_id, 'fbn_auth_key' );
	$settings['api_url'] = get_blog_option( $blog_id, 'fbn_api_url' );
	$settings['use_network_settings'] = get_blog_option( $blog_id, 'fbn_use_network_settings' );
	$settings['force_network_settings'] = get_site_option( 'fbn_force_network_settings' );
	$settings['groups'] = get_blog_option( $blog_id, 'fbn_groups' );
	if ( $settings['force_network_settings'] == '0' )
		$network_settings = __('This blog must manage it\'s own Firebase Cloud Messaging settings.', 'firebase-notifications');
	elseif ( $settings['force_network_settings'] == '1' )
		$network_settings = __('This blog is allowed to use the network wide Firebase Cloud Messaging settings.', 'firebase-notifications');
	elseif ( $settings['force_network_settings'] == '2' )
		$network_settings = __('This blog must use the network wide Firebase Cloud Messaging settings.', 'firebase-notifications');
	$result = "
	<h1>".get_admin_page_title()."</h1>
	<div class='notice notice-success'><p>$network_settings</p></div>
	<form method='post'>
		".wp_nonce_field( 'ig-fb-settings-nonce' )."
		<table>
			<tr>
				<td>Authentication Key</td><td><input type='text' name='fbn_auth_key' value='".$settings['auth_key']."' size='100'></td>
			</tr>
			<tr>
				<td>API URL</td><td><input type='text' name='fbn_api_url' value='".$settings['api_url']."' size='100'></td>
			</tr>
			<tr>
				<td>".__('Groups (separate with white space)', 'firebase-notifications')."</td>
				<td><input type='text' name='fbn_groups' value='".$settings['groups']."' size='100'></td>
			</tr>
			<tr>
				<td>".__('Use network settings', 'firebase-notifications')."</td>
				<td>
					<fieldset>
						<input type='radio' id='yes' name='fbn_use_network_settings' value='1' ".($settings['force_network_settings'] == '2' ? " checked='checked' disabled='disabled'":"" ).($settings['force_network_settings'] == '0' ? " disabled='disabled'":"" ).($settings['force_network_settings'] == '1' && $settings['use_network_settings'] == '1' ? " checked='checked'":"" )."><label for='yes'> ".__('Yes')."</label>
						<input type='radio' id='no' name='fbn_use_network_settings' value='0' ".($settings['force_network_settings'] == '2' ? " disabled='disabled'":"" ).($settings['force_network_settings'] == '0' ? " checked='checked' disabled='disabled'":"" ).($settings['force_network_settings'] == '1' && $settings['use_network_settings'] == '0' ? " checked='checked'":"" )."><label for='no'> ".__('No')."</label>
					</fieldset>
				</td>
			</tr>
		</table>
		<button>".__('Save')."</button>
	</form>
";
	return $result;
}

function firebase_notification_network_settings () {
	if ( wp_verify_nonce( $_POST['_wpnonce'], 'ig-fb-networksettings-nonce' ) && current_user_can('manage_network_options') ) {
		update_site_option( 'fbn_auth_key', $_POST['fbn_auth_key'] );
		update_site_option( 'fbn_api_url', $_POST['fbn_api_url'] );
		update_site_option( 'fbn_force_network_settings', $_POST['fbn_force_network_settings'] );
		update_site_option( 'fbn_per_blog_topic', $_POST['fbn_per_blog_topic'] );
		update_site_option( 'fbn_groups', $_POST['fbn_groups'] );
	}
	echo firebase_notification_network_settings_form();
}

function firebase_notification_network_settings_form() {
	$settings['auth_key'] = get_site_option( 'fbn_auth_key' );
	$settings['api_url'] = get_site_option( 'fbn_api_url' );
	$settings['force_network_settings'] = get_site_option( 'fbn_force_network_settings' );
	$settings['per_blog_topic'] = get_site_option( 'fbn_per_blog_topic' );
	$settings['groups'] = get_site_option( 'fbn_groups' );
	$result = "
	<h1>".get_admin_page_title()."</h1>
	<form method='post'>
		".wp_nonce_field( 'ig-fb-networksettings-nonce' )."
		<table>
			<tr>
				<td>Authentication Key</td><td><input type='text' name='fbn_auth_key' value='".$settings['auth_key']."' size='100'></td>
			</tr>
			<tr>
				<td>API URL</td><td><input type='text' name='fbn_api_url' value='".$settings['api_url']."' size='100'></td>
			</tr>
			<tr>
				<td>".__('Groups (separate with white space)', 'firebase-notifications')."</td>
				<td><input type='text' name='fbn_groups' value='".$settings['groups']."' size='100'></td>
			</tr>
			<tr>
				<td>".__('Network settings for blogs', 'firebase-notifications')."</td>
				<td>
					<fieldset>
						<ul>
							<li><input type='radio' id='own' name='fbn_force_network_settings' value='0'".($settings['force_network_settings'] == '0'?" checked='checked'":"")."><label for='own'> ".__('Each blog must manage it\'s own Firebase Cloud Messaging settings.', 'firebase-notifications')."</label></li>
							<li><input type='radio' id='optional' name='fbn_force_network_settings' value='1'".($settings['force_network_settings'] == '1'?" checked='checked'":"")."><label for='optional'> ".__('Each blog is allowed to use the network wide Firebase Cloud Messaging settings.', 'firebase-notifications')."</label></li>
							<li><input type='radio' id='force' name='fbn_force_network_settings' value='2'".($settings['force_network_settings'] == '2'?" checked='checked'":"")."><label for='force'> ".__('Each blog must use the network wide Firebase Cloud Messaging settings.', 'firebase-notifications')."</label></li>
						</ul>
					</fieldset>
				</td>
			</tr>
			<tr>
				<td>".__('Add blog ID and WPML language to topic name.<br>The topic name is then<br>/topics/[blog_id]-[language_code]-[topic],<br>e.g. /topics/1-en-news', 'firebase-notifications')."</td>
				<td>
					<fieldset>
						<input type='radio' id='yes' name='fbn_per_blog_topic' value='1' ".($settings['per_blog_topic'] == '1' ? " checked='checked'":"" )."><label for='yes'> ".__('Yes')."</label>
						<input type='radio' id='no' name='fbn_per_blog_topic' value='0' ".($settings['per_blog_topic'] == '0' ? " checked='checked'":"" )."><label for='no'> ".__('No')."</label>
					</fieldset>
				</td>
			</tr>
		</table>
		<button>".__('Save')."</button>
	</form>
";
	return $result;
}

?>
