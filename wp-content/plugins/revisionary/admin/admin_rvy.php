<?php
/**
 * admin_rvy.php
 * 
 * @author 		Kevin Behrens
 * @copyright 	Copyright 2011-2015
 * 
 */

// menu icons by Jonas Rask: http://www.jonasraskdesign.com/

if( basename(__FILE__) == basename($_SERVER['SCRIPT_FILENAME']) )
	die();

$wp_content = ( is_ssl() || ( is_admin() && defined('FORCE_SSL_ADMIN') && FORCE_SSL_ADMIN ) ) ? str_replace( 'http:', 'https:', WP_CONTENT_URL ) : WP_CONTENT_URL;
define ('RVY_URLPATH', $wp_content . '/plugins/' . RVY_FOLDER);

include_once( ABSPATH . '/wp-admin/includes/plugin.php' );
define( 'RVY_NETWORK', awp_is_mu() && is_plugin_active_for_network( RVY_BASENAME ) );

class RevisionaryAdmin
{
	var $tinymce_readonly;

	var $revision_save_in_progress;
	var $impose_pending_rev;
	
	function RevisionaryAdmin() {
		add_action('admin_head', array(&$this, 'admin_head'));
		
		if ( ! defined('XMLRPC_REQUEST') && ! strpos($_SERVER['SCRIPT_NAME'], 'p-admin/async-upload.php' ) ) {
			if ( RVY_NETWORK ) {
				require_once( dirname(__FILE__).'/admin_lib-mu_rvy.php' );
				add_action('network_admin_menu', 'rvy_mu_site_menu' );
			}
			
			add_action('admin_menu', array(&$this,'build_menu'));
			
			if ( strpos($_SERVER['SCRIPT_NAME'], 'p-admin/plugins.php') )
				add_filter( 'plugin_row_meta', array(&$this, 'flt_plugin_action_links'), 10, 2 );
		}
		
		add_action('admin_footer-edit.php', array(&$this, 'act_hide_quickedit_for_revisions') );
		add_action('admin_footer-edit-pages.php', array(&$this, 'act_hide_quickedit_for_revisions') );
		
		add_action('admin_head', array(&$this, 'add_editor_ui') );
		add_action('admin_head', array(&$this, 'act_hide_admin_divs') );
		
		if ( ! ( defined( 'SCOPER_VERSION' ) || defined( 'PP_VERSION' ) || defined( 'PPCE_VERSION' ) ) || defined( 'USE_RVY_RIGHTNOW' ) )
			require_once( 'admin-dashboard_rvy.php' );
		
		// log this action so we know when to ignore the save_post action
		add_action('inherit_revision', array(&$this, 'act_log_revision_save') );

		add_action('pre_post_type', array(&$this, 'flt_detect_revision_save') );
	
		if ( rvy_get_option( 'pending_revisions' ) ) {
			if ( strpos( $_SERVER['SCRIPT_NAME'], 'p-admin/edit.php') 
			|| strpos( $_SERVER['SCRIPT_NAME'], 'p-admin/edit-pages.php')
			|| ( strpos( $_SERVER['SCRIPT_NAME'], 'p-admin/post.php') )
			|| ( strpos( $_SERVER['SCRIPT_NAME'], 'p-admin/page.php') )
			|| false !== strpos( urldecode($_SERVER['REQUEST_URI']), 'admin.php?page=rvy-revisions')
			) {
				add_filter( 'the_title', array(&$this, 'flt_post_title'), 10, 2 );
				add_filter( 'get_edit_post_link', array(&$this, 'flt_edit_post_link'), 10, 3 );
				add_filter( 'get_delete_post_link', array(&$this, 'flt_delete_post_link'), 10, 1 );  // note: WP 2.9 does not return id argument as 2nd variable
				add_filter( 'post_link', array(&$this, 'flt_preview_post_link'), 10, 2 );
				
				add_filter( 'page_row_actions', array(&$this, 'add_preview_action'), 10, 2 );
				add_filter( 'post_row_actions', array(&$this, 'add_preview_action'), 10, 2 );
			}
			
			// special filtering to support Contrib editing of published posts/pages to revision
			add_filter('pre_post_status', array(&$this, 'flt_pendingrev_post_status') );
			add_action('pre_post_update', array(&$this, 'act_impose_pending_rev'), 2 );
		}
		
		if ( rvy_get_option('scheduled_revisions') ) {
			// users who have edit_published capability for post/page can create a scheduled revision by modifying post date to a future date (without setting "future" status explicitly)
			add_filter( 'wp_insert_post_data', array(&$this, 'flt_insert_post_data'), 99, 2 );
			add_action('pre_post_update', array(&$this, 'act_create_scheduled_rev'), 3 );  // other filters will have a chance to apply at actual publish time
		}

		$script_name = $_SERVER['SCRIPT_NAME'];
		
		// ===== Special early exit if this is a plugin install script
		if ( strpos($script_name, 'p-admin/plugins.php') || strpos($script_name, 'p-admin/plugin-install.php') || strpos($script_name, 'p-admin/plugin-editor.php') )
			return; // no further filtering on WP plugin maintenance scripts
		
		// low-level filtering for miscellaneous admin operations which are not well supported by the WP API
		$hardway_uris = array(
		'p-admin/index.php',		'p-admin/revision.php',			'admin.php?page=rvy-revisions',
		'p-admin/post.php', 		'p-admin/post-new.php', 		'p-admin/page.php', 		'p-admin/page-new.php', 
		'p-admin/link-manager.php', 'p-admin/edit.php', 			'p-admin/edit-pages.php', 	'p-admin/edit-comments.php', 
		'p-admin/categories.php', 	'p-admin/link-category.php', 	'p-admin/edit-link-categories.php', 'p-admin/upload.php',
		'p-admin/edit-tags.php', 	'p-admin/profile.php',			'p-admin/link-add.php',	'p-admin/admin-ajax.php' );

		$hardway_uris = apply_filters('rvy_admin_hardway_uris', $hardway_uris);

		$uri = urldecode($_SERVER['REQUEST_URI']);
		foreach ( $hardway_uris as $uri_sub ) {	// index.php can only be detected by index.php, but 3rd party-defined hooks may include arguments only present in REQUEST_URI
			if ( defined('XMLRPC_REQUEST') || strpos($script_name, $uri_sub) || strpos($uri, $uri_sub) ) {
				require_once(RVY_ABSPATH . '/hardway/hardway-admin_rvy.php');
				break;
			}
		}
		
		if ( strpos( $_SERVER['REQUEST_URI'], 'edit.php' ) )
			add_filter( 'get_post_time', array(&$this, 'flt_get_post_time'), 10, 3 );

		add_action( 'post_submitbox_start', array( &$this, 'pending_rev_checkbox' ) );
		add_action( 'post_submitbox_misc_actions', array( &$this, 'publish_metabox_time_display' ) );
	}
	
	function add_preview_action( $actions, $post ) {
		if ( 'revision' == $post->post_type ) {
			if ( current_user_can( 'edit_post', $post->ID ) )
				$actions['view'] = $actions['view'] = '<a href="' . esc_url( add_query_arg( 'preview', '1', get_permalink( $post->ID ) . '&post_type=revision' ) ) . '" title="' . esc_attr( sprintf( __( 'Preview &#8220;%s&#8221;' ), $post->post_title ) ) . '" rel="permalink">' . __( 'Preview' ) . '</a>';
		}
		
		return $actions;
	}
		
	function add_editor_ui() {
		if ( in_array( $GLOBALS['pagenow'], array( 'post.php', 'post-new.php' ) ) ) {
			global $post;
			if ( $post ) {
				$status_obj = get_post_status_object( $post->post_status );

				// only apply revisionary UI for currently published or scheduled posts
				if ( $status_obj->public || $status_obj->private || ( 'future' == $post->post_status ) ) {
					require_once( dirname(__FILE__).'/filters-admin-ui-item_rvy.php' );
					$GLOBALS['revisionary']->filters_admin_item_ui = new RevisionaryAdminFiltersItemUI();
				}
			}
		}	
	}
	
	function publish_metabox_time_display() {
		global $post, $action;
	
		if ( ! rvy_get_option( 'scheduled_revisions' ) )
			return;
	
		$stati = get_post_stati( array( 'public' => true, 'private' => true ), 'names', 'or' );
	
		if ( empty($post) || ! $type_obj = get_post_type_object($post->post_type) )
			return;
	
		if ( ! empty($post) && in_array( $post->post_status, $stati ) && ! current_user_can( $type_obj->cap->publish_posts ) ) :
			$datef = __( 'M j, Y @ G:i' );
			if ( 0 != $post->ID ) {
				if ( 'future' == $post->post_status ) { // scheduled for publishing at a future date
					$stamp = __('Scheduled for: <b>%1$s</b>');
				} else if ( 'publish' == $post->post_status || 'private' == $post->post_status ) { // already published
					$stamp = __('Published on: <b>%1$s</b>');
				} else if ( '0000-00-00 00:00:00' == $post->post_date_gmt ) { // draft, 1 or more saves, no date specified
					$stamp = __('Publish <b>immediately</b>');
				} else if ( time() < strtotime( $post->post_date_gmt . ' +0000' ) ) { // draft, 1 or more saves, future date specified
					$stamp = __('Schedule for: <b>%1$s</b>');
				} else { // draft, 1 or more saves, date specified
					$stamp = __('Publish on: <b>%1$s</b>');
				}
				$date = date_i18n( $datef, strtotime( $post->post_date ) );
			} else { // draft (no saves, and thus no date specified)
				$stamp = __('Publish <b>immediately</b>');
				$date = date_i18n( $datef, strtotime( current_time('mysql') ) );
			}
			?>
			<div class="misc-pub-section curtime">
				<span id="timestamp">
				<?php printf($stamp, $date); ?></span>
				<a href="#edit_timestamp" class="edit-timestamp hide-if-no-js"><?php _e('Edit') ?></a>
				<div id="timestampdiv" class="hide-if-js"><?php touch_time(($action == 'edit'), 1); ?></div>
			</div><?php // /misc-pub-section ?>
		<?php endif;
	}
	
	function pending_rev_checkbox() {
		global $post;

		$status_obj = get_post_status_object( $post->post_status );
		
		if ( ! $status_obj || ( ! $status_obj->public && ! $status_obj->private && ( 'future' != $post->post_status ) ) )
			return;

		if ( $type_obj = get_post_type_object( $post->post_type ) ) {
			if ( ! agp_user_can( $type_obj->cap->edit_post, $post->ID, '', array( 'skip_revision_allowance' => true ) ) )
				return;
		}

		$caption = __( 'save as pending revision', 'revisionary' );
		
		$float = ( awp_ver('3.5') || $GLOBALS['is_IE'] ) ? '' : 'float:right; ';
		echo "<div style='{$float}margin: 0.5em'><label for='rvy_save_as_pending_rev'><input type='checkbox' style='width: 1em; min-width: 1em; text-align: right;' name='rvy_save_as_pending_rev' value='1' id='rvy_save_as_pending_rev' /> $caption</label></div>";
	}
	
	function act_log_revision_save() {
		$this->revision_save_in_progress = true;
	}
	
	function flt_detect_revision_save( $post_type ) {
		if ( 'revision' == $post_type )
			$this->revision_save_in_progress = true;
	
		return $post_type;
	}
	
	// adds an Options link next to Deactivate, Edit in Plugins listing
	function flt_plugin_action_links($links, $file) {
		if ( $file == RVY_BASENAME ) {
			$links[] = "<a href='http://agapetry.net/forum/'>" . __awp('Support Forum') . "</a>";
			
			$page = ( RVY_NETWORK ) ? 'rvy-site_options' : 'rvy-options';
			$links[] = "<a href='admin.php?page=$page'>" . __awp('Options') . "</a>";
		}
			
		return $links;
	}
	
	// if a revision id or post object is passed in, returns parent post object
	function get_published_post( $_post ) {
		if ( is_object( $_post ) ) {
			if ( ! in_array( $_post->post_type, array( 'revision', '_revision' ) ) && ( 'inherit' != $_post->post_status ) )
				return $_post;
			else
				$_post = $_post->ID;
		}

		global $wpdb;
		
		$__post = $wpdb->get_row( "SELECT * FROM $wpdb->posts WHERE ID = '$_post'" ); // direct query so we don't fool ourself with subverted cache entry

		if ( in_array( $__post->post_type, array( 'revision', '_revision' ) ) || ( 'inherit' == $__post->post_status ) ) {
			$__post = get_post( $__post->post_parent );
		}

		return $__post;
	}
	
	function admin_head() {
		echo '<link rel="stylesheet" href="' . RVY_URLPATH . '/admin/revisionary.css" type="text/css" />'."\n";

		if ( false !== strpos(urldecode($_SERVER['REQUEST_URI']), 'admin.php?page=rvy-about') )
			echo '<link rel="stylesheet" href="' . RVY_URLPATH . '/admin/about/about.css" type="text/css" />'."\n";

		add_filter( 'contextual_help_list', array(&$this, 'flt_contextual_help_list'), 10, 2 );
		
		global $pagenow;

		if ( in_array( $pagenow, array( 'post.php', 'post-new.php' ) ) && ! defined('RVY_PREVENT_PUBHIST_CAPTION') )
			wp_enqueue_script( 'rvy_post', RVY_URLPATH . "/admin/post-edit.js", array('jquery'), RVY_VERSION, true );

		if( false !== strpos( urldecode($_SERVER['REQUEST_URI']), 'admin.php?page=rvy-revisions' ) ) {
			
			// add Ajax goodies we need for fancy publish date editing in Revisions Manager and role duration/content date limit editing Bulk Role Admin
			?>
			<script type="text/javascript">
			/* <![CDATA[ */
			jQuery(document).ready( function($) {
				$('#rvy-rev-checkall').click(function() {
					$('.rvy-rev-chk').attr( 'checked', this.checked );
				});
			});
			/* ]]> */
			</script>
			<?php
			wp_enqueue_script( 'rvy_edit', RVY_URLPATH . "/admin/revision-edit.js", array('jquery'), RVY_VERSION, true );
			
			if ( ( empty( $_GET['action'] ) || in_array( $_GET['action'], array( 'view', 'edit' ) ) ) && ! empty( $_GET['revision'] ) ) {
				if ( $revision = get_post( $_GET['revision'] ) ) {
					if ( ( 'revision' != $revision->post_type ) || $post = get_post( $revision->post_parent ) ) {
				
						// determine if tinymce textarea should be editable for displayed revision
						global $current_user;

						if ( 'revision' != $revision->post_type ) // we retrieved the parent (current revision) that corresponds to requested revision
							$read_only = true;

						elseif ( ( 'pending' == $revision->post_status ) && ( $revision->post_author == $current_user->ID ) )
							$read_only = false;
						else {
							if ( $type_obj = get_post_type_object( $post->post_type ) )
								$read_only = ! current_user_can( $type_obj->cap->edit_post, $revision->post_parent );
						}

						$this->tinymce_readonly = $read_only;
						
						require_once( dirname(__FILE__).'/revision-ui_rvy.php' );
						
						add_filter( 'tiny_mce_before_init', 'rvy_log_tiny_mce_params', 1 );
						add_filter( 'tiny_mce_before_init', 'rvy_tiny_mce_params', 998 );	// this is only applied to revisionary admin URLs, so not shy about dropping the millennial hammer

						if ( $read_only )
							add_filter( 'tiny_mce_before_init', 'rvy_tiny_mce_readonly', 999 );
						
						// WP Super Edit Workaround - $wp_super_edit->is_tinymce property is currently set true only if URI matches unfilterable list: '/tiny_mce_config\.php|page-new\.php|page\.php|post-new\.php|post\.php/'
						global $wp_super_edit;
						
						if ( ! empty($wp_super_edit) && ! $wp_super_edit->is_tinymce )
							include_once( dirname(__FILE__).'/super-edit-helper_rvy.php' );
						//
						
						if ( ! awp_ver( '3.3-dev' ) )
							wp_tiny_mce();
					}
				}
			}
			
			// need this for editor swap from visual to html
			if ( empty($read_only) )
				wp_print_scripts( 'editor', 'quicktags' );
			else {
				wp_print_scripts( 'editor' );
				
// if the revision is read-only, also disable the HTML editing area and kill the toolbar which the_editor() forces in
?>
<script type="text/javascript">
/* <![CDATA[ */
jQuery(document).ready( function($) {
	$('#ed_toolbar').hide();
	$('#content').attr('disabled', 'disabled');
});
/* ]]> */
</script>
<?php	
			} // endif read_only

			require_once( dirname(__FILE__).'/revision-ui_rvy.php' );

			rvy_revisions_js();
		}
		
		// all required JS functions are present in Role Scoper JS; TODO: review this for future version changes as necessary
		// TODO: replace some of this JS with equivalent JQuery
		if ( ! defined('SCOPER_VERSION') )
			wp_enqueue_script( 'rvy', RVY_URLPATH . "/admin/revisionary.js", array('jquery'), RVY_VERSION, true );
	}
	
	function flt_contextual_help_list ($help, $screen) {
		if ( is_object($screen) )
			$screen = $screen->id;
		
		if ( in_array( $screen, array( 'edit', 'post', 'settings_page_rvy-revisions', 'settings_page_rvy-options' ) ) ) {
			if ( ! isset($help[$screen]) )
				$help[$screen] = '';

			//$help[$screen] .= sprintf(__('%1$s Revisionary Documentation%2$s', 'revisionary'), "<a href='http://agapetry.net/downloads/Revisionary_UsageGuide.htm#$link_section' target='_blank'>", '</a>')
			$help[$screen] .= ' ' . sprintf(__('%1$s Revisionary Support Forum%2$s', 'revisionary'), "<a href='http://agapetry.net/forum/' target='_blank'>", '</a>');
			
			if ( current_user_can( 'manage_options' ) )
				$help[$screen] .= ', ' . sprintf(__('%1$s About Revisionary%2$s', 'revisionary'), "<a href='admin.php?page=rvy-about' target='_blank'>", '</a>');
		}

		return $help;
	}
	

	function build_menu() {
		if ( strpos( $_SERVER['REQUEST_URI'], 'wp-admin/network/' ) )
			return;
	
		$path = RVY_ABSPATH;

		// For Revisions Manager access, satisfy WordPress' demand that all admin links be properly defined in menu
		if ( false !== strpos( urldecode($_SERVER['REQUEST_URI']), 'admin.php?page=rvy-revisions' ) ) {
			$func_content = "include_once('$path/admin/revisions.php');";
			$func = create_function( '', $func_content );
			
			add_submenu_page( 'none', __('Revisions', 'revisionary'), __('Revisions', 'revisionary'), 'read', 'rvy-revisions', $func );
		}

		if ( ! current_user_can( 'manage_options' ) )
			return;
		
		if ( false !== strpos( urldecode($_SERVER['REQUEST_URI']), 'admin.php?page=rvy-about' ) ) {	
			add_options_page( __('About Revisionary', 'revisionary'), __('About Revisionary', 'revisionary'), 'read', 'rvy-about');
			
			$func = "include_once('$path/admin/about.php');";
			add_action( 'settings_page_rvy-about' , create_function( '', $func ) );
		}

		global $rvy_default_options, $rvy_options_sitewide;
		
		if ( empty($rvy_default_options) )
			rvy_refresh_default_options();

		// omit site-Specific Options menu item if all options are controlled network-wide
		if ( ! RVY_NETWORK || ( count($rvy_options_sitewide) != count($rvy_default_options) ) ) {
			add_options_page( __('Revisionary Options', 'revisionary'), __('Revisionary', 'revisionary'), 'read', 'rvy-options');

			$func = "include_once( '$path/admin/options.php');rvy_options( false );";
			add_action('settings_page_rvy-options', create_function( '', $func ) );	
		}
	}
	
	function act_hide_quickedit_for_revisions() {
		global $rvy_any_listed_revisions;
		
		if ( empty( $rvy_any_listed_revisions ) )
			return;

		$post_type = awp_post_type_from_uri();
		$type_obj = get_post_type_object($post_type);
		?>
		<script type="text/javascript">
		/* <![CDATA[ */
		jQuery(document).ready( function($) {
		<?php foreach( $rvy_any_listed_revisions as $id ): ?>
			$( '#<?php echo( 'post-' . $id );?> span.inline' ).hide();
		<?php endforeach; ?>
		});
		/* ]]> */
		</script>
		<?php
	}

	
	function act_hide_admin_divs() {
		// Hide unrevisionable elements if editing for revisions, regardless of Limited Editing Element settings
		//
		// TODO: allow revisioning of slug, menu order, comment status, ping status ?
		// TODO: leave Revisions metabox for links to user's own pending revisions
		if ( rvy_get_option( 'pending_revisions' ) ) {
			global $post;
			if ( ! empty($post->post_type) )
				$object_type = $post->post_type;
			else
				$object_type = awp_post_type_from_uri();

			$object_id = rvy_detect_post_id();

			if ( $object_id ) {
				$type_obj = get_post_type_object( $object_type );

				if ( $type_obj && ! agp_user_can( $type_obj->cap->edit_post, $object_id, '', array( 'skip_revision_allowance' => true ) ) ) { 
					//if ( 'page' == $object_type )
						$unrevisable_css_ids = array( 'pageparentdiv', 'pageauthordiv', 'pagecustomdiv', 'pageslugdiv', 'pagecommentstatusdiv' );
				 	//else
						$unrevisable_css_ids = array_merge( $unrevisable_css_ids, array( 'categorydiv', 'authordiv', 'postcustom', 'customdiv', 'slugdiv', 'commentstatusdiv', 'password-span', 'trackbacksdiv',  'tagsdiv-post_tag', 'visibility', 'edit-slug-box', 'postimagediv', 'ef_editorial_meta' ) );

					foreach( get_taxonomies( array(), 'object' ) as $taxonomy => $tx_obj )
						$unrevisable_css_ids []= ( $tx_obj->hierarchical ) ? "{$taxonomy}div" : "tagsdiv-$taxonomy";

					$unrevisable_css_ids = apply_filters( 'rvy_hidden_meta_boxes', $unrevisable_css_ids );
						
					echo( "\n<style type='text/css'>\n<!--\n" );
						
					foreach ( $unrevisable_css_ids as $id ) {
						// TODO: determine if id is a metabox or not
						
						// thanks to piemanek for tip on using remove_meta_box for any core admin div
						remove_meta_box($id, $object_type, 'normal');
						remove_meta_box($id, $object_type, 'advanced');
						
						// also hide via CSS in case the element is not a metabox
						echo "#$id { display: none !important; }\n";  // this line adapted from Clutter Free plugin by Mark Jaquith
					}
						
					echo "-->\n</style>\n";
					
					// display the current status, but hide edit link
					echo "\n<style type='text/css'>\n<!--\n.edit-post-status { display: none !important; }\n-->\n</style>\n";  // this line adapted from Clutter Free plugin by Mark Jaquith
				}
			}
		}	
	}

	function convert_link( $link, $topic, $operation, $args = '' ) {
		$defaults = array ( 'object_type' => '', 'id' => '' );
		$args = array_merge( $defaults, (array) $args );
		extract($args);

		global $pagenow;
		
		if ( awp_ver( '3.6-dev' ) && in_array( $pagenow, apply_filters( 'rvy_default_revision_link_pages', array( 'post.php' ), $link, $args ) ) )
			return $link;
		
		if ( 'revision' == $topic ) {
			if ( 'manage' == $operation ) {
				if ( strpos( $link, 'revision.php' ) ) {
					$link = str_replace( 'revision.php', 'admin.php?page=rvy-revisions&action=view', $link );
					$link = str_replace( '?revision=', "&amp;revision=", $link );
				}
			
			} elseif ( 'preview' == $operation ) {
				$link .= "&post_type=revision&preview=1";

			} elseif ( 'delete' == $operation ) {
				if ( $object_type && $id ) {
					$link = str_replace( "$object_type.php", 'admin.php?page=rvy-revisions', $link );
					$link = str_replace( '?post=', "&amp;revision=", $link );
					$link = wp_nonce_url( $link, 'delete-revision_' . $id );
				}
			} 
		}
		
		return $link;
	}
	
	// Thanks to hiphipvargas for providing this function
	function flt_delete_post_link( $link ) {
        if ( strpos( $link, 'revision.php' ) ) {
	        // note: WP 2.9 does not return ID argument as 2nd variable, so parse it out of link
	        $link_arr = array();
			$linkv = parse_url( str_replace('&amp;', '&', $link) );
			parse_str( $linkv['query'], $link_arr );

	        if ( isset( $link_arr['revision'] ) ) {
	       		$link = "admin.php?page=rvy-revisions&amp;action=delete&amp;&amp;return=1&amp;revision=". $link_arr['revision'];

	        	$link = 'javascript:if(confirm("'. __('Delete'). '?")) window.location="'. wp_nonce_url( $link, 'delete-revision_' . $link_arr['revision'] ). '"';
			}
	    }

		return $link;
    }
	
	function flt_edit_post_link( $link, $id, $context ) {
		if ( $post = get_post( $id ) )
			if ( 'revision' == $post->post_type ) {
				$link = $this->convert_link( $link, 'revision', 'manage' );
			
				global $rvy_any_listed_revisions;
				
				if ( ! isset( $rvy_any_listed_revisions ) )
					$rvy_any_listed_revisions = array();
				
				$rvy_any_listed_revisions []= $id;
			}
		return $link;
	}
	
	function flt_preview_post_link( $link, $post ) {
		if ( 'revision' == $post->post_type )
			$link = $this->convert_link( $link, 'revision', 'preview' );

		return $link;
	}
	
	
	function flt_post_title ( $title, $id = '' ) {
		if ( $id )
			if ( $post = get_post( $id ) )
				if ( 'revision' == $post->post_type )
					$title = sprintf( __( '%s (revision)', 'revisionary' ), $title );

		return $title;
	}
	
	// only added for edit.php and edit-pages.php
	function flt_get_post_time( $time, $format, $gmt ) {
		if ( function_exists('get_the_ID') && $post_id = get_the_ID() ) {
			if ( $post = get_post( $post_id ) ) {
				if ( ( 'revision' == $post->post_type ) && ( 'pending' == $post->post_status ) ) {
					if ( $gmt )
						$time = mysql2date($format, $post->post_modified_gmt, $gmt);
					else
						$time = mysql2date($format, $post->post_modified, $gmt);
				}
			}		
		}
		
		return $time;
	}
	
	function act_impose_pending_rev() {
		if ( isset($_POST['wp-preview']) && ( 'dopreview' == $_POST['wp-preview'] ) )
			return;

		if ( empty($this->impose_pending_rev) ) {
			return;
		}
			
		if ( false !== strpos( urldecode($_SERVER['REQUEST_URI']), 'admin.php?page=rvy-revisions' ) )
			return;
		
		// todo: can we just return instead?
		if ( isset($_POST['action']) && ( 'autosave' == $_POST['action'] ) )
			wp_die( 'Autosave disabled when editing a published post/page to create a pending revision.' );
		global $revisionary;
		
		$published_post = get_post( $this->impose_pending_rev );
		
		$post_arr = $_POST;
		
		$object_type = isset($post_arr['post_type']) ? $post_arr['post_type'] : '';
	
		$post_arr['post_type'] = 'revision';
		$post_arr['post_status'] = 'pending';
		$post_arr['post_parent'] = $this->impose_pending_rev;  // side effect: don't need to filter page parent selection because parent is set to published revision
		$post_arr['parent_id'] = $this->impose_pending_rev;
		$post_arr['post_ID'] = 0;
		$post_arr['ID'] = 0;
		$post_arr['guid'] = '';

		
		
		if ( defined('RVY_CONTENT_ROLES') ) {
			if ( isset($post_arr['post_category']) ) {	// todo: also filter other post taxonomies
				$post_arr['post_category'] = $revisionary->content_roles->filter_object_terms( $post_arr['post_category'], 'category' );
			}
		}

		global $current_user, $wpdb;
		$post_arr['post_author'] = $current_user->ID;		// store current user as revision author (but will retain current post_author on restoration)
			
		$post_arr['post_modified'] = current_time( 'mysql' );
		$post_arr['post_modified_gmt'] = current_time( 'mysql', 1 );

		$date_clause = ", post_modified = '" . current_time( 'mysql' ) . "', post_modified_gmt = '" . current_time( 'mysql', 1 ) . "'";  // make sure actual modification time is stored to revision
		
		if ( $revision_id = wp_insert_post($post_arr) ) {
			$future_date = ( ! empty($post_arr['post_date']) && ( strtotime($post_arr['post_date_gmt'] ) > agp_time_gmt() ) );
			
			$wpdb->query("UPDATE $wpdb->posts SET post_status = 'pending', post_parent = '$this->impose_pending_rev' $date_clause WHERE ID = '$revision_id'");
			
			$manage_link = $this->get_manage_link( $object_type );
							
			if ( $future_date )
				$msg = __('Your modification has been saved for editorial review.  If approved, it will be published on the date you specified.', 'revisionary') . ' ';
			else
				$msg = __('Your modification has been saved for editorial review.', 'revisionary') . ' ';
			
			$msg .= '<ul><li>';
			$msg .= sprintf( '<a href="%s">' . __('View it in Revisions Manager', 'revisionary') . '</a>', "admin.php?page=rvy-revisions&amp;revision=$revision_id&amp;action=view" );
			$msg .= '<br /><br /></li><li>';
			
			if ( $future_date ) {
				$msg .= sprintf( '<a href="%s">' . __('Go back to Submit Another revision (possibly for a different publish date).', 'revisionary') . '</a>', "javascript:back();" );
				$msg .= '<br /><br /></li><li>';
			}
			$msg .= sprintf( '<a href="%s">' . $manage_link->caption . '</a>', admin_url($manage_link->uri) );
			$msg .= '</li></ul>';

		} else {
			$msg = __('Sorry, an error occurred while attempting to save your modification for editorial review!', 'revisionary') . ' ';	
		}
		
		
		$admin_notify = rvy_get_option( 'pending_rev_notify_admin' );
		$author_notify = rvy_get_option( 'pending_rev_notify_author' );
		if ( ( $admin_notify || $author_notify ) && $revision_id ) {
			$type_obj = get_post_type_object( $object_type );
			$type_caption = $type_obj->labels->singular_name;
			$post_arr['post_type'] = $published_post->post_type;
			
			$blogname = wp_specialchars_decode( get_option('blogname'), ENT_QUOTES );
			
			if ( $admin_notify ) {
				$title = sprintf( __('[%s] Pending Revision Notification', 'revisionary'), $blogname );
				
				$message = sprintf( __('A pending revision to the %1$s "%2$s" has been submitted.', 'revisionary'), $type_caption, $post_arr['post_title'] ) . "\r\n\r\n";
				

				if ( $author = new WP_User( $post_arr['post_author'] ) )
					$message .= sprintf( __('It was submitted by %1$s.', 'revisionary' ), $author->display_name ) . "\r\n\r\n";

				if ( $revision_id )
					$message .= __( 'Review it here: ', 'revisionary' ) . admin_url("admin.php?page=rvy-revisions&action=view&revision={$revision_id}") . "\r\n";
				
				// establish the publisher recipients
				$recipient_ids = array();
				
				if ( defined('RVY_CONTENT_ROLES') && ! defined('SCOPER_DEFAULT_MONITOR_GROUPS') ) {
					global $revisionary;
					
					$monitor_groups_enabled = true;
					$revisionary->content_roles->ensure_init();
					
					$recipient_ids = $revisionary->content_roles->get_metagroup_members( 'Pending Revision Monitors' );
					
					if ( $type_obj ) {
						$revisionary->skip_revision_allowance = true;
						$post_publisher_ids = $revisionary->content_roles->users_who_can( $type_obj->cap->edit_post, $this->impose_pending_rev, array( 'cols' => 'id', 'user_ids' => $recipient_ids ) );
						$revisionary->skip_revision_allowance = false;
						$recipient_ids = array_intersect( $recipient_ids, $post_publisher_ids );
					}
				}

				if ( ! $recipient_ids && ( empty($monitor_groups_enabled) || ! defined('RVY_FORCE_MONITOR_GROUPS') ) ) {
					require_once(ABSPATH . 'wp-admin/includes/user.php');
					
					if ( defined( 'SCOPER_MONITOR_ROLES' ) )
						$use_wp_roles = SCOPER_MONITOR_ROLES;
					else
						$use_wp_roles = ( defined( 'RVY_MONITOR_ROLES' ) ) ? RVY_MONITOR_ROLES : 'administrator,editor';
					
					$use_wp_roles = str_replace( ' ', '', $use_wp_roles );
					$use_wp_roles = explode( ',', $use_wp_roles );
					
					foreach ( $use_wp_roles as $role_name ) {
						if ( awp_ver( '3.1-beta' ) ) {
							$search = new WP_User_Query( "search=&fields=id&role=$role_name" );
							$recipient_ids = array_merge( $recipient_ids, $search->results );
						} else {
							$search = new WP_User_Search( '', 0, $role_name );
							$recipient_ids = array_merge( $recipient_ids, $search->results );
						}
					}
					
					if ( $recipient_ids && $type_obj ) {
						foreach( $recipient_ids as $key => $user_id ) {
							$_user = new WP_User($user_id);
							$reqd_caps = map_meta_cap( $type_obj->cap->edit_post, $user_id, $published_post->ID );
							
							if ( array_diff( $reqd_caps, array_keys( array_intersect( $_user->allcaps, array( true, 1, '1' ) ) ) ) ) {
								unset( $recipient_ids[$key] );
							}
						}
					}
				}
				
				if ( 'always' != $admin_notify ) {
					// intersect default recipients with selected recipients
					$selected_recipients = ( ! empty($post_arr['prev_cc_user']) ) ? $post_arr['prev_cc_user'] : array();
					$recipient_ids = array_intersect( $selected_recipients, $recipient_ids );
				}
				
				if ( defined( 'RVY_NOTIFY_SUPER_ADMIN' ) && is_multisite() ) {
					$super_admin_logins = get_super_admins();
					foreach( $super_admin_logins as $user_login ) {
						if ( $super = new WP_User($user_login) )
							$recipient_ids []= $super->ID;
					}
				}
			}

			if ( $author_notify ) {
				if ( $post = get_post( $this->impose_pending_rev ) ) {



					if ( ( 'always' == $author_notify ) || ( isset($post_arr['prev_cc_user']) && is_array($post_arr['prev_cc_user']) && in_array( $post->post_author, $post_arr['prev_cc_user'] ) ) )
						$recipient_ids []= $post->post_author;	
				}
			}

			if ( $recipient_ids ) {
				global $wpdb;
				$to_addresses = array_unique( $wpdb->get_col( "SELECT user_email FROM $wpdb->users WHERE ID IN ('" . implode( "','", $recipient_ids ) . "')" ) );
			} else
				$to_addresses = array();
			
			foreach ( $to_addresses as $address )
				rvy_mail($address, $title, $message);
		}
		
		unset($this->impose_pending_rev);
		
		wp_die( $msg, __('Pending Revision Created', 'revisionary'), array( 'response' => 0 ) );
	}
	
	
	function act_create_scheduled_rev() {
		if ( isset($_POST['wp-preview']) && ( 'dopreview' == $_POST['wp-preview'] ) )
			return;
		
		if ( isset($_POST['action']) && ( 'autosave' == $_POST['action'] ) )
			return;

		$original_post_status = ( isset( $_POST['original_post_status'] ) ) ? $_POST['original_post_status'] : '';
		$hidden_post_status = ( isset( $_POST['hidden_post_status'] ) ) ? $_POST['hidden_post_status'] : '';
			
		// don't interfere with scheduling of unpublished drafts
		if ( ! in_array( $original_post_status, array( 'publish', 'private' ) )  && ! in_array( $hidden_post_status, array( 'publish', 'private' ) ) )
			return;	

		$post_arr = $_POST;
		
		if ( ! empty( $_POST['post_ID'] ) )
			$published_post = $this->get_published_post( $_POST['post_ID'] );
		
		if ( ! empty($post_arr['post_date_gmt']) && ( strtotime($post_arr['post_date_gmt'] ) > agp_time_gmt() ) ) {
			$parent_id = $post_arr['ID'];
			
			if ( $type_obj = get_post_type_object( $published_post->post_type ) ) {
				global $current_user;
				if ( ! agp_user_can( $type_obj->cap->edit_post, $published_post->ID, $current_user->ID, array( 'skip_revision_allowance' => true ) ) )
					return;
			}
			
			// a future publish date was selected
			$date_clause = ", post_modified = '" . current_time( 'mysql' ) . "', post_modified_gmt = '" . current_time( 'mysql', 1 ) . "'";  // If WP forces modified time up to post time, force it back

			$post_arr['post_modified'] = current_time( 'mysql' );
			$post_arr['post_modified_gmt'] = current_time( 'mysql', 1 );

			$object_type = isset($post_arr['post_type']) ? $post_arr['post_type'] : '';
		
			$post_arr['post_type'] = 'revision';
			$post_arr['post_status'] = 'future';
			$post_arr['post_parent'] = $post_arr['ID'];
			$post_arr['post_ID'] = 0;
			$post_arr['ID'] = 0;
			$post_arr['guid'] = '';
	
			if ( defined('RVY_CONTENT_ROLES') ) {
				if ( isset($post_arr['post_category']) ) {	// todo: also filter other post taxonomies
					$post_arr['post_category'] = $GLOBALS['revisionary']->content_roles->filter_object_terms( $post_arr['post_category'], 'category' );
				}
			}

			global $current_user;
			$post_arr['post_author'] = $current_user->ID;		// store current user as revision author (but will retain current post_author on restoration)

			if ( $revision_id = wp_insert_post($post_arr) ) {
				global $wpdb;
				$wpdb->query("UPDATE $wpdb->posts SET post_status = 'future', post_parent = '$parent_id' WHERE ID = '$revision_id'");
			}
			
			require_once( dirname(__FILE__).'/revision-action_rvy.php');
			rvy_update_next_publish_date();

			$manage_link = $this->get_manage_link( $object_type );
			
			$msg = __('Your modification was saved as a Scheduled Revision.', 'revisionary') . ' ';
			
			$msg .= '<ul><li>';
			$msg .= sprintf( '<a href="%s">' . __('View it in Revisions Manager', 'revisionary') . '</a>', "admin.php?page=rvy-revisions&amp;revision=$revision_id&amp;action=view" );
			$msg .= '<br /><br /></li><li>';
			$msg .= sprintf( '<a href="%s">' . __('Go back to Schedule Another revision.', 'revisionary') . '</a>', "javascript:back();" );
			$msg .= '<br /><br /></li><li>';
			$msg .= sprintf( '<a href="%s">' . $manage_link->caption . '</a>', admin_url($manage_link->uri) );
			$msg .= '</li></ul>';
			
			wp_die( $msg, __('Scheduled Revision Created', 'revisionary'), array( 'response' => 0 ) );
		}
	}
	
	function get_manage_link( $post_type ) {
		$arr = (object) array();
		
		// maintaining these for back compat with existing translations
		if ( 'post' == $post_type ) {
			$arr->uri = 'edit.php';
			$arr->caption = __( 'Return to Edit Posts', 'revisionary' );
		} elseif ( 'page' == $post_type ) {
			$arr->uri = "edit.php?post_type=$post_type";
			$arr->caption = __( 'Return to Edit Pages', 'revisionary' );
		} else {
			$wp_post_type = get_post_type_object( $post_type );
			$arr->uri = "edit.php?post_type=$post_type";
			$arr->caption = sprintf( __( 'Return to Edit %s', 'revisionary' ), $wp_post_type->labels->name );
		}
		
		return $arr;
	}
	
	// If Scheduled Revisions are enabled, don't allow WP to force current post status to future based on publish date
	function flt_insert_post_data( $data, $postarr ) {
		if ( ( 'future' == $data['post_status'] ) && ( 'publish' == $postarr['post_status'] ) ) {
			// don't interfere with scheduling of unpublished drafts
			if ( in_array( $_POST['original_post_status'], array( 'publish', 'private' ) )  || in_array( $_POST['hidden_post_status'], array( 'publish', 'private' ) ) )
				$data['post_status'] = 'publish';
		}
		
		return $data;
	}
	
	function flt_pendingrev_post_status($status) {
		if ( empty( $_POST['post_ID'] ) )
			return $status;

		// Make sure the stored post is published / scheduled		
		// With Events Manager plugin active, Role Scoper 1.3 to 1.3.12 caused this filter to fire prematurely as part of object_id detection, flagging for pending_rev needlessly on update of an unpublished post
		if ( $stored_post = get_post( $_POST['post_ID'] ) )
			$status_obj = get_post_status_object( $stored_post->post_status );

		if ( empty($status_obj) || ( ! $status_obj->public && ! $status_obj->private && ( 'future' != $stored_post->post_status ) ) )
			return $status;
		
		if ( ! empty( $_POST['rvy_save_as_pending_rev'] ) && ! empty($_POST['post_ID']) ) {
			$this->impose_pending_rev = $_POST['post_ID'];
		}
		
		if ( is_content_administrator_rvy() )
			return $status;
		
		if ( isset($_POST['wp-preview']) && ( 'dopreview' == $_POST['wp-preview'] ) )
			return $status;
			
		if ( isset($_POST['post_ID']) && isset($_POST['post_type']) ) {
			$post_id = $_POST['post_ID'];

			if ( $type_obj = get_post_type_object( $_POST['post_type'] ) ) {
				if ( ! agp_user_can( $type_obj->cap->edit_post, $post_id, '', array( 'skip_revision_allowance' => true ) ) )
					$this->impose_pending_rev = $post_id;
			}
		}
		
		return $status;
	}
} // end class RevisionaryAdmin
?>