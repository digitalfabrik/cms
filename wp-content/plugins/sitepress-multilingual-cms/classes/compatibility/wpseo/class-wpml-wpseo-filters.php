<?php

/**
 * Class WPML_ST_WPSEO_Filters
 *
 * Compatibility class for WordPress SEO plugin
 */
Class WPML_WPSEO_Filters {

	/**
	 * @var array
	 */
	private $user_meta_fields = array(
		'wpseo_title',
		'wpseo_metadesc',
	);

	public function init_hooks() {
		add_filter( 'wpml_translatable_user_meta_fields', array( $this, 'wpml_translatable_user_meta_fields_filter' ) );
	}

	/**
	 * @param array $fields
	 *
	 * @return array
	 */
	public function wpml_translatable_user_meta_fields_filter( $fields ) {
		return array_merge( $this->user_meta_fields, $fields );
	}

	/**
	 * @return array
	 */
	public function get_user_meta_fields() {
		return $this->user_meta_fields;
	}
}