<?php

class WPML_Redirect_By_Domain extends WPML_Redirection {

	/** @var array $domains */
	private $domains;

	/**
	 * @param array                    $domains
	 * @param WPML_URL_Converter       $url_converter
	 * @param WPML_Request             $request_handler
	 * @param WPML_Language_Resolution $lang_resolution
	 */
	public function __construct( $domains, &$request_handler, &$url_converter, &$lang_resolution ) {
		parent::__construct( $url_converter, $request_handler, $lang_resolution );
		$this->domains = $domains;
	}

	public function get_redirect_target( $language = false ) {
		$target = $this->lang_resolution->is_language_hidden( $language )
		          && strpos( $_SERVER['REQUEST_URI'], 'wp-login.php' ) === false
		          && ! user_can( wp_get_current_user(), 'manage_options' )
			? trailingslashit( $this->domains[ $language ] ) . 'wp-login.php' : false;

		return $target;
	}
}