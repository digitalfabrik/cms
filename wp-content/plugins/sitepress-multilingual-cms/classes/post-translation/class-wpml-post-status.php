<?php

class WPML_Post_Status extends WPML_WPDB_User {

	private $needs_update = array();
	private $status       = array();
	private $wp_api;
	
	public function __construct( &$wpdb, $wp_api ) {
		parent::__construct( $wpdb );
		$this->wp_api = $wp_api;
	}

	public function needs_update( $post_id ) {
		if ( !isset( $this->needs_update[ $post_id ] ) ) {
			global $wpml_post_translations;

			$this->needs_update[ $post_id ] = (bool) $this->wpdb->get_var (
				$this->wpdb->prepare (
					"SELECT ts.needs_update
                     FROM {$this->wpdb->prefix}icl_translation_status ts
		             JOIN {$this->wpdb->prefix}icl_translations it
						ON it.translation_id = ts.translation_id
					 WHERE it.trid = %d AND it.language_code = %s",
					$wpml_post_translations->get_element_trid ( $post_id ),
					$wpml_post_translations->get_element_lang_code ( $post_id )
				)
			);
		}

		return $this->needs_update [ $post_id ];
	}

	public function reload() {
		$this->needs_update = array();
		$this->status       = array();
	}

	public function set_update_status( $post_id, $update ) {
		global $wpml_post_translations;

		$update = (bool) $update;
		$translation_id = $this->wpdb->get_var (
			$this->wpdb->prepare (
				"SELECT ts.translation_id
                     FROM {$this->wpdb->prefix}icl_translations it
		             JOIN {$this->wpdb->prefix}icl_translation_status ts
						ON it.translation_id = ts.translation_id
					 WHERE it.trid = %d AND it.language_code = %s",
				$wpml_post_translations->get_element_trid ( $post_id ),
				$wpml_post_translations->get_element_lang_code ( $post_id )
			)
		);

		if ( $translation_id ) {
			$res = $this->wpdb->update (
				$this->wpdb->prefix . 'icl_translation_status',
				array( 'needs_update' => $update ),
				array( 'translation_id' => $translation_id )
			);
		}

		$this->needs_update[ $post_id ] = (bool) $update;

		return isset( $res );
	}

	/**
	 * @param int $post_id
	 * @param int $status
	 *
	 * @return bool
	 */
	public function set_status( $post_id, $status ) {
		global $wpml_post_translations;

		if ( ! $post_id ) {
			throw new InvalidArgumentException(
				'Tried to set status' . $status . ' for falsy post_id ' . serialize( $post_id ) );
		}

		$translation_id = $this->wpdb->get_row (
			$this->wpdb->prepare (
				"SELECT it.translation_id AS transid, ts.translation_id AS status_id
                     FROM {$this->wpdb->prefix}icl_translations it
		             LEFT JOIN {$this->wpdb->prefix}icl_translation_status ts
						ON it.translation_id = ts.translation_id
					 WHERE it.trid = %d AND it.language_code = %s
					 LIMIT 1",
				$wpml_post_translations->get_element_trid ( $post_id ),
				$wpml_post_translations->get_element_lang_code ( $post_id )
			)
		);

		if ( $translation_id->status_id && $translation_id->transid ) {
			$res                      = $this->wpdb->update (
				$this->wpdb->prefix . 'icl_translation_status',
				array( 'status' => $status ),
				array( 'translation_id' => $translation_id->transid )
			);
			$this->status[ $post_id ] = $status;
		} else {
			$res = $this->wpdb->insert (
				$this->wpdb->prefix . 'icl_translation_status',
				array( 'status' => $status, 'translation_id' => $translation_id->transid )
			);
		}

		return isset( $res );
	}

	public function get_status( $post_id, $trid = false, $lang_code = false ) {
		global $wpml_post_translations;

		$trid      = $trid !== false ? $trid : $wpml_post_translations->get_element_trid ( $post_id );
		$lang_code = $lang_code !== false ? $lang_code : $wpml_post_translations->get_element_lang_code ( $post_id );
		$post_id = $post_id ? $post_id : $wpml_post_translations->get_element_id ( $lang_code, $trid );
		if ( !$post_id ) {
			$status  = ICL_TM_NOT_TRANSLATED;
			$post_id = $lang_code . $trid;
		} else {
			$status = $this->is_duplicate( $post_id )
				? ICL_TM_DUPLICATE : ( $this->needs_update ( $post_id ) ? ICL_TM_NEEDS_UPDATE : ICL_TM_COMPLETE );
		}
		$status = apply_filters (
			'wpml_translation_status',
			$status,
			$trid,
			$lang_code,
			true
		);
		$this->status[ $post_id ] = $status;

		return $status;
	}
	
	public function is_duplicate( $post_id ) {
		return (bool) $this->wp_api->get_post_meta ( $post_id, '_icl_lang_duplicate_of', true );
	}
}