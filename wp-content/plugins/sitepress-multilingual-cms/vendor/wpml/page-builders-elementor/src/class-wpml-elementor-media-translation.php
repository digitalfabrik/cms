<?php

class WPML_Elementor_Media_Translation {

	private $data_settings;
	private $image_translate;
	private $translation_element_factory;

	public function __construct(
		IWPML_Page_Builders_Data_Settings $data_settings,
		WPML_Media_Image_Translate $image_translate,
		WPML_Translation_Element_Factory $translation_element_factory
	) {
		$this->data_settings               = $data_settings;
		$this->image_translate             = $image_translate;
		$this->translation_element_factory = $translation_element_factory;
	}

	public function add_hooks() {
		add_action( 'wpml_media_after_translate_media_in_post_content', array( $this, 'translate_image' ), PHP_INT_MAX, 3 );
	}

	public function translate_image( $post_id, $attachment_id, $target_language ) {
		$post        = $this->translation_element_factory->create( $post_id, 'post' );
		$translation = $post->get_translation( $target_language );

		$attachment             = $this->translation_element_factory->create( $attachment_id, 'post' );
		$attachment_translation = $attachment->get_translation( $target_language );

		if ( $translation ) {
			$custom_field_data = get_post_meta(
				$translation->get_element_id(),
				$this->data_settings->get_meta_field()
			);

			$custom_field_data = $this->data_settings->convert_data_to_array( $custom_field_data );
			$this->update_images_in_modules(
				$custom_field_data,
				wp_get_attachment_url( $attachment_id ),
				$post->get_language_code(),
				$target_language,
				$attachment_translation->get_element_id()
			);

			update_post_meta(
				$translation->get_element_id(),
				$this->data_settings->get_meta_field(),
				$this->data_settings->prepare_data_for_saving( $custom_field_data )
			);
		}
	}

	private function update_images_in_modules( &$custom_field_data, $attachment_url, $source_language, $target_language, $attachment_translation_id ) {
		foreach ( $custom_field_data as $key => &$data ) {
			if ( is_array( $data ) ) {
				$this->update_images_in_modules( $data, $attachment_url, $source_language, $target_language, $attachment_translation_id );
			} else if ( 'image' === $data ) {
				$custom_field_data['settings']['image'] = array(
					'url' => $this->image_translate->get_translated_image_by_url( $attachment_url, $source_language, $target_language ),
					'id'  => $attachment_translation_id,
				);
			}
		}
	}
}