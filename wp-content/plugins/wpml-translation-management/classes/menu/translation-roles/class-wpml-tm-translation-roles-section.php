<?php

class WPML_TM_Translation_Roles_Section implements IWPML_TM_Admin_Section {
	const SLUG = 'translators';

	/**
	 * The WPML_Translation_Manager_Settings instance.
	 *
	 * @var WPML_Translator_Settings_Interface $translator_settings
	 */
	private $translator_settings;

	/**
	 * The WPML_Translator_Settings_Interface instance.
	 *
	 * @var WPML_Translation_Manager_Settings $translation_manager_settings
	 */
	private $translation_manager_settings;

	/**
	 * WPML_TM_Translation_Roles_Section constructor.
	 *
	 * @param \WPML_Translation_Manager_Settings  $translation_manager_settings The WPML_Translation_Manager_Settings instance.
	 * @param \WPML_Translator_Settings_Interface $translator_settings          The WPML_Translator_Settings_Interface instance.
	 */
	public function __construct(
		WPML_Translation_Manager_Settings $translation_manager_settings,
		WPML_Translator_Settings_Interface $translator_settings
	) {
		$this->translation_manager_settings = $translation_manager_settings;
		$this->translator_settings          = $translator_settings;
	}

	/**
	 * Returns a value which will be used for sorting the sections.
	 *
	 * @return int
	 */
	public function get_order() {
		return 300;
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
		return current_user_can( 'manage_options' ) ?
			__( 'Translation Roles', 'wpml-translation-management' ) :
			__( 'Translators', 'wpml-translation-management' );

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
	 * This method is hooked to the `admin_enqueue_scripts` action.
	 *
	 * @param string $hook The current page.
	 */
	public function admin_enqueue_scripts( $hook ) {}

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
		echo $this->translator_settings->render();
		echo $this->translation_manager_settings->render();
	}
}
