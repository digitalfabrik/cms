<?php
/**
 * Plugin Name: de plus Benutzerrollen
 * Description: Benutzerrollen fuer das de plus project: Verwalter, Organisatoren (Administratoren existieren immer)
 * Version: 0.1
 * Author: Martin Schrimpf
 * Author URI: https://github.com/Meash
 * License: MIT
 */

register_activation_hook(__FILE__, function ($network_wide) {
	if (!function_exists('is_multisite') || !is_multisite() || !$network_wide) {
		deactivate_plugins(plugin_basename(__FILE__));
		wp_die('Dieses Plugin setzt eine Multisite Installation voraus');
	}

	$mu_blogs = wp_get_sites();

	foreach ($mu_blogs as $mu_blog) {
		switch_to_blog($mu_blog['blog_id']);

		/* Add specific user roles */
		add_role('manager', 'Verwalter', [
			/* Users */
			'add_users' => true,
			'create_users' => true,
			/* Pages */
			'edit_pages' => true,
			'edit_others_pages' => true,
			'edit_private_pages' => true,
			'edit_published_pages' => true,
			'read_private_pages' => true,
			'delete_others_pages' => true,
			'delete_pages' => true,
			'delete_publishes_pages' => true,
			'publish_pages' => true,
			'upload_files' => true,
			/* Profile */
			'read' => true,
			/* WPML */
			'wpml_manage_translation_management' => true,
			'wpml_manage_languages' => true,
			'wpml_manage_navigation' => true,
			'wpml_manage_media_translation' => true,
			/* Events */
			'publish_events' => true,
			'delete_others_events' => true,
			'edit_others_events' => true,
			'delete_events' => true,
			'edit_events' => true,
			'read_private_events' => true,

			'publish_recurring_events' => true,
			'delete_others_recurring_events' => true,
			'edit_others_recurring_events' => true,
			'delete_recurring_events' => true,
			'edit_recurring_events' => true,

			'publish_locations' => true,
			'delete_others_locations' => true,
			'edit_others_locations' => true,
			'delete_locations' => true,
			'edit_locations' => true,
			'read_private_locations' => true,
			'read_others_locations' => true,

			'delete_event_categories' => true,
			'edit_event_categories' => true,
			'upload_event_images' => true,
		]);
		add_role('organizer', 'Organisator', [
			/* Pages */
			'edit_pages' => true,
			'edit_others_pages' => true,
			'edit_private_pages' => true,
			'edit_published_pages' => true,
			'read_private_pages' => true,
			'delete_others_pages' => true,
			'delete_pages' => true,
			'delete_publishes_pages' => true,
			'publish_pages' => true,
			'upload_files' => true,
			/* Profile */
			'read' => true,
			/* WPML */
			'wpml_manage_translation_management' => true,
			'wpml_manage_navigation' => true,
			'wpml_manage_media_translation' => true,
			/* Events */
			'delete_events' => true,
			'edit_events' => true,

			'delete_recurring_events' => true,
			'edit_recurring_events' => true,

			'publish_locations' => true,
			'delete_locations' => true,
			'edit_locations' => true,
			'read_others_locations' => true,

			'upload_event_images' => true,
		]);

		/* Delete default user roles */
		remove_role('subscriber');
		remove_role('editor');
		remove_role('author');
		remove_role('contributor');
		remove_role('administrator');
	}

	restore_current_blog();
});

add_filter('pre_option_default_role',
	/* change the default role to organizer */
	function () {
		return 'organizer';
	}
);

register_deactivation_hook(__FILE__,
	/** Reset all user roles */
	function () {
		$method = new ReflectionMethod('Ure_Lib', 'reset_user_roles'); // work around protected method access
		$method->setAccessible(true);
		$ure_lib = new Ure_Lib('user_role_editor');
		$ure_lib->apply_to_all = true;
		$method->invoke($ure_lib);
	}
);
