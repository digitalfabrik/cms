<?php
if( basename(__FILE__) == basename($_SERVER['SCRIPT_FILENAME']) )
	die();
	

class Revisionary_Submittee {

	function handle_submission($action, $sitewide = false, $customize_defaults = false) {
		if ( ( $sitewide || $customize_defaults ) ) {
			if ( ! is_super_admin() )
				wp_die(__awp('Cheatin&#8217; uh?'));
		
		} elseif ( ! current_user_can( 'manage_options' ) )
			 wp_die(__awp('Cheatin&#8217; uh?'));

		if ( $customize_defaults )
			$sitewide = true;		// default customization is only for per-site options, but is network-wide in terms of DB storage in sitemeta table
		
		if ( false === strpos( $_GET["page"], 'rvy-' ) )
			return;
		
		if ( empty($_POST['rvy_submission_topic']) )
			return;
		
		if ( 'options' == $_POST['rvy_submission_topic'] ) {
			rvy_refresh_default_options();

			$method = "{$action}_options";
			if ( method_exists( $this, $method ) )
				call_user_func( array($this, $method), $sitewide, $customize_defaults );

			if ( $sitewide && ! $customize_defaults ) {
				$method = "{$action}_sitewide";
				if ( method_exists( $this, $method ) )
					call_user_func( array($this, $method) );
			}
		}

		rvy_refresh_options();
	}
	
	function update_options( $sitewide = false, $customize_defaults = false ) {
		check_admin_referer( 'rvy-update-options' );
	
		$this->update_page_options( $sitewide, $customize_defaults );
		
		global $wpdb;
		$wpdb->query( "UPDATE $wpdb->options SET autoload = 'no' WHERE option_name LIKE 'rvy_%' AND option_name != 'rvy_next_rev_publish_gmt'" );
	}
	
	function default_options( $sitewide = false, $customize_defaults = false ) {
		check_admin_referer( 'rvy-update-options' );
	
		$default_prefix = ( $customize_defaults ) ? 'default_' : '';

		$reviewed_options = explode(',', $_POST['all_options']);
		foreach ( $reviewed_options as $option_name )
			rvy_delete_option($default_prefix . $option_name, $sitewide );
	}
	
	function update_sitewide() {
		check_admin_referer( 'rvy-update-options' );
		
		$reviewed_options = isset($_POST['rvy_all_movable_options']) ? explode(',', $_POST['rvy_all_movable_options']) : array();
		
		$options_sitewide = isset($_POST['rvy_options_sitewide']) ? (array) $_POST['rvy_options_sitewide'] : array();

		update_site_option( "rvy_options_sitewide_reviewed", $reviewed_options );
		update_site_option( "rvy_options_sitewide", $options_sitewide );
	}
	
	function default_sitewide() {
		check_admin_referer( 'rvy-update-options' );

		rvy_delete_option( 'options_sitewide', true );
		rvy_delete_option( 'options_sitewide_reviewed', true );
	}
	
	function update_page_options( $sitewide = false, $customize_defaults = false ) {
		$default_prefix = ( $customize_defaults ) ? 'default_' : '';
		
		$reviewed_options = explode(',', $_POST['all_options']);

		foreach ( $reviewed_options as $option_basename ) {
			$value = isset($_POST[$option_basename]) ? $_POST[$option_basename] : '';

			if ( ! is_array($value) )
				$value = trim($value);
			$value = stripslashes_deep($value);

			rvy_update_option( $default_prefix . $option_basename, $value, $sitewide );
		}
	}
}
	
	
?>