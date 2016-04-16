<?php

class WPML_Custom_Field_Setting_Factory extends WPML_TM_User {

	/**
	 * @param  string $meta_key
	 *
	 * @return WPML_Post_Custom_Field_Setting
	 */
	public function post_meta_setting( $meta_key ) {

		return new WPML_Post_Custom_Field_Setting( $this->tm_instance, $meta_key );
	}

	/**
	 * @param  string $meta_key
	 *
	 * @return WPML_Term_Custom_Field_Setting
	 */
	public function term_meta_setting( $meta_key ) {

		return new WPML_Term_Custom_Field_Setting( $this->tm_instance, $meta_key );
	}

	/**
	 * Returns all custom field names for which a site has either a setting
	 * in the TM settings or that can be found on any post.
	 *
	 * @return string[]
	 */
	public function get_post_meta_keys() {

		return $this->tm_instance->initial_custom_field_translate_states();
	}

	/**
	 * Returns all term custom field names for which a site has either a setting
	 * in the TM settings or that can be found on any term.
	 *
	 * @return string[]
	 */
	public function get_term_meta_keys() {

		return $this->tm_instance->initial_term_custom_field_translate_states();
	}
}