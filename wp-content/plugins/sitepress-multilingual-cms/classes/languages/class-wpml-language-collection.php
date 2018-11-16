<?php

class WPML_Language_Collection {

	/** @var SitePress $sitepress */
	private $sitepress;

	/** @var array $languages */
	private $languages = array();

	public function __construct( SitePress $sitepress ) {
		$this->sitepress = $sitepress;
		foreach ( $sitepress->get_active_languages() as $lang ) {
			$this->add( $lang['code'] );
		}
	}

	public function add( $code ) {
		if ( ! isset( $this->languages[ $code ] ) ) {
			$language = new WPML_Language( $this->sitepress, $code );
			if ( $language->is_valid() ) {
				$this->languages[ $code ] = $language;
			}
		}
	}

	public function get( $code ) {
		if( ! isset( $this->languages[ $code ] ) ) {
			$this->add( $code );
		}
		return $this->languages[ $code ];
	}

	public function get_codes() {
		return array_keys( $this->languages );
	}
}