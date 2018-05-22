<?php

class WPML_TM_Translation_Services_Admin_Section_Services_List_Template {

	const SERVICES_LIST_TEMPLATE = 'services-list.twig';

	/**
	 * @var IWPML_Template_Service
	 */
	private $template_service;

	/**
	 * @var WPML_TP_Service
	 */
	private $active_service;

	/**
	 * @var array
	 */
	private $available_services;

	/**
	 * @var array
	 */
	private $filtered_services;

	/**
	 * @var string
	 */
	private $translation_service_type_requested;

	/**
	 * @var string
	 */
	private $pagination;

	/**
	 * @var string
	 */
	private $current_url;

	/**
	 * @var string
	 */
	private $search_string;

	/**
	 * @var WPML_Admin_Table_Sort
	 */
	private $table_sort;

	/**
	 * @var int
	 */
	private $items_per_page;

	/**
	 * @var
	 */
	private $has_preferred_service;

	/**
	 * WPML_TM_Translation_Services_Admin_Section_Template constructor.
	 *
	 * @param array $args
	 */
	public function __construct( $args ) {
		$this->template_service                   = $args['template_service'];
		$this->active_service                     = $args['active_service'];
		$this->available_services                 = $args['available_services'];
		$this->filtered_services                  = $args['filtered_services'];
		$this->translation_service_type_requested = $args['translation_service_type_requested'];
		$this->current_url                        = $args['current_url'];
		$this->search_string                      = $args['search_string'];
		$this->pagination                         = $args['pagination'];
		$this->table_sort                         = $args['table_sort'];
		$this->items_per_page                     = $args['items_per_page'];
		$this->has_preferred_service              = $args['has_preferred_service'];
	}

	public function render() {
		echo $this->template_service->show( $this->get_services_list_model(), self::SERVICES_LIST_TEMPLATE );
	}

	/**
	 * @return array
	 */
	private function get_services_list_model() {
		$active_service_model = array();
		$active_service       = $this->active_service;

		if ( $active_service ) {
			$active_service_model = array(
				'id'                      => $active_service->get_id(),
				'logo_url'                => $active_service->get_logo_url(),
				'url'                     => $active_service->get_url(),
				'name'                    => $active_service->get_name(),
				'description'             => $active_service->get_description(),
				'doc_url'                 => $active_service->get_doc_url(),
				'requires_authentication' => (int) $active_service->get_requires_authentication(),
				'custom_fields'           => esc_attr( wp_json_encode( $active_service->get_custom_fields() ) ),
				'custom_fields_data'      => $active_service->get_custom_fields_data(),
				'has_language_pairs'      => $active_service->get_has_language_pairs(),
			);
		}

		$filtered_services = $this->get_filtered_services();

		$model = array(
			'active_service'                     => $active_service_model,
			'available_services'                 => $this->available_services,
			'filtered_services'                  => $filtered_services,
			'has_preferred_service'              => $this->has_preferred_service,
			'pagination_model'                   => $this->pagination,
			'table_sort'                         => $this->get_table_sort_columns(),
			'clean_search_url'                   => remove_query_arg( array( 'paged', 's' ), $this->current_url ),
			'current_url'                        => $this->current_url,
			'items_per_page'                     => $this->items_per_page,
			'nonces'                             => array(
				WPML_TM_Translation_Services_Admin_Section_Ajax::NONCE_ACTION => wp_create_nonce( WPML_TM_Translation_Services_Admin_Section_Ajax::NONCE_ACTION ),
				WPML_TM_Translation_Service_Authentication_Ajax::AJAX_ACTION  => wp_create_nonce( WPML_TM_Translation_Service_Authentication_Ajax::AJAX_ACTION ),
				WPML_TP_Refresh_Language_Pairs::AJAX_ACTION                   => wp_create_nonce( WPML_TP_Refresh_Language_Pairs::AJAX_ACTION ),
			),
			'translation_service_type_requested' => $this->translation_service_type_requested,
			'search_string'                      => $this->search_string,
			'strings'                            => array(
				'no_service_found'        => array(
					__( 'WPML cannot load the list of translation services. This can be a connection problem. Please wait a minute and reload this page.', 'wpml-translation-management' ),
					__( 'If the problem continues, please contact %s.', 'wpml-translation-management' ),
				),
				'wpml_support'            => 'WPML support',
				'support_link'            => 'https://wpml.org/forums/forum/english-support/',
				'services_per_page'       => __( 'Services per page:', 'wpml-translation-management' ),
				'refresh_language_pairs'  => __( 'Refresh language pairs', 'wpml-translation-management' ),
				'inactive_services_title' => WPML_TP_API_Services::TRANSLATION_MANAGEMENT_SYSTEM === $this->translation_service_type_requested ?
					__( 'Available Translation Management Systems', 'wpml-translation-management' ) :
					__( 'Available Translation Services', 'wpml-translation-management' ),
				'activate'                => __( 'Activate', 'wpml-translation-management' ),
				'documentation'           => __( 'Documentation', 'wpml-translation-management' ),
				'documentation_lower'     => __( 'documentation', 'wpml-translation-management' ),
				'ts'                      => array(
					'link'        => __( "I'm looking for translation services", 'wpml-translation-management' ),
					'different'   => __( 'Looking for a different translation service?', 'wpml-translation-management' ),
					'tell_us_url' => 'https://wpml.org/documentation/content-translation/how-to-add-translation-services-to-wpml/#add-service-form',
					'tell_us'     => __( 'Tell us which one', 'wpml-translation-management' ),
					'url'         => add_query_arg( 'service-type', 'ts', $this->current_url ),
					'visible'     => WPML_TP_API_Services::TRANSLATION_MANAGEMENT_SYSTEM === $this->translation_service_type_requested,
				),
				'tms'                     => array(
					'link'    => __( "I'm looking for translation management systems", 'wpml-translation-management' ),
					'url'     => add_query_arg( 'service-type', WPML_TP_API_Services::TRANSLATION_MANAGEMENT_SYSTEM, $this->current_url ),
					'visible' => WPML_TP_API_Services::TRANSLATION_SERVICE === $this->translation_service_type_requested,
				),
				'filter'                  => array(
					'search'       => WPML_TP_API_Services::TRANSLATION_MANAGEMENT_SYSTEM === $this->translation_service_type_requested ?
						__( 'Search Translation Management Services', 'wpml-translation-management' ) :
						__( 'Search Translation Services', 'wpml-translation-management' ),
					'countries'    => __( 'All countries', 'wpml-translation-management' ),
					'filter_label' => __( 'Filter', 'wpml-translation-management' ),
					'clean_search' => __( 'Clear search', 'wpml-translation-management' ),
				),
				'pagination_items'        => __( 'items', 'wpml-translation-management' ),
				'columns'                 => array(
					'name'        => __( 'Name', 'wpml-translation-management' ),
					'description' => __( 'Description', 'wpml-translation-management' ),
					'popularity'  => __( 'Popularity', 'wpml-translation-management' ),
					'speed'       => __( 'Speed', 'wpml-translation-management' ),
				)
			)
		);

		if ( $active_service ) {
			$model['strings']['active_service'] = array(
				'title'        => __( 'Active service:', 'wpml-translation-management' ),
				'deactivate'   => __( 'Deactivate', 'wpml-translation-management' ),
				'modal_header' => sprintf( __( 'Enter here your %s authentication details', 'wpml-translation-management' ), $active_service->name ),
				'modal_tip'    => $active_service->get_popup_message() ?
					$active_service->get_popup_message() :
					__( 'You can find API token at %s site', 'wpml-translation-management' ),
				'modal_title'  => sprintf( __( '%s authentication', 'wpml-translation-management' ), $active_service->name ),
			);

			$authentication_message = array();
			/* translators: sentence 1/3: create account with the translation service ("%1$s" is the service name) */
			$authentication_message[] = __( 'To send content for translation to %1$s, you need to have an %1$s account.', 'wpml-translation-management' );
			/* translators: sentence 2/3: create account with the translation service ("one" is "one account) */
			$authentication_message[] = __( "If you don't have one, you can create it after clicking the authenticate button.", 'wpml-translation-management' );
			/* translators: sentence 3/3: create account with the translation service ("%2$s" is "documentation") */
			$authentication_message[] = __( 'Please, check the %2$s page for more details.', 'wpml-translation-management' );

			$model['strings']['authentication'] = array(
				'description'         => implode( ' ', $authentication_message ),
				'authenticate_button' => __( 'Authenticate', 'wpml-translation-management' ),
				'de_authorize_button' => __( 'De-authorize', 'wpml-translation-management' ),
				'is_authorized'       => sprintf( __( '%s is authorized.', 'wpml-translation-management' ), $active_service->name ),
			);
		}

		return $model;
	}

	/**
	 * @return array
	 */
	private function get_table_sort_columns() {
		return array(
			'name'       => array(
				'url'     => $this->table_sort->get_column_url( 'name' ),
				'classes' => $this->table_sort->get_column_classes( 'name' ),
			),
			'popularity' => array(
				'url'     => $this->table_sort->get_column_url( 'popularity' ),
				'classes' => $this->table_sort->get_column_classes( 'popularity' ),
			),
			'speed'      => array(
				'url'     => $this->table_sort->get_column_url( 'speed' ),
				'classes' => $this->table_sort->get_column_classes( 'speed' ),
			),
		);
	}

	/**
	 * @return array
	 */
	private function get_filtered_services() {
		$services_model = array();

		foreach ( $this->filtered_services as $service ) {
			$services_model[] = array(
				'id'          => $service->get_id(),
				'logo_url'    => $service->get_logo_url(),
				'name'        => $service->get_name(),
				'description' => $service->get_description(),
				'doc_url'     => $service->get_doc_url(),
				'active'      => $this->active_service && $service->get_id() === $this->active_service->get_id() ? 'active' : 'inactive',
				'popularity'  => $service->get_rankings()->popularity,
				'speed'       => $service->get_rankings()->speed,
			);
		}

		return $services_model;
	}
}