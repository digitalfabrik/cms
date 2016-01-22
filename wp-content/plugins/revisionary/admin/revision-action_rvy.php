<?php

if( basename(__FILE__) == basename($_SERVER['SCRIPT_FILENAME']) )
	die( 'This page cannot be called directly.' );

add_action( '_wp_put_post_revision', 'rvy_review_revision' );
	
/**
 * revision-action_rvy.php
 * 
 * @author 		Kevin Behrens
 * @copyright 	Copyright 2011-2015
 * 
 */
function rvy_revision_diff() {
	require_once( ABSPATH . 'wp-admin/admin.php');
	
	$right = $_POST['right'];
	$left = $_POST['left'];
	do {
		if ( !$left_revision  = get_post( $left ) )
			break;

		if ( !$right_revision = get_post( $right ) )
			break;

		if ( $post = get_post( $left_revision->post_parent ) ) {
			if ( $type_obj = get_post_type_object( $post->post_type ) ) {
				if ( !current_user_can( $type_obj->cap->read_post, $left_revision->ID ) || !current_user_can( $type_obj->cap->read_post, $right_revision->ID ) )
					break;
			}
		}

		// If we're comparing a revision to itself, redirect to the 'view' page for that revision or the edit page for that post
		if ( $left_revision->ID == $right_revision->ID ) {
			if ( file_exists( 'js/revisions-js.php' ) )
				include( dirname(__FILE__).'/js/revisions-js.php' );	// pass on message from HAL, if it exists
				
			$redirect = "admin.php?page=rvy-revisions&revision={$left_revision->ID}&action=view";
			wp_redirect( $redirect );
			exit( 0 );
		}

		// Don't allow reverse diffs?
		if ( strtotime($right_revision->post_modified_gmt) < strtotime($left_revision->post_modified_gmt) ) {
			$left = $_POST['right'];
			$right = $_POST['left'];
		}

		if ( $left_revision->ID == $right_revision->post_parent ) // right is a revision of left
			$post =& $left_revision;

		elseif ( $left_revision->post_parent == $right_revision->ID ) // left is a revision of right
			$post =& $right_revision;

		elseif ( $left_revision->post_parent == $right_revision->post_parent ) // both are revisions of common parent
			$post = get_post( $left_revision->post_parent );

		else {
			break; // Don't diff two unrelated revisions
		}

	} while ( 0 );

	$public_types = array_diff( get_post_types( array( 'public' => true ) ), array( 'attachment' ) );
	
	if ( empty($post) || ! in_array( $post->post_type, $public_types ) )
		$redirect = 'edit.php';
	else
		$redirect = "admin.php?page=rvy-revisions&action=diff&left=$left&right=$right";	
	
	wp_redirect( $redirect );
	exit;
}

// schedules publication of a revision ( or publishes if requested publish date has already passed )
function rvy_revision_approve() {

	require_once( ABSPATH . 'wp-admin/admin.php');
	$revision_id = $_GET['revision'];
	$redirect = '';
	
	$blogname = wp_specialchars_decode( get_option('blogname'), ENT_QUOTES );
	
	do {
		if ( !$revision = wp_get_post_revision( $revision_id ) )
			break;

		if ( !$post = get_post( $revision->post_parent ) )
			break;

		if ( $type_obj = get_post_type_object( $post->post_type ) ) {
			if ( ! agp_user_can( $type_obj->cap->edit_post, $revision->post_parent, '', array( 'skip_revision_allowance' => true ) ) )
				break;

		}

		check_admin_referer( "approve-post_$post->ID|$revision->ID" );
		
		delete_option( 'rvy_next_rev_publish_gmt' );
		
		$db_action = false;
		
		$query_args = array( 'message' => 5, 'revision' => $post->ID, 'action' => 'view' );
		
		if ( strtotime( $revision->post_date_gmt ) <= agp_time_gmt() ) {
			$status_obj = get_post_status_object( $revision->post_status );

			if ( empty($status_obj->public) && empty($status_obj->private) && ( 'future' != $revision->post_status ) ) {
				// prep the revision to look like a normal one so WP doesn't reject it
				global $wpdb;
				
				$data = array( 'post_status' => 'inherit', 'post_date' => $revision->post_modified, 'post_date_gmt' => $revision->post_modified );
				
				if ( class_exists('WPCom_Markdown') && ! defined( 'RVY_DISABLE_MARKDOWN_WORKAROUND' ) )
					$data['post_content_filtered'] = $revision->post_content;
				
				$wpdb->update( $wpdb->posts, $data, array( 'post_type' => 'revision', 'ID' => $revision->ID ) );
				
				wp_restore_post_revision( $revision->ID );
				$db_action = true;
				
				rvy_format_content( $revision->post_content, $revision->post_content_filtered, $post->ID );
				
				clean_post_cache( $revision->ID );
			}

			$revision_id = $revision->post_parent;
			$revision_status = '';
			$last_arg = "&published_post=$revision->ID";
			$scheduled = '';
		} else {
			if ( 'future' != $revision->post_status ) {
				global $wpdb;
				$wpdb->update( $wpdb->posts, array( 'post_status' => 'future' ), array( 'post_type' => 'revision', 'ID' => $revision->ID ) );
				rvy_update_next_publish_date();
				$db_action = true;
				
				clean_post_cache( $revision->ID );
			}

			$revision_id = $revision->ID;
			$revision_status = 'future';
			$last_arg = "&scheduled=$revision->ID";
			$scheduled = $revision->post_date;
		}
		

		$type_obj = get_post_type_object( $post->post_type );
		$type_caption = $type_obj->labels->singular_name;

		$title = sprintf(__('[%s] Revision Approval Notice', 'revisionary' ), $blogname );
		$message = sprintf( __('A revision to your %1$s "%2$s" has been approved.', 'revisionary' ), $type_caption, $post->post_title ) . "\r\n\r\n";

		if ( $revisor = new WP_User( $revision->post_author ) )
			$message .= sprintf( __('The submitter was %1$s.', 'revisionary'), $revisor->display_name ) . "\r\n\r\n";

		if ( $scheduled ) {
			$datef = __awp( 'M j, Y @ G:i' );
			$message .= sprintf( __('It will be published on %s', 'revisionary' ), agp_date_i18n( $datef, strtotime($revision->post_date) ) ) . "\r\n\r\n";
			$message .= __( 'Review it here: ', 'revisionary' ) . admin_url("admin.php?page=rvy-revisions&action=view&revision={$revision->ID}") . "\r\n\r\n";
		} else {
			$message .= __( 'View it online: ', 'revisionary' ) . get_permalink($post->ID) . "\r\n";	
		}
		
		if ( $db_action && ( $post->post_author != $revision->post_author ) && rvy_get_option( 'rev_approval_notify_author' ) ) {
			if ( $author = new WP_User( $post->post_author ) ) {
				rvy_mail( $author->user_email, $title, $message );
			}
		}
		
		if ( $db_action && defined( 'RVY_NOTIFY_SUPER_ADMIN' ) && is_multisite() ) {
			$super_admin_logins = get_super_admins();
			foreach( $super_admin_logins as $user_login ) {
				if ( $super = new WP_User($user_login) )
					rvy_mail( $super->user_email, $title, $message );
			}
		}
		
		if ( $db_action && rvy_get_option( 'rev_approval_notify_revisor' ) ) {
			$title = sprintf(__('[%s] Revision Approval Notice', 'revisionary' ), $blogname );
			$message = sprintf( __('The revision you submitted for the %1$s "%2$s" has been approved.', 'revisionary' ), $type_caption, $revision->post_title ) . "\r\n\r\n";

			if ( $scheduled ) {
				$datef = __awp( 'M j, Y @ G:i' );
				$message .= sprintf( __('It will be published on %s', 'revisionary' ), agp_date_i18n( $datef, strtotime($revision->post_date) ) ) . "\r\n\r\n";
				$message .= __( 'Review it here: ', 'revisionary' ) . admin_url("admin.php?page=rvy-revisions&action=view&revision={$revision->ID}") . "\r\n\r\n";
			} else {
				$message .= __( 'View it online: ', 'revisionary' ) . get_permalink($post->ID) . "\r\n";	
			}

			if ( $author = new WP_User( $revision->post_author, '' ) ) {
				rvy_mail( $author->user_email, $title, $message );
			}
		}
		
		// possible TODO: support redirect back to WP post/page edit
		//$redirect = add_query_arg( $query_args, get_edit_post_link( $post->ID, 'url' ) );
		
		$redirect = "admin.php?page=rvy-revisions&revision=$revision_id&action=view&revision_status=$revision_status{$last_arg}";

	} while (0);

	

	if ( ! $redirect ) {
		if ( ! empty($post) && is_object($post) && ( 'post' != $post->post_type ) ) {
			$redirect = "edit.php?post_type={$post->post_type}";
		} else
			$redirect = 'edit.php';
	}

	do_action( 'revision_approved', $revision->post_parent, $revision->ID );
	
	wp_redirect( $redirect );
	exit;
}

function rvy_revision_restore() {
	require_once( ABSPATH . 'wp-admin/admin.php');
	$revision_id = $_GET['revision'];
	$redirect = '';
	
	do {
		if ( !$revision = wp_get_post_revision( $revision_id ) )
			break;

		if ( !$post = get_post( $revision->post_parent ) )
			break;

		if ( $type_obj = get_post_type_object( $post->post_type ) ) {
			if ( ! agp_user_can( $type_obj->cap->edit_post, $revision->post_parent, '', array( 'skip_revision_allowance' => true ) ) )
				break;
		}

		check_admin_referer( "restore-post_{$post->ID}|$revision->ID" );
		wp_restore_post_revision( $revision_id );

		rvy_format_content( $revision->post_content, $revision->post_content_filtered, $post->ID );
		
		// also set the revision status to 'inherit' so it is listed as a past revision if the current revision is further changed (As of WP 2.9, wp_restore_post_revision no longer does this automatically)
		$revision->post_status = 'inherit';
		$revision = add_magic_quotes( (array) $revision ); //since data is from db
		wp_update_post( $revision );
		
		// possible TODO: support redirect back to WP post/page edit
		//$query_args = array( 'message' => 5, 'revision' => $revision->ID, 'action' => 'view', 'revision_status' => '' );

		if ( 'inherit' == $revision['post_status'] )
			$last_arg = "&restored_post=$post->ID";
		else
			$last_arg = "&published_post=$post->ID";
			
		//$redirect = add_query_arg( $query_args, get_edit_post_link( $post->ID, 'url' ) );
		$redirect = "admin.php?page=rvy-revisions&revision={$post->ID}&action=view{$last_arg}";
	} while (0);

	if ( ! $redirect ) {
		if ( ! empty($post) && is_object($post) && ( 'post' != $post->post_type ) ) {
			$redirect = "edit.php?post_type={$post->post_type}";
		} else
			$redirect = 'edit.php';
	}

	wp_redirect( $redirect );
	exit;
}


function rvy_do_revision_restore( $revision_id ) {
	global $wpdb;

	$revision = wp_get_post_revision( $revision_id );
	
	//rvy_errlog("restoring $revision_id");
	wp_restore_post_revision($revision_id);
	rvy_update_next_publish_date();
	
	if ( $revision && ! empty($revision->post_parent) ) {
		rvy_format_content( $revision->post_content, $revision->post_content_filtered, $revision->post_parent );
	}
	
	clean_post_cache( $revision_id );
}

function rvy_revision_delete() {
	require_once( ABSPATH . 'wp-admin/admin.php');
	$revision_id = $_GET['revision'];
	$redirect = '';
	
	do {
		if ( ! $revision = wp_get_post_revision( $revision_id ) )
			break;

		if ( ! $post = get_post( $revision->post_parent ) )
			break;

		if ( $type_obj = get_post_type_object( $post->post_type ) ) {
			if ( ! current_user_can( $type_obj->cap->delete_post, $revision->post_parent ) ) {
				global $current_user;
	
				if ( ( 'pending' != $revision->post_status ) || ( $revision->post_author != $current_user->ID ) )	// allow submitters to delete their own still-pending revisions
					break;
			}
		}
		
		check_admin_referer('delete-revision_' .  $revision_id);
		
		// before deleting the revision, note its status for redirect
		$revision_status = $revision->post_status;
		wp_delete_post_revision( $revision_id );
		$redirect = "admin.php?page=rvy-revisions&revision={$revision->post_parent}&action=view&revision_status=$revision_status&deleted=1";
	} while (0);
	
	if ( ! empty( $_GET['return'] ) && ! empty( $_SERVER['HTTP_REFERER'] ) ) {
		$redirect = str_replace( 'trashed=', 'deleted=', $_SERVER['HTTP_REFERER'] );

	} elseif ( ! $redirect ) {
		if ( ! empty($post) && is_object($post) && ( 'post' != $post->post_type ) ) {
			$redirect = "edit.php?post_type={$post->post_type}";
		} else
			$redirect = 'edit.php';
	}

	wp_redirect( $redirect );
	exit;
}


function rvy_revision_bulk_delete() {
	global $current_user;
	require_once( ABSPATH . 'wp-admin/admin.php');

	check_admin_referer( 'rvy-revisions' );
	
	$redirect = '';
	$delete_count = 0;
	$post_id = 0;
	$revision_status = '';
	
	if ( empty($_POST['delete_revisions']) || empty($_POST['delete_revisions']) ) {

		if ( ! empty( $_POST['left'] ) )
			$post_id = 	$_POST['left'];

		elseif ( ! empty( $_POST['right'] ) )
			$post_id = 	$_POST['right'];	

	} else {
		foreach ( $_POST['delete_revisions'] as $revision_id ) {
			if ( ! $revision = wp_get_post_revision( $revision_id ) )
				continue;

			if ( ! $post_id ) {
				if ( $post = get_post( $revision->post_parent ) )
					$post_id = $post->ID;
				else
					continue;
			}

			if ( $post = get_post( $revision->post_parent ) ) {
				if ( $type_obj = get_post_type_object( $post->post_type ) ) {
					if ( ! current_user_can( $type_obj->cap->delete_post, $revision->post_parent ) ) {
						if ( ( 'pending' != $revision->post_status ) || ( $revision->post_author != $current_user->ID ) )	// allow submitters to delete their own still-pending revisions
							continue;
					}
				}
			}
	
			// before deleting the revision, note its status for redirect
			$revision_status = $revision->post_status;
			wp_delete_post_revision( $revision_id );
			$delete_count++;
		}
	}

	$redirect = "admin.php?page=rvy-revisions&revision=$post_id&action=view&revision_status=$revision_status&bulk_deleted=$delete_count";
	
	wp_redirect( $redirect );
	exit;
}

function rvy_revision_edit() {
	require_once( ABSPATH . 'wp-admin/admin.php');
	$post_data = &$_POST;
	$revision_id = $post_data['revision_ID'];
	$redirect = '';
	
	do {
		if ( !$revision = wp_get_post_revision( $revision_id ) )
			break;

		if ( !$post = get_post( $revision->post_parent ) )
			break;
			
		if ( $type_obj = get_post_type_object( $post->post_type ) ) {
			if ( ! agp_user_can( $type_obj->cap->edit_post, $revision->post_parent, '', array( 'skip_revision_allowance' => true ) ) ) {
				global $current_user;

				// post-assigned Revisor role is sufficient to edit others' revisions, but post-assigned Contributor role is not
				if ( isset( $GLOBALS['cap_interceptor'] ) )
					$GLOBALS['cap_interceptor']->require_full_object_role = true;

				$can_edit_others = agp_user_can( $type_obj->cap->edit_others_posts, $revision->post_parent );

				if ( isset( $GLOBALS['cap_interceptor'] ) )
					$GLOBALS['cap_interceptor']->require_full_object_role = false;

				if ( ( 'pending' != $revision->post_status ) || ( ( $revision->post_author != $current_user->ID ) && ! $can_edit_others ) )	// allow submitters to edit their own still-pending revisions
					break;
			}

		}

		
		check_admin_referer('update-revision_' .  $revision_id);
		
		delete_option( 'rvy_next_rev_publish_gmt' );
		
		$post_data['post_content'] = $post_data['content'];
		
		$post_data = sanitize_post($post_data, 'db');
		
		foreach ( array('aa', 'mm', 'jj', 'hh', 'mn') as $timeunit ) {
			if ( !empty( $post_data['hidden_' . $timeunit] ) && $post_data['hidden_' . $timeunit] != $post_data[$timeunit] ) {
				$post_data['edit_date'] = '1';
				break;
			}
		}

		if ( !empty( $post_data['edit_date'] ) ) {
			$aa = $post_data['aa'];
			$mm = $post_data['mm'];
			$jj = $post_data['jj'];
			$hh = $post_data['hh'];
			$mn = $post_data['mn'];
			$ss = $post_data['ss'];
			$aa = ($aa <= 0 ) ? date('Y') : $aa;
			$mm = ($mm <= 0 ) ? date('n') : $mm;
			$jj = ($jj > 31 ) ? 31 : $jj;
			$jj = ($jj <= 0 ) ? date('j') : $jj;
			$hh = ($hh > 23 ) ? $hh -24 : $hh;
			$mn = ($mn > 59 ) ? $mn -60 : $mn;
			$ss = ($ss > 59 ) ? $ss -60 : $ss;
			$post_data['post_date'] = sprintf( "%04d-%02d-%02d %02d:%02d:%02d", $aa, $mm, $jj, $hh, $mn, $ss );
			$post_data['post_date_gmt'] = get_gmt_from_date( $post_data['post_date'] );
		}

		extract( $post_data );

		// If the post date is empty, don't modify post date
		if ( empty($post_date) || '0000-00-00 00:00:00' == $post_date || empty($post_date_gmt) || '0000-00-00 00:00:00' == $post_date_gmt ) {
			unset( $post_date );
			unset( $post_date_gmt );
		}

		$post_modified     = current_time( 'mysql' );
		$post_modified_gmt = current_time( 'mysql', 1 );

		global $current_user;
		$post_author = $current_user->ID;

		global $wpdb;

		// todo: update excerpt, others

		$data = compact( array( 'post_author', 'post_date', 'post_date_gmt', 'post_content', 'post_title', 'post_modified', 'post_modified_gmt' ) );
		//$data = apply_filters(rs_update_revision_data', $data, $postarr);
		$data = stripslashes_deep( $data );
		$where = array( 'ID' => $revision_id );

		//do_action( 'pre_post_update', $post_ID );
		$db_success = $wpdb->update( $wpdb->posts, $data, $where );
		$redirect = "admin.php?page=rvy-revisions&revision=$revision_id&action=view&rvy_updated=$db_success";
		
		rvy_format_content( $post_content, $post_content, $revision_id );
		
		clean_post_cache( $revision_id );
		
		do_action( 'post_revision_update', $revision_id );
		
		//die($redirect);
	} while (0);

	if ( ! $redirect ) {
		if ( ! empty($post) && is_object($post) && ( 'post' != $post->post_type ) ) {
			$redirect = "edit.php?post_type={$post->post_type}";
		} else
			$redirect = 'edit.php';
	}

	wp_redirect( $redirect );
	exit;
}





function rvy_revision_unschedule() {
	require_once( ABSPATH . 'wp-admin/admin.php');
	$revision_id = $_GET['revision'];
	$redirect = '';
	
	do {
		if ( !$revision = wp_get_post_revision( $revision_id ) )
			break;

		if ( !$post = get_post( $revision->post_parent ) )
			break;

		if ( $type_obj = get_post_type_object( $post->post_type ) ) {
			if ( ! agp_user_can( $type_obj->cap->edit_post, $revision->post_parent, '', array( 'skip_revision_allowance' => true ) ) )
				break;
		}
		
		check_admin_referer('unschedule-revision_' .  $revision_id);

		global $wpdb;
		$wpdb->query( "UPDATE $wpdb->posts SET post_status = 'pending' WHERE post_type = 'revision' AND ID = '$revision_id'" );
		
		delete_option( 'rvy_next_rev_publish_gmt' );
		$redirect = "admin.php?page=rvy-revisions&revision=$revision_id&action=view&unscheduled=$revision_id";
	} while (0);
	
	if ( ! $redirect ) {
		if ( ! empty($post) && is_object($post) && ( 'post' != $post->post_type ) ) {
			$redirect = "edit.php?post_type={$post->post_type}";
		} else
			$redirect = 'edit.php';
	}

	wp_redirect( $redirect );
	exit;
}

function rvy_publish_scheduled_revisions() {
	global $wpdb;
	
	rvy_confirm_async_execution( 'publish_scheduled' );
	delete_option( 'rvy_next_rev_publish_gmt' );
	
	$time_gmt = current_time('mysql', 1);
	
	$restored_post_ids = array();
	$skip_revision_ids = array();
	$blogname = wp_specialchars_decode( get_option('blogname'), ENT_QUOTES );

	if ( ! empty( $_GET['rs_debug'] ) )
		echo "current time: $time_gmt";

	if ( $results = $wpdb->get_results( "SELECT * FROM $wpdb->posts WHERE post_type = 'revision' AND post_status = 'future' AND post_date_gmt <= '$time_gmt' ORDER BY post_date_gmt DESC" ) ) {
		foreach ( $results as $row ) {
			if ( ! isset($restored_post_ids[$row->post_parent]) ) {

				// prep the revision to look like a normal one so WP doesn't reject it
				$wpdb->query( "UPDATE $wpdb->posts SET post_status = 'inherit', post_date = '$row->post_modified', post_date_gmt = '$row->post_modified' WHERE post_type = 'revision' AND post_status = 'future' AND ID = '$row->ID'" );
				$func = "rvy_do_revision_restore('{$row->ID}');";
				
				if ( is_admin() ) 
					add_action( 'shutdown', create_function( '', $func ) );
				else
					add_action( 'shutdown', create_function( '', $func ) );

				if ( ! empty( $_GET['rs_debug'] ) )
					echo '<br />' . "publishing revision $row->ID";

				//add_action( 'template_redirect', create_function( '', $func ) );
				$restored_post_ids[$row->post_parent] = true;
				
				$post =& get_post( $row->post_parent );
				
				$type_obj = get_post_type_object( $post->post_type );
				$type_caption = $type_obj->labels->singular_name;
				
				if ( rvy_get_option( 'publish_scheduled_notify_revisor' ) ) {
					$title = sprintf( __('[%s] Scheduled Revision Publication Notice', 'revisionary' ), $blogname );
					$message = sprintf( __('The scheduled revision you submitted for the %1$s "%2$s" has been published.', 'revisionary' ), $type_caption, $row->post_title ) . "\r\n\r\n";

					if ( ! empty($post->ID) )
						$message .= __( 'View it online: ', 'revisionary' ) . get_permalink($post->ID) . "\r\n";

					if ( $author = new WP_User( $row->post_author ) )
						rvy_mail( $author->user_email, $title, $message );
				}

				if ( ( $post->post_author != $revision->post_author ) && rvy_get_option( 'publish_scheduled_notify_author' ) ) {
					$title = sprintf( __('[%s] Scheduled Revision Publication Notice', 'revisionary' ), $blogname );
					$message = sprintf( __('A scheduled revision to your %1$s "%2$s" has been published.', 'revisionary' ), $type_caption, $post->post_title ) . "\r\n\r\n";

					if ( $revisor = new WP_User( $row->post_author ) )
						$message .= sprintf( __('It was submitted by %1$s.'), $revisor->display_name ) . "\r\n\r\n";

					if ( ! empty($post->ID) )
						$message .= __( 'View it online: ', 'revisionary' ) . get_permalink($post->ID) . "\r\n";
						
					if ( $author = new WP_User( $post->post_author ) )
						rvy_mail( $author->user_email, $title, $message );
				}
				

				if ( rvy_get_option( 'publish_scheduled_notify_admin' ) ) {
					$title = sprintf(__('[%s] Scheduled Revision Publication'), $blogname );
					
					$message = sprintf( __('A scheduled revision to the %1$s "%2$s" has been published.'), $type_caption, $row->post_title ) . "\r\n\r\n";

					if ( $author = new WP_User( $row->post_author ) )
						$message .= sprintf( __('It was submitted by %1$s.'), $author->display_name ) . "\r\n\r\n";

					if ( ! empty($post->ID) )
						$message .= __( 'View it online: ', 'revisionary' ) . get_permalink($post->ID) . "\r\n";

					$object_id = ( isset($post) && isset($post->ID) ) ? $post->ID : $row->ID;
					$object_type = ( isset($post) && isset($post->post_type) ) ? $post->post_type : 'post';
					

					// if it was not stored, or cleared, use default recipients
					$to_addresses = array();
					
					if ( defined('RVY_CONTENT_ROLES') && ! defined('SCOPER_DEFAULT_MONITOR_GROUPS') ) { // e-mail to Scheduled Revision Montiors metagroup if Role Scoper is activated
						global $revisionary;
						
						$monitor_groups_enabled = true;
						$revisionary->content_roles->ensure_init();

						if ( $default_ids = $revisionary->content_roles->get_metagroup_members( 'Scheduled Revision Monitors' ) ) {
							if ( $type_obj = get_post_type_object( $object_type ) ) {
								$revisionary->skip_revision_allowance = true;
								$cols = ( defined('COLS_ALL_RS') ) ? COLS_ALL_RS : 'all';
								$post_publishers = $revisionary->content_roles->users_who_can( $type_obj->cap->edit_post, $object_id, array( 'cols' => $cols ) );
								$revisionary->skip_revision_allowance = false;
								
								/*
								foreach( $recipient_ids as $user_id ) {
									$_user = new WP_User($user_id);
									$reqd_caps = map_meta_cap( $type_obj->cap->edit_post, $user_id, $post->ID );
									
									if ( ! array_diff( $reqd_caps, array_keys( array_intersect( $_user->allcaps, array( true, 1, '1' ) ) ) ) ) {
										$post_publishers []= $_user;
									}
								}
								*/
								foreach ( $post_publishers as $user )
									if ( in_array( $user->ID, $default_ids ) )
										$to_addresses []= $user->user_email;
							}
						}
					} 
					
					if ( ! $to_addresses && ( empty($monitor_groups_enabled) || ! defined('RVY_FORCE_MONITOR_GROUPS') ) ) {  // if RS/PP are not active, monitor groups have been disabled or no monitor group members can publish this post...
						$use_wp_roles = ( defined( 'SCOPER_MONITOR_ROLES' ) ) ? SCOPER_MONITOR_ROLES : 'administrator,editor';
						
						$use_wp_roles = str_replace( ' ', '', $use_wp_roles );
						$use_wp_roles = explode( ',', $use_wp_roles );
						
						$recipient_ids = array();

						foreach ( $use_wp_roles as $role_name ) {
							if ( awp_ver( '3.1-beta' ) ) {
								$search = new WP_User_Query( "search=&fields=id&role=$role_name" );
								$recipient_ids = array_merge( $recipient_ids, $search->results );
							} else {
								$search = new WP_User_Search( '', 0, $role_name );
								$recipient_ids = array_merge( $recipient_ids, $search->results );
							}
						}
						
						foreach ( $recipient_ids as $userid ) {
							$user = new WP_User($userid);
							$to_addresses []= $user->user_email;
						}
					}
					
					if ( defined( 'RVY_NOTIFY_SUPER_ADMIN' ) && is_multisite() ) {
						$super_admin_logins = get_super_admins();
						foreach( $super_admin_logins as $user_login ) {
							if ( $super = new WP_User($user_login) )
								$to_addresses []= $super->user_email;
						}
					}
					
					$to_addresses = array_unique( $to_addresses );
					
					//dump($to_addresses);
					
					foreach ( $to_addresses as $address )
						rvy_mail( $address, $title, $message );
				}
				
				
			} else {
				$skip_revision_ids[$row->ID] = true;
			}
		}
		
		if ( $skip_revision_ids ) {
			// if more than one scheduled revision was not yet published, convert the older ones to regular revisions
			$id_clause = "AND ID IN ('" . implode( "','", array_keys($skip_revision_ids) ) . "')";
			$wpdb->query( "UPDATE $wpdb->posts SET post_status = 'inherit' WHERE post_type = 'revision' AND post_status = 'future' $id_clause" );
		}
	}

	rvy_update_next_publish_date();
	
	// if this was initiated by an asynchronous remote call, we're done.
	if ( ! empty( $_GET['action']) && ( 'publish_scheduled' == $_GET['action'] ) )
		exit( 0 );

}

function rvy_update_next_publish_date() {
	global $wpdb;
	
	if ( ! $next_publish_date_gmt = $wpdb->get_var( "SELECT post_date_gmt FROM $wpdb->posts WHERE post_type = 'revision' AND post_status = 'future' ORDER BY post_date_gmt ASC LIMIT 1" ) )
		$next_publish_date_gmt = '2035-01-01 00:00:00';

	update_option( 'rvy_next_rev_publish_gmt', $next_publish_date_gmt );
}

function rvy_review_revision( $revision_id ) {
	if ( class_exists('WPCom_Markdown') && ! defined( 'RVY_DISABLE_MARKDOWN_WORKAROUND' ) ) {
		$revision = wp_get_post_revision( $revision_id );
		if ( ! $revision->post_content_filtered ) {
			if ( $post = get_post( $revision->post_parent ) ) {
				global $wpdb;
				$wpdb->update( $wpdb->posts, array( 'post_content_filtered' => $post->post_content_filtered ), array( 'ID' => $revision_id ) );
			}
		}
	}
}

// apply any necessary third-party transformations to post content after publishing a revision
function rvy_format_content( $content, $content_filtered, $post_id, $args = array() ) {
	$defaults = array( 'update_db' => true );
	$args = array_merge( $defaults, $args );
	$args = apply_filters( 'rvy_format_content_args', $args, $post_id );
	extract( $args, EXTR_SKIP );
	
	if ( ! $content_filtered )
		$content_filtered = $content;
	
	$formatted_content = $content;
	
	if ( class_exists('WPCom_Markdown') && ! defined( 'RVY_DISABLE_MARKDOWN_WORKAROUND' ) ) {
		$wpcmd = WPCom_Markdown::get_instance();
		
		if ( method_exists( $wpcmd, 'transform' ) ) {
			$formatted_content = $wpcmd->transform( $content_filtered, array( 'post_id' => $post_id ) );
			
			if ( $update_db ) {
				global $wpdb;
				$wpdb->update( $wpdb->posts, array( 'post_content' => $formatted_content, 'post_content_filtered' => $content_filtered ), array( 'ID' => $post_id ) );
			}
		}
	}

	$formatted_content = apply_filters( 'rvy_formatted_content', $formatted_content, $post_id, $content, $args );
	
	return $formatted_content;
}
?>