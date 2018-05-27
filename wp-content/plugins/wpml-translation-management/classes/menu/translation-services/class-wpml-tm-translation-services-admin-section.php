<?php

class WPML_TM_Translation_Services_Admin_Section implements IWPML_TM_Admin_Section {

	/**
	 * @var SitePress
	 */
	private $sitepress;

	/**
	 * @var WPML_TP_Client
	 */
	private $tp_client;

	/**
	 * @var WPML_WP_API
	 */
	private $wp_api;

	/**
	 * @var WPML_TM_Array_Search
	 */
	private $search;

	/**
	 * @var bool
	 */
	private $site_key_exists;

	/**
	 * @var array
	 */
	private $available_services;

	/**
	 * @var WPML_Admin_Pagination_Render
	 */
	private $pagination;

	/**
	 * @var WPML_TM_Translation_Services_Admin_Section_Services_List_Template
	 */
	private $services_list_template;

	/**
	 * @var WPML_TM_Translation_Services_Admin_Section_No_Site_Key_Template
	 */
	private $no_site_key_template;

	public function __construct(
		SitePress $sitepress,
		WPML_TP_Client $tp_client,
		WPML_TM_Array_Search $search,
		$site_key_exists,
		WPML_TM_Translation_Services_Admin_Section_No_Site_Key_Template $no_site_key_template
	) {
		$this->sitepress              = $sitepress;
		$this->tp_client              = $tp_client;
		$this->wp_api                 = $sitepress->get_wp_api();
		$this->search                 = $search;
		$this->site_key_exists        = $site_key_exists;
		$this->no_site_key_template   = $no_site_key_template;
	}

	public function render() {
		if ( $this->site_key_exists ) {
			$this->services_list_template->render();
		} else {
			$this->no_site_key_template->render();
		}
	}

	/**
	 * @param WPML_TM_Translation_Services_Admin_Section_Services_List_Template $template
	 */
	public function set_services_list_template( WPML_TM_Translation_Services_Admin_Section_Services_List_Template $template ) {
		$this->services_list_template = $template;
	}

	public function set_pagination( WPML_Admin_Pagination_Render $pagination ) {
		$this->pagination = $pagination;
	}

	/**
	 * @return null|WPML_TP_Service
	 */
	public function get_active_service() {
		return $this->tp_client->services()->get_active();
	}

	/**
	 * @return array
	 */
	public function get_available_services() {
		$type = $this->get_translation_service_type_requested();

		if ( ! $this->available_services ) {
			$this->available_services = WPML_TP_API_Services::TRANSLATION_MANAGEMENT_SYSTEM === $type ?
				$this->tp_client->services()->get_translation_management_systems() :
				$this->tp_client->services()->get_translation_services();
		}

		if ( $this->doing_search() ) {
			$this->available_services = $this->search
				->set_data( $this->available_services )
				->set_where(
					array(
						array(
							'field'    => 'name',
							'value'    => $this->get_search_string(),
							'operator' => 'LIKE'
						),

						array(
							'field'    => 'description',
							'value'    => $this->get_search_string(),
							'operator' => 'LIKE'
						),
					)
				)
				->get_results();

			foreach ( $this->available_services as $key => $service ) {
				if ( false !== stripos( $service->get_description(), $this->get_search_string() ) ) {
					$this->available_services[ $key ]->set_description( preg_replace('/' . $this->get_search_string() . '/i', '<b>$0</b>', $this->available_services[ $key ]->get_description() ) );
				}
			}
		}

		if ( $this->is_sorting() ) {
			$this->fix_ranking_columns_for_sorting();
			$this->available_services = wp_list_sort(
				$this->available_services,
				filter_var( $_GET['orderby'], FILTER_SANITIZE_FULL_SPECIAL_CHARS ),
				filter_var( $_GET['order'], FILTER_SANITIZE_FULL_SPECIAL_CHARS )
			);
		}

		return $this->available_services;
	}

	private function fix_ranking_columns_for_sorting() {
		foreach( $this->available_services as $key => $service ) {
			$this->available_services[ $key ]->popularity = (string) $this->available_services[ $key ]->rankings->popularity;
			$this->available_services[ $key ]->speed = (string) $this->available_services[ $key ]->rankings->speed;
		}
	}

	/**
	 * @return array
	 */
	public function get_paginated_services() {
		return $this->pagination->paginate( $this->get_available_services() );
	}

	/**
	 * @return string
	 */
	public function get_search_string() {
		return $this->doing_search() ? filter_var( $_GET['s'], FILTER_SANITIZE_FULL_SPECIAL_CHARS ) : '';
	}

	/**
	 * @return string
	 */
	public function get_pagination() {
		return $this->pagination->get_model();
	}

	/**
	 * @return string
	 */
	public function get_translation_service_type_requested() {
		return isset( $_GET['service-type'] ) && WPML_TP_API_Services::TRANSLATION_MANAGEMENT_SYSTEM === $_GET['service-type'] ?
			WPML_TP_API_Services::TRANSLATION_MANAGEMENT_SYSTEM :
			WPML_TP_API_Services::TRANSLATION_SERVICE;
	}

	/**
	 * @return string
	 */
	public function get_current_url() {
		$base = $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'];

		return is_ssl() ? 'https://' . $base : 'http://' . $base;
	}

	/**
	 * @return int
	 */
	public function get_items_per_page() {
		return isset( $_GET['items_per_page'] ) ? filter_var( $_GET['items_per_page'], FILTER_SANITIZE_NUMBER_INT ) : 10;
	}

	/**
	 * @return bool
	 */
	private function doing_search() {
		return isset( $_GET['s'] ) && '' !== $_GET['s'];
	}

	/**
	 * @return bool
	 */
	private function is_sorting() {
		return isset( $_GET['orderby'], $_GET['order'] ) && '' !== $_GET['orderby'] && '' !== $_GET['order'];
	}

	/**
	 * @return bool
	 */
	public function is_visible() {
		return ! $this->wp_api->constant( 'ICL_HIDE_TRANSLATION_SERVICES' ) &&
		       ( $this->wp_api->constant( 'WPML_BYPASS_TS_CHECK' ) || ! $this->sitepress->get_setting( 'translation_service_plugin_activated' ) );
	}

	/**
	 * @return string
	 */
	public function get_slug() {
		return 'translation-services';
	}

	/**
	 * @return string
	 */
	public function get_capability() {
		return 'list_users';
	}

	/**
	 * @return string
	 */
	public function get_caption() {
		return __( 'Translation Services', 'wpml-translation-management' );
	}

	/**
	 * @return callable
	 */
	public function get_callback() {
		return array( $this, 'render' );
	}
}