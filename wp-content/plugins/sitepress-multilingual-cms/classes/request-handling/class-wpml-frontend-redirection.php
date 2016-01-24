<?php

class WPML_Frontend_Redirection extends WPML_SP_User {

	/** @var  WPML_Frontend_Request $request_handler */
	private $request_handler;

	/** @var  WPML_Redirection */
	private $redirect_helper;

	/**
	 * WPML_Frontend_Redirection constructor.
	 *
	 * @param SitePress $sitepress
	 * @param           $request_handler
	 * @param           $redir_helper
	 */
	public function __construct( &$sitepress, &$request_handler, &$redir_helper ) {
		parent::__construct( $sitepress );
		$this->request_handler = &$request_handler;
		$this->redirect_helper = &$redir_helper;
	}

	/**
	 * Redirects to a URL corrected for the language information in it, in case request URI and $_REQUEST['lang'],
	 * requested domain or $_SERVER['REQUEST_URI'] do not match and gives precedence to the explicit language parameter if
	 * there.
	 *
	 * @return string The language code of the currently requested URL in case no redirection was necessary.
	 */
	public function maybe_redirect() {
		if ( ( $target = $this->redirect_helper->get_redirect_target() ) !== false ) {
			$this->sitepress->get_wp_api()->wp_safe_redirect( $target );
		};

		// allow forcing the current language when it can't be decoded from the URL
		return apply_filters( 'icl_set_current_language', $this->request_handler->get_requested_lang() );
	}
}
