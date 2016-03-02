<?php
// WP Super Edit Workaround - $wp_super_edit->is_tinymce property is currently set true only if URI matches unfilterable list: '/tiny_mce_config\.php|page-new\.php|page\.php|post-new\.php|post\.php/'
// (but we want to enable it on the Revions Management form too)

global $wp_super_edit, $wpdb;

$wp_super_edit->is_tinymce = true;

$button_query = "
	SELECT name, provider, plugin, status FROM $wp_super_edit->db_buttons ORDER BY name
";

if ( $wp_super_edit->ui == 'buttons' ) {
	$button_query = "
		SELECT name, nicename, description, provider, status 
		FROM $wp_super_edit->db_buttons ORDER BY name
	";
}

$buttons = $wpdb->get_results( $wpdb->prepare( $button_query ) );

foreach( $buttons as $button ) {
	$wp_super_edit->buttons[$button->name] = $button;
	if ( $button->status == 'yes' ) {
		$wp_super_edit->active_buttons[$button->name] = $button;
	}
}
?>