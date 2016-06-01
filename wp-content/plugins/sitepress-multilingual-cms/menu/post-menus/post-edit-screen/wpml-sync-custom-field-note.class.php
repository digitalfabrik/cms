<?php

class WPML_Sync_Custom_Field_Note extends WPML_SP_User {

	/**
	 * Prints and admins notice if custom fields where copied to a new post.
	 *
	 * @param string $source_lang
	 * @param int[]  $translations
	 */
	public function print_sync_copy_custom_field_note( $source_lang, $translations ) {
		$copied_cf = $this->get_copied_custom_fields( $source_lang, $translations );
		if ( ! empty( $copied_cf ) ) {
			$lang_details     = $this->sitepress->get_language_details( $source_lang );
			$user_preferences = $this->sitepress->get_user_preferences();
			if ( (bool) $copied_cf === true && ( ! isset( $user_preferences['notices']['hide_custom_fields_copy'] ) || ! $user_preferences['notices']['hide_custom_fields_copy'] ) ) {
				$ccf_note = '<img src="' . ICL_PLUGIN_URL . '/res/img/alert.png" alt="Notice" width="16" height="16" style="margin-right:8px" />';
				$ccf_note .= '<a class="icl_user_notice_hide" href="#hide_custom_fields_copy" style="float:right;margin-left:20px;">' . __(
						'Never show this.',
						'sitepress'
					) . '</a>';
				$ccf_note .= wp_nonce_field( 'save_user_preferences_nonce', '_icl_nonce_sup', false, false );
				$ccf_note .= sprintf(
					__( 'WPML will copy %s from %s when you save this post.', 'sitepress' ),
					'<i><strong>' . join( '</strong>, <strong>', $copied_cf ) . '</strong></i>',
					$lang_details['display_name']
				);
				$this->sitepress->admin_notices( $ccf_note, 'error' );
			}
		}
	}

	/**
	 * @param string $source_lang
	 * @param array  $translations
	 *
	 * @return array
	 */
	private function get_copied_custom_fields( $source_lang, $translations ) {
		$tm_settings               = $this->sitepress->get_setting( 'translation-management', array() );
		$custom_fields_translation = ! empty( $tm_settings['custom_fields_translation'] )
			? (array) $tm_settings['custom_fields_translation'] : array();
		$copied_cf                 = array_keys( (array) $custom_fields_translation, 1 );
		$source_lang               = $source_lang ? $source_lang : $this->sitepress->get_default_language();
		if ( isset( $translations[ $source_lang ] ) ) {
			$original_custom = $this->sitepress->get_wp_api()->get_post_custom( $translations[ $source_lang ] );
			$copied_cf       = (bool) $original_custom
				? array_intersect( $copied_cf, array_keys( $original_custom ) ) : array();
			$copied_cf       = apply_filters(
				'icl_custom_fields_to_be_copied',
				$copied_cf,
				$translations[ $source_lang ]
			);
		} else {
			$copied_cf = array();
		}

		return $copied_cf;
	}
}