<?php

class WPML_TM_Blog_Translators extends WPML_WPDB_User {

	/**
	 * @param array $args
	 *
	 * @return array
	 */
	function get_blog_translators( $args = array() ) {
		$translators = TranslationManagement::get_blog_translators( $args );
		foreach ( $translators as $key => $user ) {
			$translators[ $key ] = isset( $user->data ) ? $user->data : $user;
		}

		return $translators;
	}

	/**
	 * @param int   $user_id
	 * @param array $args
	 *
	 * @return bool
	 */
	function is_translator( $user_id, $args = array() ) {
		$admin_override = true;
		extract( $args, EXTR_OVERWRITE );
		$user          = new WP_User( $user_id );
		$is_translator = $user->has_cap( 'translate' );
		// check if user is administrator and return true if he is
		$user_caps = $user->allcaps;
		if ( $admin_override && ! empty( $user_caps['activate_plugins'] ) ) {
			$is_translator = true;
		} else {
			if ( isset( $lang_from ) && isset( $lang_to ) ) {
				$um            = get_user_meta( $user_id, $this->wpdb->prefix . 'language_pairs', true );
				$is_translator = $is_translator && isset( $um[ $lang_from ] ) && isset( $um[ $lang_from ][ $lang_to ] ) && $um[ $lang_from ][ $lang_to ];
			}
			if ( isset( $job_id ) ) {
				$translator_id = $this->wpdb->get_var( $this->wpdb->prepare( "
							SELECT j.translator_id
								FROM {$this->wpdb->prefix}icl_translate_job j
								JOIN {$this->wpdb->prefix}icl_translation_status s ON j.rid = s.rid
							WHERE job_id = %d AND s.translation_service='local'
						",
				                                                             $job_id ) );
				$is_translator = $is_translator && ( ( $translator_id == $user_id ) || empty( $translator_id ) );
			}
		}

		return apply_filters( 'wpml_override_is_translator', $is_translator, $user_id, $args );
	}
}