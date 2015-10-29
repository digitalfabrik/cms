<?php

register_activation_hook(__FILE__, function ($network_wide) {
	// TODO translate existing content
	if (!function_exists('is_multisite') || !is_multisite() || !$network_wide) {
		// ...
	} else {
		$mu_blogs = wp_get_sites();
		foreach ($mu_blogs as $mu_blog) {
			switch_to_blog($mu_blog['blog_id']);
			// ...
		}
		restore_current_blog();
	}
});

register_deactivation_hook(__FILE__,
	// TODO: delete all automatic translations
	function () {

	}
);
