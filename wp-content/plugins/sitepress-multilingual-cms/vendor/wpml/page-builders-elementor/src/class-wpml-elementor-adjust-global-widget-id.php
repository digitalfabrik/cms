<?php

class WPML_Elementor_Adjust_Global_Widget_ID {

	/** @var IWPML_Page_Builders_Data_Settings */
	private $elementor_settings;

	/** @var WPML_Translation_Element_Factory */
	private $translation_element_factory;

	/** @var SitePress */
	private $sitepress;

	/** @var string */
	private $current_language;

	public function __construct(
		IWPML_Page_Builders_Data_Settings $elementor_settings,
		WPML_Translation_Element_Factory $translation_element_factory,
		SitePress $sitepress
	) {
		$this->elementor_settings          = $elementor_settings;
		$this->translation_element_factory = $translation_element_factory;
		$this->sitepress                   = $sitepress;
	}

	public function add_hooks() {
		add_action( 'elementor/editor/before_enqueue_scripts', array( $this, 'adjust_ids' ) );
		add_action( 'elementor/editor/after_enqueue_scripts', array( $this, 'restore_current_language' ) );
	}

	public function adjust_ids() {
		$this->current_language = $this->sitepress->get_current_language();

		$post_id = absint( $_REQUEST['post'] ); // WPCS: sanitization ok.

		$post     = $this->translation_element_factory->create_post( $post_id );
		$language = $post->get_language_code();
		$this->sitepress->switch_lang( $language );

		$custom_field_data = get_post_meta(
			$post_id,
			$this->elementor_settings->get_meta_field()
		);

		if ( ! $custom_field_data ) {
			return;
		}

		$custom_field_data = $this->elementor_settings->convert_data_to_array( $custom_field_data );

		$custom_field_data_adjusted = $this->set_global_widget_id_for_language( $custom_field_data, $language );

		if ( $custom_field_data_adjusted !== $custom_field_data ) {
			update_post_meta(
				$post_id,
				$this->elementor_settings->get_meta_field(),
				$this->elementor_settings->prepare_data_for_saving( $custom_field_data_adjusted )
			);

			// Update post date so Elementor doesn't use auto saved post
			$post_data                  = get_post( $post_id, ARRAY_A );
			$post_data['post_date']     = current_time( 'mysql' );
			$post_data['post_date_gmt'] = '';

			wp_update_post( $post_data );
		}

	}

	private function set_global_widget_id_for_language( $data_array, $language ) {
		foreach ( $data_array as &$data ) {
			if ( isset( $data['elType'] ) && 'widget' === $data['elType'] && 'global' === $data['widgetType'] ) {
				try {
					$widget_post = $this->translation_element_factory->create_post( $data['templateID'] );
					if ( $widget_post->get_language_code() !== $language ) {
						$translation = $widget_post->get_translation( $language );
						if ( $translation ) {
							$data['templateID'] = $translation->get_element_id();
						}
					}
				} catch ( Exception $e ) {
					// Not much we can do if the elementor templateID is a non existing post
				}
			}
			$data['elements'] = $this->set_global_widget_id_for_language( $data['elements'], $language );
		}

		return $data_array;
	}

	public function restore_current_language() {
		$this->sitepress->switch_lang( $this->current_language );
	}

}