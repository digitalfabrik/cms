<?php
/*
 * Plugin Name: Author Chat Plugin
 * Plugin URI: http://ordin.pl/
 * Description: Plugin that gives your authors an easy way to communicate through back-end UI (admin panel).
 * Author: Piotr Pesta
 * Version: 1.4.3
 * Author URI: http://ordin.pl/
 * License: GPL12
 * Text Domain: author-chat
 * Domain Path: /lang
 */

include 'pp-process.php';

add_action('admin_menu', 'pp_author_chjquery-simply-countableat_setup_menu');
add_action('wp_dashboard_setup', 'pp_wp_dashboard_author_chat');
add_action('admin_enqueue_scripts', 'pp_scripts_admin_chat');
register_activation_hook(__FILE__, 'pp_author_chat_activate');
register_uninstall_hook(__FILE__, 'pp_author_chat_uninstall');
add_action('plugins_loaded', 'pp_author_chat_load_textdomain');
add_action('in_admin_footer', 'pp_author_chat_chat_on_top');

function pp_author_chat_load_textdomain() {
	switch_to_blog(1);
	load_plugin_textdomain('author-chat', false, dirname(plugin_basename(__FILE__)) . '/lang/');
	restore_current_blog();
}

// create author_chat table
function pp_author_chat_activate() {
	switch_to_blog(1);
	global $wpdb;
	$mydb = new wpdb(DB_USER,DB_PASSWORD,DB_NAME,DB_HOST);
	$author_chat_table = $wpdb->base_prefix.'author_chat';
	$author_chat_color = $wpdb->base_prefix.'author_chat_colors';
	$mydb->query("CREATE TABLE IF NOT EXISTS $author_chat_table (
		id BIGINT(50) NOT NULL AUTO_INCREMENT PRIMARY KEY,
		nickname TINYTEXT NOT NULL,
		content TEXT NOT NULL,
		date DATETIME,
		email TINYTEXT NOT NULL,
		tag TINYTEXT NOT NULL,
		color TINYTEXT NOT NULL)
		CHARACTER SET utf8 COLLATE utf8_bin
		;");
    $mydb->query("CREATE TABLE IF NOT EXISTS $author_chat_color (
		id BIGINT(50) NOT NULL AUTO_INCREMENT PRIMARY KEY,
		site TINYTEXT NOT NULL,
		tag TINYTEXT NOT NULL,
		color TINYTEXT NOT NULL)
		CHARACTER SET utf8 COLLATE utf8_bin
		;");
	add_option('author_chat_settings', 30);
	add_option('author_chat_settings_access_editor', 0);
	add_option('author_chat_settings_access_author', 0);
	add_option('author_chat_settings_access_contributor', 0);
	add_option('author_chat_settings_access_subscriber', 0);
	add_option('author_chat_settings_access_all_users', 1);
	add_option('author_chat_settings_name', 0);
	restore_current_blog();
}

// delete author_chat table
function pp_author_chat_uninstall() {
	switch_to_blog(1);
	global $wpdb;
	$mydb = new wpdb(DB_USER,DB_PASSWORD,DB_NAME,DB_HOST);
	$author_chat_table = $wpdb->base_prefix.'author_chat';
	$mydb->query("DROP TABLE IF EXISTS $author_chat_table");
	delete_option('author_chat_settings');
	delete_option('author_chat_settings_delete');
	delete_option('author_chat_settings_access_editor');
	delete_option('author_chat_settings_access_author');
	delete_option('author_chat_settings_access_contributor');
	delete_option('author_chat_settings_access_subscriber');
	delete_option('author_chat_settings_access_all_users');
	delete_option('author_chat_settings_name');
	restore_current_blog();
}

function pp_scripts_admin_chat() {
	switch_to_blog(1);
	wp_enqueue_script('chat-script', plugins_url('chat.js', __FILE__), array('jquery'));
	wp_enqueue_style('author-chat-style', plugins_url('author-chat-style.css', __FILE__));
	restore_current_blog();
}

function pp_author_chat_setup_menu() {
	switch_to_blog(1);
	include 'pp-options.php';
	$optionsTitle = __('Author Chat Options', 'author-chat');
	add_dashboard_page('Author Chat', 'Author Chat', 'read', 'author-chat', 'pp_author_chat');
	add_menu_page($optionsTitle, $optionsTitle, 'administrator', 'acset', 'author_chat_settings', 'dashicons-carrot');
	add_action('admin_init', 'register_author_chat_settings');
	restore_current_blog();
}

function pp_wp_dashboard_author_chat() {
	switch_to_blog(1);
	//add_meta_box('author-chat-widget', 'Autorenchat', 'pp_author_chat', 'dashboard', 'advanced', 'high');
	wp_add_dashboard_widget('author-chat-widget', 'Author Chat', 'pp_author_chat');
	restore_current_blog();
}

function register_author_chat_settings() {
	switch_to_blog(1);
	register_setting('author_chat_settings_group', 'author_chat_settings');
	register_setting('author_chat_settings_group', 'author_chat_settings_delete');
	register_setting('author_chat_settings_group', 'author_chat_settings_access_editor');
	register_setting('author_chat_settings_group', 'author_chat_settings_access_author');
	register_setting('author_chat_settings_group', 'author_chat_settings_access_contributor');
	register_setting('author_chat_settings_group', 'author_chat_settings_access_subscriber');
	register_setting('author_chat_settings_group', 'author_chat_settings_access_all_users');
	register_setting('author_chat_settings_group', 'author_chat_settings_name');
	restore_current_blog();
}

class author_chat {

}

function pp_author_chat() {
	switch_to_blog(1);
	$current_user = wp_get_current_user();
	$current_site = get_current_site();
	if ( current_user_can('publish_pages') || (get_option('author_chat_settings_access_subscriber') == '1' && $current_user->user_level == '0') || (get_option('author_chat_settings_access_contributor') == '1' && $current_user->user_level == '1') || (get_option('author_chat_settings_access_author') == '1' && $current_user->user_level == '2') || (get_option('author_chat_settings_access_editor') == '1' && $current_user->user_level == '3') || (get_option('author_chat_settings_access_editor') == '1' && $current_user->user_level == '4') || (get_option('author_chat_settings_access_editor') == '1' && $current_user->user_level == '5') || (get_option('author_chat_settings_access_editor') == '1' && $current_user->user_level == '6') || (get_option('author_chat_settings_access_editor') == '1' && $current_user->user_level == '7' || $current_user->user_level == '8' || $current_user->user_level == '9' || $current_user->user_level == '10') || get_option('author_chat_settings_access_all_users') == '1') {
		?>

		<script type="text/javascript">
			var chat = new Chat();
			jQuery(window).load(function () {
				chat.initiate();
				setInterval(function () {
					chat.getState();
				}, 2000);
			});

		</script>

		<div id="page-wrap">
			<span id="subject-area">
		<!--<form id="select-subject-area">
			<select name="Thema">
				<option>General</option>
			</select>
		</form>-->
		</span>

			<span id="name-area" style="float: left"></span>

			<div id="chat-wrap"><div id="chat-area"></div></div>

			<form id="send-message-area">
				<textarea id="sendie" maxlength = "2000" placeholder="<?php _e('Deine Nachricht...', 'author-chat'); ?>"></textarea>
			</form>

		</div>

		<script type="text/javascript">

			// shows current user name as name
			var name = "<?php echo $username = (get_option('author_chat_settings_name') == 0) ? $current_user->user_login : $current_user->display_name; ?>";
		var mail = "<?php echo $usermail = $current_user->user_email; ?>";
		var site = "<?php echo $usersite = $current_site->site_name; ?>";
			// display name on page
			jQuery("#name-area").html("<?php _e('Dein Nickname:', 'author-chat'); ?> <span>" +name + "</span>");

			// kick off chat
			var chat = new Chat();
			jQuery(function () {

				// watch textarea for key presses
				jQuery("#sendie").keydown(function (event) {

					var key = event.which;

					//all keys including return.  
					if (key >= 33) {

						var maxLength = jQuery(this).attr("maxlength");
						var length = this.value.length;

						// don't allow new content if length is maxed out
						if (length >= maxLength) {
							event.preventDefault();
						}
					}
				});
				// watch textarea for release of key press
				jQuery('#sendie').keyup(function (e) {

					if (e.keyCode == 13) {

						var text = jQuery(this).val();
						var maxLength = jQuery(this).attr("maxlength");
						var length = text.length;

						// send 
						if (length <= maxLength + 1) {

							chat.send(text, name, mail,site);
							jQuery(this).val("");

						} else {

							jQuery(this).val(text.substring(0, maxLength));

						}
					}
				});
			});
		</script>

		<?php
	}
	pp_author_chat_clean_up_chat_history();

	if (get_option('author_chat_settings_delete') == 1) {
		pp_author_chat_clean_up_database();
	}
	restore_current_blog();
	}

function pp_author_chat_chat_on_top() {
	switch_to_blog(1);
	restore_current_blog();
}

function pp_author_chat_clean_up_chat_history() {
	switch_to_blog(1);
	global $wpdb;
	$mydb = new wpdb(DB_USER,DB_PASSWORD,DB_NAME,DB_HOST);
	$author_chat_table = $wpdb->base_prefix.'author_chat';
	$daystoclear = get_option('author_chat_settings');
	$mydb->query("DELETE FROM $author_chat_table WHERE date <= NOW() - INTERVAL $daystoclear DAY");
	restore_current_blog();
}

function pp_author_chat_clean_up_database() {
	switch_to_blog(1);
	global $wpdb;    
	$mydb = new wpdb(DB_USER,DB_PASSWORD,DB_NAME,DB_HOST);
	$author_chat_table = $wpdb->base_prefix.'author_chat';
	$mydb->query("TRUNCATE TABLE $author_chat_table");
	$update_options = get_option('author_chat_settings_delete');
	$update_options = '';
	update_option('author_chat_settings_delete', $update_options);
	restore_current_blog();
}
?>
