<?php

class WPML_TM_Translatable_Element_Provider {

	/** @var SitePress $sitepress */
	private $sitepress;

	/** @var wpdb $wpdb */
	private $wpdb;

	/** @var null|WPML_ST_Package_Factory $st_package_factory */
	private $st_package_factory;

	public function __construct(
		SitePress $sitepress,
		wpdb $wpdb,
		WPML_ST_Package_Factory $st_package_factory = null
	) {
		$this->sitepress          = $sitepress;
		$this->wpdb               = $wpdb;
		$this->st_package_factory = $st_package_factory;
	}

	/**
	 * @param WPML_Translation_Job $job
	 *
	 * @return null|WPML_TM_Package_Element|WPML_TM_Post|WPML_TM_String
	 */
	public function get_from_job( WPML_Translation_Job $job ) {
		$id = $job->get_original_element_id();

		if ( $job instanceof WPML_Post_Translation_Job ) {
			return new WPML_TM_Post( $id, $this->sitepress, $this->wpdb );
		}

		if ( $job instanceof WPML_String_Translation_Job ) {
			return new WPML_TM_String( $id, $this->sitepress, $this->wpdb );
		}

		if ( $job instanceof WPML_External_Translation_Job ) {
			return new WPML_TM_Package_Element( $id, $this->sitepress, $this->wpdb, $this->st_package_factory );
		}

		return null;
	}

	/**
	 * @param string $type
	 * @param int    $id
	 *
	 * @return null|WPML_TM_Package_Element|WPML_TM_Post|WPML_TM_String
	 */
	public function get_from_type( $type, $id ) {
		switch ( $type ) {
			case 'post':
				return new WPML_TM_Post( $id, $this->sitepress, $this->wpdb );

			case 'string':
				return new WPML_TM_String( $id, $this->sitepress, $this->wpdb );

			case 'package':
				return new WPML_TM_Package_Element( $id, $this->sitepress, $this->wpdb, $this->st_package_factory );
		}

		return null;
	}
}
