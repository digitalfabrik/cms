<?php
/*
Plugin Name: Admin Dashboard RSS Feed
Description: This plugin will display company news in WordPress Admin Dashboard. It uses RSS feed URL as input and display the news synopsis in the Admin Dashboard.
Plugin URI: https://www.webstix.com
Author: Webstix
Version:     1.6
Text Domain: admin-dashboard-rss-feed
Author:      Webstix, Inc.
Author URI:  https://www.webstix.com/wordpress-plugin-development
Domain Path: /languages
License: GPL-2.0+
License URI: http://www.gnu.org/licenses/gpl-2.0.txt
*/
// Abort, if the plugin file is accessed from outside.

// header ("Content-Type:text/xml");

if (!defined('WPINC'))
{
	die;
}
/**
 * The code that runs during plugin activation.
 */
function wsx_rss_feed_plugin_activate()
{
	/* Create transient data */
	set_transient('wsx-rss-feed-admin-notice', true, 5);
}
/**
 * Admin Notice on Activation.
 */
add_action('admin_notices', 'wsx_rss_feed_active_admin_notice');
function wsx_rss_feed_active_admin_notice()
{
	/* Check transient, if available display notice */
	if (get_transient('wsx-rss-feed-admin-notice'))
	{
?>
        <div class="updated notice is-dismissible">
        <?php
		echo '<p>';
		echo __('Thank you for using the <strong>Admin Rss Feed</strong> plugin! Go to Plugin ', 'admin-dashboard-rss-feed');
		echo '<a href="' . esc_url(get_admin_url(null, 'options-general.php?page=admin-dashboard-rss-feed%2Fadmin-rss-feed.php')) . '">' . __('Settings Page', 'admin-dashboard-rss-feed') . '</a>';
		echo '</p>';
?>
        </div>
        <?php
		/* Delete transient, only display this notice once. */
		delete_transient('wsx-rss-feed-admin-notice');
	}
}
register_activation_hook(__FILE__, "wsx_rss_feed_plugin_activate");
function wsx_rss_feed_plugin_deactivate()
{
}
register_deactivation_hook(__FILE__, "wsx_rss_feed_plugin_deactivate");
// Settings link on the plugins page
add_filter('plugin_action_links_' . plugin_basename(__FILE__) , 'wsx_rss_feed_plugin_settings_link');
function wsx_rss_feed_plugin_settings_link($wsx_rss_feed_link)
{
	$wsx_rss_feed_link[] = '<a href="' . esc_url(get_admin_url(null, 'options-general.php?page=admin-dashboard-rss-feed%2Fadmin-rss-feed.php')) . '">' . __('Settings', 'admin-dashboard-rss-feed') . '</a>';
	return $wsx_rss_feed_link;
}
// Create custom plugin settings menu
add_action('admin_menu', 'wsx_rss_feed_admin_page');
function wsx_rss_feed_admin_page()
{
	// create new top-level menu
	add_options_page('Admin Dashboard RSS Feed Plugin Settings', 'Admin Dashboard RSS Feed', 'manage_options', __FILE__, 'wsx_rss_feed_admin_settings_page');
	// call register settings function
	add_action('admin_init', 'wsx_rss_feed_admin_settings');
}
function wsx_rss_feed_styles()
{
	wp_register_style('wsx-rss-styles', plugin_dir_url(__FILE__) . 'admin/css/style.css');
	wp_enqueue_style('wsx-rss-styles');
}
add_action('admin_enqueue_scripts', 'wsx_rss_feed_styles');
function wsx_rss_feed_admin_settings()
{
	// register our settings
	// Save attachment ID
	if (isset($_POST['image_attachment_id'])):
		update_option('wsx_rss_feed_image_attachment_id', absint($_POST['image_attachment_id']));
	endif;

	if($_GET['page'] == "admin-dashboard-rss-feed/admin-rss-feed.php") {
		wp_enqueue_media();

	}	

	// Get the Company Logo Title
	register_setting('wsx-rss-feed-settings-group', 'wsx_logo_title');
	// Get the Company Logo Target Link
	register_setting('wsx-rss-feed-settings-group', 'wsx_logo_target_link');
	// Feed URL
	register_setting('wsx-rss-feed-settings-group', 'wsx_rss_feed_url');
	// Feed Count
	register_setting('wsx-rss-feed-settings-group', 'wsx_rss_feed_count');
}

function wsx_rss_feed_admin_settings_page() { ?>
<div class="wrap">
<h2>Admin Dashboard RSS Feed Settings </h2>

<form method="post" action="options.php">
<?php wp_nonce_field('nonce-to-check'); ?>
    <?php
	settings_fields('wsx-rss-feed-settings-group'); ?>
    <?php
	do_settings_sections('wsx-rss-feed-settings-group');
?>
    <table class="form-table">

    	<tr valign="top">
			<th scope="row">Your Company Name:</th>
			<td><input type="text" name="wsx_logo_title" value="<?php
	echo esc_attr(get_option('wsx_logo_title')); ?>" style="width: 450px;" /></td>
        </tr>	
	
		<tr valign="top">
			<th scope="row">Your Company Logo:</th>
			<td>
			<div class='image-preview-wrapper'>
			<img id='image-preview' src="<?php
	echo wp_get_attachment_url(get_option('wsx_rss_feed_image_attachment_id')); ?>" height='100'>
		</div>
		<input id="upload_image_button" type="button" class="button" value="<?php
	_e('Upload image'); ?>" />

	<input id="delete_image_button" type="button" class="button" value="<?php
	_e('Delete image'); ?>" /><small class="wsx-small">(We suggest 64 pixels wide x 64 pixels high)</small>
		<input type='hidden' name='image_attachment_id' id='image_attachment_id' value='<?php
	echo get_option('wsx_rss_feed_image_attachment_id'); ?>'></td>
        </tr>		
		
		<tr valign="top">
			<th scope="row">Your Company Website:</th>
			<td><input type="text" name="wsx_logo_target_link" value="<?php
	echo esc_attr(get_option('wsx_logo_target_link')); ?>" style="width: 450px;" /></td>
        </tr>
	
        <tr valign="top">
			<th scope="row">Your Company RSS Feed URL:</th>
			<td><input class="wsx_rss_feed_url" type="text" name="wsx_rss_feed_url" value="<?php
	echo esc_attr(get_option('wsx_rss_feed_url')); ?>" style="width: 450px;" required /></td>
        </tr>
         
        <tr valign="top">
			<th scope="row">Number of items to display: <small style="font-weight: normal;">(You can show between 1 to 10)</small></th>
			<td><input type="text" name="wsx_rss_feed_count" value="<?php
	echo esc_attr(get_option('wsx_rss_feed_count')); ?>" required /></td>
        </tr>
    </table>
	<input name="wsx-rss-feed-form-submit" id="submit" class="button button-primary wsx-rss-feed-btn" value="Save Changes" type="submit">

</form>
</div>
<?php
}
// Initiate the admin page option here:
function admin_rss_feed_register_widgets()
{
	global $wp_meta_boxes;
	$wsx_company_name = esc_attr(get_option('wsx_logo_title'));
	wp_add_dashboard_widget('widget_wsx_rss_feed', __($wsx_company_name . ' News', 'wsx_rss_feeds') , 'wsx_rss_feed_create_box');
}
add_action('wp_dashboard_setup', 'admin_rss_feed_register_widgets');
// Show RSS Feeds on the WP Dashboard page
function wsx_rss_feed_create_box()
{
	// Get RSS Feed(s)
	include_once (ABSPATH . WPINC . '/feed.php');

	// Get the RSS feed URL
	$rss_feed_url = esc_attr(get_option('wsx_rss_feed_url'));
	if ($rss_feed_url <> "")
	{
		$rss_feed_url = esc_attr(get_option('wsx_rss_feed_url'));
	}
	else
	{
		echo "Enter the valid feed URL";
	}


	$wsx_logo_url = wp_get_attachment_url(get_option('wsx_rss_feed_image_attachment_id'));
	$wsx_logo_title = esc_attr(get_option('wsx_logo_title'));
	$wsx_logo_target_link = esc_attr(get_option('wsx_logo_target_link'));
	
	echo '<div class="wsx-rss-feed-widget1">';

		if ($wsx_logo_url <> "" && $wsx_logo_target_link <> "" && $wsx_logo_title <> "")
		{
			$title = 'title="' . $wsx_logo_title . '"';
			$alt = 'alt="' . $wsx_logo_title . '"';
			echo "<div class='wsx-log-wrap clearfix'><a $title href=" . $wsx_logo_target_link . " target='_blank'><img $alt src=" . $wsx_logo_url . "></a>";
			echo '<div class="wsx_company_name"><span class="wsx_comp_name">' . $wsx_logo_title . '</span>';
			echo '<a href="' . $wsx_logo_target_link . '" target="_blank" title="' . $wsx_logo_title . '"><span class="wsx_company_url">' . $wsx_logo_target_link . '</span></a></div></div>';
		}
		else if ($wsx_logo_url <> "" && $wsx_logo_target_link <> "")
		{
			echo "<div class='wsx-log-wrap clearfix'><a href=" . $wsx_logo_target_link . " target='_blank'><img src=" . $wsx_logo_url . "></a>";
			echo '<a href="' . $wsx_logo_target_link . '" target="_blank"><span class="wsx_company_url">' . $wsx_logo_target_link . '</span></a></div>';
		}
		else if ($wsx_logo_url <> "" && $wsx_logo_title <> "")
		{
			$alt = 'alt="' . $wsx_logo_title . '"';
			echo "<div class='wsx-log-wrap clearfix'><img $alt src=" . $wsx_logo_url . ">";
			echo '<div class="wsx_company_name"><span class="wsx_comp_name">' . $wsx_logo_title . '</span></div>';
		}
		else if ($wsx_logo_title <> "" && $wsx_logo_target_link <> "")
		{
			echo '<div class="wsx-log-wrap clearfix"><div style="float: none;" class="wsx_company_name"><span class="wsx_comp_name">' . $wsx_logo_title . '</span></div><br />';
			echo '<a style="float: none;" href="' . $wsx_logo_target_link . '" target="_blank" title="' . $wsx_logo_title . '"><span class="wsx_company_url">' . $wsx_logo_target_link . '</span></a></div>';
		}
		else
		{
			if ($wsx_logo_url <> "")
			{
				echo "<div class='wsx-log-wrap clearfix'><img src=" . $wsx_logo_url . "></div>";
			}
			if ($wsx_logo_title <> "")
			{
				echo '<div class="wsx-log-wrap clearfix"><div class="wsx_company_name"><span class="wsx_comp_name">' . $wsx_logo_title . '</span></div></div>';
			}
			if ($wsx_logo_target_link <> "")
			{
				echo "<div class='wsx-log-wrap clearfix'><a href=" . $wsx_logo_target_link . " target='_blank'><span class='wsx_company_url'>" . $wsx_logo_target_link . "</span></a></div>";
			}
		}
	
	echo '</div>';
	
	echo '<ul class="wsx-feed-list">';

	if($rss_feed_url !="http://feeds.feedburner.com/Webstix") {

		$xml = simplexml_load_file($rss_feed_url);

	    $in = 1; 
	    
	    $feed_count = esc_attr(get_option('wsx_rss_feed_count'));
	    
	    foreach($xml->channel->item as $entry) {
	    	if($in <= $feed_count) {
				echo "<li><a href='$entry->link' title='$entry->title'>" . $entry->title . "</a>";
				echo "<p style='color:grey'>" . date_i18n("j. F Y", strtotime($entry->pubDate)) . "</p>";
	        	echo "<p>" . wp_html_excerpt($entry->description, 150) . "...</p></li>";
	        	$in++;
	        }
	        
	    }	    

    } else {
    	$settings = '<a href="' . esc_url(get_admin_url(null, 'options-general.php?page=admin-dashboard-rss-feed%2Fadmin-rss-feed.php')) . '">Settings</a>';
		echo '<li><strong>' . __('Something went wrong! Please check the Feed URL ' . $settings . ' and provide valid RSS feed URL and check back this page.', 'admin_rss_feed') . '</strong></li>';
    }

    echo '</ul>';

}
add_action('admin_footer', 'media_selector_print_script');
function media_selector_print_script()
{

	if($_GET['page'] == "admin-dashboard-rss-feed/admin-rss-feed.php") {

		$wsx_rss_feed_image_attachment_post_id = get_option('wsx_rss_feed_image_attachment_id', 0);
	?><script type='text/javascript'>
			jQuery( document ).ready( function( $ ) {

				$(' .image-preview-wrapper').css('display', 'none');
				$(' .image-preview-wrapper > img#image-preview ').css('display', 'none');

				// Uploading files
				var file_frame;
				var wp_media_post_id = wp.media.model.settings.post.id; // Store the old id
				var set_to_post_id = <?php
		echo $wsx_rss_feed_image_attachment_post_id; ?>; // Set this

			if(set_to_post_id == 0) {
				$(' .image-preview-wrapper').css('display', 'none');
				$(' .image-preview-wrapper > img#image-preview ').css('display', 'none');
			} else {
				$(' .image-preview-wrapper').css('display', 'block');
				$(' .image-preview-wrapper > img#image-preview ').css('display', 'block');
			}

				jQuery('#upload_image_button').on('click', function( event ){
					event.preventDefault();

					$(' .image-preview-wrapper').css('display', 'block');
					$(' .image-preview-wrapper > img#image-preview ').css('display', 'block');

					// If the media frame already exists, reopen it.
					if ( file_frame ) {

						// Set the post ID to what we want
						file_frame.uploader.uploader.param( 'post_id', set_to_post_id );

						// Open frame
						file_frame.open();
						return;
					} else {
						// Set the wp.media post id so the uploader grabs the ID we want when initialised
						wp.media.model.settings.post.id = set_to_post_id;
					}

					// Create the media frame.
					file_frame = wp.media.frames.file_frame = wp.media({
						title: 'Select a image to upload',
						button: {
							text: 'Use this image',
						},
						multiple: false	// Set to true to allow multiple files to be selected
					});

					// When an image is selected, run a callback.
					file_frame.on( 'select', function() {

					$(' .image-preview-wrapper').css('display', 'block!important');
					$(' .image-preview-wrapper > img#image-preview ').show();

						// We set multiple to false so only get one image from the uploader
						attachment = file_frame.state().get('selection').first().toJSON();

						// Do something with attachment.id and/or attachment.url here
						$( '#image-preview' ).attr( 'src', attachment.url ).css( 'width', 'auto' );
						$( '#image_attachment_id' ).val( attachment.id );

						// Restore the main post ID
						wp.media.model.settings.post.id = wp_media_post_id;
					});

						// Finally, open the modal
						file_frame.open();
				});

				$("form").on('click', '#delete_image_button', function (e) {
			        $( '#image-preview' ).attr('src', '').hide();
			        $( '#image_attachment_id' ).val( "" );
			    });

				// Restore the main ID when the add media button is pressed
				jQuery( 'a.add_media' ).on( 'click', function() {
					wp.media.model.settings.post.id = wp_media_post_id;
				});
			});
		</script>
<?php } // End of If part
} ?>