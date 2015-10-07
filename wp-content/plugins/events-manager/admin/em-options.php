<?php

//Function composing the options subpanel
function em_options_save(){
	global $EM_Notices;
	/*
	 * Here's the idea, we have an array of all options that need super admin approval if in multi-site mode
	 * since options are only updated here, its one place fit all
	 */
	if( current_user_can('list_users') && !empty($_POST['em-submitted']) && check_admin_referer('events-manager-options','_wpnonce') ){
		//Build the array of options here
		$post = $_POST;
		foreach ($_POST as $postKey => $postValue){
			if( substr($postKey, 0, 5) == 'dbem_' ){
				//TODO some more validation/reporting
				$numeric_options = array('dbem_locations_default_limit','dbem_events_default_limit');
				if( in_array($postKey, array('dbem_bookings_notify_admin','dbem_event_submitted_email_admin','dbem_js_limit_events_form','dbem_js_limit_search','dbem_js_limit_general','dbem_css_limit_include','dbem_css_limit_exclude','dbem_search_form_geo_distance_options')) ){ $postValue = str_replace(' ', '', $postValue); } //clean up comma seperated emails, no spaces needed
				if( in_array($postKey,$numeric_options) && !is_numeric($postValue) ){
					//Do nothing, keep old setting.
				}elseif( $postKey == 'dbem_category_default_color' && !preg_match("/^#([abcdef0-9]{3}){1,2}?$/i",$postValue)){
					$EM_Notices->add_error( sprintf(esc_html_x('Colors must be in a valid %s format, such as #FF00EE.', 'hex format', 'dbem'), '<a href="http://en.wikipedia.org/wiki/Web_colors">hex</a>').' '. esc_html__('This setting was not changed.', 'dbem'), true);					
				}else{
					//TODO slashes being added?
					if( is_array($postValue) ){
					    foreach($postValue as $postValue_key=>$postValue_val) $postValue[$postValue_key] = stripslashes($postValue_val);
					}else{
					    $postValue = stripslashes($postValue);
					}
					update_option($postKey, $postValue);
				}
			}
		}
		//set capabilities
		if( !empty($_POST['em_capabilities']) && is_array($_POST['em_capabilities']) && (!is_multisite() || is_multisite() && is_super_admin()) ){
			global $em_capabilities_array, $wp_roles;
			if( is_multisite() && is_network_admin() && $_POST['dbem_ms_global_caps'] == 1 ){
			    //apply_caps_to_blog
				global $current_site,$wpdb;
				$blog_ids = $wpdb->get_col('SELECT blog_id FROM '.$wpdb->blogs.' WHERE site_id='.$current_site->id);
				foreach($blog_ids as $blog_id){
					switch_to_blog($blog_id);
				    //normal blog role application
					foreach( $wp_roles->role_objects as $role_name => $role ){
						foreach( array_keys($em_capabilities_array) as $capability){
							if( !empty($_POST['em_capabilities'][$role_name][$capability]) ){
								$role->add_cap($capability);
							}else{
								$role->remove_cap($capability);
							}
						}
					}
					restore_current_blog();
				}
			}elseif( !is_network_admin() ){
			    //normal blog role application
				foreach( $wp_roles->role_objects as $role_name => $role ){
					foreach( array_keys($em_capabilities_array) as $capability){
						if( !empty($_POST['em_capabilities'][$role_name][$capability]) ){
							$role->add_cap($capability);
						}else{
							$role->remove_cap($capability);
						}
					}
				}
			}
		}
		update_option('dbem_flush_needed',1);
		do_action('em_options_save');
		$EM_Notices->add_confirm('<strong>'.__('Changes saved.', 'dbem').'</strong>', true);
		wp_redirect(wp_get_referer());
		exit();
	}
	//Migration
	if( !empty($_GET['em_migrate_images']) && check_admin_referer('em_migrate_images','_wpnonce') && get_option('dbem_migrate_images') ){
		include(plugin_dir_path(__FILE__).'../em-install.php');
		$result = em_migrate_uploads();
		if($result){
			$failed = ( $result['fail'] > 0 ) ? $result['fail'] . ' images failed to migrate.' : '';
			$EM_Notices->add_confirm('<strong>'.$result['success'].' images migrated successfully. '.$failed.'</strong>');
		}
		wp_redirect(admin_url().'edit.php?post_type=event&page=events-manager-options&em_migrate_images');
	}elseif( !empty($_GET['em_not_migrate_images']) && check_admin_referer('em_not_migrate_images','_wpnonce') ){
		delete_option('dbem_migrate_images_nag');
		delete_option('dbem_migrate_images');
	}
	//Uninstall
	if( !empty($_REQUEST['action']) && $_REQUEST['action'] == 'uninstall' && current_user_can('activate_plugins') && !empty($_REQUEST['confirmed']) && check_admin_referer('em_uninstall_'.get_current_user_id().'_wpnonce') && is_super_admin() ){
		if( check_admin_referer('em_uninstall_'.get_current_user_id().'_confirmed','_wpnonce2') ){
			//We have a go to uninstall
			global $wpdb;
			//delete EM posts
			remove_action('before_delete_post',array('EM_Location_Post_Admin','before_delete_post'),10,1);
			remove_action('before_delete_post',array('EM_Event_Post_Admin','before_delete_post'),10,1);
			remove_action('before_delete_post',array('EM_Event_Recurring_Post_Admin','before_delete_post'),10,1);
			$post_ids = $wpdb->get_col('SELECT ID FROM '.$wpdb->posts." WHERE post_type IN ('".EM_POST_TYPE_EVENT."','".EM_POST_TYPE_LOCATION."','event-recurring')");
			foreach($post_ids as $post_id){
				wp_delete_post($post_id);
			}
			//delete categories
			$cat_terms = get_terms(EM_TAXONOMY_CATEGORY, array('hide_empty'=>false));
			foreach($cat_terms as $cat_term){
				wp_delete_term($cat_term->term_id, EM_TAXONOMY_CATEGORY);
			}
			$tag_terms = get_terms(EM_TAXONOMY_TAG, array('hide_empty'=>false));
			foreach($tag_terms as $tag_term){
				wp_delete_term($tag_term->term_id, EM_TAXONOMY_TAG);
			}
			//delete EM tables
			$wpdb->query('DROP TABLE '.EM_EVENTS_TABLE);
			$wpdb->query('DROP TABLE '.EM_BOOKINGS_TABLE);
			$wpdb->query('DROP TABLE '.EM_LOCATIONS_TABLE);
			$wpdb->query('DROP TABLE '.EM_TICKETS_TABLE);
			$wpdb->query('DROP TABLE '.EM_TICKETS_BOOKINGS_TABLE);
			$wpdb->query('DROP TABLE '.EM_RECURRENCE_TABLE);
			$wpdb->query('DROP TABLE '.EM_CATEGORIES_TABLE);
			$wpdb->query('DROP TABLE '.EM_META_TABLE);
			
			//delete options
			$wpdb->query('DELETE FROM '.$wpdb->options.' WHERE option_name LIKE \'em_%\' OR option_name LIKE \'dbem_%\'');
			//deactivate and go!
			deactivate_plugins(array('events-manager/events-manager.php','events-manager-pro/events-manager-pro.php'), true);
			wp_redirect(admin_url('plugins.php?deactivate=true'));
			exit();
		}
	}
	//Reset
	if( !empty($_REQUEST['action']) && $_REQUEST['action'] == 'reset' && !empty($_REQUEST['confirmed']) && check_admin_referer('em_reset_'.get_current_user_id().'_wpnonce') && is_super_admin() ){
		if( check_admin_referer('em_reset_'.get_current_user_id().'_confirmed','_wpnonce2') ){
			//We have a go to uninstall
			global $wpdb;
			//delete options
			$wpdb->query('DELETE FROM '.$wpdb->options.' WHERE option_name LIKE \'em_%\' OR option_name LIKE \'dbem_%\'');
			//reset capabilities
			global $em_capabilities_array, $wp_roles;
			foreach( $wp_roles->role_objects as $role_name => $role ){
				foreach( array_keys($em_capabilities_array) as $capability){
					$role->remove_cap($capability);
				}
			}
			//go back to plugin options page
			$EM_Notices->add_confirm(__('Settings have been reset back to default. Your events, locations and categories have not been modified.','dbem'), true);
			wp_redirect(EM_ADMIN_URL.'&page=events-manager-options');
			exit();
		}
	}
	//Force Update Recheck - Workaround for now
	if( !empty($_REQUEST['action']) && $_REQUEST['action'] == 'recheck_updates' && check_admin_referer('em_recheck_updates_'.get_current_user_id().'_wpnonce') && is_super_admin() ){
		//force recheck of plugin updates, to refresh dl links
		delete_transient('update_plugins');
		delete_site_transient('update_plugins');
		$EM_Notices->add_confirm(__('If there are any new updates, you should now see them in your Plugins or Updates admin pages.','dbem'), true);
		wp_redirect(wp_get_referer());
		exit();
	}
	//Flag version checking to look at trunk, not tag
	if( !empty($_REQUEST['action']) && $_REQUEST['action'] == 'check_devs' && check_admin_referer('em_check_devs_wpnonce') && is_super_admin() ){
		//delete transients, and add a flag to recheck dev version next time round
		delete_transient('update_plugins');
		delete_site_transient('update_plugins');
		update_option('em_check_dev_version', true);
		$EM_Notices->add_confirm(__('Checking for dev versions.','dbem').' '. __('If there are any new updates, you should now see them in your Plugins or Updates admin pages.','dbem'), true);
		wp_redirect(wp_get_referer());
		exit();
	}
	
}
add_action('admin_init', 'em_options_save');

function em_admin_email_test_ajax(){
    if( wp_verify_nonce($_REQUEST['_check_email_nonce'],'check_email') && current_user_can('activate_plugins') ){
        $subject = __("Events Manager Test Email",'dbem');
        $content = __('Congratulations! Your email settings work.','dbem');
        $current_user = get_user_by('id', get_current_user_id());
        //add filters for options used in EM_Mailer so the current supplied ones are used
        ob_start();
        function pre_option_dbem_mail_sender_name(){ return sanitize_email($_REQUEST['dbem_mail_sender_name']); }
        add_filter('pre_option_dbem_mail_sender_name', 'pre_option_dbem_mail_sender_name');
        function pre_option_dbem_mail_sender_address(){ return sanitize_text_field($_REQUEST['dbem_mail_sender_address']); }
        add_filter('pre_option_dbem_mail_sender_address', 'pre_option_dbem_mail_sender_address');
        function pre_option_dbem_rsvp_mail_send_method(){ return sanitize_text_field($_REQUEST['dbem_rsvp_mail_send_method']); }
        add_filter('pre_option_dbem_rsvp_mail_send_method', 'pre_option_dbem_rsvp_mail_send_method');
        function pre_option_dbem_rsvp_mail_port(){ return sanitize_text_field($_REQUEST['dbem_rsvp_mail_port']); }
        add_filter('pre_option_dbem_rsvp_mail_port', 'pre_option_dbem_rsvp_mail_port');
        function pre_option_dbem_rsvp_mail_SMTPAuth(){ return sanitize_text_field($_REQUEST['dbem_rsvp_mail_SMTPAuth']); }
        add_filter('pre_option_dbem_rsvp_mail_SMTPAuth', 'pre_option_dbem_rsvp_mail_SMTPAuth');
        function pre_option_dbem_smtp_host(){ return sanitize_text_field($_REQUEST['dbem_smtp_host']); }
        add_filter('pre_option_dbem_smtp_host', 'pre_option_dbem_smtp_host');
        function pre_option_dbem_smtp_username(){ return sanitize_text_field($_REQUEST['dbem_smtp_username']); }
        add_filter('pre_option_dbem_smtp_username', 'pre_option_dbem_smtp_username');
        function pre_option_dbem_smtp_password(){ return sanitize_text_field($_REQUEST['dbem_smtp_password']); }
        add_filter('pre_option_dbem_smtp_password', 'pre_option_dbem_smtp_password');        
        ob_clean(); //remove any php errors/warnings output
        $EM_Event = new EM_Event();
        if( $EM_Event->email_send($subject,$content,$current_user->user_email) ){
        	$result = array(
        		'result' => true,
        		'message' => sprintf(__('Email sent succesfully to %s','dbem'),$current_user->user_email)
        	);
        }else{
            $result = array(
            	'result' => false,
            	'message' => __('Email not sent.','dbem')." <ul><li>".implode('</li><li>',$EM_Event->get_errors()).'</li></ul>'
            );
        }
        echo EM_Object::json_encode($result);
    }
    exit();
}
add_action('wp_ajax_em_admin_test_email','em_admin_email_test_ajax');

function em_admin_options_reset_page(){
	if( check_admin_referer('em_reset_'.get_current_user_id().'_wpnonce') && is_super_admin() ){
		?>
		<div class="wrap">		
			<div id='icon-options-general' class='icon32'><br /></div>
			<h2><?php _e('Reset Events Manager','dbem'); ?></h2>
			<p style="color:red; font-weight:bold;"><?php _e('Are you sure you want to reset Events Manager?','dbem')?></p>
			<p style="font-weight:bold;"><?php _e('All your settings, including email templates and template formats for Events Manager will be deleted.','dbem')?></p>
			<p>
				<a href="<?php echo esc_url(add_query_arg(array('_wpnonce2' => wp_create_nonce('em_reset_'.get_current_user_id().'_confirmed'), 'confirmed'=>1))); ?>" class="button-primary"><?php _e('Reset Events Manager','dbem'); ?></a>
				<a href="<?php echo wp_get_referer(); ?>" class="button-secondary"><?php _e('Cancel','dbem'); ?></a>
			</p>
		</div>		
		<?php
	}
}
function em_admin_options_uninstall_page(){
	if( check_admin_referer('em_uninstall_'.get_current_user_id().'_wpnonce') && is_super_admin() ){
		?>
		<div class="wrap">		
			<div id='icon-options-general' class='icon32'><br /></div>
			<h2><?php _e('Uninstall Events Manager','dbem'); ?></h2>
			<p style="color:red; font-weight:bold;"><?php _e('Are you sure you want to uninstall Events Manager?','dbem')?></p>
			<p style="font-weight:bold;"><?php _e('All your settings and events will be permanently deleted. This cannot be undone.','dbem')?></p>
			<p><?php echo sprintf(__('If you just want to deactivate the plugin, <a href="%s">go to your plugins page</a>.','dbem'), wp_nonce_url(admin_url('plugins.php'))); ?></p>
			<p>
				<a href="<?php echo esc_url(add_query_arg(array('_wpnonce2' => wp_create_nonce('em_uninstall_'.get_current_user_id().'_confirmed'), 'confirmed'=>1))); ?>" class="button-primary"><?php _e('Uninstall and Deactivate','dbem'); ?></a>
				<a href="<?php echo wp_get_referer(); ?>" class="button-secondary"><?php _e('Cancel','dbem'); ?></a>
			</p>
		</div>		
		<?php
	}
}

function em_admin_options_page() {
	global $wpdb, $EM_Notices;
	//Check for uninstall/reset request
	if( !empty($_REQUEST['action']) && $_REQUEST['action'] == 'uninstall' ){
		em_admin_options_uninstall_page();
		return;
	}	
	if( !empty($_REQUEST['action']) && $_REQUEST['action'] == 'reset' ){
		em_admin_options_reset_page();
		return;
	}
	//substitute dropdowns with input boxes for some situations to improve speed, e.g. if there 1000s of locations or users
	$total_users = $wpdb->get_var("SELECT COUNT(ID) FROM {$wpdb->users};");
	if( $total_users > 100 && !defined('EM_OPTIMIZE_SETTINGS_PAGE_USERS') ){ define('EM_OPTIMIZE_SETTINGS_PAGE_USERS',true); }
	$total_locations = EM_Locations::count();
	if( $total_locations > 100 && !defined('EM_OPTIMIZE_SETTINGS_PAGE_LOCATIONS') ){ define('EM_OPTIMIZE_SETTINGS_PAGE_LOCATIONS',true); }
	//TODO place all options into an array
	global $events_placeholder_tip, $locations_placeholder_tip, $categories_placeholder_tip, $bookings_placeholder_tip;
	$events_placeholders = '<a href="'.EM_ADMIN_URL .'&amp;page=events-manager-help#event-placeholders">'. __('Event Related Placeholders','dbem') .'</a>';
	$locations_placeholders = '<a href="'.EM_ADMIN_URL .'&amp;page=events-manager-help#location-placeholders">'. __('Location Related Placeholders','dbem') .'</a>';
	$bookings_placeholders = '<a href="'.EM_ADMIN_URL .'&amp;page=events-manager-help#booking-placeholders">'. __('Booking Related Placeholders','dbem') .'</a>';
	$categories_placeholders = '<a href="'.EM_ADMIN_URL .'&amp;page=events-manager-help#category-placeholders">'. __('Category Related Placeholders','dbem') .'</a>';
	$events_placeholder_tip = " ". sprintf(__('This accepts %s and %s placeholders.','dbem'),$events_placeholders, $locations_placeholders);
	$locations_placeholder_tip = " ". sprintf(__('This accepts %s placeholders.','dbem'), $locations_placeholders);
	$categories_placeholder_tip = " ". sprintf(__('This accepts %s placeholders.','dbem'), $categories_placeholders);
	$bookings_placeholder_tip = " ". sprintf(__('This accepts %s, %s and %s placeholders.','dbem'), $bookings_placeholders, $events_placeholders, $locations_placeholders);
	
	global $save_button;
	$save_button = '<tr><th>&nbsp;</th><td><p class="submit" style="margin:0px; padding:0px; text-align:right;"><input type="submit" class="button-primary" id="dbem_options_submit" name="Submit" value="'. __( 'Save Changes', 'dbem') .' ('. __('All','dbem') .')" /></p></ts></td></tr>';
	
	if( defined('EM_SETTINGS_TABS') && EM_SETTINGS_TABS ){
	    $tabs_enabled = true;
	    $general_tab_link = esc_url(add_query_arg( array('em_tab'=>'general')));
	    $pages_tab_link = esc_url(add_query_arg( array('em_tab'=>'pages')));
	    $formats_tab_link = esc_url(add_query_arg( array('em_tab'=>'formats')));
	    $bookings_tab_link = esc_url(add_query_arg( array('em_tab'=>'bookings')));
	    $emails_tab_link = esc_url(add_query_arg( array('em_tab'=>'emails')));
	}else{
	    $general_tab_link = $pages_tab_link = $formats_tab_link = $bookings_tab_link = $emails_tab_link = '';
	}
	?>
	<script type="text/javascript" charset="utf-8"><?php include(EM_DIR.'/includes/js/admin-settings.js'); ?></script>
	<style type="text/css">.postbox h3 { cursor:pointer; }</style>
	<div class="wrap <?php if(empty($tabs_enabled)) echo 'tabs-active' ?>">		
		<div id='icon-options-general' class='icon32'><br /></div>
		<h2 class="nav-tab-wrapper">
			<a href="<?php echo $general_tab_link; ?>#general" id="em-menu-general" class="nav-tab nav-tab-active"><?php _e('General','dbem'); ?></a>
			<a href="<?php echo $pages_tab_link; ?>#pages" id="em-menu-pages" class="nav-tab"><?php _e('Pages','dbem'); ?></a>
			<a href="<?php echo $formats_tab_link; ?>#formats" id="em-menu-formats" class="nav-tab"><?php _e('Formatting','dbem'); ?></a>
			<?php if( get_option('dbem_rsvp_enabled') ): ?>
			<a href="<?php echo $bookings_tab_link; ?>#bookings" id="em-menu-bookings" class="nav-tab"><?php _e('Bookings','dbem'); ?></a>
			<?php endif; ?>
			<a href="<?php echo $emails_tab_link; ?>#emails" id="em-menu-emails" class="nav-tab"><?php _e('Emails','dbem'); ?></a>
		</h2>
		<h3 id="em-options-title"><?php _e ( 'Event Manager Options', 'dbem' ); ?></h3>
		<form id="em-options-form" method="post" action="">
			<div class="metabox-holder">         
			<!-- // TODO Move style in css -->
			<div class='postbox-container' style='width: 99.5%'>
			<div id="">
			
			<?php
			if( !empty($tabs_enabled) ){
			    if( empty($_REQUEST['em_tab']) || $_REQUEST['em_tab'] == 'general' ){ 
			        include('settings/tabs/general.php');
			    }else{
        			if( $_REQUEST['em_tab'] == 'pages' ) include('settings/tabs/pages.php');
        			if( $_REQUEST['em_tab'] == 'formats' ) include('settings/tabs/formats.php');
        			if( get_option('dbem_rsvp_enabled') && $_REQUEST['em_tab'] == 'bookings'  ){
        			    include('settings/tabs/bookings.php');
        			}
        			if( $_REQUEST['em_tab'] == 'emails' ) include('settings/tabs/emails.php');
			    }
			}else{
    			include('settings/tabs/general.php');
    			include('settings/tabs/pages.php');
    			include('settings/tabs/formats.php');
    			if( get_option('dbem_rsvp_enabled') ){
    			    include('settings/tabs/bookings.php');
    			}
    			include('settings/tabs/emails.php');
			}
			?>
			
			<?php /*
			<div  class="postbox " >
			<div class="handlediv" title="<?php __('Click to toggle', 'dbem'); ?>"><br /></div><h3><span><?php _e ( 'Debug Modes', 'dbem' ); ?> </span></h3>
			<div class="inside">
				<table class='form-table'>
					<?php
					em_options_radio_binary ( __( 'EM Debug Mode?', 'dbem' ), 'dbem_debug', __( 'Setting this to yes will display different content to admins for event pages and emails so you can see all the available placeholders and their values.', 'dbem' ) );
					em_options_radio_binary ( __( 'WP Debug Mode?', 'dbem' ), 'dbem_wp_debug', __( 'This will turn WP_DEBUG mode on. Useful if you want to troubleshoot php errors without looking at your logs.', 'dbem' ) );
					?>
				</table>
			</div> <!-- . inside -->
			</div> <!-- .postbox -->
			*/ ?>

			<p class="submit">
				<input type="submit" id="dbem_options_submit" class="button-primary" name="Submit" value="<?php esc_attr_e( 'Save Changes', 'dbem' ); ?>" />
				<input type="hidden" name="em-submitted" value="1" />
				<input type="hidden" name="_wpnonce" value="<?php echo wp_create_nonce('events-manager-options'); ?>" />
			</p>  
			
			</div> <!-- .metabox-sortables -->
			</div> <!-- .postbox-container -->
			
			</div> <!-- .metabox-holder -->	
		</form>
	</div>
	<?php
}

/**
 * Meta options box for image sizes. Shared in both MS and Normal options page, hence it's own function 
 */
function em_admin_option_box_image_sizes(){
	global $save_button;
	?>
	<div  class="postbox " id="em-opt-image-sizes" >
	<div class="handlediv" title="<?php __('Click to toggle', 'dbem'); ?>"><br /></div><h3><span><?php _e ( 'Image Sizes', 'dbem' ); ?> </span></h3>
	<div class="inside">
	    <p class="em-boxheader"><?php _e('These settings will only apply to the image uploading if using our front-end forms. In your WP admin area, images are handled by WordPress.','dbem'); ?></p>
		<table class='form-table'>
			<?php
			em_options_input_text ( __( 'Maximum width (px)', 'dbem' ), 'dbem_image_max_width', __( 'The maximum allowed width for images uploads', 'dbem' ) );
			em_options_input_text ( __( 'Minimum width (px)', 'dbem' ), 'dbem_image_min_width', __( 'The minimum allowed width for images uploads', 'dbem' ) );
			em_options_input_text ( __( 'Maximum height (px)', 'dbem' ), 'dbem_image_max_height', __( "The maximum allowed height for images uploaded, in pixels", 'dbem' ) );
			em_options_input_text ( __( 'Minimum height (px)', 'dbem' ), 'dbem_image_min_height', __( "The minimum allowed height for images uploaded, in pixels", 'dbem' ) );
			em_options_input_text ( __( 'Maximum size (bytes)', 'dbem' ), 'dbem_image_max_size', __( "The maximum allowed size for images uploaded, in bytes", 'dbem' ) );
			echo $save_button;
			?>
		</table>
	</div> <!-- . inside -->
	</div> <!-- .postbox -->
	<?php	
}

/**
 * Meta options box for email settings. Shared in both MS and Normal options page, hence it's own function 
 */
function em_admin_option_box_email(){
	global $save_button;
	$current_user = get_user_by('id', get_current_user_id());
	?>
	<div  class="postbox "  id="em-opt-email-settings">
	<div class="handlediv" title="<?php __('Click to toggle', 'dbem'); ?>"><br /></div><h3><span><?php _e ( 'Email Settings', 'dbem' ); ?></span></h3>
	<div class="inside em-email-form">
		<p class="em-email-settings-check em-boxheader">
			<em><?php _e('Before you save your changes, you can quickly send yourself a test email by clicking this button.','dbem'); ?>
			<?php echo sprintf(__('A test email will be sent to your account email - %s','dbem'), $current_user->user_email . ' <a href="'.admin_url( 'profile.php' ).'">'.__('edit','dbem').'</a>'); ?></em><br />
			<input type="button" id="em-admin-check-email" class="secondary-button" value="<?php esc_attr_e('Test Email Settings','dbem'); ?>" />
			<input type="hidden" name="_check_email_nonce" value="<?php echo wp_create_nonce('check_email'); ?>" />
			<span id="em-email-settings-check-status"></span>
		</p>
		<table class="form-table">
			<?php
			em_options_input_text ( __( 'Notification sender name', 'dbem' ), 'dbem_mail_sender_name', __( "Insert the display name of the notification sender.", 'dbem' ) );
			em_options_input_text ( __( 'Notification sender address', 'dbem' ), 'dbem_mail_sender_address', __( "Insert the address of the notification sender.", 'dbem' ) );
			em_options_select ( __( 'Mail sending method', 'dbem' ), 'dbem_rsvp_mail_send_method', array ('smtp' => 'SMTP', 'mail' => __( 'PHP mail function', 'dbem' ), 'sendmail' => 'Sendmail', 'qmail' => 'Qmail', 'wp_mail' => 'WP Mail' ), __( 'Select the method to send email notification.', 'dbem' ) );
			em_options_radio_binary ( __( 'Send HTML Emails?', 'dbem' ), 'dbem_smtp_html', __( 'If set to yes, your emails will be sent in HTML format, otherwise plaintext.', 'dbem' ).' '.__( 'Depending on server settings, some sending methods may ignore this settings.', 'dbem' ) );
			em_options_radio_binary ( __( 'Add br tags to HTML emails?', 'dbem' ), 'dbem_smtp_html_br', __( 'If HTML emails are enabled, br tags will automatically be added for new lines.', 'dbem' ) );
			?>
			<tbody class="em-email-settings-smtp">
				<?php
				em_options_input_text ( 'Mail sending port', 'dbem_rsvp_mail_port', __( "The port through which you e-mail notifications will be sent. Make sure the firewall doesn't block this port", 'dbem' ) );
				em_options_radio_binary ( __( 'Use SMTP authentication?', 'dbem' ), 'dbem_rsvp_mail_SMTPAuth', __( 'SMTP authentication is often needed. If you use GMail, make sure to set this parameter to Yes', 'dbem' ) );
				em_options_input_text ( 'SMTP host', 'dbem_smtp_host', __( "The SMTP host. Usually it corresponds to 'localhost'. If you use GMail, set this value to 'ssl://smtp.gmail.com:465'.", 'dbem' ) );
				em_options_input_text ( __( 'SMTP username', 'dbem' ), 'dbem_smtp_username', __( "Insert the username to be used to access your SMTP server.", 'dbem' ) );
				em_options_input_password ( __( 'SMTP password', 'dbem' ), "dbem_smtp_password", __( "Insert the password to be used to access your SMTP server", 'dbem' ) );
				?>
			</tbody>
			<?php
			echo $save_button;
			?>
		</table>
		<script type="text/javascript" charset="utf-8">
			jQuery(document).ready(function($){
				$('#dbem_rsvp_mail_send_method_row select').change(function(){
					el = $(this);
					if( el.find(':selected').val() == 'smtp' ){
						$('.em-email-settings-smtp').show();
					}else{
						$('.em-email-settings-smtp').hide();
					}
				}).trigger('change');
				$('input#em-admin-check-email').click(function(e,el){
					var email_data = $('.em-email-form input, .em-email-form select').serialize();
					$.ajax({
						url: EM.ajaxurl,
						dataType: 'json',
						data: email_data+"&action=em_admin_test_email",
						success: function(data){
							if(data.result && data.message){
								$('#em-email-settings-check-status').css({'color':'green','display':'block'}).html(data.message);
							}else{
								var msg = (data.message) ? data.message:'Email not sent';
								$('#em-email-settings-check-status').css({'color':'red','display':'block'}).html(msg);
							}
						},
						error: function(){ $('#em-email-settings-check-status').css({'color':'red','display':'block'}).html('Server Error'); },
						beforeSend: function(){ $('input#em-admin-check-email').val('<?php _e('Checking...','dbem') ?>'); },
						complete: function(){ $('input#em-admin-check-email').val('<?php _e('Test Email Settings','dbem'); ?>');  }
					});
				});
			});
		</script>
	</div> <!-- . inside -->
	</div> <!-- .postbox --> 
	<?php
}

/**
 * Meta options box for user capabilities. Shared in both MS and Normal options page, hence it's own function 
 */
function em_admin_option_box_caps(){
	global $save_button, $wpdb;
	?>
	<div  class="postbox" id="em-opt-user-caps" >
	<div class="handlediv" title="<?php __('Click to toggle', 'dbem'); ?>"><br /></div><h3><span><?php _e ( 'User Capabilities', 'dbem' ); ?></span></h3>
	<div class="inside">
            <table class="form-table">
            <tr><td colspan="2" class="em-boxheader">
            	<p><strong><?php _e('Warning: Changing these values may result in exposing previously hidden information to all users.', 'dbem')?></strong></p>
            	<p><em><?php _e('You can now give fine grained control with regards to what your users can do with events. Each user role can have perform different sets of actions.','dbem'); ?></em></p>
            </td></tr>
			<?php
            global $wp_roles;
			$cap_docs = array(
				sprintf(__('%s Capabilities','dbem'),__('Event','dbem')) => array(
					/* Event Capabilities */
					'publish_events' => sprintf(__('Users can publish %s and skip any admin approval','dbem'),__('events','dbem')),
					'delete_others_events' => sprintf(__('User can delete other users %s','dbem'),__('events','dbem')),
					'edit_others_events' => sprintf(__('User can edit other users %s','dbem'),__('events','dbem')),
					'delete_events' => sprintf(__('User can delete their own %s','dbem'),__('events','dbem')),
					'edit_events' => sprintf(__('User can create and edit %s','dbem'),__('events','dbem')),
					'read_private_events' => sprintf(__('User can view private %s','dbem'),__('events','dbem')),
					/*'read_events' => sprintf(__('User can view %s','dbem'),__('events','dbem')),*/
				),
				sprintf(__('%s Capabilities','dbem'),__('Recurring Event','dbem')) => array(
					/* Recurring Event Capabilties */
					'publish_recurring_events' => sprintf(__('Users can publish %s and skip any admin approval','dbem'),__('recurring events','dbem')),
					'delete_others_recurring_events' => sprintf(__('User can delete other users %s','dbem'),__('recurring events','dbem')),
					'edit_others_recurring_events' => sprintf(__('User can edit other users %s','dbem'),__('recurring events','dbem')),
					'delete_recurring_events' => sprintf(__('User can delete their own %s','dbem'),__('recurring events','dbem')),
					'edit_recurring_events' => sprintf(__('User can create and edit %s','dbem'),__('recurring events','dbem'))						
				),
				sprintf(__('%s Capabilities','dbem'),__('Location','dbem')) => array(
					/* Location Capabilities */
					'publish_locations' => sprintf(__('Users can publish %s and skip any admin approval','dbem'),__('locations','dbem')),
					'delete_others_locations' => sprintf(__('User can delete other users %s','dbem'),__('locations','dbem')),
					'edit_others_locations' => sprintf(__('User can edit other users %s','dbem'),__('locations','dbem')),
					'delete_locations' => sprintf(__('User can delete their own %s','dbem'),__('locations','dbem')),
					'edit_locations' => sprintf(__('User can create and edit %s','dbem'),__('locations','dbem')),
					'read_private_locations' => sprintf(__('User can view private %s','dbem'),__('locations','dbem')),
					'read_others_locations' => __('User can use other user locations for their events.','dbem'),
					/*'read_locations' => sprintf(__('User can view %s','dbem'),__('locations','dbem')),*/
				),
				sprintf(__('%s Capabilities','dbem'),__('Other','dbem')) => array(
					/* Category Capabilities */
					'delete_event_categories' => sprintf(__('User can delete %s categories and tags.','dbem'),__('event','dbem')),
					'edit_event_categories' => sprintf(__('User can edit %s categories and tags.','dbem'),__('event','dbem')),
					/* Booking Capabilities */
					'manage_others_bookings' => __('User can manage other users individual bookings and event booking settings.','dbem'),
					'manage_bookings' => __('User can use and manage bookings with their events.','dbem'),
					'upload_event_images' => __('User can upload images along with their events and locations.','dbem')
				)
			);
            ?>
            <?php 
        	if( is_multisite() && is_network_admin() ){
	            echo em_options_radio_binary(__('Apply global capabilities?','dbem'), 'dbem_ms_global_caps', __('If set to yes the capabilities will be applied all your network blogs and you will not be able to set custom capabilities each blog. You can select no later and visit specific blog settings pages to add/remove capabilities.','dbem') );
	        }
	        ?>
            <tr><td colspan="2">
	            <table class="em-caps-table" style="width:auto;" cellspacing="0" cellpadding="0">
					<thead>
						<tr>
							<td>&nbsp;</td>
							<?php 
							$odd = 0;
							foreach(array_keys($cap_docs) as $capability_group){
								?><th class="<?php echo ( !is_int($odd/2) ) ? 'odd':''; ?>"><?php echo $capability_group ?></th><?php
								$odd++;
							} 
							?>
						</tr>
					</thead>
					<tbody>
            			<?php foreach($wp_roles->role_objects as $role): ?>
	            		<tr>
	            			<td class="cap"><strong><?php echo $role->name; ?></strong></td>
							<?php 
							$odd = 0;
							foreach($cap_docs as $capability_group){
								?>
	            				<td class="<?php echo ( !is_int($odd/2) ) ? 'odd':''; ?>">
									<?php foreach($capability_group as $cap => $cap_help){ ?>
	            					<input type="checkbox" name="em_capabilities[<?php echo $role->name; ?>][<?php echo $cap ?>]" value="1" id="<?php echo $role->name.'_'.$cap; ?>" <?php echo $role->has_cap($cap) ? 'checked="checked"':''; ?> />
	            					&nbsp;<label for="<?php echo $role->name.'_'.$cap; ?>"><?php echo $cap; ?></label>&nbsp;<a href="#" title="<?php echo $cap_help; ?>">?</a>
	            					<br />
	            					<?php } ?>
	            				</td>
	            				<?php
								$odd++;
							} 
							?>
	            		</tr>
			            <?php endforeach; ?>
			        </tbody>
	            </table>
	        </td></tr>
	        <?php echo $save_button; ?>
		</table>
	</div> <!-- . inside -->
	</div> <!-- .postbox -->
	<?php
}

function em_admin_option_box_uninstall(){
	global $save_button;
	if( is_multisite() ){
		$uninstall_url = admin_url().'network/admin.php?page=events-manager-options&amp;action=uninstall&amp;_wpnonce='.wp_create_nonce('em_uninstall_'.get_current_user_id().'_wpnonce');
		$reset_url = admin_url().'network/admin.php?page=events-manager-options&amp;action=reset&amp;_wpnonce='.wp_create_nonce('em_reset_'.get_current_user_id().'_wpnonce');
		$recheck_updates_url = admin_url().'network/admin.php?page=events-manager-options&amp;action=recheck_updates&amp;_wpnonce='.wp_create_nonce('em_recheck_updates_'.get_current_user_id().'_wpnonce');
		$check_devs = admin_url().'network/admin.php?page=events-manager-options&amp;action=check_devs&amp;_wpnonce='.wp_create_nonce('em_check_devs_wpnonce');
	}else{
		$uninstall_url = EM_ADMIN_URL.'&amp;page=events-manager-options&amp;action=uninstall&amp;_wpnonce='.wp_create_nonce('em_uninstall_'.get_current_user_id().'_wpnonce');
		$reset_url = EM_ADMIN_URL.'&amp;page=events-manager-options&amp;action=reset&amp;_wpnonce='.wp_create_nonce('em_reset_'.get_current_user_id().'_wpnonce');
		$recheck_updates_url = EM_ADMIN_URL.'&amp;page=events-manager-options&amp;action=recheck_updates&amp;_wpnonce='.wp_create_nonce('em_recheck_updates_'.get_current_user_id().'_wpnonce');
		$check_devs = EM_ADMIN_URL.'&amp;page=events-manager-options&amp;action=check_devs&amp;_wpnonce='.wp_create_nonce('em_check_devs_wpnonce');
	}
	?>
	<div  class="postbox" id="em-opt-admin-tools" >
		<div class="handlediv" title="<?php __('Click to toggle', 'dbem'); ?>"><br /></div><h3><span><?php _e ( 'Admin Tools', 'dbem' ); ?> (<?php _e ( 'Advanced', 'dbem' ); ?>)</span></h3>
		<div class="inside">
			<table class="form-table">
    		    <tr class="em-header"><td colspan="2">
        			<h4><?php _e ( 'Development Versions &amp; Updates', 'dbem' ); ?></h4>
        			<p><?php _e('We\'re always making improvements, adding features and fixing bugs between releases. We incrementally make these changes in between updates and make it available as a development version. You can download these manually, but we\'ve made it easy for you. <strong>Warning:</strong> Development versions are not always fully tested before release, use wisely!','dbem'); ?></p>
    			</td></tr>
				<?php em_options_radio_binary ( __( 'Enable Dev Updates?', 'dbem' ), 'dbem_pro_dev_updates', __('If enabled, the latest dev version will always be checked instead of the latest stable version of the plugin.', 'dbem') ); ?>
				<tr>
    			    <th style="text-align:right;"><a href="<?php echo $recheck_updates_url; ?>" class="button-secondary"><?php _e('Re-Check Updates','dbem'); ?></a></th>
    			    <td><?php _e('If you would like to check and see if there is a new stable update.','dbem'); ?></td>
    			</tr>
    			<tr>
    			    <th style="text-align:right;"><a href="<?php echo $check_devs; ?>" class="button-secondary"><?php _e('Check Dev Versions','dbem'); ?></a></th>
    			    <td><?php _e('If you would like to download a dev version, but just as a one-off, you can force a dev version check by clicking the button below. If there is one available, it should appear in your plugin updates page as a regular update.','dbem'); ?></td>
				</tr>
			</table>
			
			<table class="form-table">
    		    <tr class="em-header"><td colspan="2">
    		        <h4><?php _e ( 'Uninstall/Reset', 'dbem' ); ?></h4>
    		        <p><?php _e('Use the buttons below to uninstall Events Manager completely from your system or reset Events Manager to original settings and keep your event data.','dbem'); ?></p>
    		    </td></tr>
    		    <tr><td colspan="2">
        			<a href="<?php echo $uninstall_url; ?>" class="button-secondary"><?php _e('Uninstall','dbem'); ?></a>
        			<a href="<?php echo $reset_url; ?>" class="button-secondary"><?php _e('Reset','dbem'); ?></a>
    		    </td></tr>
			</table>
			<?php do_action('em_options_page_panel_admin_tools'); ?>
			<?php echo $save_button; ?>
		</div>
	</div>
	<?php	
}
?>