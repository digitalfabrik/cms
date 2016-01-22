<?php
if( basename(__FILE__) == basename($_SERVER['SCRIPT_FILENAME']) )
	die( 'This page cannot be called directly.' );
	
/**
 * Revisionary PHP class for the WordPress plugin Revisionary
 * revisionary_main.php
 * 
 * @author 		Kevin Behrens
 * @copyright 	Copyright 2009-2013
 * 
 */
class Revisionary
{		
	var $admin;					// object ref - RevisionaryAdmin
	var $filters_admin_item_ui; // object ref - RevisionaryAdminFiltersItemUI
	var $skip_revision_allowance = false;
	var $content_roles;			// object ref - instance of RevisionaryContentRoles subclass, set by external plugin
	
	// minimal config retrieval to support pre-init usage by WP_Scoped_User before text domain is loaded
	function Revisionary() {
		rvy_refresh_options_sitewide();
		
		// NOTE: $_GET['preview'] and $_GET['post_type'] arguments are set by rvy_init() at response to ?p= request when the requested post is a revision.
		if ( ! is_admin() && ( ! empty( $_GET['preview'] ) || ! empty( $_GET['mark_current_revision'] ) ) && empty($_GET['preview_id']) ) { // preview_id indicates a regular preview via WP core, based on autosave revision
			require_once( dirname(__FILE__).'/front_rvy.php' );
			$this->front = new RevisionaryFront();
		}
			
		if ( ! is_content_administrator_rvy() ) {
			add_filter( 'user_has_cap', array( &$this, 'flt_user_has_cap' ), 98, 3 );
			add_filter( 'pp_has_cap_bypass', array( &$this, 'flt_has_cap_bypass' ), 10, 4 );
			
			add_filter( 'map_meta_cap', array( &$this, 'flt_limit_others_drafts' ), 10, 4 );
			//add_filter( 'posts_where', array( &$this, 'flt_posts_where' ), 1 );
		}
		
		if ( is_admin() ) {
			require_once( dirname(__FILE__).'/admin/admin_rvy.php');
			$this->admin = new RevisionaryAdmin();
		}	
		
		add_action( 'wpmu_new_blog', array( &$this, 'act_new_blog'), 10, 2 );
		
		add_filter( 'posts_results', array( &$this, 'inherit_status_workaround' ) );
		add_filter( 'the_posts', array( &$this, 'undo_inherit_status_workaround' ) );
	
		add_action( 'wp_loaded', array( &$this, 'set_revision_capdefs' ) );
		
		do_action( 'rvy_init' );
	}
	
	// prevent revisors from editing other users' regular drafts and pending posts
	function flt_limit_others_drafts( $caps, $meta_cap, $user_id, $args ) {
		if ( ! in_array( $meta_cap, array( 'edit_post', 'edit_page' ) ) )
			return $caps;
		
		$object_id = ( is_array($args) && ! empty($args[0]) ) ? $args[0] : $args;
		
		if ( ! $object_id || ! is_scalar($object_id) || ( $object_id < 0 ) )
			return $caps;
		
		if ( ! rvy_get_option( 'require_edit_others_drafts' ) )
			return $caps;
		
		if ( $post = get_post( $object_id ) ) {
			if ( 'revision' != $post->ID ) {
				global $current_user;
			
				$status_obj = get_post_status_object( $post->post_status );
			
				if ( ( $current_user->ID != $post->post_author ) && $status_obj && ! $status_obj->public && ! $status_obj->private ) {
					$post_type_obj = get_post_type_object( $post->post_type );
					if ( current_user_can( $post_type_obj->cap->edit_published_posts ) ) {	// don't require any additional caps for sitewide Editors
						return $caps;
					}
				
					static $stati;
					static $private_stati;
				
					if ( ! isset($public_stati) ) {
						$stati = get_post_stati( array( 'internal' => false, 'protected' => true ) );
						$stati = array_diff( $stati, array( 'future' ) );
					}
					
					if ( in_array( $post->post_status, $stati ) ) {
						//if ( $post_type_obj = get_post_type_object( $post->post_type ) ) {
							$caps[]= "edit_others_drafts";
						//}
					}
				}
			}
		}
		
		return $caps;
	}
	
	function set_content_roles( $content_roles_obj ) {
		$this->content_roles = $content_roles_obj;

		if ( ! defined( 'RVY_CONTENT_ROLES' ) )
			define( 'RVY_CONTENT_ROLES', true );
	}
	
	// we generally want Revisors to edit other users' posts, but not other users' revisions
	function set_revision_capdefs() {
		global $wp_post_types;
		if ( 'edit_others_posts' == $wp_post_types['revision']->cap->edit_others_posts ) {
			$wp_post_types['revision']->cap->edit_others_posts = 'edit_others_revisions';
			//$wp_post_types['revision']->cap->delete_others_posts = 'delete_others_revisions';
		}
	}
	
	// work around WP 3.2 query_posts behavior (won't allow preview on posts unless status is public, private or protected)
	function inherit_status_workaround( $results ) {
		if ( isset( $this->orig_inherit_protected_value ) )
			return $results;
		
		$this->orig_inherit_protected_value = $GLOBALS['wp_post_statuses']['inherit']->protected;
		
		$GLOBALS['wp_post_statuses']['inherit']->protected = true;
		return $results;
	}
	
	function undo_inherit_status_workaround( $results ) {
		if ( ! empty( $this->orig_inherit_protected_value ) )
			$GLOBALS['wp_post_statuses']['inherit']->protected = $this->orig_inherit_protected_value;
		
		return $results;
	}
	
	function act_new_blog( $blog_id, $user_id ) {
		rvy_add_revisor_role( $blog_id );
	}
	
	function flt_has_cap_bypass( $bypass, $wp_sitecaps, $pp_reqd_caps, $args ) {
		if ( ! $GLOBALS['pp_attributes']->is_metacap( $args[0] ) && ( ! array_intersect( $pp_reqd_caps, array_keys($GLOBALS['pp_attributes']->condition_cap_map) )
		|| ( is_admin() && strpos( $_SERVER['SCRIPT_NAME'], 'p-admin/post.php' ) && ! is_array($args[0]) && ( false !== strpos( $args[0], 'publish_' ) && empty( $_REQUEST['publish'] ) ) ) )
		) {						// TODO: simplify (Press Permit filter for publish_posts cap check which determines date selector visibility)
			return $wp_sitecaps;
		}

		return $bypass;
	}
	
	function flt_user_has_cap($wp_blogcaps, $reqd_caps, $args)	{
		if ( ! rvy_get_option('pending_revisions') )
			return $wp_blogcaps;

		$script_name = $_SERVER['SCRIPT_NAME'];
		
		if ( ( defined( 'PP_VERSION' ) || defined( 'PPC_VERSION' ) ) && strpos( $script_name, 'p-admin/post.php' ) ) {
			$support_publish_cap = empty( $_REQUEST['publish'] ) && ! is_array($args[0]) && ( false !== strpos( $args[0], 'publish_' ) );  // TODO: support custom publish cap prefix without perf hit?
		}
		
		if ( ! in_array( $args[0], array( 'edit_post', 'edit_page', 'delete_post', 'delete_page' ) ) && empty($support_publish_cap) )
			return $wp_blogcaps;

		// integer value indicates internally triggered on previous execution of this filter
		if ( 1 === $this->skip_revision_allowance ) {
			$this->skip_revision_allowance = false;
		}
		
		if ( ! empty($args[2]) )
			$post_id = $args[2];
		else
			$post_id = rvy_detect_post_id();

		if ( $post = get_post( $post_id ) )
			$object_type = $post->post_type;
		else
			$object_type = awp_post_type_from_uri();
			
		if ( rvy_get_option( 'revisor_lock_others_revisions' ) ) {
			if ( $post ) {
				// Revisors are enabled to edit other users' posts for revision, but cannot edit other users' revisions unless cap is explicitly set sitewide
				if ( ( 'revision' == $post->post_type ) && ! $this->skip_revision_allowance ) {
					if ( $post->post_author != $GLOBALS['current_user']->ID ) {
						if ( empty( $GLOBALS['current_user']->allcaps['edit_others_revisions'] ) ) {
							$this->skip_revision_allowance = 1;
						}
					}
				}

				if ( 'revision' == $post->post_type ) {  // Role Scoper / Press Permit may have already done this
					$object_type = get_post_field( 'post_type', $post->post_parent );
				}
			}
		} elseif ( 'revision' == $object_type ) {
			if ( $post )
				$object_type = get_post_field( 'post_type', $post->post_parent );
		}
		
		$object_type_obj = get_post_type_object( $object_type );
		
		if ( empty( $object_type_obj->cap ) )
			return $wp_blogcaps;
		
		$cap = $object_type_obj->cap;
		
		$edit_published_cap = ( isset($cap->edit_published_posts) ) ? $cap->edit_published_posts : "edit_published_{$object_type}s";
		$edit_private_cap = ( isset($cap->edit_private_posts) ) ? $cap->edit_private_posts : "edit_private_{$object_type}s";

		if ( ! $this->skip_revision_allowance ) {
			// Allow Contributors / Revisors to edit published post/page, with change stored as a revision pending review
			//$replace_caps = apply_filters( 'rvy_replace_post_edit_caps', array( 'edit_published_posts', 'edit_private_posts', $edit_published_cap, $edit_private_cap ), $object_type, $post_id );
			$replace_caps = array( 'edit_published_posts', $edit_published_cap, 'edit_private_posts', $edit_private_cap );
			
			if ( ! strpos( $script_name, 'p-admin/edit.php' ) )
				$replace_caps = array_merge( $replace_caps, array( $cap->publish_posts, 'publish_posts' ) );

			if ( array_intersect( $reqd_caps, $replace_caps) ) {	// don't need to fudge the capreq for post.php unless existing post has public/private status
				/*
				$post_status = get_post_field('post_status', $post_id );
				$post_status_obj = get_post_status_object( $post_status );
				
				if ( is_preview() || strpos($script_name, 'p-admin/edit.php') || strpos($script_name, 'p-admin/widgets.php') || ( $post_status_obj && ( $post_status_obj->public || $post_status_obj->private ) ) ) {
				*/				

				if ( is_preview() || strpos($script_name, 'p-admin/edit.php') || strpos($script_name, 'p-admin/widgets.php') || ( in_array( get_post_field('post_status', $post_id ), array('publish', 'private') ) ) ) {
					if ( $type_obj = get_post_type_object( $object_type ) ) {
						if ( ! empty( $wp_blogcaps[ $type_obj->cap->edit_posts ] ) ) {
							foreach ( $replace_caps as $replace_cap_name )
								$wp_blogcaps[$replace_cap_name] = true;
						}
					}
				}
			}
		}
		
		// Special provision for Pages as of WP 2.8.4 (may become unnecessary in future WP versions)
		if ( is_admin() && in_array( 'edit_others_posts', $reqd_caps ) && ( 'post' != $object_type ) ) {
			// Allow contributors to edit published post/page, with change stored as a revision pending review
			if ( ! rvy_metaboxes_started() && ! strpos($script_name, 'p-admin/revision.php') && false === strpos(urldecode($_SERVER['REQUEST_URI']), 'admin.php?page=rvy-revisions' )  ) // don't enable contributors to view/restore revisions
				$use_cap_req = $cap->edit_posts;
			else
				$use_cap_req = $edit_published_cap;
				
			if ( ! empty( $wp_blogcaps[$use_cap_req] ) )
				$wp_blogcaps['edit_others_posts'] = true;
		}

		// TODO: possible need to redirect revision cap check to published parent post/page ( RS cap-interceptor "maybe_revision" )
		return $wp_blogcaps;			
	}
	
} // end Revisionary class
?>