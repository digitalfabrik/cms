<?php
//This file will handle upgradeing from EM < 5.9 for timezones, removing the unecessary postmeta fields.
function em_admin_update_timezone_backcompat_site( $site_id = false ){
	global $wpdb;
	if( is_multisite() && $site_id != false ){ switch_to_blog($site_id); }
	$sql = $wpdb->prepare('DELETE FROM '.$wpdb->postmeta.' WHERE meta_key=%s OR meta_key=%s', array('_start_ts', '_end_ts'));
	$result = $wpdb->query($sql);
	if( $result !== false && !is_multisite() ){
		EM_Options::remove('updates', 'timezone-backcompat');
		EM_Admin_Notices::remove('date_time_migration');
	}
	if( is_multisite() && $site_id != false ){ restore_current_blog(); }
	return $result !== false;
}

function em_admin_update_timezone_backcompat(){
	if( !empty($_REQUEST['confirmed']) && check_admin_referer('em_timezone_backcompat_confirmed') && em_wp_is_super_admin() ){
		global $wpdb, $EM_Notices;
		if( em_admin_update_timezone_backcompat_site() ){
			$EM_Notices->add_confirm(__('You have successfully finalized upgrading your site.', 'events-manager'), true);
			$redirect = esc_url_raw( remove_query_arg(array('action','update','confirmed','_wpnonce')) );
			wp_redirect($redirect);
			exit();
		}else{
			$EM_Notices->add_error(__('There was an error upgrading your site, please try again or contact support.', 'events-manager'), true);
			$redirect = esc_url_raw( remove_query_arg(array('confirmed','_wpnonce')) );
			wp_redirect($redirect);
			exit();
		}
	}
}
add_action('em_admin_update_timezone-backcompat', 'em_admin_update_timezone_backcompat');

function em_admin_update_settings_timezone_backcompat(){
	if( is_multisite() ) return;
	?>
	<div>
		<h4 style="color:#ca4a1f;"><?php esc_html_e ( 'Finalize Timezones Upgrade', 'events-manager'); ?></h4>
		<p><?php esc_html_e('Events Manager 5.9 introduced timezone functionality, which does not require certain fields in your database. To maintain backwards compatibility with earlier versions, these fields will still be created.','events-manager'); ?></p>
		<p><?php esc_html_e('This is not a required step to enable any extra functionality, and therefore is not urgent. Only until you are happy with the upgrade and are confident you don\'t need to downgrade, finalize your upgrade by deleting and discontinuing these fields.','events-manager'); ?></p>
		<p><a href="<?php echo esc_url(EM_ADMIN_URL.'&page=events-manager-options&amp;action=update&update_action=timezone-backcompat'); ?>" class="button-secondary"><?php esc_html_e ( 'Finalize Timezones Upgrade', 'events-manager'); ?></a>
	</div>
	<?php
}
add_action('em_admin_update_settings_timezone-backcompat', 'em_admin_update_settings_timezone_backcompat');

function em_admin_update_settings_confirm_timezone_backcompat(){
	?>
	<div class="wrap">
		<h1><?php esc_html_e ( 'Finalize Timezones Upgrade', 'events-manager'); ?></h1>
		<p><?php esc_html_e('Events Manager 5.9 introduced timezone functionality, which does not require certain fields in your database. To maintain backwards compatibility with earlier versions, these fields will still be created.','events-manager'); ?></p>
		<p><?php esc_html_e('This is not a required step to enable any extra functionality, and therefore is not urgent. Only until you are happy with the upgrade and are confident you don\'t need to downgrade, finalize your upgrade by deleting and discontinuing these fields.','events-manager'); ?></p>
		<p style="font-weight:bold;"><?php esc_html_e('We recommend you back up your database! Once the upgrade is finalized, you cannot downgrade to an earlier version of the plugin. This cannot be undone.','events-manager')?></p>
		<p>
			<a href="<?php echo esc_url(add_query_arg(array('_wpnonce' => wp_create_nonce('em_timezone_backcompat_confirmed'), 'confirmed'=>1))); ?>" class="button-primary"><?php _e('Finalize Timezones Upgrade','events-manager'); ?></a>
			<a href="<?php echo esc_url(em_wp_get_referer()); ?>" class="button-secondary"><?php _e('Cancel','events-manager'); ?></a>
		</p>
	</div>		
	<?php
}
add_action('em_admin_update_settings_confirm_timezone-backcompat', 'em_admin_update_settings_confirm_timezone_backcompat');

function em_admin_update_ms_settings_timezone_backcompat(){
	?>
	<div>
		<br><hr>
		<h2 style="color:#ca4a1f;"><?php esc_html_e( 'Finalize Timezones Upgrade', 'events-manager'); ?></h2>
		<p><?php esc_html_e('Events Manager 5.9 introduced timezone functionality, which does not require certain fields in your database. To maintain backwards compatibility with earlier versions, these fields will still be created.','events-manager'); ?></p>
		<p><?php esc_html_e('This is not a required step to enable any extra functionality, and therefore is not urgent. Only until you are happy with the upgrade and are confident you don\'t need to downgrade, finalize your upgrade by deleting and discontinuing these fields.','events-manager'); ?></p>
		<p style="font-weight:bold;"><?php esc_html_e('We recommend you back up your database! Once the upgrade is finalized, you cannot downgrade to an earlier version of the plugin. This cannot be undone.','events-manager')?></p>
		<p>
			<a href="<?php echo esc_url(add_query_arg(array('action'=>'timezone-backcompat', '_wpnonce' => wp_create_nonce('em_ms_finalize_timezone_upgrade')))); ?>" class="button-primary">
				<?php esc_html_e ( 'Finalize Timezones Upgrade', 'events-manager'); ?>
			</a>
		</p>
	</div>
	<?php
}
add_action('em_admin_update_ms_settings_timezone-backcompat', 'em_admin_update_ms_settings_timezone_backcompat');

function em_admin_update_ms_timezone_backcompat(){
	if( check_admin_referer('em_ms_finalize_timezone_upgrade') && em_wp_is_super_admin() ){
		global $current_site,$wpdb;
		$blog_ids = $wpdb->get_col('SELECT blog_id FROM '.$wpdb->blogs.' WHERE site_id='.$current_site->id);
		$result = true;
		echo '<h2 style="color:#ca4a1f;">'. esc_html__( 'Finalize Timezones Upgrade', 'events-manager') .'</h2>';
		echo '<ul>';
		$plugin_basename = plugin_basename(EM_DIR.'/events-manager.php');
		$network_active = is_plugin_active_for_network($plugin_basename);
		foreach($blog_ids as $blog_id){
			if( $network_active || is_plugin_active($plugin_basename.'/events-manager.php') ){
				if( em_admin_update_timezone_backcompat_site($blog_id) ){
					echo "<li>".sprintf(_x('Updated %s.', 'Multisite Blog Update','events-manager'), get_blog_option($blog_id, 'blogname'))."</li>";
				}else{
					echo "<li>".sprintf(_x('Failed to update %s.', 'Multisite Blog Update','events-manager'), get_blog_option($blog_id, 'blogname'))."</li>";
					$result = false;
				}
			}else{
				echo "<li>".sprintf(_x('%s does not have Events Manager activated.', 'Multisite Blog Update','events-manager'), get_blog_option($blog_id, 'blogname'))."</li>";
			}
		}
		echo '</ul>';
		if( $result ){
			EM_Admin_Notices::remove('date_time_migration', true);
			EM_Options::site_remove('updates', 'timezone-backcompat');
			echo "<p>".esc_html__('Update process has finished.', 'events-manager')."</p>";
		}else{
			echo "<p>".esc_html__('An error has occurred, not all sites were upgraded successfully.', 'events-manager')."</p>";
		}
	}
}
add_action('em_admin_update_ms_timezone-backcompat', 'em_admin_update_ms_timezone_backcompat');