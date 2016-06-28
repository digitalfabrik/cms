<?php

/**
 * Class WPML_WPDB_User
 *
 * Superclass for all WPML classes using the @global wpdb $wpdb
 *
 * @since 3.2.3
 */
abstract class WPML_WPDB_User {

	/** @var WPDB $wpdb */
	protected $wpdb;

	/**
	 * @param WPDB $wpdb
	 */
	public function __construct( &$wpdb ) {
		$this->wpdb = &$wpdb;
	}
}