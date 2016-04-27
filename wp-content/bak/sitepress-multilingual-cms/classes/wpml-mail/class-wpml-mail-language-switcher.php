<?php
/**
 * Send emails in receiver's preferred language, not current language (wpmlcore-1532)
 * @package wpml-mail
 */

class WPML_Mail_Language_Switcher {

	private $nonce_name = 'wpml_mail_language_switcher';
	/**
	 * @var WPML_Mail_Languages_Helper
	 */
	private $WPML_Mail_Languages_Helper;

	public function __construct( &$WPML_Mail_Languages_Helper ) {
		$this->WPML_Mail_Languages_Helper = &$WPML_Mail_Languages_Helper;
		$this->register_hooks();
	}

	public function register_hooks() {
		add_action( 'wpml_mail_language_switcher', array( $this, 'wpml_mail_language_switcher_action' ), 10, 1 );
		add_action( 'wp_ajax_wpml_mail_language_switcher_form_ajax', array( $this, 'wpml_mail_language_switcher_form_ajax_callback' ) );
		add_action( 'wp_ajax_nopriv_wpml_mail_language_switcher_form_ajax', array( $this, 'wpml_mail_language_switcher_form_ajax_callback' ) );
	}

	public function wpml_mail_language_switcher_action( $args ) {
		echo $this->wpml_mail_language_switcher( $args );
	}

	private function wpml_mail_language_switcher( $args ) {

		$atts = array(
			'mail'              => null,
			'auto_refresh_page' => 0,
		);

		$atts = array_replace( $atts, $args );

		wp_register_script( 'wpml-mail', ICL_PLUGIN_URL . '/res/js/wpml-mail.js', array( 'jquery' ) );

		$wp_mail_script_data = array(
			'ajax_url'          => admin_url( 'admin-ajax.php' ),
			'mail'              => $atts['mail'],
			'auto_refresh_page' => $atts['auto_refresh_page'],
			'nonce'             => wp_create_nonce( $this->nonce_name ),
		);

		wp_localize_script( 'wpml-mail', 'wpml_mail_data', $wp_mail_script_data );

		wp_enqueue_script( 'wpml-mail' );

		$form = $this->get_view( $atts['mail'] );

		return $form;
	}

	private function to_be_selected( $email ) {
		$language = $this->get_language_from_usermeta( $email );
		if ( ! $language ) {
			$language = isset( $_POST['language'] ) ? $_POST['language'] : null;
		}

		return $language;
	}

	private function get_language_from_usermeta( $email ) {
		return $this->WPML_Mail_Languages_Helper->get_language_from_usermeta( $email );
	}

	public function wpml_mail_language_switcher_form_ajax_callback() {
		$this->wpml_mail_language_switcher_form_ajax();
	}

	private function wpml_mail_language_switcher_form_ajax() {

		list( $email, $language ) = $this->sanitize_ajax_data();

		$valid = $this->is_valid_data( $_POST['nonce'], $email );

		if ( ! $valid || ! $language ) {
			wp_send_json_error();
		}

		$saved_by_third_party = $updated = apply_filters( 'wpml_mail_language_switcher_save', false, $email, $language );

		if ( ! $saved_by_third_party ) {
			$updated = $this->save_language_user_meta( $email, $language );
		}
		wp_send_json_success( $updated );
	}

	private function sanitize_ajax_data() {
		$language = filter_input( INPUT_POST, 'language', FILTER_SANITIZE_STRING );
		$language = $this->WPML_Mail_Languages_Helper->sanitize_language_code( $language );

		$email = filter_input( INPUT_POST, 'mail', FILTER_SANITIZE_EMAIL );

		return array( $email, $language );
	}

	private function is_valid_data( $nonce, $email ) {
		return ( wp_verify_nonce( $nonce, $this->nonce_name ) && is_email( $email ) );
	}

	private function save_language_user_meta( $email, $language ) {
		$user    = get_user_by( 'email', $email );
		$updated = false;
		if ( $user && isset( $user->ID ) ) {
			$language = $this->WPML_Mail_Languages_Helper->sanitize_language_code( $language );
			$updated  = update_user_meta( $user->ID, 'icl_admin_language', $language );
		}

		return $updated;
	}

	/**
	 * @param string $email
	 *
	 * @return string
	 */
	protected function get_view( $email ) {
		$model          = $this->get_model( $email );
		$template_paths = array(
			ICL_PLUGIN_PATH . '/templates/wpml-mail/',
		);

		$template = 'language-switcher.twig';

		$loader           = new Twig_Loader_Filesystem( $template_paths );
		$environment_args = array();
		if ( WP_DEBUG ) {
			$environment_args['debug'] = true;
		}

		$twig = new Twig_Environment( $loader, $environment_args );
		$view = $twig->render( $template, $model );

		return $view;
	}

	private function get_model( $email ) {

		$active_languages = apply_filters( 'wpml_active_languages', null, null );

		$to_be_selected = $this->to_be_selected( $email );

		$options = array();

		$options[] = array(
			'label'    => __( 'Choose language:', 'sitepress' ),
			'value'    => 0,
			'selected' => false,

		);

		foreach ( $active_languages as $code => $lang ) {
			$selected = ( $to_be_selected == $code );

			$options[] = array(
				'label'    => $lang['translated_name'],
				'value'    => $code,
				'selected' => $selected,

			);
		}

		$model = array(
			'options' => $options,
		);

		return $model;
	}
}
