<?php

class WPML_Media_Texts_Sync{

	/**
	 * @var SitePress
	 */
	private $sitepress;
	/**
	 * @var wpdb
	 */
	private $wpdb;
	/**
	 * @var array
	 */
	private $media_duplication_settings;


	/**
	 * WPML_Media_Meta_Data_Sync constructor.
	 * @param SitePress $sitepress
	 * @param wpdb $wpdb
	 * @param array $content_settings
	 */
	public function __construct( SitePress $sitepress, wpdb $wpdb, $content_settings ) {
		$this->sitepress                  = $sitepress;
		$this->wpdb                       = $wpdb;
		$this->media_duplication_settings = $content_settings;
	}

	public function add_hooks(){
		if( true === $this->media_duplication_settings['duplicate_media'] && wpml_is_ajax()  ){
			add_action( 'attachment_updated', array( $this, 'sync_media_with_translations' ), 10, 3 );
		}
	}

	/**
	 * @param int $attachment_id
	 * @param WP_Post $post_before
	 * @param WP_Post $post_after
	 */
	public function sync_media_with_translations( $attachment_id, WP_Post $post_after, WP_Post $post_before ){

		$attachment = new WPML_Post_Element( $attachment_id, $this->sitepress );
		$attachment_source = $attachment->get_source_element();

		if( null === $attachment_source ){
			$trid = $this->sitepress->get_element_trid( $attachment_id, 'post_attachment' );
			if ( $trid ) {
				$translations = $this->sitepress->get_element_translations( $trid, 'post_attachment', true, true );
				foreach( $translations  as $translation ){
					if( (int) $translation->element_id !== $attachment_id ) {
						$this->copy_post_fields_to_duplicates( $translation->element_id, $post_after, $post_before );
						$this->copy_custom_fields_to_duplicate( $attachment_id, $translation->element_id );
					}
				}
			}
		}
	}

	/**
	 * @param int $translated_attachment_id
	 * @param WP_Post $post_after
	 * @param WP_Post $post_before
	 */
	private function copy_post_fields_to_duplicates( $translated_attachment_id, WP_Post $post_after, WP_Post $post_before ){
		$fields_to_duplicate = array( 'post_title', 'post_excerpt', 'post_content' );
		$attachment_update = array();
		foreach ( $fields_to_duplicate as $field ) {
			if ( $post_after->{$field} !== $post_before->{$field} ) {
				$attachment_update[ $field ] = $post_after->{$field};
			}
		}
		if ( $attachment_update ) {
			$this->wpdb->update( $this->wpdb->posts, $attachment_update, array( 'ID' => $translated_attachment_id ) );
		}
	}

	/**
	 * @param int $attachment_id
	 * @param int $translated_attachment_id
	 */
	private function copy_custom_fields_to_duplicate( $attachment_id, $translated_attachment_id ){
		$alt_text = get_post_meta( $attachment_id, '_wp_attachment_image_alt', true );
		update_post_meta( $translated_attachment_id, '_wp_attachment_image_alt', $alt_text );
	}

}