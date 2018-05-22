<?php

class WPML_TM_Translation_Services_Admin_Section_Factory implements IWPML_TM_Admin_Section_Factory {

	/**
	 * @return WPML_TM_Translation_Services_Admin_Section
	 */
	public function create() {
		global $sitepress;

		$twig_loader = new WPML_Twig_Template_Loader( array(
			WPML_TM_PATH . '/templates/menus/translation-services/',
			WPML_PLUGIN_PATH . '/templates/pagination/',
		) );

		$tp_client_factory = new WPML_TP_Client_Factory();
		$tp_client = $tp_client_factory->create();

		$section = new WPML_TM_Translation_Services_Admin_Section(
			$sitepress,
			$tp_client,
			new WPML_TM_Array_Search(),
			$this->site_key_exists(),
			new WPML_TM_Translation_Services_Admin_Section_No_Site_Key_Template( $twig_loader->get_template() )
		);

		$pagination_factory = new WPML_Admin_Pagination_Factory(
			count( $section->get_available_services() ),
			$section->get_items_per_page()
		);

		$pagination = $pagination_factory->create();
		$section->set_pagination( $pagination );
		$section_template = $this->create_services_list_template( $section, $twig_loader );
		$section->set_services_list_template( $section_template );

		return $section;
	}

	/**
	 * @return bool|string
	 */
	private function site_key_exists(){
		$site_key = false;

		if ( class_exists( 'WP_Installer' ) ){
			$repository_id = 'wpml';
			$site_key = WP_Installer()->get_site_key( $repository_id );
		}

		return $site_key;
	}

	/**
	 * @param $section
	 * @param WPML_Twig_Template_Loader $twig_loader
	 *
	 * @return WPML_TM_Translation_Services_Admin_Section_Services_List_Template
	 */
	private function create_services_list_template( $section, WPML_Twig_Template_Loader $twig_loader ) {
		global $sitepress;

		$active_service = $sitepress->get_setting( 'translation_service' );

		$section_template = new WPML_TM_Translation_Services_Admin_Section_Services_List_Template(
			array(
				'template_service'                   => $twig_loader->get_template(),
				'active_service'                     => $active_service ? new WPML_TP_Service( $active_service ) : null,
				'filtered_services'                  => $section->get_paginated_services(),
				'available_services'                 => $section->get_available_services(),
				'translation_service_type_requested' => $section->get_translation_service_type_requested(),
				'current_url'                        => $section->get_current_url(),
				'search_string'                      => $section->get_search_string(),
				'pagination'                         => $section->get_pagination(),
				'table_sort'                         => new WPML_Admin_Table_Sort(),
				'items_per_page'                     => $section->get_items_per_page(),
				'has_preferred_service'              => TranslationProxy::has_preferred_translation_service(),
			)
		);

		return $section_template;
	}
}