<?php

class RevisionaryFront {

	function RevisionaryFront() {
		if ( ! defined('RVY_CONTENT_ROLES') || ! $GLOBALS['revisionary']->content_roles->is_direct_file_access() ) {
			add_filter( 'posts_request', array( &$this, 'flt_view_revision' ) );
			add_action( 'template_redirect', array( &$this, 'act_template_redirect' ) );
		}

		add_action( 'template_redirect', array(&$this, 'builder_revision_workaround'), -20 );
		
		if ( ! empty($_REQUEST['preview']) )
			add_filter( 'get_post_metadata', array(&$this, 'return_parent_metadata'), 10, 4 );
	}
	
	function return_parent_metadata( $val, $object_id, $meta_key, $single ) {
		if ( $_post = get_post( $object_id ) ) {
			if ( 'revision' == $_post->post_type ) {
				if ( $_post->post_parent ) {
					return get_post_meta( $_post->post_parent, $meta_key, $single );
				}
			}
		}
		
		return $val;
	}
	
	// fixes Builder theme conflict with Revisionary (previews of pending revisions to pages could not be displayed)
	function builder_revision_workaround() {
		if ( ! empty($_REQUEST['preview']) && ! empty($_REQUEST['post_type']) && ( 'revision' == $_REQUEST['post_type'] ) ) {
			global $wp_query;
			if ( ! empty( $wp_query->query_vars['p'] ) ) {
				if ( $_post = get_post( $wp_query->query_vars['p'] ) ) {
					if ( $type = get_post_field( 'post_type', $_post->post_parent ) ) {
						if ( $type_obj = get_post_type_object( $type ) ) {
							if ( $type_obj->hierarchical ) {
								$wp_query->is_page = true;
								$wp_query->is_single = false;
							}
						}
					}
				}
			}
		}
	}

	function act_template_redirect() {
		if ( ! empty($_GET['p']) )
			$id = $_GET['p'];
		else {
			global $wp_query;
			if ( ! empty($wp_query->queried_object_id) )
				$id = $wp_query->queried_object_id;
		}
	
		if ( $revision = get_post( $id ) )
			if ( $parent = get_post( $revision->post_parent ) )
				if ( ( 'page' == $parent->post_type ) && ( $parent->post_name == $revision->post_name ) ) {
					global $wp_query;
					$wp_query->is_page = true;
					$wp_query->is_single = false;

					add_filter( 'page_template', array( &$this, 'flt_revision_page_template' ) );	// todo: pass parent ID so filter doesn't have to redetermine it
				} else
					add_filter( 'single_template', array( &$this, 'flt_revision_post_template' ) );	// todo: pass parent ID so filter doesn't have to redetermine it

	}	
	
	function flt_revision_post_template( $single_template, $id = 0 ) {
		if ( ! $id ) {
			if ( ! empty($_GET['p']) )
				$id = $_GET['p'];
			else {
				global $wp_query;
				if ( ! empty($wp_query->queried_object_id) )
					$id = $wp_query->queried_object_id;
			}
		}
		
		if ( $id ) {
			$revision = get_post( $id );

			// support custom_post_template entry (as set by Custom Post Templates plugin)
			if ( ! $template_file = (string) get_post_meta($id, '_custom_post_template', true) )
				$template_file = (string) get_post_meta($id, 'custom_post_template', true);	// Custom Post Templates 0.92 uses this string, which leaves entry visible in Custom Fields UI

			if ( ! $template_file ) {
				if ( $revision ) {
					if ( ! $template_file = (string) get_post_meta($revision->post_parent, '_custom_post_template', true) )
						$template_file = (string) get_post_meta($revision->post_parent, 'custom_post_template', true);	
				}
			}
			
			if ( $template_file ) {
				$custom_template = TEMPLATEPATH . "/" . $template_file;

				if ( file_exists( $custom_template ) ) 
					return $custom_template;
			} 
			elseif ( $revision ) {
				$parent = get_post( $revision->post_parent );

				if ( $parent && ( 'post' != $parent->post_type ) ) {
					$templates = array( 'single-' . $parent->post_type . '.php', 'single.php' );
					$single_template = locate_template( $templates );	
				}
			}
		}

		return $single_template;	
	}
	
	function flt_revision_page_template( $page_template, $id = 0 ) {
		if ( ! $id ) {
			if ( ! empty($_GET['p']) )
				$id = $_GET['p'];
			else {
				global $wp_query;
				if ( ! empty($wp_query->queried_object_id) )
					$id = $wp_query->queried_object_id;
			}
		}
		
		if ( $id ) {
			if ( ! $template = get_post_meta($id, '_wp_page_template', true) ) {
				if ( $revision = get_post( $id ) ) {
					$template = get_post_meta($revision->post_parent, '_wp_page_template', true);
				}
			}

			// this code ported from wp-includes/theme/get_page_template() :
			if ( 'default' == $template )
				$template = '';

			$templates = array();
			if ( !empty($template) && !validate_file($template) )
				$templates[] = $template;

			$templates[] = "page.php";
			return locate_template($templates);
		}
		
		return $page_template;	
	}
	
	
	// allows for front-end viewing of a revision by those who can edit the current revision (also needed for post preview by users editing for pending revision)
	function flt_view_revision( $request ) {
		if ( is_admin() )
			return $request;
			
		// rvy_list_post_revisions passes these args
		if(  ! empty( $_GET['post_type'] ) && ( 'revision' == $_GET['post_type'] ) && ! empty( $_GET['p'] ) ) {
			$revision_id = $_GET['p'];
			if ( $revision = get_post( $revision_id ) ) {
				
				$datef = __awp( 'M j, Y @ G:i' );
				$date = agp_date_i18n( $datef, strtotime( $revision->post_date ) );

				$color = '#ccc';
				
				$parent = get_post( $revision->post_parent );

				if ( in_array( $revision->post_status, array( 'pending', 'future', 'inherit' ) ) )
					$published_post_id = $revision->post_parent;
				

				// This topbar is presently only for those with restore / approve / publish rights
				if ( $type_obj = get_post_type_object( $parent->post_type ) )
					$cap_name = $type_obj->cap->edit_post;	

				$orig_skip = ! empty( $GLOBALS['revisionary']->skip_revision_allowance );
				$GLOBALS['revisionary']->skip_revision_allowance = true;
				
				if ( agp_user_can( $cap_name, $revision->post_parent, '', array( 'skip_revision_allowance' => true ) ) ) {
					load_plugin_textdomain('revisionary', false, RVY_FOLDER . '/languages');
					
					switch ( $revision->post_status ) {
					case 'publish' :
					case 'private' :
						$class = 'published';
						$link_caption = sprintf( __( 'This Revision was Published on %s', 'revisionary' ), $date );
						break;
					case 'pending' :
						if ( strtotime( $revision->post_date_gmt ) > agp_time_gmt() ) {
							$class = 'pending_future';
							$date_msg = sprintf( __('(for publication on %s)', 'revisionary'), $date );
							$link_caption = sprintf( __( 'Schedule this Pending Revision', 'revisionary' ), $date );
						} else {
							$class = 'pending';
							$date_msg = '';
							$link_caption = __( 'Publish this Pending Revision now.', 'revisionary' );
						}
						break;
						
					case 'future' :
						$class = 'future';
						$date_msg = sprintf( __('(already scheduled for publication on %s)', 'revisionary'), $date );
						$link_caption = sprintf( __( 'Publish now.', 'revisionary' ), $date );
						break;
	
					case 'inherit' :
						$class = 'past';
						$date_msg = sprintf( __('(dated %s)', 'revisionary'), $date );
						$link_caption = sprintf( __( 'Restore this Past Revision', 'revisionary' ), $date_msg );
						break;
					}

					if ( in_array( $revision->post_status, array( 'pending' ) ) ) {
						$link = wp_nonce_url( 'wp-admin/' . "admin.php?page=rvy-revisions&amp;revision=$revision_id&amp;diff=false&amp;action=approve", "approve-post_$published_post_id|$revision_id" );
					
					} elseif ( in_array( $revision->post_status, array( 'inherit', 'future' ) ) ) {
						$link = wp_nonce_url( 'wp-admin/' . "admin.php?page=rvy-revisions&amp;revision=$revision_id&amp;diff=false&amp;action=restore", "restore-post_$published_post_id|$revision_id" );
					
					} else
						$link = '';
	
					add_action( 'wp_head', 'rvy_front_css' );
	
					$html = '<div class="rvy_view_revision rvy_view_' . $class . '">' . '<span class="rvy_preview_linkspan"><a href="' . $link . '">' . $link_caption . '</a><span class="rvy_rev_datemsg">'. "$date_msg</span></span></div>";
	
					add_action( 'wp_head', create_function( '', "echo('". $html . "');" ), 99 );	// this should be inserted at the top of <body> instead, but currently no way to do it 
				}
				
				$GLOBALS['revisionary']->skip_revision_allowance = $orig_skip;
			}	
				
		} elseif( ! empty( $_GET['mark_current_revision'] ) ) {
			global $wp_query;
			if ( ! empty($wp_query->queried_object_id) ) {
				load_plugin_textdomain('revisionary', false, RVY_FOLDER . '/languages');
				
				$link_caption = __( 'Current Revision', 'revisionary' );
			
				$link = get_edit_post_link($wp_query->queried_object_id, 'url');

				add_action( 'wp_head', 'rvy_front_css' );

				$html = '<div class="preview_approval_rvy"><span class="rvy_preview_linkspan"><a href="' . $link . '">' . $link_caption . '</a></span></div>';
				add_action( 'template_redirect', create_function( '', "echo('". $html . "');" ) );
			}
		// WP post/page preview passes this arg
		} elseif ( ! empty( $_GET['preview_id'] ) ) {
			$published_post_id = $_GET['preview_id'];
			
			remove_filter( 'posts_request', array( &$this, 'flt_view_revision' ) ); // no infinite recursion!

			if ( $preview = wp_get_post_autosave($published_post_id) )
				$request = str_replace( "ID = '$published_post_id'", "ID = '$preview->ID'", $request );
				
			add_filter( 'posts_request', array( &$this, 'flt_view_revision' ) );
		} else
			return $request;

		
		if ( isset($published_post_id) ) {
			if ( $pub_post = get_post($published_post_id) ) {
				if ( $type_obj = get_post_type_object( $pub_post->post_type ) ) {
					$test_id = ( ! empty($revision_id) ) ? $revision_id : $published_post_id;

					if ( current_user_can( $type_obj->cap->edit_post, $test_id ) ) {
						$request = str_replace( "post_type = 'post'", "post_type = 'revision'", $request );
						$request = str_replace( "post_type = '{$pub_post->post_type}'", "post_type = 'revision'", $request );
					}
				}
			}
		}

		return $request;
	}

}


function rvy_front_css() {
	$wp_content = ( is_ssl() || ( is_admin() && defined('FORCE_SSL_ADMIN') && FORCE_SSL_ADMIN ) ) ? str_replace( 'http:', 'https:', WP_CONTENT_URL ) : WP_CONTENT_URL;
	$path = $wp_content . '/plugins/' . RVY_FOLDER;
	
	echo '<link rel="stylesheet" href="' . $path . '/revisionary-front.css" type="text/css" />'."\n";
}

?>