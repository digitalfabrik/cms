<?php

class WPML_Set_Attachments_Language_Factory implements IWPML_Backend_Action_Loader, IWPML_Deferred_Action_Loader {

	public function get_load_action() {
		return 'wpml_loaded';
	}

	public function create() {
		global $wpdb, $sitepress;

		$wpml_media_settings = get_option( '_wpml_media', array() );

		if ( empty( $wpml_media_settings['starting_help'] ) && ! $this->is_media_page() ) {

			$active_languages = $sitepress->get_active_languages();
			if ( count( $active_languages ) > 1 ) {

				$total_attachments_prepared = $wpdb->prepare( "
		                SELECT COUNT(*) FROM {$wpdb->posts} WHERE post_type = %s AND ID NOT IN
		                (SELECT post_id FROM {$wpdb->postmeta} WHERE meta_key = %s)", array(
					'attachment',
					'wpml_media_processed'
				) );
				$total_attachments          = $wpdb->get_var( $total_attachments_prepared );

				if ( $total_attachments && ! $this->wpml_media_is_active_or_set_up() ) {
					return new WPML_Set_Attachments_Language( $sitepress );
				} else {
					$wpml_media_settings['starting_help'] = 1;
					update_option( '_wpml_media', $wpml_media_settings );
				}
			}
		}

		return null;
	}

	private function is_media_page() {
		return isset( $_GET['page'] ) && $_GET['page'] === 'wpml-media-settings';
	}

	private function wpml_media_is_active_or_set_up() {
		$wpml_media_settings = get_option( '_wpml_media', array() );

		return defined( 'WPML_MEDIA_VERSION' ) || ! empty( $wpml_media_settings['setup_run'] );
	}

}