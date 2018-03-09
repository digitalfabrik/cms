<?php
ob_start();
?>
	<h1><?php echo get_admin_page_title(); ?></h1>
	<div class='notice notice-success'><p><?php echo $network_settings; ?></p></div>
	<form method='post'>
		<?php echo wp_nonce_field( 'ig-fb-settings-nonce' ); ?>
		<table>
			<tr>
				<td>Authentication Key</td><td><input type='text' name='fbn_auth_key' value='<?php echo $settings['auth_key']; ?>' size='100'></td>
			</tr>
			<tr>
				<td>API URL</td><td><input type='text' name='fbn_api_url' value='<?php echo $settings['api_url']; ?>' size='100'></td>
			</tr>
			<tr>
				<td><?php echo __('Groups (separate with white space)', 'firebase-notifications'); ?></td>
				<td><input type='text' name='fbn_groups' value='".$settings['groups']."' size='100'></td>
			</tr>
			<tr>
				<td><?php echo __('Use network settings', 'firebase-notifications'); ?></td>
				<td>
					<fieldset>
						<input type='radio' id='yes' name='fbn_use_network_settings' value='1' <?php echo ($settings['force_network_settings'] == '2' ? " checked='checked' disabled='disabled'":"" ).($settings['force_network_settings'] == '0' ? " disabled='disabled'":"" ).($settings['force_network_settings'] == '1' && $settings['use_network_settings'] == '1' ? " checked='checked'":"" );?> ><label for='yes'> <?php echo __('Yes'); ?></label>
						<input type='radio' id='no' name='fbn_use_network_settings' value='0' <?php echo ($settings['force_network_settings'] == '2' ? " disabled='disabled'":"" ).($settings['force_network_settings'] == '0' ? " checked='checked' disabled='disabled'":"" ).($settings['force_network_settings'] == '1' && $settings['use_network_settings'] == '0' ? " checked='checked'":"" );?> ><label for='no'> <?php echo __('No'); ?></label>
					</fieldset>
				</td>
			</tr>
			<tr>
				<td>Title Prefix</td><td><input type='text' name='fbn_title_prefix' value='<?php echo $settings['fbn_title_prefix']; ?>' size='100'></td>
			</tr>
		</table>
		<button><?php echo __('Save'); ?></button>
	</form>
<?php
$html = ob_get_clean();
?>
