<?php

namespace WPML\Core\REST\Disabled;

class Notice extends \WPML_Notice {

	const NOTICE_ID = 'rest-disabled';

	public function __construct() {
		$text = '<h3>' . esc_html__( 'REST API is disabled, blocking some features of WPML', 'sitepress' ) . '</h3>';

		$text .= '<p>' .
		         esc_html__(
			         'It looks like the WordPress REST API is disabled on this site. This blocks some features of WordPress itself and of WPML.',
			         'sitepress'
		         )
		         . '</p>';

		$moreInfo = '<a href="https://wpml.org/documentation/support/rest-api-dependencies/">' .
		            esc_html__( 'More info', 'sitepress' ) .
		            '</a>';

		$dismiss  = '<a href="#" class="otgs-dismiss-link">' . esc_html__( 'Dismiss', 'sitepress' ) . '</a>';

		$text .= '<p>' . $moreInfo . ' | ' . $dismiss . '</p>';

		parent::__construct( self::NOTICE_ID, $text );

		$this->set_css_class_types( 'warning' );
		$this->set_dismissible( true );
	}

}