<?php

class WPML_TM_Jobs_Deadline_Estimate_Factory {

	public function create() {
		global $wpdb, $sitepress;

		$st_package_factory = class_exists( 'WPML_ST_Package_Factory' ) ? new WPML_ST_Package_Factory() : null;
		$translatable_element_provider = new WPML_TM_Translatable_Element_Provider( $sitepress, $wpdb, $st_package_factory );
		$translation_jobs_collection = new WPML_Translation_Jobs_Collection( $wpdb, array() );

		return new WPML_TM_Jobs_Deadline_Estimate( $translatable_element_provider, $translation_jobs_collection );
	}
}
