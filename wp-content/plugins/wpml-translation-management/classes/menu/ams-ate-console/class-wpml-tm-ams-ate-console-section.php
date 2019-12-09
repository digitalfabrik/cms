<?php

/**
 * It handles the TM section responsible for displaying the AMS/ATE console.
 *
 * This class takes care of the following:
 * - enqueuing the external script which holds the React APP
 * - adding the ID to the enqueued script (as it's required by the React APP)
 * - adding an inline script to initialize the React APP
 *
 * @author OnTheGo Systems
 */
class WPML_TM_AMS_ATE_Console_Section implements IWPML_TM_Admin_Section {
	const ATE_APP_ID         = 'eate_widget';
	const TAB_ORDER          = 10000;
	const CONTAINER_SELECTOR = '#ams-ate-console';
	const TAB_SELECTOR       = '.wpml-tabs .nav-tab.nav-tab-active.nav-tab-ate-ams';
	const SLUG               = 'ate-ams';

	/**
	 * An instance of \SitePress.
	 *
	 * @var \SitePress The instance of \SitePress.
	 */
	private $sitepress;
	/**
	 * Instance of WPML_TM_ATE_AMS_Endpoints.
	 *
	 * @var \WPML_TM_ATE_AMS_Endpoints
	 */
	private $endpoints;

	/**
	 * Instance of WPML_TM_ATE_Authentication.
	 *
	 * @var \WPML_TM_ATE_Authentication
	 */
	private $auth;

	/**
	 * Instance of WPML_TM_AMS_API.
	 *
	 * @var \WPML_TM_AMS_API
	 */
	private $ams_api;

	/**
	 * WPML_TM_AMS_ATE_Console_Section constructor.
	 *
	 * @param \SitePress                  $sitepress The instance of \SitePress.
	 * @param \WPML_TM_ATE_AMS_Endpoints  $endpoints The instance of WPML_TM_ATE_AMS_Endpoints.
	 * @param \WPML_TM_ATE_Authentication $auth      The instance of WPML_TM_ATE_Authentication.
	 * @param \WPML_TM_AMS_API            $ams_api   The instance of WPML_TM_AMS_API.
	 */
	public function __construct( SitePress $sitepress, WPML_TM_ATE_AMS_Endpoints $endpoints, WPML_TM_ATE_Authentication $auth, WPML_TM_AMS_API $ams_api ) {
		$this->sitepress = $sitepress;
		$this->endpoints = $endpoints;
		$this->auth      = $auth;
		$this->ams_api   = $ams_api;
	}

	/**
	 * Returns a value which will be used for sorting the sections.
	 *
	 * @return int
	 */
	public function get_order() {
		return self::TAB_ORDER;
	}

	/**
	 * Returns the unique slug of the sections which is used to build the URL for opening this section.
	 *
	 * @return string
	 */
	public function get_slug() {
		return self::SLUG;
	}

	/**
	 * Returns one or more capabilities required to display this section.
	 *
	 * @return string|array
	 */
	public function get_capabilities() {
		return array( WPML_Manage_Translations_Role::CAPABILITY, 'manage_options' );
	}

	/**
	 * Returns the caption to display in the section.
	 *
	 * @return string
	 */
	public function get_caption() {
		return __( 'Translation Tools', 'wpml-translation-management' );
	}

	/**
	 * Returns the callback responsible for rendering the content of the section.
	 *
	 * @return callable
	 */
	public function get_callback() {
		return array( $this, 'render' );
	}

	/**
	 * Used to extend the logic for displaying/hiding the section.
	 *
	 * @return bool
	 */
	public function is_visible() {
		return true;
	}

	/**
	 * Outputs the content of the section.
	 */
	public function render() {
		echo '<div id="ams-ate-console"></div>';
	}

	/**
	 * This method is hooked to the `admin_enqueue_scripts` action.
	 *
	 * @param string $hook The current page.
	 */
	public function admin_enqueue_scripts( $hook ) {
		if ( ! $this->is_ate_console_tab() ) {
			return;
		}

		wp_enqueue_script( self::ATE_APP_ID, $this->endpoints->get_base_url( WPML_TM_ATE_AMS_Endpoints::SERVICE_AMS ) . '/mini_app/main.js', [] );
		$this->add_initialization_script();
	}

	/**
	 * It returns true if the current page and tab are the ATE Console.
	 *
	 * @return bool
	 */
	private function is_ate_console_tab() {
		return array_key_exists( 'sm', $_GET ) && array_key_exists( 'page', $_GET )
			   && filter_var( $_GET['sm'], FILTER_SANITIZE_STRING ) === self::SLUG
			   && filter_var( $_GET['page'], FILTER_SANITIZE_STRING ) === WPML_TM_FOLDER . '/menu/main.php';
	}

	/**
	 * It returns the list of all translatable post types.
	 *
	 * @return array
	 */
	private function get_post_types_data() {
		$translatable_types = $this->sitepress->get_translatable_documents( true );

		$data = [];
		if ( $translatable_types ) {
			foreach ( $translatable_types as $name => $post_type ) {
				$data[ esc_js( $name ) ] = [
					'labels'      => [
						'name'          => esc_js( $post_type->labels->name ),
						'singular_name' => esc_js( $post_type->labels->singular_name ),
					],
					'description' => esc_js( $post_type->description ),
				];
			}
		}

		return $data;
	}

	/**
	 * It returns the current user's language.
	 *
	 * @return string
	 */
	private function get_user_admin_language() {
		return $this->sitepress->get_user_admin_language( wp_get_current_user()->ID );
	}

	/**
	 * Initializes the React APP.
	 */
	private function add_initialization_script() {
		$registration_data = $this->ams_api->get_registration_data();
		$app_constructor   = [
			'host'         => esc_js( $this->endpoints->get_base_url( WPML_TM_ATE_AMS_Endpoints::SERVICE_AMS ) ),
			'wpml_host'    => esc_js( get_site_url() ),
			'wpml_home'    => esc_js( get_home_url() ),
			'secret_key'   => esc_js( $registration_data['secret'] ),
			'shared_key'   => esc_js( $registration_data['shared'] ),
			'status'       => esc_js( $registration_data['status'] ),
			'tm_email'     => esc_js( wp_get_current_user()->user_email ),
			'website_uuid' => esc_js( $this->auth->get_site_id() ),
			'site_key'     => esc_js( WP_Installer()->get_site_key( 'wpml' ) ),
			'tab'          => self::TAB_SELECTOR,
			'container'    => self::CONTAINER_SELECTOR,
			'post_types'   => $this->get_post_types_data(),
			'ui_language'  => esc_js( $this->get_user_admin_language() ),
			'restNonce'    => wp_create_nonce( 'wp_rest' ),
		];

		wp_add_inline_script( self::ATE_APP_ID, 'LoadEateWidget(' . wp_json_encode( $app_constructor, JSON_PRETTY_PRINT ) . ');', 'after' );
	}
}
