<?php
if( basename(__FILE__) == basename($_SERVER['SCRIPT_FILENAME']) )
	die();

// separated these functions into separate module for use by RS extension plugins

if ( ! function_exists('awp_ver') ) {
function awp_ver($wp_ver_requirement) {
	static $cache_wp_ver;
	
	if ( empty($cache_wp_ver) ) {
		global $wp_version;
		$cache_wp_ver = $wp_version;
	}
	
	if ( ! version_compare($cache_wp_ver, '0', '>') ) {
		// If global $wp_version has been wiped by WP Security Scan plugin, temporarily restore it by re-including version.php
		if ( file_exists (ABSPATH . WPINC . '/version.php') ) {
			include ( ABSPATH . WPINC . '/version.php' );
			$return = version_compare($wp_version, $wp_ver_requirement, '>=');
			$wp_version = $cache_wp_ver;	// restore previous wp_version setting, assuming it was cleared for security purposes
			return $return;
		} else
			// Must be running a future version of WP which doesn't use version.php
			return true;
	}

	// normal case - global $wp_version has not been tampered with
	return version_compare($cache_wp_ver, $wp_ver_requirement, '>=');
}
}

// TODO: move these function to core-admin_lib.php, update extensions accordingly
if ( ! function_exists('awp_plugin_info_url') ) {
function awp_plugin_info_url( $plugin_slug ) {
	$url = admin_url("plugin-install.php?tab=plugin-information&plugin=$plugin_slug");
	return $url;
}
}

if ( ! function_exists('awp_plugin_update_url') ) {
function awp_plugin_update_url( $plugin_file ) {
	$url = wp_nonce_url("update.php?action=upgrade-plugin&amp;plugin=$plugin_file", "upgrade-plugin_$plugin_file");
	return $url;
}
}

if ( ! function_exists('awp_plugin_search_url') ) {
function awp_plugin_search_url( $search, $search_type = 'tag' ) {
	$wp_org_dir = 'tags';
	
	$url = admin_url("plugin-install.php?tab=search&type=$search_type&s=$search");
	return $url;
}
}

if ( ! function_exists('awp_is_mu') ) {
function awp_is_mu() {
	global $wpdb, $wpmu_version;
	
	return ( ( defined('MULTISITE') && MULTISITE ) || function_exists('get_current_site_name') || ! empty($wpmu_version) || ( ! empty( $wpdb->base_prefix ) && ( $wpdb->base_prefix != $wpdb->prefix ) ) );
}
}

// returns true GMT timestamp
if ( ! function_exists('agp_time_gmt') ) {
function agp_time_gmt() {	
	return strtotime( gmdate("Y-m-d H:i:s") );
}
}

// date_i18n does not support pre-1970 dates, as of WP 2.8.4
if ( ! function_exists('agp_date_i18n') ) {
function agp_date_i18n( $datef, $timestamp ) {
	if ( $timestamp >= 0 )
		return date_i18n( $datef, $timestamp );
	else
		return date( $datef, $timestamp );
}
}

// equivalent to current_user_can, 
// except it supports array of reqd_caps, supports non-current user, and does not support numeric reqd_caps
//
// set object_id to 'blog' to suppress any_object_check and any_term_check

if ( ! function_exists('agp_user_can') ) {
function agp_user_can($reqd_caps, $object_id = 0, $user_id = 0, $args = array() ) {
	if ( function_exists('is_super_admin') && is_super_admin() ) 
		return true;

	// $args supports 'skip_revision_allowance'.  For now, skip array_merge with defaults, for perf
	$user = wp_get_current_user();
	if ( empty($user) )
		return false;
		
	if ( $user_id && ($user_id != $user->ID) ) {
		$user = new WP_User($user_id);  // don't need Scoped_User because only using allcaps property (which contain WP blogcaps)
		if ( empty($user) )
			return false;
	}
	
	$orig_skip = ! empty($GLOBALS['revisionary']->skip_revision_allowance);
	
	if ( ! empty( $args['skip_revision_allowance'] ) ) {
		$GLOBALS['revisionary']->skip_revision_allowance = true;	// this will affect the behavior of Press Permit / Role Scoper's user_has_cap filter
	}
	
	if ( ( $user->ID != $GLOBALS['current_user']->ID ) || ( ! defined( 'PP_VERSION' ) && ! defined( 'PPC_VERSION' ) ) ) { // TODO: also with Role Scoper?
		$reqd_caps = (array) $reqd_caps;
		$check_caps = $reqd_caps;
		foreach ( $check_caps as $cap_name ) {
			if ( $meta_caps = map_meta_cap($cap_name, $user->ID, $object_id) ) {
				$reqd_caps = array_diff( $reqd_caps, array($cap_name) );
				$reqd_caps = array_unique( array_merge( $reqd_caps, $meta_caps ) );
			}
		}
	}

	if ( defined('RVY_CONTENT_ROLES') && ( 'blog' == $object_id ) ) {
		// if this is being called with Press Permit / Role Scoper loaded, any_object_check won't be called anyway
		$flags = array_fill_keys( array( 'skip_any_object_check', 'skip_any_term_check', 'skip_id_generation' ), true );
		$GLOBALS['revisionary']->content_roles->set_hascap_flags( $flags );
	}
	
	if ( ( $user->ID == $GLOBALS['current_user']->ID ) && ( defined( 'PP_VERSION' ) || defined( 'PPC_VERSION' ) ) ) {  // temp workaround
		$user_can = current_user_can( $reqd_caps, $object_id );
		$GLOBALS['revisionary']->skip_revision_allowance = $orig_skip;
		return $user_can;
		
	} else {
		global $current_user;
	
		if ( defined( 'PPC_VERSION' ) ) { // temp workaround
			global $current_user, $pp, $cap_interceptor;
			
			if ( $current_user->ID != $user_id ) {
				$buffer_user_id = $current_user->ID;
				wp_set_current_user( $user_id );
				$pp_user_workaround = true;
			}
			
			if ( ! empty($GLOBALS['revisionary']->skip_revision_allowance) ) {
				//$cap_interceptor->memcache = array();
				$cap_interceptor->flags['memcache_disabled'] = true;
			}
		}
		
		$_args = ( 'blog' == $object_id ) ? array( $reqd_caps, $user->ID, 0 ) : array( $reqd_caps, $user->ID, $object_id );
		
		
		$capabilities = apply_filters('user_has_cap', $user->allcaps, $reqd_caps, $_args);
		
		if ( defined( 'PPC_VERSION' ) && ! empty($pp_user_workaround) ) { // temp workaround
			wp_set_current_user( $buffer_user_id );
			$cap_interceptor->flags['memcache_disabled'] = false;
		}
		
		if ( defined('RVY_CONTENT_ROLES') && ('blog' == $object_id) ) {
			$flags = array_fill_keys( array( 'skip_any_object_check', 'skip_any_term_check', 'skip_id_generation' ), false );
			$GLOBALS['revisionary']->content_roles->set_hascap_flags( $flags );
		}
		
		if ( ! empty( $args['skip_revision_allowance'] ) ) {
			$GLOBALS['revisionary']->skip_revision_allowance = false;
		}

		foreach ($reqd_caps as $cap_name) {
			if( empty($capabilities[$cap_name]) || ! $capabilities[$cap_name] ) {
				// if we're about to fail due to a missing create_child_pages cap, honor edit_pages cap as equivalent
				// TODO: abstract this with cap_defs property
				if ( 0 === strpos( $cap_name, 'create_child_' ) ) {
					$alternate_cap_name = str_replace( 'create_child_', 'edit_', $cap_name );
					$_args = array( array($alternate_cap_name), $user->ID, $object_id );
					
					if ( defined( 'PPC_VERSION' ) && ! empty($GLOBALS['revisionary']->skip_revision_allowance) ) {
						//$cap_interceptor->memcache = array();
						$cap_interceptor->flags['memcache_disabled'] = true;
					}
					
					$capabilities = apply_filters('user_has_cap', $user->allcaps, array($alternate_cap_name), $_args);
					
					if( empty($capabilities[$alternate_cap_name]) || ! $capabilities[$alternate_cap_name] ) {
						$GLOBALS['revisionary']->skip_revision_allowance = $orig_skip;
						return false;
					}
				} else {
					$GLOBALS['revisionary']->skip_revision_allowance = $orig_skip;
					return false;
				}
			}
		}
	}

	$GLOBALS['revisionary']->skip_revision_allowance = $orig_skip;
	return true;
}
}

if ( ! function_exists('awp_post_type_from_uri') ) {
function awp_post_type_from_uri() {
	$script_name = $_SERVER['SCRIPT_NAME'];
	
	if ( strpos( $script_name, 'post-new.php' ) || strpos( $script_name, 'edit.php' ) ) {
		$object_type = ! empty( $_GET['post_type'] ) ? $_GET['post_type'] : 'post';
		
	} elseif ( ! empty( $_GET['post'] ) ) {	 // post.php
		if ( $_post = get_post( $_GET['post'] ) )
			$object_type = $_post->post_type;
	}

	if ( ! empty( $_REQUEST['post_type'] ) && ( 'revision' == $_REQUEST['post_type'] ) )
		return 'revision';
	elseif ( ! empty($object_type) )
		return $object_type;
	else {
		global $post;
		if ( ! empty($post->post_type) )
			return $post->post_type;
		else
			return 'post';
	}
}
}

// wrapper for __(), prevents WP strings from being forced into plugin .po
if ( ! function_exists( '__awp' ) ) {
function __awp( $string, $unused = '' ) {
	return __( $string );		
}
}

?>