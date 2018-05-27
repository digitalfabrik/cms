<?php

/**
 * Class WPML_Media_Image_Translate
 * Allows getting translated images in a give language from an attachment
 */
class WPML_Media_Image_Translate {

	/**
	 * @var SitePress
	 */
	private $sitepress;

	/**
	 * @var wpdb
	 */
	private $wpdb;

	/**
	 * WPML_Media_Image_Translate constructor.
	 *
	 * @param SitePress $sitepress
	 */
	public function __construct( SitePress $sitepress, wpdb $wpdb ) {
		$this->sitepress = $sitepress;
		$this->wpdb      = $wpdb;
	}

	/**
	 * @param int $attachment_id
	 * @param string $language
	 * @param string $size
	 *
	 * @throws WPML_Media_Exception
	 *
	 * @return string
	 */
	public function get_translated_image( $attachment_id, $language, $size = null ) {

		$attachment = new WPML_Post_Element( $attachment_id, $this->sitepress );
		$attachment_translation = $attachment->get_translation( $language );
		$image_url = '';

		if ( $attachment_translation ) {
			$uploads_dir = wp_get_upload_dir();
			if ( null === $size ) {
				$image_url = $uploads_dir['baseurl'] . '/' .
				             get_post_meta( $attachment_translation->get_id(), '_wp_attached_file', true );
			} else {
				$meta_data = wp_get_attachment_metadata( $attachment_translation->get_id() );
				if ( isset( $meta_data['sizes'][ $size ] ) ) {
					$image_url = $uploads_dir['baseurl'] . '/' . $meta_data['sizes'][ $size ]['file'];
				}
			}
		}

		return $image_url;
	}

	/**
	 * @param string|bool $img_src
	 * @param string      $source_language
	 * @param string      $target_language
	 *
	 * @return string|bool
	 */
	public function get_translated_image_by_url( $img_src, $source_language, $target_language ){
		$attachment_id = $this->get_attachment_image_from_guid( $img_src, $source_language );
		if( ! $attachment_id ){
			$attachment_id = $this->get_attachment_image_from_meta( $img_src, $source_language );
		}

		if ( $attachment_id ) {
			$size = $this->get_image_size_from_url( $img_src, $attachment_id );
			try {
				$img_src = $this->get_translated_image( $attachment_id, $target_language, $size );
			} catch ( Exception $e ) {
				$img_src = false;
			}
		} else {
			$img_src = false;
		}

		return $img_src;
	}

	/**
	 * @param string $url
	 * @param int $attachment_id
	 *
	 * @return string
	 */
	private function get_image_size_from_url( $url, $attachment_id ){
		$size = null;

		$thumb_file_name = basename( $url );
		$attachment_meta_data = wp_get_attachment_metadata( $attachment_id );
		foreach ( $attachment_meta_data['sizes'] as $key => $size_array ) {
			if ( $thumb_file_name === $size_array['file'] ) {
				$size = $key;
				break;
			}
		}

		return $size;
	}

	/**
	 * @param string $img_src
	 * @param string $source_language
	 *
	 * @return null|string
	 */
	private function get_attachment_image_from_guid( $img_src, $source_language ){
		$attachment_id = $this->wpdb->get_var( $this->wpdb->prepare( "
			SELECT ID FROM {$this->wpdb->posts} p
			JOIN {$this->wpdb->prefix}icl_translations t ON t.element_id = p.ID
			WHERE t.element_type='post_attachment' AND t.language_code=%s AND p.guid=%s
			", $source_language, $img_src ) );

		return $attachment_id;
	}

	/**
	 * @param string $img_src
	 * @param string $source_language
	 *
	 * @return null|string
	 */
	private function get_attachment_image_from_meta( $img_src, $source_language ){

		$uploads_dir = wp_get_upload_dir();
		$relative_path = ltrim( preg_replace( '@^' . $uploads_dir['baseurl'] . '@', '', $img_src ) , '/' );

		// using _wp_attached_file
		$attachment_id = $this->wpdb->get_var( $this->wpdb->prepare( "
			SELECT post_id 
			FROM {$this->wpdb->postmeta} p 
			JOIN {$this->wpdb->prefix}icl_translations t ON t.element_id = p.post_id 
			WHERE p.meta_key='_wp_attached_file' AND p.meta_value=%s 
				AND t.element_type='post_attachment' AND t.language_code=%s
			", $relative_path, $source_language ));

		// using attachment meta (fallback)
		if( ! $attachment_id && preg_match( '/-([0-9]+)x([0-9]+)\.([a-z]{3,4})$/', $relative_path ) ){
			$attachment_id = $this->get_attachment_image_from_meta_fallback( $relative_path, $source_language  );
		}

		return $attachment_id;
	}

	/**
	 * @param string $relative_path
	 * @param string $source_language
	 *
	 * @return null|string
	 */
	private function get_attachment_image_from_meta_fallback( $relative_path, $source_language ){
		$attachment_id = null;
		if( preg_match( '/-([0-9]+)x([0-9]+)\.([a-z]{3,4})$/', $relative_path ) ) {
			$relative_path_original = preg_replace('/-([0-9]+)x([0-9]+)\.([a-z]{3,4})$/', '.$3', $relative_path );
			$attachment_id_original = $this->wpdb->get_var( $this->wpdb->prepare( "
				SELECT p.post_id 
				FROM {$this->wpdb->postmeta} p
				JOIN {$this->wpdb->prefix}icl_translations t ON t.element_id = p.post_id
				WHERE p.meta_key='_wp_attached_file' AND p.meta_value=%s 
					AND t.element_type='post_attachment' AND t.language_code=%s
				", $relative_path_original, $source_language ));
			// validate size
			$attachment_meta_data = wp_get_attachment_metadata( $attachment_id_original );
			if( $this->validate_image_size( $relative_path, $attachment_meta_data ) ){
				$attachment_id = $attachment_id_original;
			}
		}

		return $attachment_id;
	}

	/**
	 * @param string $path
	 * @param array $attachment_meta_data
	 *
	 * @return bool
	 */
	private function validate_image_size( $path, $attachment_meta_data ) {
		$valid = false;
		$thumb_file_name = basename( $path );

		foreach ( $attachment_meta_data['sizes'] as $size ) {
			if ( $thumb_file_name === $size['file'] ) {
				$valid = true;
				break;
			}
		}

		return $valid;
	}

}