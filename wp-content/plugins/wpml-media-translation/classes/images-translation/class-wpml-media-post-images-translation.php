<?php

/**
 * Class WPML_Media_Post_Images_Translation
 * Translate images in posts translations when a post is created or updated
 */
class WPML_Media_Post_Images_Translation {

	/**
	 * @var WPML_Media_Translated_Images_Update
	 */
	private $images_updater;

	/**
	 * @var SitePress
	 */
	private $sitepress;

	/**
	 * @var wpdb
	 */
	private $wpdb;

	/**
	 * WPML_Media_Post_Images_Translation constructor.
	 *
	 * @param WPML_Media_Translated_Images_Update $images_updater
	 * @param SitePress $sitepress
	 * @param wpdb $wpdb
	 */
	public function __construct( WPML_Media_Translated_Images_Update $images_updater, SitePress $sitepress, wpdb $wpdb ) {
		$this->images_updater = $images_updater;
		$this->sitepress      = $sitepress;
		$this->wpdb           = $wpdb;
	}

	public function add_hooks() {
		add_action( 'save_post', array( $this, 'translate_images' ), PHP_INT_MAX, 2 );
		add_filter( 'wpml_pre_save_pro_translation', array( $this, 'translate_images_in_content' ), PHP_INT_MAX, 2 );
	}

	/**
	 * @param int $post_id
	 * @param WP_Post $post
	 */
	public function translate_images( $post_id, WP_Post $post ) {

		$post_element    = new WPML_Post_Element( $post_id, $this->sitepress );
		$source_language = $post_element->get_source_language_code();
		$language        = $post_element->get_language_code();
		if ( null !== $source_language ) {

			$this->translate_images_in_post_content( $post, $language, $source_language );

		} else { // is original

			foreach ( array_keys( $this->sitepress->get_active_languages() ) as $target_language ) {
				$translation = $post_element->get_translation( $target_language );
				if ( null !== $translation && $post_id !== $translation->get_id() ) {
					$this->translate_images_in_post_content( get_post( $translation->get_id() ), $target_language, $language );
				}
			}

		}
	}

	/**
	 * @param WP_Post $post
	 * @param string $target_language
	 * @param string $source_language
	 */
	private function translate_images_in_post_content( WP_Post $post, $target_language, $source_language ) {
		$post_content_filtered = $this->images_updater->replace_images_with_translations(
			$post->post_content,
			$target_language,
			$source_language
		);
		$this->wpdb->update(
			$this->wpdb->posts,
			array( 'post_content' => $post_content_filtered ),
			array( 'ID' => $post->ID ),
			array( '%s' ),
			array( '%d' )
		);
	}

	/**
	 * @param array $postarr
	 * @param stdClass $job
	 *
	 * @return array
	 */
	public function translate_images_in_content( array $postarr, stdclass $job ){

		$postarr['post_content'] = $this->images_updater->replace_images_with_translations(
			$postarr['post_content'],
			$job->language_code,
			$job->source_language_code
		);

		return $postarr;
	}

}