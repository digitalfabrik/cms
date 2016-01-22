<?php

if( basename(__FILE__) == basename($_SERVER['SCRIPT_FILENAME']) )
	die( 'This page cannot be called directly.' );
	
/**
 * revisions.php
 * 
 * Revisions Manager for Revisionary plugin, derived and heavily expanded from WP 2.8.4 core
 *
 * @author 		Kevin Behrens
 * @copyright 	Copyright 2009-2015
 * 
 */

global $current_user, $revisionary; 
 
include_once( dirname(__FILE__).'/revision-ui_rvy.php' ); 

if ( defined( 'FV_FCK_NAME' ) && current_user_can('activate_plugins') ) {
	echo( '<div class="error">' );
	_e( "<strong>Note:</strong> For visual display of revisions, add the following code to foliopress-wysiwyg.php:<br />&nbsp;&nbsp;if ( strpos( $" . "_SERVER['REQUEST_URI'], 'admin.php?page=rvy-revisions' ) ) return;", 'revisionary');
	echo( '</div><br />' );
}
//wp_reset_vars( array('revision', 'left', 'right', 'action', 'revision_status') );

if ( ! empty($_GET['revision']) )
	$revision_id = absint($_GET['revision']);

if ( ! empty($_GET['left']) )
	$left = absint($_GET['left']);
else
	$left = '';

if ( ! empty($_GET['right']) )
	$right = absint($_GET['right']);
else
	$right = '';

if ( ! empty($_GET['revision_status']) )
	$revision_status = $_GET['revision_status'];
else
	$revision_status = '';
	
if ( ! empty($_GET['action']) )
	$action = $_GET['action'];
else
	$action = '';

if ( ! empty($_GET['restored_post'] ) )
	$revision_id = $_GET['restored_post'];

if ( empty($revision_id) && ! $left && ! $right ) {
	echo( '<div><br />' );
	_e( 'No revision specified.', 'revisionary');
	echo( '</div>' );
	return;
}

$revision_status_captions = array( 'inherit' => __( 'Past', 'revisionary' ), 'pending' => __awp('Pending', 'revisionary'), 'future' => __awp( 'Scheduled', 'revisionary' ) );

if( 'edit' == $action )
	$action = 'view';

switch ( $action ) :
case 'diff' :
	if ( !$left_revision  = get_post( $left ) )
		break;
	if ( !$right_revision = get_post( $right ) )
		break;

	// actual status of compared objects overrides any revision_Status arg passed in
	if ( 'revision' == $left_revision->post_type )
		$revision_status = $left_revision->post_status;
	else
		$revision_status = $right_revision->post_status;
		
	// TODO: review revision read_post implementation with Press Permit
	if ( ( ! current_user_can( 'read_post', $left_revision->ID ) && ! current_user_can( 'edit_post', $left_revision->ID ) ) || ( ! current_user_can( 'read_post', $right_revision->ID ) && ! current_user_can( 'edit_post', $right_revision->ID ) ) )
		break;

	if ( $left_revision->ID == $right_revision->post_parent ) // right is a revision of left
		$rvy_post = $left_revision;
	elseif ( $left_revision->post_parent == $right_revision->ID ) // left is a revision of right
		$rvy_post = $right_revision;
	elseif ( $left_revision->post_parent == $right_revision->post_parent ) // both are revisions of common parent
		$rvy_post = get_post( $left_revision->post_parent );
	else
		break; // Don't diff two unrelated revisions

	if (
		// They're the same
		$left_revision->ID == $right_revision->ID
	||
		// Neither is a revision
		( !wp_get_post_revision( $left_revision->ID ) && !wp_get_post_revision( $right_revision->ID ) )
	)
		break;

	if ( $type_obj = get_post_type_object( $rvy_post->post_type ) ) {
		$edit_cap = $type_obj->cap->edit_post;
		$edit_others_cap = $type_obj->cap->edit_others_posts;
		$delete_cap = $type_obj->cap->delete_post;
	}
		
	if ( ! $can_fully_edit_post = agp_user_can( $edit_cap, $rvy_post->ID, '', array( 'skip_revision_allowance' => true ) ) ) {
		// post-assigned Revisor role is sufficient to edit others' revisions, but post-assigned Contributor role is not
		if ( isset( $GLOBALS['cap_interceptor'] ) )
			$GLOBALS['cap_interceptor']->require_full_object_role = true;
		
		$_can_edit_others = agp_user_can( $edit_others_cap, $rvy_post->ID );

		if ( isset( $GLOBALS['cap_interceptor'] ) )
			$GLOBALS['cap_interceptor']->require_full_object_role = false;
	}

	foreach( array( $left_revision, $right_revision ) as $_revision ) {
		if ( $_revision->ID == $rvy_post->ID ) {
			$can_view = current_user_can( $edit_cap, $rvy_post->ID );
		} else {
			$can_view = ( ( 'revision' == $_revision->post_type ) ) && (
				$can_fully_edit_post || 
				( ( $_revision->post_author == $current_user->ID || $_can_edit_others ) && ( 'pending' == $_revision->post_status ) ) 
				 );	
		}
				 
		if ( ! $can_view && ( $_revision->post_author != $current_user->ID ) ) {
			wp_die();
		}
	}
	
	$post_title = "<a href='post.php?action=edit&post=$rvy_post->ID'>$rvy_post->post_title</a>";

	$h2 = sprintf( __( '%1$s Revisions for &#8220;%2$s&#8221;', 'revisionary' ), $revision_status_captions[$revision_status], $post_title );

	$left  = $left_revision->ID;
	$right = $right_revision->ID;

	break;
case 'view' :
default :
	$left = 0;
	$right = 0;
	$h2 = '';
	
	if ( ! $revision = wp_get_post_revision( $revision_id ) ) {
		// Support published post/page in revision argument
		if ( ! $rvy_post = get_post( $revision_id) )
			break;

		$public_types = array_diff( get_post_types( array( 'public' => true ) ), array( 'attachment' ) );
	
		if ( ! in_array( $rvy_post->post_type, $public_types ) ) {
			$rvy_post = '';  // todo: is this necessary?
			break;
		}

		// revision_id is for a published post.  List all its revisions - either for type specified or default to past
		if ( ! $revision_status )
			$revision_status = 'inherit';

		if ( !current_user_can( 'edit_post', $rvy_post->ID ) && ( $rvy_post->post_author != $current_user->ID ) )
			wp_die();

	} else {
		if ( !$rvy_post = get_post( $revision->post_parent ) )
			break;

		// actual status of compared objects overrides any revision_Status arg passed in
		$revision_status = $revision->post_status;

		if ( !current_user_can( 'edit_post', $rvy_post->ID ) && ( $revision->post_author != $current_user->ID ) )
			wp_die();
	}

	if ( $type_obj = get_post_type_object( $rvy_post->post_type ) ) {
		$edit_cap = $type_obj->cap->edit_post;
		$edit_others_cap = $type_obj->cap->edit_others_posts;
		$delete_cap = $type_obj->cap->delete_post;
	}

	// Sets up the diff radio buttons
	$right = $rvy_post->ID;

	// temporarily remove filter so we don't change it into a revisions.php link
	remove_filter( 'get_edit_post_link', array($revisionary->admin, 'flt_edit_post_link'), 10, 3 );
		
	if ( $revision ) {
		$left = $revision_id;
		$post_title = "<a href='post.php?action=edit&post=$rvy_post->ID'>$rvy_post->post_title</a>";

		$revision_title = wp_post_revision_title( $revision, false );
		
		$caption = ( strpos($revision->post_name, '-autosave' ) ) ? '' : $revision_status_captions[$revision_status];
		
		// TODO: combine this code with captions for front-end preview approval bar
		switch ( $revision_status ) :
		case 'inherit':
			if ( strpos( $revision->post_name, '-autosave' ) )
				$h2 = sprintf( __( 'Revision of &#8220;%1$s&#8221;', 'revisionary' ), $post_title);
			else
				$h2 = sprintf( __( 'Past Revision of &#8220;%1$s&#8221;', 'revisionary' ), $post_title);
			break;
		case 'pending':
			$h2 = sprintf( __( 'Pending Revision of &#8220;%1$s&#8221;', 'revisionary' ), $post_title);
			break;
		case 'future':
			$h2 = sprintf( __( 'Scheduled Revision of &#8220;%1$s&#8221;', 'revisionary' ), $post_title);
			break;
		endswitch;

		if ( ('diff' != $action) && ($rvy_post->ID != $revision->ID) ) {
			if ( agp_user_can( $edit_cap, $rvy_post->ID, '', array( 'skip_revision_allowance' => true ) ) ) {
				switch( $revision->post_status ) :
				case 'future' :
					$caption = str_replace( ' ', '&nbsp;', __('Publish Now', 'revisionary') );
					$link = wp_nonce_url( add_query_arg( array( 'revision' => $revision->ID, 'diff' => false, 'action' => 'restore' ) ), "restore-post_$rvy_post->ID|$revision->ID" );
					break;
				case 'pending' :
					if ( strtotime($revision->post_date_gmt) > agp_time_gmt() ) {
						$caption = str_replace( ' ', '&nbsp;', __('Schedule Now', 'revisionary') );
					} else {
						$caption = str_replace( ' ', '&nbsp;', __('Publish Now', 'revisionary') );
					}
					
					$link = wp_nonce_url( add_query_arg( array( 'revision' => $revision->ID, 'diff' => false, 'action' => 'approve' ) ), "approve-post_$rvy_post->ID|$revision->ID" );
					break;
				default :
					$caption = str_replace( ' ', '&nbsp;', __('Restore Now', 'revisionary') );
					$link = wp_nonce_url( add_query_arg( array( 'revision' => $revision->ID, 'diff' => false, 'action' => 'restore' ) ), "restore-post_$rvy_post->ID|$revision->ID" );
				endswitch;
		
				$restore_link = '<a href="' . $link . '">' .$caption . "</a> ";
			} else
				$restore_link = '';
		}
		
	} else {
		$revision = $rvy_post;	

		$link = apply_filters( 'get_edit_post_link', admin_url("{$rvy_post->post_type}.php?action=edit&post=$revision_id"), $revision_id, '' );

		$post_title = "<a href='post.php?action=edit&post=$rvy_post->ID'>$rvy_post->post_title</a>";
		
		$revision_title = wp_post_revision_title( $revision, false );
		$h2 = sprintf( __( '&#8220;%1$s&#8221; (Currently Published)' ), $post_title );
	}

	add_filter( 'get_edit_post_link', array($revisionary->admin, 'flt_edit_post_link'), 10, 3 );
	
	// pending revisions are newer than current revision
	if ( 'pending' == $revision_status ) {
		$buffer_left = $left;
		$left  = $right;
		$right = $buffer_left;
	}

	break;
endswitch;


if ( empty($revision) && empty($right_revision) && empty($left_revision) ) {
	echo( '<div><br />' );
	_e( 'The requested revision does not exist.', 'revisionary');
	echo( '</div>' );
	return;
}

if ( ! $revision_status )
	$revision_status = 'inherit'; 	// default to showing past revisions
?>

<div class="wrap">

<form name="post" action="" method="post" id="post">

<?php
if ( ! $can_fully_edit_post = agp_user_can( $edit_cap, $rvy_post->ID, '', array( 'skip_revision_allowance' => true ) ) ) {
	// post-assigned Revisor role is sufficient to edit others' revisions, but post-assigned Contributor role is not
	if ( isset( $GLOBALS['cap_interceptor'] ) )
		$GLOBALS['cap_interceptor']->require_full_object_role = true;
	
	$_can_edit_others = ! rvy_get_option( 'revisor_lock_others_revisions' ) && agp_user_can( $edit_others_cap, $rvy_post->ID );

	if ( isset( $GLOBALS['cap_interceptor'] ) )
		$GLOBALS['cap_interceptor']->require_full_object_role = false;
}

if ( 'diff' != $action ) {
	$can_edit = ( 'revision' == $revision->post_type ) && (
		$can_fully_edit_post || 
		( ( $revision->post_author == $current_user->ID || $_can_edit_others ) && ( 'pending' == $revision->post_status ) ) 
		 );

	if ( $can_edit ) {
		wp_nonce_field('update-revision_' .  $revision->ID);

		echo "<input type='hidden' id='revision_ID' name='revision_ID' value='" . esc_attr($revision->ID) . "' />";
	} elseif ( ( $revision->post_author != $current_user->ID ) ) {
		if ( $revision->ID == $rvy_post->ID ) {
			if ( ! current_user_can( $edit_cap, $rvy_post->ID ) )
				wp_die();
		} else {
			wp_die();
		}
	}
}
?>

<table class="rvy-editor-table">
<tr><td class="rvy-editor-table-top">
<h2><?php 

echo $h2; 
if ( ! empty($restore_link) )
	echo "<span class='rs-revision_top_action rvy-restore-link'> $restore_link</span>";	
?></h2>

<?php
	$msg = '';

	if ( ! empty($_GET['deleted']) )
		$msg = __('The revision was deleted.', 'revisionary');

	elseif ( isset($_GET['bulk_deleted']) )
		$msg = sprintf( _n( '%s revision was deleted', '%s revisions were deleted', $_GET['bulk_deleted'] ), number_format_i18n( $_GET['bulk_deleted'] ) );
		
	elseif ( ! empty($_GET['rvy_updated']) )
		$msg = __('The revision was updated.', 'revisionary');
		
	elseif ( ! empty($_GET['restored_post'] ) )
		$msg = __('The revision was restored.', 'revisionary');
		
	elseif ( ! empty($_GET['scheduled'] ) )
		$msg = __('The revision was scheduled for publication.', 'revisionary');

	elseif ( ! empty($_GET['published_post'] ) )
		$msg = __('The revision was published.', 'revisionary');

	elseif ( ! empty($_GET['delete_request']) ) {
		if ( current_user_can( $delete_cap, $rvy_post->ID, '', array( 'skip_revision_allowance' => true ) ) 
		|| ( ( 'pending' == $revision->post_status ) && ( $revision->post_author == $current_user->ID ) ) )
			$msg = __('To delete the revision, click the link below.', 'revisionary');
		else
			$msg = __('You do not have permission to delete that revision.', 'revisionary');

	} elseif ( ! empty($_GET['unscheduled'] ) )
		$msg = __('The revision was unscheduled.', 'revisionary');

	
	if ( $msg ) {
		echo '<div id="message" class="updated fade clear rvy-message"><p>';
		echo $msg;
		echo '</p></div><br />';	
	}
?>
</td>
<?php
if ( ( ! $action || ( 'view' == $action ) ) && ( $revision ) ) {
echo '<td class="rvy-date-selection">';
	
	// date stuff
	// translators: Publish box date formt, see http://php.net/date
	$datef = __awp( 'M j, Y @ G:i' );

	if ( in_array( $revision->post_status, array( 'publish', 'private' ) ) )
		$stamp = __('Published on: <strong>%1$s</strong>', 'revisionary');
	elseif ( 'future' == $revision->post_status )
		$stamp = __('Scheduled for: <strong>%1$s</strong>', 'revisionary');
	elseif ( 'pending' == $revision->post_status ) {
		if ( strtotime($revision->post_date_gmt) > agp_time_gmt() )
			$stamp = __('Requested Publish Date: <strong>%1$s</strong>', 'revisionary');
		else
			$stamp = __('Requested Publish Date: <strong>Immediate</strong>', 'revisionary');
	} else
		$stamp = __('Modified on: <strong>%1$s</strong>', 'revisionary');

	$use_date = ( 'inherit' == $revision->post_status ) ? $revision->post_modified : $revision->post_date;
	
	$date = agp_date_i18n( $datef, strtotime( $use_date ) );
	
	echo '<div id="rvy_time" class="curtime clear"><span id="saved_timestamp">';
	printf($stamp, $date);
	echo '</span>';
	
	if ( $can_edit && in_array( $revision->post_status, array( 'pending', 'future' ) ) ) {
		echo '&nbsp;<a href="#edit_timestamp" class="edit-timestamp hide-if-no-js" tabindex="4">';
		echo __awp('Edit');
		echo '</a>';
	}
	
	echo '<div id="selected_timestamp_div" style="display:none;">';
	echo '<span id="selected_timestamp"></span>';
	echo '</div>';
	
	if ( $can_edit && in_array( $revision->post_status, array( 'pending', 'future' ) ) ) {
		echo '<div id="timestampdiv" class="hide-if-js clear">';
		
		global $post;	// touch_time function requires this as of WP 2.8
		$buffer_post = $post;
		$post = $revision;
		touch_time(($action == 'edit'),1,4);
		$post = $buffer_post;
		
		echo '</div>';
		
		?>
		<div id="rvy_revision_edit_secondary_div" style="margin-bottom:1em;margin-top:1em">
		<input name="rvy_revision_edit" type="submit" class="button-primary" id="rvy_revision_edit_secondary" tabindex="5" accesskey="p" value="<?php esc_attr_e('Update Revision', 'revisionary') ?>" />
		</div>
		<?php
	}
	echo '</div>';

echo '</td></tr>';
echo '</table>';
	
	echo '
	<div id="poststuff" class="metabox-holder rvy-editor">
	<div id="post-body">
	<div id="post-body-content">
	';
	
	// title stuff
	echo '
	<div id="titlediv" class="rvy-title-div">
	<div id="titlewrap">
		<label class="screen-reader-text" for="title">';
		
	echo( __awp('Title') );
	$disabled = ( $can_edit ) ? '' : 'disabled="disabled"';
	
	echo '
	</label><input type="text" name="post_title" size="30" tabindex="1" value="';
	
	echo esc_attr( htmlspecialchars( $revision->post_title ) );
	
	echo '" id="title" ' . $disabled . '/></div></div>';

		
	// post content
	$id = ( user_can_richedit() ) ? 'postdivrich' : 'postdiv';
	echo "<div id='$id' class='postarea rvy-postarea'>";
	
	$content = ( rvy_edit_content_filtered($revision) ) ? $revision->post_content_filtered : $revision->post_content;
	
	$content = apply_filters( "_wp_post_revision_field_post_content", $content, 'post_content' );
	
	if ( ! user_can_richedit() )
		$content = htmlentities($content);

	if ( awp_ver( '3.3' ) )
		wp_editor( $content, 'content', array( 'media_buttons' => false ) );
	else
		the_editor($content, 'content', 'title', false);
	
	echo '</div>';
	
	do_action( 'rvy-revisions_sidebar' );
	
	if ( $can_edit ) {
?>
<br />
<input name="original_publish" type="hidden" id="original_publish" value="<?php esc_attr_e('Update Revision', 'revisionary') ?>" />
<input name="rvy_revision_edit" type="submit" class="button-primary" id="rvy_revision_edit" tabindex="5" accesskey="p" value="<?php esc_attr_e('Update Revision', 'revisionary') ?>" />
<?php
	}
		
	echo '
	</div>
	</div>
	</div>
	';
} else 
	echo '</tr></table>';
?>

<?php do_action( 'rvy-revisions_meta_boxes' ); ?>

</form>

<div class="ie-fixed">
<?php if ( 'diff' == $action ) : ?>
<?php
$left_status_obj = get_post_status_object( $left_revision->post_status );
$right_status_obj = get_post_status_object( $right_revision->post_status );

// if only one of the revisions is past, force it to left
if ( $right_status_obj && ( $right_status_obj->name == 'inherit' ) && $left_status_obj && ( $right_status_obj->name != 'inherit' ) ) {
	$temp = $right_revision;
	$right_revision = $left_revision;
	$left_revision = $temp;
	
// if one of the comparison revisions is published, force it to left (unless left revision is past)
} elseif( $left_status_obj && ( $left_status_obj->name != 'inherit' ) && $right_status_obj && ( $right_status_obj->public || $right_status_obj->private || $left_status_obj->public || $left_status_obj->private ) ) {
	if ( $right_status_obj && ( $right_status_obj->public || $right_status_obj->private ) ) {
		$temp = $right_revision;
		$right_revision = $left_revision;
		$left_revision = $temp;
	}
	// note: if left revision is published, it will be displayed there regardless of modification dates

// otherwise, force most recently modified revision to the right
} elseif ( strtotime($left_revision->post_modified) > strtotime($right_revision->post_modified) ) {
	$temp = $left_revision;
	$left_revision = $right_revision;
	$right_revision = $temp;
}

do_action( 'rvy_diff_display', $left_revision, $right_revision, $rvy_post );

$title_left = sprintf( __('Older: modified %s', 'revisionary'), $revisionary->admin->convert_link( rvy_post_revision_title( $left_revision, true, 'post_modified' ), 'revision', 'manage' ) );

$title_right = sprintf( __('Newer: modified %s', 'revisionary'), $revisionary->admin->convert_link( rvy_post_revision_title( $right_revision, true, 'post_modified' ), 'revision', 'manage' ) );

$compare_fields = apply_filters( 'rvy_diff_fields', _wp_post_revision_fields(), $rvy_post );

$identical = true;
foreach ( $compare_fields as $field => $field_title ) :
	if ( ( 'post_content' == $field ) && ( ! $action || ( 'view' == $action ) ) )
		continue;
	
	$left_content = maybe_serialize( apply_filters( "_wp_post_revision_field_$field", $left_revision->$field, $field ) );
	$right_content = maybe_serialize( apply_filters( "_wp_post_revision_field_$field", $right_revision->$field, $field ) );
	
	if ( rvy_get_option('diff_display_strip_tags') ) {
		$left_content = strip_tags($left_content);
		$right_content = strip_tags($right_content);
	}
	
	if ( !$content = rvy_text_diff( $left_content, $right_content, array( 'title_left' => $title_left, 'title_right' => $title_right ) ) )
		continue; // There is no difference between left and right
	$identical = false;
	
	if ( ! empty($content) ) :?>
	<div id="revision-field-<?php echo $field; ?>">
		<p class="rvy-revision-field clear"><strong>
		<?php 
		echo esc_html( $field_title ); 
		?>
		</strong></p>
		
		<div class="pre clear"><?php echo $content; ?></div>
	</div>
	<?php endif;

	$title_left = '';
	$title_right = '';
	
endforeach;

endif;  // 'diff' == $action


if ( 'diff' == $action && $identical ) :
	?>

	<div class="updated"><p><?php _e( 'These revisions are identical.' ); ?></p></div>

	<?php

endif;

?>

</div>

<br class="clear" /><br />

<?php
if ( $is_administrator = is_content_administrator_rvy() ) {
	global $wpdb;
	$results = $wpdb->get_results( "SELECT post_status, COUNT( * ) AS num_posts FROM {$wpdb->posts} WHERE post_type = 'revision' AND post_parent = '$rvy_post->ID' GROUP BY post_status" );
	
	$num_revisions = array( 'inherit' => 0, 'pending' => 0, 'future' => 0 );
	foreach( $results as $row )
		$num_revisions[$row->post_status] = $row->num_posts;
		
	$num_revisions = (object) $num_revisions;
}

$status_links = '<ul class="subsubsub">';
foreach ( array_keys($revision_status_captions) as $_revision_status ) {
	$post_id = ( ! empty($rvy_post->ID) ) ? $rvy_post->ID : $revision_id;
	$link = "admin.php?page=rvy-revisions&amp;revision={$post_id}&amp;revision_status=$_revision_status";
	$class = ( $revision_status == $_revision_status ) ? ' class="rvy_current_status rvy_select_status"' : 'class="rvy_select_status"';

	switch( $_revision_status ) {
		case 'inherit':
			$status_caption = __( 'Past Revisions', 'revisionary' );
			break;
		case 'pending':
			$status_caption = __( 'Pending Revisions', 'revisionary' );
			break;
		case 'future':
			$status_caption = __( 'Scheduled Revisions', 'revisionary' );
			break;
	}
	
	if ( $is_administrator ) {
		$label = __( '%1$s <span class="count"> (%2$s)</span>', 'revisionary' );
		$status_links .= "<li $class><a href='$link'>" . sprintf( _nx( $label, $label, $num_revisions->$_revision_status, $label ), $status_caption, number_format_i18n( $num_revisions->$_revision_status ) ) . '</a></li>';
	} else
		$status_links .= "<li $class><a href='$link'>" . $status_caption . '</a></li>';
}
$status_links .= '</ul>';

echo $status_links;

$args = array( 'format' => 'form-table', 'parent' => true, 'right' => $right, 'left' => $left, 'current_id' => isset($revision_id) ? $revision_id : 0 );

$count = rvy_list_post_revisions( $rvy_post, $revision_status, $args );
if ( $count < 2 ) {
	echo( '<br class="clear" /><p>' );
	printf( __( 'no %s revisions available.', 'revisionary'), strtolower($revision_status_captions[$revision_status]) );
	echo( '</p>' );
}

?>

</div>

<?php
// WP 3.6 changed diff table format.  For now, just port text diff code from WP 3.5.

/**
 * Displays a human readable HTML representation of the difference between two strings.
 *
 * The Diff is available for getting the changes between versions. The output is
 * HTML, so the primary use is for displaying the changes. If the two strings
 * are equivalent, then an empty string will be returned.
 *
 * The arguments supported and can be changed are listed below.
 *
 * 'title' : Default is an empty string. Titles the diff in a manner compatible
 *		with the output.
 * 'title_left' : Default is an empty string. Change the HTML to the left of the
 *		title.
 * 'title_right' : Default is an empty string. Change the HTML to the right of
 *		the title.
 *
 * @since 2.6
 * @see wp_parse_args() Used to change defaults to user defined settings.
 * @uses Text_Diff
 * @uses WP_Text_Diff_Renderer_Table
 *
 * @param string $left_string "old" (left) version of string
 * @param string $right_string "new" (right) version of string
 * @param string|array $args Optional. Change 'title', 'title_left', and 'title_right' defaults.
 * @return string Empty string if strings are equivalent or HTML with differences.
 */
function rvy_text_diff( $left_string, $right_string, $args = null ) {
	$defaults = array( 'title' => '', 'title_left' => '', 'title_right' => '' );
	$args = wp_parse_args( $args, $defaults );

	if ( !class_exists( 'WP_Text_Diff_Renderer_Table' ) )
		require( dirname(__FILE__) . '/includes/wp-diff.php' );

	$left_string  = normalize_whitespace($left_string);
	$right_string = normalize_whitespace($right_string);

	$left_lines  = explode("\n", $left_string);
	$right_lines = explode("\n", $right_string);

	$text_diff = new Text_Diff($left_lines, $right_lines);
	$renderer  = new WP_Text_Diff_Renderer_Table();
	$diff = $renderer->render($text_diff);

	if ( !$diff )
		return '';

	$r  = "<table class='diff'>\n";
	$r .= "<col class='ltype' /><col class='content' /><col class='ltype' /><col class='content' />";

	if ( $args['title'] || $args['title_left'] || $args['title_right'] )
		$r .= "<thead>";
	if ( $args['title'] )
		$r .= "<tr class='diff-title'><th colspan='4'>$args[title]</th></tr>\n";
	if ( $args['title_left'] || $args['title_right'] ) {
		$r .= "<tr class='diff-sub-title'>\n";
		$r .= "\t<td></td><th>$args[title_left]</th>\n";
		$r .= "\t<td></td><th>$args[title_right]</th>\n";
		$r .= "</tr>\n";
	}
	if ( $args['title'] || $args['title_left'] || $args['title_right'] )
		$r .= "</thead>\n";

	$r .= "<tbody>\n$diff\n</tbody>\n";
	$r .= "</table>";

	return $r;
}

function rvy_edit_content_filtered( $revision ) {
	$use_content_filtered = false;
	
	if ( ! empty( $revision->post_content_filtered ) ) {
		if ( class_exists('WPCom_Markdown') && ! defined( 'RVY_DISABLE_MARKDOWN_WORKAROUND' ) )
			$use_content_filtered = true;
	}
	
	return apply_filters( 'rvy_edit_content_filtered', $use_content_filtered, $revision );
}
?>