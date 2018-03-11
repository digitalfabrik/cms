<?php
//ob_start();
?>
	<h1><?php echo get_admin_page_title(); ?></h1>
	<form method='post'>
		<?php echo wp_nonce_field( 'ig-fb-networksettings-nonce' ); ?>
		<table>
			<tr>
				<td>Authentication Key</td><td><input type='text' name='fbn_auth_key' value='<?php echo $settings['auth_key']; ?>' size='100'></td>
			</tr>
			<tr>
				<td>API URL</td><td><input type='text' name='fbn_api_url' value='<?php echo $settings['api_url']; ?>' size='100'></td>
			</tr>
			<tr>
				<td><?php echo __('Groups (separate with white space)', 'firebase-notifications'); ?></td>
				<td><input type='text' name='fbn_groups' value='<?php echo $settings['groups']; ?>' size='100'></td>
			</tr>
			<tr>
				<td><?php echo __('Network settings for blogs', 'firebase-notifications'); ?></td>
				<td>
					<fieldset>
						<ul>
							<li><input type='radio' id='own' name='fbn_force_network_settings' value='0'<?php echo ($settings['force_network_settings'] == '0'?" checked='checked'":""); ?>><label for='own'> <?php echo __('Each blog must manage it\'s own Firebase Cloud Messaging settings.', 'firebase-notifications'); ?></label></li>
							<li><input type='radio' id='optional' name='fbn_force_network_settings' value='1'<?php echo ($settings['force_network_settings'] == '1'?" checked='checked'":""); ?>><label for='optional'> <?php echo __('Each blog is allowed to use the network wide Firebase Cloud Messaging settings.', 'firebase-notifications'); ?></label></li>
							<li><input type='radio' id='force' name='fbn_force_network_settings' value='2'<?php echo ($settings['force_network_settings'] == '2'?" checked='checked'":""); ?>><label for='force'> <?php echo __('Each blog must use the network wide Firebase Cloud Messaging settings.', 'firebase-notifications'); ?></label></li>
						</ul>
					</fieldset>
				</td>
			</tr>
			<tr>
				<td><?php echo __('Add blog ID and WPML language to topic name.<br>The topic name is then<br>/topics/[blog_id]-[language_code]-[topic],<br>e.g. /topics/1-en-news', 'firebase-notifications'); ?></td>
				<td>
					<fieldset>
						<input type='radio' id='yes' name='fbn_per_blog_topic' value='1' <?php echo ($settings['per_blog_topic'] == '1' ? " checked='checked'":"" ); ?>><label for='yes'> <?php echo __('Yes'); ?></label>
						<input type='radio' id='no' name='fbn_per_blog_topic' value='0' <?php echo ($settings['per_blog_topic'] == '0' ? " checked='checked'":"" ); ?>><label for='no'> <?php echo __('No'); ?></label>
					</fieldset>
				</td>
			</tr>
		</table>
		<button><?php echo __('Save'); ?></button>
	</form>
<?php
//$html = ob_get_clean();
?>
