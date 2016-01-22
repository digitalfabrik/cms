<?php
if( basename(__FILE__) == basename($_SERVER['SCRIPT_FILENAME']) )
	die();


// auto-define the Revisor role to include custom post type capabilities equivalent to those added for post, page in rvy_add_revisor_role()
function rvy_add_revisor_custom_caps() {
	if ( ! rvy_get_option( 'revisor_role_add_custom_rolecaps' ) )
		return;

	global $wp_roles;

	if ( isset( $wp_roles->roles['revisor'] ) ) {
		if ( $custom_types = get_post_types( array( 'public' => true, '_builtin' => false ), 'object' ) ) {
			foreach( $custom_types as $post_type => $type_obj ) {
				$cap = $type_obj->cap;	
				$custom_caps = array_fill_keys( array( $cap->read_private_posts, $cap->edit_posts, $cap->edit_others_posts, "delete_{$post_type}s" ), true );

				$wp_roles->roles['revisor']['capabilities'] = array_merge( $wp_roles->roles['revisor']['capabilities'], $custom_caps );
				$wp_roles->role_objects['revisor']->capabilities = array_merge( $wp_roles->role_objects['revisor']->capabilities, $custom_caps );
			}
		}
	}
}


function rvy_activate() {
	rvy_add_revisor_role();
	
	// force this timestamp to be regenerated, in case something went wrong before
	delete_option( 'rvy_next_rev_publish_gmt' );
}

function rvy_detect_post_id() {
	if ( ! empty( $_GET['post'] ) )
		$post_id = $_GET['post'];
	elseif ( ! empty( $_POST['post_ID'] ) )
		$post_id = $_POST['post_ID'];
	elseif ( ! empty( $_GET['post_id'] ) )
		$post_id = $_GET['post_id'];
	elseif ( ! empty( $_GET['p'] ) )
		$post_id = $_GET['p'];
	elseif ( ! empty( $_GET['id'] ) )
		$post_id = $_GET['id'];
	else
		$post_id = 0;
	
	return $post_id;	
}

function rvy_add_revisor_role( $requested_blog_id = '' ) {
	global $wp_roles;
	
	$wp_role_name = 'revisor';
	$display_name = __( 'Revisor', 'revisionary' );
	
	$wp_role_caps = array(
			'read' => true,
			'read_private_posts' => true,
			'read_private_pages' => true,
			'edit_posts' => true,
			'delete_posts' => true,
			'edit_others_posts' => true,
			'edit_pages' => true,
			'delete_pages' => true,
			'edit_others_pages' => true,
			'level_3' => true,
			'level_2' => true,
			'level_1' => true,
			'level_0' => true
		);
	
	// add the role for all blogs if RS role def is sitewide
	if ( IS_MU_RVY ) {
		global $wpdb, $blog_id;

		if ( $requested_blog_id )
			$blog_ids = (array) $requested_blog_id;
		else
			$blog_ids = $wpdb->get_col( "SELECT blog_id FROM $wpdb->blogs" );
		
		$orig_blog_id = $blog_id;	
	} else
		$blog_ids = array( '' );

	foreach ( $blog_ids as $id ) {
		if ( $id )
			switch_to_blog( $id );
	
		if ( ! isset( $wp_roles->role_objects['revisor'] ) )
			$wp_roles->add_role( $wp_role_name, $display_name, $wp_role_caps );
	}
	
	if ( count($blog_ids) > 1 )
		switch_to_blog( $orig_blog_id );	
}

function rvy_init() {
	if ( ! rvy_check_rs_version() )
		return;

	if ( is_admin() ) {
		require_once( dirname(__FILE__).'/admin/admin-init_rvy.php' );
		rvy_load_textdomain();
		rvy_admin_init();

	} else {
		// fill in the missing args for Pending / Scheduled revision preview link from Edit Posts / Pages
		if ( isset($_SERVER['HTTP_REFERER']) 
		&& ( false !== strpos( urldecode($_SERVER['HTTP_REFERER']),'p-admin/edit-pages.php') 
		|| false !== strpos( urldecode($_SERVER['HTTP_REFERER']),'p-admin/edit.php') ) ) {

			if ( ! empty($_GET['p']) ) {
				if ( rvy_get_option( 'scheduled_revisions' ) || rvy_get_option( 'pending_revisions' ) ) {
					if ( $post =& get_post( $_GET['p'] ) ) {
						if ( 'revision' == $post->post_type ) {
							$_GET['preview'] = 1;
							$_GET['post_type'] = 'revision';
						}
					}
				}
			}

		} elseif( strpos( urldecode($_SERVER['REQUEST_URI']), 'index.php') ) {
				
			if ( ! empty($_GET['action']) && ('publish_scheduled' == $_GET['action']) ) {
				require_once( dirname(__FILE__).'/admin/revision-action_rvy.php');
				add_action( 'rvy_init', 'rvy_publish_scheduled_revisions' );
			
			}
		}	
	}
	
	if ( empty( $_GET['action'] ) || ( 'publish_scheduled' != $_GET['action'] ) ) {
		if ( ! strpos( $_SERVER['REQUEST_URI'], 'login.php' ) ) {
		
			// If a previously requested asynchronous request was ineffective, perform the actions now
			// (this is not executed if the current URI has action=publish_scheduled)
			$requested_actions = get_option( 'requested_remote_actions_rvy' );
			if ( is_array( $requested_actions) && ! empty($requested_actions) ) {
				if ( ! empty($requested_actions['publish_scheduled']) ) {
					require_once( dirname(__FILE__).'/admin/revision-action_rvy.php');
					rvy_publish_scheduled_revisions();
					unset( $requested_actions['publish_scheduled'] );
				}
	
				update_option( 'requested_remote_actions_rvy', $requested_actions );
			}
			
			if ( rvy_get_option( 'scheduled_revisions' ) ) {

				$next_publish = get_option( 'rvy_next_rev_publish_gmt' );
			
				if ( ! $next_publish || ( agp_time_gmt() >= strtotime( $next_publish ) ) ) {
					if ( ini_get( 'allow_url_fopen' ) && rvy_get_option('async_scheduled_publish') ) {
						// asynchronous secondary site call to avoid delays // TODO: pass site key here
						rvy_log_async_request('publish_scheduled');
						$url = site_url( 'index.php?action=publish_scheduled' );
						wp_remote_post( $url, array('timeout' => 5, 'blocking' => false, 'sslverify' => apply_filters('https_local_ssl_verify', true)) );
					} else {
						define( 'DOING_CRON', true );
						require_once( dirname(__FILE__).'/admin/revision-action_rvy.php');
						rvy_publish_scheduled_revisions();
					}
				}	
			}
		}
	}

	require_once( dirname(__FILE__).'/revisionary_main.php');

	global $revisionary;
	$revisionary = new Revisionary();
}
	
function rvy_refresh_options() {
	rvy_retrieve_options(true);
	rvy_retrieve_options(false);
	
	rvy_refresh_default_options();
	rvy_refresh_options_sitewide();
}

function rvy_refresh_options_sitewide() {
	if ( ! IS_MU_RVY )
		return;
		
	global $rvy_options_sitewide;
	$rvy_options_sitewide = apply_filters( 'options_sitewide_rvy', rvy_default_options_sitewide() );	// establishes which options are set site-wide

	if ( $options_sitewide_reviewed =  rvy_get_option( 'options_sitewide_reviewed', true ) ) {
		$custom_options_sitewide = (array) rvy_get_option( 'options_sitewide', true );
		
		$unreviewed_default_sitewide = array_diff( array_keys($rvy_options_sitewide), $options_sitewide_reviewed );

		$rvy_options_sitewide = array_fill_keys( array_merge( $custom_options_sitewide, $unreviewed_default_sitewide ), true );
	}
}

function rvy_refresh_default_options() {
	global $rvy_default_options;

	$rvy_default_options = apply_filters( 'default_options_rvy', rvy_default_options() );
	
	if ( IS_MU_RVY )
		rvy_apply_custom_default_options();
}

function rvy_apply_custom_default_options() {
	global $wpdb, $rvy_default_options, $rvy_options_sitewide;
	
	if ( $results = $wpdb->get_results( "SELECT meta_key, meta_value FROM $wpdb->sitemeta WHERE site_id = '$wpdb->siteid' AND meta_key LIKE 'rvy_default_%'" ) ) {
		foreach ( $results as $row ) {
			$option_basename = str_replace( 'rvy_default_', '', $row->meta_key );

			if ( ! empty( $rvy_options_sitewide[$option_basename] ) )
				continue;	// custom defaults are only for site-specific options

			if( isset( $rvy_default_options[$option_basename] ) )
				$rvy_default_options[$option_basename] = maybe_unserialize( $row->meta_value );
		}
	}
}

function rvy_delete_option( $option_basename, $sitewide = -1 ) {
	
	// allow explicit selection of sitewide / non-sitewide scope for better performance and update security
	if ( -1 === $sitewide ) {
		global $rvy_options_sitewide;
		$sitewide = isset( $rvy_options_sitewide ) && ! empty( $rvy_options_sitewide[$option_basename] );
	}

	if ( $sitewide ) {
		global $wpdb;
		$wpdb->query( "DELETE FROM {$wpdb->sitemeta} WHERE site_id = '$wpdb->siteid' AND meta_key = 'rvy_$option_basename'" );
	} else 
		delete_option( "rvy_$option_basename" );
}

function rvy_update_option( $option_basename, $option_val, $sitewide = -1 ) {
	
	// allow explicit selection of sitewide / non-sitewide scope for better performance and update security
	if ( -1 === $sitewide ) {
		global $rvy_options_sitewide;
		$sitewide = isset( $rvy_options_sitewide ) && ! empty( $rvy_options_sitewide[$option_basename] );
	}
		
	if ( $sitewide ) {
		//d_echo("<br />sitewide: $option_basename, value '$option_val'" );
		//add_site_option( "rvy_$option_basename", $option_val );
		update_site_option( "rvy_$option_basename", $option_val );
	} else { 
		//d_echo("<br />blogwide: $option_basename" );
		update_option( "rvy_$option_basename", $option_val );
	}
}

function rvy_retrieve_options( $sitewide = false ) {
	global $wpdb;
	
	if ( $sitewide ) {
		if ( ! IS_MU_RVY )
			return;

		global $rvy_site_options;
		
		$rvy_site_options = array();

		if ( $results = $wpdb->get_results( "SELECT meta_key, meta_value FROM $wpdb->sitemeta WHERE site_id = '$wpdb->siteid' AND meta_key LIKE 'rvy_%'" ) )
			foreach ( $results as $row )
				$rvy_site_options[$row->meta_key] = $row->meta_value;

		$rvy_site_options = apply_filters( 'site_options_rvy', $rvy_site_options );
		return $rvy_site_options;

	} else {
		global $rvy_blog_options;
		
		$rvy_blog_options = array();
		
		if ( $results = $wpdb->get_results("SELECT option_name, option_value FROM $wpdb->options WHERE option_name LIKE 'rvy_%'") )
			foreach ( $results as $row )
				$rvy_blog_options[$row->option_name] = $row->option_value;
				
		$rvy_blog_options = apply_filters( 'options_rvy', $rvy_blog_options );
		return $rvy_blog_options;
	}
}

function rvy_get_option($option_basename, $sitewide = -1, $get_default = false) {
	if ( ! $get_default ) {
		// allow explicit selection of sitewide / non-sitewide scope for better performance and update security
		if ( -1 === $sitewide ) {
			global $rvy_options_sitewide;
			$sitewide = isset( $rvy_options_sitewide ) && ! empty( $rvy_options_sitewide[$option_basename] );
		}
	
		if ( $sitewide ) {
			// this option is set site-wide
			global $rvy_site_options;
			
			if ( ! isset($rvy_site_options) )
				$rvy_site_options = rvy_retrieve_options( true );	
				
			if ( isset($rvy_site_options["rvy_{$option_basename}"]) )
				$optval = $rvy_site_options["rvy_{$option_basename}"];
			
		} else {	
			global $rvy_blog_options;
			
			if ( ! isset($rvy_blog_options) )
				$rvy_blog_options = rvy_retrieve_options( false );	
				
			if ( isset($rvy_blog_options["rvy_$option_basename"]) )
				$optval = $rvy_blog_options["rvy_$option_basename"];
		}
	}

	if ( ! isset( $optval ) ) {
		global $rvy_default_options;
			
		if ( empty( $rvy_default_options ) ) {
			if ( did_action( 'rvy_init' ) )	// Make sure other plugins have had a chance to apply any filters to default options
				rvy_refresh_default_options();
			else {
				$hardcode_defaults = rvy_default_options();
				if ( isset($hardcode_defaults[$option_basename]) )
					$optval = $hardcode_defaults[$option_basename];	
			}
		}
		
		if ( ! empty($rvy_default_options) && ! empty( $rvy_default_options[$option_basename] ) )
			$optval = $rvy_default_options[$option_basename];
			
		if ( ! isset($optval) )
			return '';
	}

	return maybe_unserialize($optval);
}
 
function rvy_get_post_revisions($post_id, $status = 'inherit', $args = '' ) {
	global $wpdb;
	
	$defaults = array( 'order' => 'DESC', 'orderby' => 'post_modified_gmt', 'use_memcache' => true, 'fields' => COLS_ALL_RVY, 'return_flipped' => false );
	$args = wp_parse_args( $args, $defaults );
	extract( $args );
	
	if ( COL_ID_RVY == $fields ) {
		// performance opt for repeated calls by user_has_cap filter
		if ( $use_memcache ) {
			static $last_results;
			
			if ( ! isset($last_results) )
				$last_results = array();
		
			elseif ( isset($last_results[$post_id][$status]) )
				return $last_results[$post_id][$status];
		}
		
		$revisions = $wpdb->get_col("SELECT $fields FROM $wpdb->posts WHERE post_type = 'revision' AND post_parent = '$post_id' AND post_status = '$status'");
	
		if ( $return_flipped )
			$revisions = array_fill_keys( $revisions, true );

		if ( $use_memcache ) {
			if ( ! isset($last_results[$post_id]) )
				$last_results[$post_id] = array();
				
			$last_results[$post_id][$status] = $revisions;
		}	
			
	} else {
		$order_clause = ( $order && $orderby ) ? "ORDER BY $orderby $order" : '';
		$revisions = $wpdb->get_results("SELECT * FROM $wpdb->posts WHERE post_type = 'revision' AND post_parent = '$post_id' AND post_status = '$status' $order_clause");
	}

	return $revisions;
}


function rvy_check_rs_version() {
	if ( defined( 'PP_VERSION' ) ) {
		$min_pp_version = '0.9-beta2';

		// Give a heads-up and download link if Role Scoper version is not compatible
		if ( version_compare( PP_VERSION, $min_pp_version, '<' ) ) {
			$err_msg = sprintf(__('Revisionary is not compatible with Press Permit versions prior to %1$s.  Please upgrade or deactivate.', 'revisionary'), $min_pp_version );
		}
	
	} elseif ( defined( 'SCOPER_VERSION' ) ) {
		$min_scoper_version = '1.3.47';

		// Give a heads-up and download link if Role Scoper version is not compatible
		if ( version_compare( SCOPER_VERSION, $min_scoper_version, '<' ) ) {
			$active_scoper_file = false;
			$plugins = get_option('active_plugins');
			foreach ( $plugins as $plugin_file )
				if ( false !== strpos($plugin_file, 'role-scoper') ) {
					$active_scoper_file = $plugin_file;
					break;	
				}

			$err_msg = sprintf(__('Revisionary is not compatible with Role Scoper versions prior to %1$s.  Please upgrade or deactivate %2$s Role Scoper%3$s.', 'revisionary'), $min_scoper_version, "<a href='__rs-update__'>", '</a>');
		}
	}
	
	if ( ! empty($err_msg) ) {
		if ( is_admin() ) {
			if ( ! empty($active_scoper_file) )
				$func_body = '$msg = str_replace( "__rs-update__", awp_plugin_update_url("' . $active_scoper_file . '"), "' . $err_msg . '");';
			else
				$func_body = '$msg = ' . "'" . $err_msg . "';";

			$func_body .= "echo '" 
			. '<div id="message" class="error fade" style="color: black"><p><strong>' 
			. "'" 
			. ' . $msg . ' 
			. "'</strong></p></div>';";
			
			$action = ( is_network_admin() ) ? 'network_admin_notices' : 'admin_notices';
			add_action( $action, create_function('', $func_body) );
		}
		
		return false;
	}

	return true;
}

function rvy_log_async_request($action) {						
	// the function which performs requested action will clear this entry to confirm that the asynchronous call was effective 
	$requested_actions = get_option( 'requested_remote_actions_rvy' );
	if ( ! is_array($requested_actions) )
		$requested_actions = array();
		
	$requested_actions[$action] = true;
	update_option( 'requested_remote_actions_rvy', $requested_actions );
}

function rvy_confirm_async_execution($action) {
	$requested_actions = get_option( 'requested_remote_actions_rvy' );
	if ( is_array($requested_actions) && isset($requested_actions[$action]) ) {
		unset( $requested_actions[$action] );
		update_option( 'requested_remote_actions_rvy', $requested_actions );
	}
}

function is_content_administrator_rvy() {
	$cap_name = defined( 'SCOPER_CONTENT_ADMIN_CAP' ) ? SCOPER_CONTENT_ADMIN_CAP : 'activate_plugins';
	return current_user_can( $cap_name );
}

function rvy_notice($message) {
	// slick method copied from NextGEN Gallery plugin			// TODO: why isn't there a class that can turn this text black?
	add_action('admin_notices', create_function('', 'echo \'<div id="message" class="error fade" style="color: black">' . $message . '</div>\';'));
	trigger_error("Revisionary internal notice: $message");
	$err = new WP_Error('Revisionary', $message);
}

function rvy_mail( $address, $title, $message ) {
	//$blog_name = get_option( 'blogname' );
	//$admin_email = get_option( 'admin_email' );
	
    //$headers = 'From: ' . $blog_name . ' <' . $admin_email . '>' . "\r\n";
    //$headers .= 'Reply-To: ' . $blog_name . ' <'. $admin_email . '>' . "\r\n";
	//$headers .= 'Return-Path: ' . $blog_name . ' <'. $admin_email . '>' . "\r\n";

	if ( defined( 'RS_DEBUG' ) )
		wp_mail( $address, $title, $message );
	else
		@wp_mail( $address, $title, $message );
}

?>