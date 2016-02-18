<?php

// back compat for older MU versions
if ( IS_MU_RVY && ! function_exists( 'is_super_admin' ) ) :
function is_super_admin() {
	return is_site_admin();
}
endif;

if ( ! function_exists('get_post_type_object') ) :
function get_post_type_object( $post_type ) {
	global $wp_post_types;

	if ( empty($wp_post_types[$post_type]) )
		return null;

	return $wp_post_types[$post_type];
}
endif;

?>