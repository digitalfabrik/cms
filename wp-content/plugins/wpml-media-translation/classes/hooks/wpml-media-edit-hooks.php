<?php

/**
 * Class WPML_Media_Edit_Hooks
 */
class WPML_Media_Edit_Hooks implements IWPML_Action {

	/** @var WPML_URL_Converter $url_converter */
	private $url_converter;

	/**
	 * @param WPML_URL_Converter $url_converter
	 */
	public function __construct( WPML_URL_Converter $url_converter ) {
		$this->url_converter = $url_converter;
	}

	public function add_hooks() {
		add_filter( 'get_sample_permalink', array( $this, 'convert_sample_permalink' ) );
		add_filter( 'attachment_link', array( $this->url_converter, 'convert_url' ) );
	}

	/**
	 * @param array $permalink Array containing the sample permalink with placeholder for the post name, and the post name.
	 *
	 * @return array
	 */
	public function convert_sample_permalink( $permalink ) {
		$permalink[0] = $this->url_converter->convert_url( $permalink[0] );
		return $permalink;
	}
}
