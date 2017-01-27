<?php

/**
 * @author OnTheGo Systems
 */
class WPML_TM_Integration extends WPML_WPDB_And_SP_User {

	/**
	 * WPML_Integration constructor.
	 *
	 * @param wpdb      $wpdb
	 * @param SitePress $sitepress
	 */
	public function __construct(wpdb &$wpdb, SitePress &$sitepress ) {
		parent::__construct( $wpdb, $sitepress );
		if ( class_exists( 'acf' ) ) {
			new WPML_TM_ACF($wpdb, $sitepress );
		}
	}
}