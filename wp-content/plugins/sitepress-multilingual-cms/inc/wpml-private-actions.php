<?php

function wpml_new_duplicated_terms_filter( $post_ids, $duplicates_only = true ) {
	global $wpdb, $sitepress;

	require_once ICL_PLUGIN_PATH . '/inc/taxonomy-term-translation/wpml-term-hierarchy-duplication.class.php';
	$hier_dupl = new WPML_Term_Hierarchy_Duplication( $wpdb, $sitepress );
	$taxonomies = $hier_dupl->duplicates_require_sync( $post_ids, $duplicates_only );

	if ( (bool) $taxonomies ) {
		$text = __(
			"The posts you just saved led to the creation of new hierarchical terms.\nTheir hierarchical relationship to one another is not yet synchronized with the original post's terms for the following taxonomies:",
			'wpml-translation-management'
		);

		$text = '<p>' . $text . '</p>';
		foreach ( $taxonomies as $taxonomy ) {
			$text .= '<p><a href="admin.php?page='
					 . ICL_PLUGIN_FOLDER . '/menu/taxonomy-translation.php&taxonomy='
					 . $taxonomy . '&sync=1">' . get_taxonomy_labels(
						 get_taxonomy( $taxonomy )
					 )->name . '</a></p>';
		}

		$message_args = array(
			'id'            => 'duplication-tm-dashboard-notification',
			'text'          => $text,
			'type'          => 'information',
			'group'         => 'duplication-notification',
			'admin_notice'  => true,
			'show_once'     => true,
			'hide_per_user' => true
		);
		ICL_AdminNotifier::add_message( $message_args );
	}
}

add_action( 'wpml_new_duplicated_terms', 'wpml_new_duplicated_terms_filter', 10, 2 );

function wpml_validate_language_domain_action() {

	if ( wp_verify_nonce( filter_input( INPUT_POST, 'nonce' ),
		filter_input( INPUT_POST,
			'action' ) ) ) {
		global $sitepress;
		$http                    = new WP_Http();
		$wp_api                  = $sitepress->get_wp_api();
		$language_domains_helper = new WPML_Language_Domain_Validation( $wp_api,
			$http, filter_input( INPUT_POST,
				'url' ), '' );
		$res                     = $language_domains_helper->is_valid();
	}
	if ( ! empty( $res ) ) {
		wp_send_json_success( __( 'Valid', 'sitepress' ) );
	}
	wp_send_json_error( __( 'Not valid', 'sitepress' ) );
}

add_action( 'wp_ajax_validate_language_domain',
	'wpml_validate_language_domain_action' );