<?php

class WPML_Media_Attachments_Duplication {
	/** @var  WPML_Model_Attachments */
	private $attachments_model;

	/** @var SitePress */
	private $sitepress;

	/**
	 * WPML_Media_Attachments_Duplication constructor.
	 *
	 * @param SitePress $sitepress
	 * @param WPML_Model_Attachments $attachments_model
	 *
	 * @internal param WPML_WP_API $wpml_wp_api
	 */
	public function __construct( $sitepress, WPML_Model_Attachments $attachments_model ) {
		$this->sitepress   = $sitepress;
		$this->attachments_model = $attachments_model;
	}

	/**
	 * @param int $attachment_id
	 * @param int $parent_id
	 * @param string $target_language
	 *
	 * @return int|null
	 */
	public function create_duplicate_attachment( $attachment_id, $parent_id, $target_language ) {
		try {
			$attachment_post = get_post( $attachment_id );
			if ( ! $attachment_post ) {
				throw new WPML_Media_Exception( sprintf( 'Post with id %d does not exist', $attachment_id ) );
			}

			$trid = $this->sitepress->get_element_trid( $attachment_id, WPML_Model_Attachments::ATTACHMENT_TYPE );
			if ( ! $trid ) {
				throw new WPML_Media_Exception( sprintf( 'Attachment with id %s does not contain language information', $attachment_id ) );
			}

			$duplicated_attachment    = $this->attachments_model->find_duplicated_attachment( $trid, $target_language );
			$duplicated_attachment_id = null;
			if ( null !== $duplicated_attachment ) {
				$duplicated_attachment_id = $duplicated_attachment->ID;
			}
			$translated_parent_id = $this->attachments_model->fetch_translated_parent_id( $duplicated_attachment, $parent_id, $target_language );

			if ( null !== $duplicated_attachment ) {
				if ( $duplicated_attachment->post_parent !== $translated_parent_id ) {
					$this->attachments_model->update_parent_id_in_existing_attachment( $translated_parent_id, $duplicated_attachment );
				}
			} else {
				$duplicated_attachment_id = $this->attachments_model->duplicate_attachment( $attachment_id, $target_language, $translated_parent_id, $trid );
			}

			$this->attachments_model->duplicate_post_meta_data( $attachment_id, $duplicated_attachment_id );

			return $duplicated_attachment_id;
		} catch ( WPML_Media_Exception $e ) {
			return null;
		}
	}
}
