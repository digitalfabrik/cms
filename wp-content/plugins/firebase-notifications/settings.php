<?php

function FirebaseNotificationSettings () {
	$blog_id = get_current_blog_id();
	$settings['auth_key'] = get_blog_option( $blog_id, 'fbn_auth_key' );
	$settings['api_url'] = get_blog_option( $blog_id, 'fbn_api_url' );
	$settings['use_network_settings'] = get_blog_option( $blog_id, 'fbn_use_network_settings' );
	$settings['force_network_settings'] = get_site_option('fbn_force_network_settings');
	if ( $settings['force_network_settings'] == '0' )
		$network_settings = __('This blog must manage it\'s own Firebase Cloud Messaging settings.', 'firebase-notifications');
	elseif ( $settings['force_network_settings'] == '1' )
		$network_settings = __('This blog is allowed to use the network wide Firebase Cloud Messaging settings.', 'firebase-notifications');
	elseif ( $settings['force_network_settings'] == '2' )
		$network_settings = __('This blog must use the network wide Firebase Cloud Messaging settings.', 'firebase-notifications');
	echo "<h1>".get_admin_page_title()."</h1>";
	echo "<div class='notice notice-success'><p>$network_settings</p></div>";
	echo "
	<form>
		<table>
			<tr>
				<td>Authentication Key</td><td><input type='text' name='fbn_auth_key' value='".$settings['auth_key']."'></td>
			</tr>
			<tr>
				<td>API URL</td><td><input type='text' name='fbn_auth_key' value='".$settings['api_url']."'></td>
			</tr>
			<tr>
				<td>".__('Use network settings', 'firebase-notifications')."</td>
				<td>
					<fieldset>
						<input type='radio' id='yes' name='pn-nw-settings' value='1'><label for='yes'> ".__('Yes')."</label>
						<input type='radio' id='no' name='pn-nw-settings' value='0'><label for='no'> ".__('No')."</label>
					</fieldset>
				</td>
			</tr>
		</table>
	</form>
";
	
}

function FirebaseNotificationNetworkSettings () {
	$settings['auth_key'] = get_site_option('fbn_auth_key');
	$settings['api_url'] = get_site_option('fbn_api_url');
	$settings['force_network_settings'] = get_site_option('fbn_force_network_settings');
	echo "<h1>".get_admin_page_title()."</h1>";
	echo "
	<form>
		<table>
			<tr>
				<td>Authentication Key</td><td><input type='text' name='fbn_auth_key' value='".$settings['auth_key']."'></td>
			</tr>
			<tr>
				<td>API URL</td><td><input type='text' name='fbn_auth_key' value='".$settings['api_url']."'></td>
			</tr>
			<tr>
				<td>".__('Network settings for blogs', 'firebase-notifications')."</td>
				<td>
					<fieldset>
						<ul>
							<li><input type='radio' id='own' name='pn-nw-settings' value='0'><label for='own'> ".__('Each blog must manage it\'s own Firebase Cloud Messaging settings.', 'firebase-notifications')."</label></li>
							<li><input type='radio' id='optional' name='pn-nw-settings' value='1'><label for='optional'> ".__('Each blog is allowed to use the network wide Firebase Cloud Messaging settings.', 'firebase-notifications')."</label></li>
							<li><input type='radio' id='force' name='pn-nw-settings' value='2'><label for='force'> ".__('Each blog must use the network wide Firebase Cloud Messaging settings.', 'firebase-notifications')."</label></li>
						</ul>
					</fieldset>
				</td>
			</tr>
		</table>
	</form>
";
}

?>
