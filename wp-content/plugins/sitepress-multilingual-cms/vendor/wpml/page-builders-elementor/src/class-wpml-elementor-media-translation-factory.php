<?php

class WPML_Elementor_Media_Translation_Factory implements IWPML_Backend_Action_Loader {

	public function create() {
		global $sitepress;

		$hooks = null;

		if ( class_exists( 'WPML_Media_Image_Translate' ) && class_exists( 'WPML_Media_Attachment_By_URL_Factory' ) ) {
			$elementor_db_factory = new WPML_Elementor_DB_Factory();
			$data_settings        = new WPML_Elementor_Data_Settings( $elementor_db_factory->create() );

			$hooks = new WPML_Elementor_Media_Translation(
				$data_settings,
				new WPML_Media_Image_Translate( $sitepress, new WPML_Media_Attachment_By_URL_Factory() ),
				new WPML_Translation_Element_Factory( $sitepress )
			);
		}

		return $hooks;
	}
}