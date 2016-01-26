<?php
function rvy_load_textdomain() {
	if ( defined('RVY_TEXTDOMAIN_LOADED') )
		return;

	load_plugin_textdomain('revisionary', false, RVY_FOLDER . '/languages');

	define('RVY_TEXTDOMAIN_LOADED', true);
}

function rvy_admin_init() {
	if ( ! empty($_POST['rvy_submit']) || ! empty($_POST['rvy_defaults']) ) {
		require_once( RVY_ABSPATH . '/submittee_rvy.php');	
		$handler = new Revisionary_Submittee();
	
		if ( isset($_POST['rvy_submit']) ) {
			$sitewide = isset($_POST['rvy_options_doing_sitewide']);
			$customize_defaults = isset($_POST['rvy_options_customize_defaults']);
			$handler->handle_submission( 'update', $sitewide, $customize_defaults );
			
		} elseif ( isset($_POST['rvy_defaults']) ) {
			$sitewide = isset($_POST['rvy_options_doing_sitewide']);
			$customize_defaults = isset($_POST['rvy_options_customize_defaults']);
			$handler->handle_submission( 'default', $sitewide, $customize_defaults );
		}
		
	// don't bother with the checks in this block unless action arg was passed or rvy_compare_revs field was posted
	} elseif ( ! empty($_GET['action']) || ! empty( $_POST['rvy_compare_revs'] ) || ! empty( $_POST['rvy_revision_edit'] ) || ! empty($_POST['action']) ) {
		
		if ( false !== strpos( urldecode($_SERVER['REQUEST_URI']), 'admin.php?page=rvy-revisions') ) {
			if ( ! empty( $_POST['rvy_compare_revs'] ) ) {
				require_once( dirname(__FILE__).'/revision-action_rvy.php');	
				add_action( 'wp_loaded', 'rvy_revision_diff' );
				
			} elseif ( ! empty($_GET['action']) && ('restore' == $_GET['action']) ) {
				require_once( dirname(__FILE__).'/revision-action_rvy.php');	
				add_action( 'wp_loaded', 'rvy_revision_restore' );
		
			} elseif ( ! empty($_GET['action']) && ('delete' == $_GET['action']) ) {
				require_once( dirname(__FILE__).'/revision-action_rvy.php');	
				add_action( 'wp_loaded', 'rvy_revision_delete' );
				
			} elseif ( ! empty($_GET['action']) && ('approve' == $_GET['action']) ) {
				require_once( dirname(__FILE__).'/revision-action_rvy.php');	
				add_action( 'wp_loaded', 'rvy_revision_approve' );
				
			} elseif ( ! empty($_GET['action']) && ('unschedule' == $_GET['action']) ) {
				require_once( dirname(__FILE__).'/revision-action_rvy.php');	
				add_action( 'wp_loaded', 'rvy_revision_unschedule' );
				
			} elseif ( ! empty( $_POST['rvy_revision_edit'] ) ) {
				require_once( dirname(__FILE__).'/revision-action_rvy.php');
				add_action( 'wp_loaded', 'rvy_revision_edit' );
			
			} elseif ( ! empty($_POST['action']) && ('bulk-delete' == $_POST['action'] ) ) {
				require_once( dirname(__FILE__).'/revision-action_rvy.php');	
				add_action( 'wp_loaded', 'rvy_revision_bulk_delete' );
			}
	
		// special workaround for delete links of attachments in Edit Posts / Edit Pages
		} elseif ( false !== strpos( urldecode($_SERVER['REQUEST_URI']),'p-admin/edit-pages.php') 
		|| false !== strpos( urldecode($_SERVER['REQUEST_URI']),'p-admin/edit.php') ) {

			if ( ! empty($_GET['action']) && ('delete' == $_GET['action']) && ! empty( $_GET['post'] ) ) {
				if ( rvy_get_option( 'scheduled_revisions' ) || rvy_get_option( 'pending_revisions' ) ) {
					if ( ! empty($_GET['mode']) && 'list' == $_GET['mode'] ) {
						// we're not ready to deal with bulk deletion of revisions, so strip them out of the request array
						if ( is_array( $_GET['post'] ) ) {
							global $wpdb;
							
							if ( $remove_ids = $wpdb->get_col( "SELECT ID FROM $wpdb->posts WHERE post_type = 'revision' AND ID IN ('" . implode( "','", $_GET['post'] ) . "')" ) )
								$_GET['post'] = array_diff( $_GET['post'], $remove_ids );
						}
						
					} elseif ( $post =& get_post( $_GET['post'] ) ) {
						if ( 'revision' == $post->post_type ) {
							$link = "admin.php?page=rvy-revisions&action=view&revision={$_GET['post']}&delete_request={$_GET['post']}";
							wp_redirect( $link );
							exit( 0 );
						} // endif the requested post is a revision
					} // endif the request post exists
				} // endif scheduled revisions or pending revisions are in use
			} // endif URL args action=delete and post=nonzero
		} // endif URL is edit.php or edit-pages.php
		
	} // endif action arg passed
}
	
?>