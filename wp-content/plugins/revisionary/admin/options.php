<?php
if( basename(__FILE__) == basename($_SERVER['SCRIPT_FILENAME']) )
	die( 'This page cannot be called directly.' );


class RvyOptionUI {
	var $sitewide;
	var $customize_defaults;
	var $form_options;
	var $tab_captions;
	var $section_captions;
	var $option_captions;
	var $all_options;
	var $all_otype_options;
	var $def_otype_options;
	var $display_hints = true;
		
	function RvyOptionUI( $sitewide, $customize_defaults ) {
		$this->sitewide = $sitewide;
		$this->customize_defaults = $customize_defaults;
	}
	
	function option_checkbox( $option_name, $tab_name, $section_name, $hint_text, $trailing_html, $args = '') {
		$return = array( 'in_scope' => false, 'val' => '', 'subcaption' => '' );
		
		if ( ! is_array($args) )
			$args = array();
		
		if ( in_array( $option_name, $this->form_options[$tab_name][$section_name] ) ) {
			$this->all_options []= $option_name;
			
			$return['val'] = rvy_get_option($option_name, $this->sitewide, $this->customize_defaults);
				
			$js_clause = ( ! empty($args['js_call']) ) ? 'onclick="' . $args['js_call'] . '"'  : '';
			$style = ( ! empty($args['style']) ) ? $args['style'] : '';
			
			echo "<div class='agp-vspaced_input'>"
				. "<label for='$option_name'><input name='$option_name' type='checkbox' $js_clause $style id='$option_name' value='1' " . checked('1', $return['val'], false) . " /> "
				. $this->option_captions[$option_name]
				. "</label>";
				
			if ( $hint_text && $this->display_hints )
				echo "<br /><span class='rs-subtext'>" . $hint_text . "</span>";
				
			if ( ! empty($args['subcaption']) )
				echo $args['subcaption'];
				
			echo "</div>";

			if ( $trailing_html )
				echo $trailing_html;
			
			$return['in_scope'] = true;	
		}
		
		return $return;
	}
}
	
	
function rvy_options( $sitewide = false, $customize_defaults = false ) {

if ( ! current_user_can( 'manage_options' ) || ( $sitewide && ! is_super_admin() ) )
	wp_die(__awp('Cheatin&#8217; uh?'));

if ( $sitewide )
	$customize_defaults = false;	// this is intended only for storing custom default values for site-specific options

$ui = new RvyOptionUI( $sitewide, $customize_defaults );
	
rvy_refresh_default_options();

$ui->all_options = array();

$ui->tab_captions = array( 'features' => __( 'Features', 'revisionary' ), 'optscope' => __( 'Option Scope', 'revisionary' ) );

$ui->section_captions = array(
	'features' => array(
		'role_definition' => __('Role Definition', 'revisionary'),
		'revisions'		=> __('Revisions', 'revisionary'),
		'notification'	=> __('Email Notification', 'revisionary')
	)
);


// TODO: replace individual _e calls with these (and section, tab captions)
$ui->option_captions = array(
	'pending_revisions' => __('Pending Revisions', 'revisionary'),
	'scheduled_revisions' => __('Scheduled Revisions', 'revisionary'),
	'revisor_lock_others_revisions' => __("Prevent Revisors from editing others&apos; revisions", 'revisionary'),
	'diff_display_strip_tags' => __('Strip html tags out of difference display', 'revisionary'),
	'async_scheduled_publish' => __('Asynchronous Publishing', 'revisionary'),
	'pending_rev_notify_author' => __('Email original Author when a Pending Revision is submitted', 'revisionary'),
	'rev_approval_notify_author' => __('Email the original Author when a Pending Revision is approved', 'revisionary'),
	'rev_approval_notify_revisor' => __('Email the Revisor when a Pending Revision is approved', 'revisionary'),
	'publish_scheduled_notify_author' => __('Email the original Author when a Scheduled Revision is published', 'revisionary'),
	'publish_scheduled_notify_revisor' => __('Email the Revisor when a Scheduled Revision is published', 'revisionary'),
	'revisor_role_add_custom_rolecaps' => __('Include capabilities for all custom post types in the WordPress Revisor role', 'revisionary' ),
	'require_edit_others_drafts' => __('Prevent Revisors from editing other user&apos;s drafts', 'revisionary' ),
);

if ( defined('RVY_CONTENT_ROLES') ) {
	$ui->option_captions['pending_rev_notify_admin'] = __('Email designated Publishers when a Pending Revision is submitted', 'revisionary');
	$ui->option_captions['publish_scheduled_notify_admin'] = __('Email designated Publishers when a Scheduled Revision is published', 'revisionary');
} else {
	$ui->option_captions['pending_rev_notify_admin'] = __('Email Editors and Administrators when a Pending Revision is submitted', 'revisionary');
	$ui->option_captions['publish_scheduled_notify_admin'] = __('Email Editors and Administrators when a Scheduled Revision is published', 'revisionary');
}
	

$ui->form_options = array( 
'features' => array(
	'role_definition' => 	array( 'revisor_role_add_custom_rolecaps', 'require_edit_others_drafts' ),
	'revisions'		=>		array( 'scheduled_revisions', 'pending_revisions', 'revisor_lock_others_revisions', 'diff_display_strip_tags', 'async_scheduled_publish' ),
	'notification'	=>		array( 'pending_rev_notify_admin', 'pending_rev_notify_author', 'rev_approval_notify_author', 'rev_approval_notify_revisor', 'publish_scheduled_notify_admin', 'publish_scheduled_notify_author', 'publish_scheduled_notify_revisor' )
)
);


if ( RVY_NETWORK ) {
	if ( $sitewide )
		$available_form_options = $ui->form_options;
	
	global $rvy_options_sitewide;
	
	foreach ( $ui->form_options as $tab_name => $sections )
		foreach ( $sections as $section_name => $option_names ) {
			if ( $sitewide )
				$ui->form_options[$tab_name][$section_name] = array_intersect( $ui->form_options[$tab_name][$section_name], array_keys($rvy_options_sitewide) );
			else
				$ui->form_options[$tab_name][$section_name] = array_diff( $ui->form_options[$tab_name][$section_name], array_keys($rvy_options_sitewide) );
		}
				
	foreach ( $ui->form_options as $tab_name => $sections )
		foreach ( array_keys($sections) as $section_name )
			if ( empty( $ui->form_options[$tab_name][$section_name] ) )
				unset( $ui->form_options[$tab_name][$section_name] );
}
?>
<div class='wrap'>
<?php
echo '<form action="" method="post">';
wp_nonce_field( 'rvy-update-options' );

if ( $sitewide )
	echo "<input type='hidden' name='rvy_options_doing_sitewide' value='1' />";
	
if ( $customize_defaults )
	echo "<input type='hidden' name='rvy_options_customize_defaults' value='1' />";
	
?>
<table width = "100%"><tr>
<td width = "90%">
<h2><?php 
if ( $sitewide )
	_e('Revisionary Network Options', 'revisionary');
elseif ( $customize_defaults )
	_e('Revisionary Default Site Options', 'revisionary');
elseif ( RVY_NETWORK )
	_e('Revisionary Site Options', 'revisionary');
else
	_e('Revisionary Options', 'revisionary');
?>
</h2>
</td>
<td>
<div class="submit" style="border:none;float:right;margin:0;">
<input type="submit" name="rvy_submit" class="button-primary" value="<?php _e('Update &raquo;', 'revisionary');?>" />
</div>
</td>
</tr></table>
<?php
if ( $sitewide ) {
	$color_class = 'rs-backgreen';
	echo '<p style="margin-top:0">';
	_e( 'These settings will be applied to all sites.', 'revisionary' );
	echo '</p>';
	
} elseif ( $customize_defaults ) {
	$color_class = 'rs-backgray';
	echo '<p style="margin-top:0">';
	_e( 'These are the <strong>default</strong> settings for options which can be adjusted per-site.', 'revisionary' );
	echo '</p>';
	
} else
	$color_class = 'rs-backtan';

$class_selected = "agp-selected_agent agp-agent $color_class";
$class_unselected = "agp-unselected_agent agp-agent";

// todo: prevent line breaks in these links
$js_call = "agp_swap_display('rs-features', 'rs-optscope', 'rvy_show_features', 'rvy_show_optscope', '$class_selected', '$class_unselected');";
echo "<ul class='rs-list_horiz' style='margin-bottom:-0.1em'>"
	. "<li class='$class_selected'>"
	. "<a id='rvy_show_features' href='javascript:void(0)' onclick=\"$js_call\">" . $ui->tab_captions['features'] . '</a>'
	. '</li>';

if ( $sitewide ) {
	$js_call = "agp_swap_display('rs-optscope', 'rs-features', 'rvy_show_optscope', 'rvy_show_features', '$class_selected', '$class_unselected');";
	echo "<li class='$class_unselected'>"
		. "<a id='rvy_show_optscope' href='javascript:void(0)' onclick=\"$js_call\">" . $ui->tab_captions['optscope'] . '</a>'
		. '</li>';
}

echo '</ul>';

// ------------------------- BEGIN Features tab ---------------------------------

$tab = 'features';
echo "<div id='rs-features' style='clear:both;margin:0' class='rs-options $color_class'>";
	
if ( rvy_get_option('display_hints', $sitewide, $customize_defaults) ) {
	echo '<div class="rs-optionhint">';
	_e('This page enables <strong>optional</strong> adjustment of Revisionary\'s features. For most installations, the default settings are fine.', 'revisionary');
	
	if ( RVY_NETWORK && is_super_admin() ) {
		if ( $sitewide ) {
			if ( ! $customize_defaults ) {
				$link_open = "<a href='admin.php?page=rvy-options'>";
				$link_close = '</a>';
		
				echo  ' ';
				printf( __('Note that %1$s site-specific options%2$s may also be available.', 'revisionary'), $link_open, $link_close );
			}
		} else {
			$link_open = "<a href='admin.php?page=rvy-site_options'>";
			$link_close = '</a>';
	
			echo ' ';
			printf( __('Note that %1$s network-wide options%2$s may also be available.', 'revisionary'), $link_open, $link_close );
		}
	}
	
	echo '</div>';
}

$table_class = 'form-table rs-form-table';
?>

<table class="<?php echo($table_class);?>" id="rs-admin_table">

<?php
// possible TODO: replace redundant hardcoded IDs with $id

	$section = 'role_definition';			// --- ROLE DEFINITION SECTION ---

	if ( ! empty( $ui->form_options[$tab][$section] ) ) :?>
		<tr valign="top"><th scope="row">
		<?php echo $ui->section_captions[$tab][$section]; ?>
		</th><td>

		<?php 
		$ui->option_checkbox( 'revisor_role_add_custom_rolecaps', $tab, $section, '', '' );
		?>
		
		<?php
		$hint = __( 'If checked, users lacking site-wide publishing capabilities will also be checked for the edit_others_drafts capability', 'revisionary' );
		$ui->option_checkbox( 'require_edit_others_drafts', $tab, $section, $hint, '' );
		?>
		
		</td></tr>
	<?php endif; // any options accessable in this section


$pending_revisions_available = ! RVY_NETWORK || $sitewide || empty( $rvy_options_sitewide['pending_revisions'] ) || rvy_get_option( 'pending_revisions', true );
$scheduled_revisions_available = ! RVY_NETWORK || $sitewide || empty( $rvy_options_sitewide['scheduled_revisions'] ) || rvy_get_option( 'scheduled_revisions', true );

if ( 	// To avoid confusion, don't display any revision options in MS Site Options if pending revisions / scheduled revisions are unavailable
$pending_revisions_available || $scheduled_revisions_available ) :

	$section = 'revisions';			// --- REVISIONS SECTION ---

	if ( ! empty( $ui->form_options[$tab][$section] ) ) :?>
		<tr valign="top"><th scope="row">
		<?php echo $ui->section_captions[$tab][$section]; ?>
		</th><td>
		
		<?php 
		$hint = __( 'If a currently published post or page is edited and a future date set, the change will not be applied until the selected date.', 'revisionary' );
		$ui->option_checkbox( 'scheduled_revisions', $tab, $section, $hint, '' );
		
		echo "<div style='display:margin-top: 1em;margin-left:2em;'>";	
		$hint = __( 'Publish scheduled revisions asynchronously, via a secondary http request from the server.  This is normally preferable as it eliminates delay, but some servers do not support it.', 'revisionary' );
		$ui->option_checkbox( 'async_scheduled_publish', $tab, $section, $hint, '<br />' );
		echo '</div>';
		
		$hint = __( 'Enable some users to submit a revision for an existing published post or page which they cannot otherwise edit.  Contributors can submit revisions to their own published content, and users who have the edit_others_ capability (but not edit_published_) can submit revisions to other user\'s content.<br /><br />These Pending Revisions are listed alongside regular pending content, but link to a Revisions management form.  There the pending revision can be viewed, compared to other revisions, and approved or deleted by qualified editors.  If the submitter set a future publish date, approval schedules delayed publication of the revised content.', 'revisionary' );
		$ui->option_checkbox( 'pending_revisions', $tab, $section, $hint, '<br />' );
		
		$hint = '';
		$ui->option_checkbox( 'revisor_lock_others_revisions', $tab, $section, $hint, '' );
		
		$hint = '';
		$ui->option_checkbox( 'diff_display_strip_tags', $tab, $section, $hint, '' );
		?>
		</td></tr>
	<?php endif; // any options accessable in this section
		
	
	$section = 'notification';			// --- NOTIFICATION SECTION ---
		
	if ( ! empty( $ui->form_options[$tab][$section] ) ) :?>
		<tr valign="top"><th scope="row">
		<?php echo $ui->section_captions[$tab][$section]; ?>
		</th><td>
		
		<?php 
		if( $pending_revisions_available ) {
			$subcaption = ( defined('RVY_CONTENT_ROLES') && $group_link = $GLOBALS['revisionary']->content_roles->get_metagroup_edit_link( 'Pending Revision Monitors' ) ) ?
				sprintf( " &bull;&nbsp;<a href='%s'>" . __('select recipients', 'revisionary') . "</a>", $group_link ) : '';
			
			// TODO: $ui->option_dropdown() method
			$id = 'pending_rev_notify_admin';
			if ( in_array( $id, $ui->form_options[$tab][$section] ) ) {
				$ui->all_options []= $id;
				$current_setting = rvy_get_option($id, $sitewide, $customize_defaults);
				
				echo "<select name='$id' id='$id'>";
				$captions = array( 0 => __('Never', 'revisionary'), 1 => __('By default', 'revisionary'), 'always' => __('Always', 'revisionary') );
				foreach ( $captions as $key => $value) {
					$selected = ( $current_setting == $key ) ? 'selected="selected"' : '';
					echo "\n\t<option value='$key' " . $selected . ">$captions[$key]</option>";
				}
				echo '</select>&nbsp;';
				
				echo $ui->option_captions[$id];
				echo $subcaption;
				echo "<br />";
			}
			
			$id = 'pending_rev_notify_author';
			if ( in_array( $id, $ui->form_options[$tab][$section] ) ) {
				$ui->all_options []= $id;
				$current_setting = rvy_get_option($id, $sitewide, $customize_defaults);
				
				echo "<select name='$id' id='$id'>";
				$captions = array( 0 => __('Never', 'revisionary'), 1 => __('By default', 'revisionary'), 'always' => __('Always', 'revisionary') );
				foreach ( $captions as $key => $value) {
					$selected = ( $current_setting == $key ) ? 'selected="selected"' : '';
					echo "\n\t<option value='$key' " . $selected . ">$captions[$key]</option>";
				}
				echo '</select>&nbsp;';
				
				echo $ui->option_captions[$id];
				echo "<br />";
			}
			
			$hint = '';
			$ui->option_checkbox( 'pending_rev_notify_revisor', $tab, $section, $hint, '' );
			
			echo '<br />';
			
			$hint = '';
			$ui->option_checkbox( 'rev_approval_notify_author', $tab, $section, $hint, '' );
			
			$hint = '';
			$ui->option_checkbox( 'rev_approval_notify_revisor', $tab, $section, $hint, '' );
		}
		
		if( $scheduled_revisions_available ) {
			echo '<br />';
					
			$subcaption = ( defined('RVY_CONTENT_ROLES') && $group_link = $GLOBALS['revisionary']->content_roles->get_metagroup_edit_link( 'Scheduled Revision Monitors' ) ) ?
				sprintf( " &bull;&nbsp;<a href='%s'>" . __('select recipients', 'revisionary') . "</a>", $group_link ) : '';

			$hint = '';
			$ui->option_checkbox( 'publish_scheduled_notify_admin', $tab, $section, $hint, '', array( 'subcaption' => $subcaption ) );
			
			$hint = '';
			$ui->option_checkbox( 'publish_scheduled_notify_author', $tab, $section, $hint, '' );
			
			$hint = '';
			$ui->option_checkbox( 'publish_scheduled_notify_revisor', $tab, $section, $hint, '' );
		}
		
		if( $pending_revisions_available ) {
			if ( in_array( 'pending_rev_notify_admin', $ui->form_options[$tab][$section] ) || in_array( 'pending_rev_notify_author', $ui->form_options[$tab][$section] ) ) {
				echo '<br />';
				if ( $ui->display_hints ) {
					echo '<span class="rs-subtext">';
					if ( defined('RVY_CONTENT_ROLES') )
						_e('Note: "by default" means Pending Revision creators can customize email notification recipients before submitting.  Eligibile "Publisher" email recipients are members of the Pending Revision Monitors group who <strong>also</strong> have the ability to publish the revision.  If not explicitly defined, the Monitors group is all users with a primary WP role of Administrator or Editor.', 'revisionary');
					else
						printf( __('Note: "by default" means Pending Revision creators can customize email notification recipients before submitting.  For more flexibility in moderation and notification, install the %1$s Press Permit%2$s %3$s Role Scoper%4$s plugin.', 'revisionary'), "<a href='http://presspermit.com'>", '</a>', "<a href='" . awp_plugin_info_url("role-scoper") . "'>", '</a>' );
					echo '</span>';
				}
			}
		}
		?>
		</td></tr>
	<?php endif; // any options accessable in this section
	
endif;
?>
	
</table>

</div>


<?php


// ------------------------- BEGIN Option Scope tab ---------------------------------
$tab = 'optscope';

if ( $sitewide ) : ?>
<?php
echo "<div id='rs-optscope' style='clear:both;margin:0' class='rs-options agp_js_hide $color_class'>";

if ( $ui->display_hints ) {
	echo '<div class="rs-optionhint">';
	_e("Specify which Revisionary Options should be applied network-wide.", 'revisionary');
	echo '</div><br />';
}

echo '<ul>';
$all_movable_options = array();

$option_scope_stamp = __( 'network-wide control of "%s"', 'revisionary' );

foreach ( $available_form_options as $tab_name => $sections ) {
	echo '<li>';
	
	$explanatory_caption = __( 'Selected options will be controlled network-wide via <strong>Sites > Revisionary Options</strong>; unselected options can be set per-site via <strong>Settings > Revisionary</strong>', 'revisionary' );
	
	if ( isset( $ui->tab_captions[$tab_name] ) )
		$tab_caption = $ui->tab_captions[$tab_name];
	else
		$tab_caption = $tab_name;

	echo '<div style="margin:1em 0 1em 0">';
	if ( $ui->display_hints )
		printf( _x( '<span class="rs-h3text">%1$s</span> (%2$s)', 'option_tabname (explanatory note)', 'revisionary' ), $tab_caption, $explanatory_caption );
	else
		echo $tab_caption;
	echo '</div>';
	
	echo '<ul style="margin-left:2em">';

	foreach ( $sections as $section_name => $option_names ) {
		if ( empty( $sections[$section_name] ) )
			continue;
		
		echo '<li><strong>';
		
		if ( isset( $section_captions[$tab_name][$section_name] ) )
			echo $section_captions[$tab_name][$section_name];
		else
			_e( $section_name );
		
		echo '</strong><ul style="margin-left:2em">';
			
		foreach ( $option_names as $option_name ) {
			if ( $option_name && $ui->option_captions[$option_name] ) {
				$all_movable_options []= $option_name;
				echo '<li>';
				
				$disabled = ( in_array( $option_name, array( 'file_filtering', 'mu_sitewide_groups' ) ) ) ? "disabled='disabled'" : '';
				
				$id = "{$option_name}_sitewide";
				$val = isset( $rvy_options_sitewide[$option_name] );
				echo "<label for='$id'>";
				echo "<input name='rvy_options_sitewide[]' type='checkbox' id='$id' value='$option_name' $disabled " . checked('1', $val, false) . " />";

				printf( $option_scope_stamp, $ui->option_captions[$option_name] );
					
				echo '</label></li>';
			}
		}
		
		echo '</ul>';
	}
	echo '</ul><br /><hr />';
}
echo '</ul>';

echo '</div>';

$all_movable_options = implode(',', $all_movable_options);
echo "<input type='hidden' name='rvy_all_movable_options' value='$all_movable_options' />";

endif; // any options accessable in this tab
// ------------------------- END Option Scope tab ---------------------------------



// this was required for Access Types section, which was removed
//$all = implode(',', $all_otypes);
//echo "<input type='hidden' name='all_object_types' value='$all' />";

$ui->all_options = implode(',', $ui->all_options);
echo "<input type='hidden' name='all_options' value='$ui->all_options' />";

echo "<input type='hidden' name='rvy_submission_topic' value='options' />";
?>
<p class="submit" style="border:none;float:right">
<input type="submit" name="rvy_submit" class="button-primary" value="<?php _e('Update &raquo;', 'revisionary');?>" />
</p>

<?php
$msg = __( "All settings in this form (including those on unselected tabs) will be reset to DEFAULTS.  Are you sure?", 'revisionary' );
$js_call = "javascript:if (confirm('$msg')) {return true;} else {return false;}";
?>
<p class="submit" style="border:none;float:left">

<input type="submit" name="rvy_defaults" value="<?php _e('Revert to Defaults', 'revisionary') ?>" onclick="<?php echo $js_call;?>" />
</p>
</form>
<p style='clear:both'>
</p>
</div>

<?php
} // end function
?>