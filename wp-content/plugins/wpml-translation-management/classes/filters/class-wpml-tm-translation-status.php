<?php

class WPML_TM_Translation_Status {

	private $element_id_cache;

	public function init() {
		add_filter(
			'wpml_allowed_target_langs',
			array( $this, 'filter_target_langs' ),
			10,
			3
		);
		add_filter(
			'wpml_translation_status',
			array( $this, 'filter_translation_status' ),
			1,
			4
		);
		add_filter( 'wpml_job_assigned_to_after_assignment', array( $this, 'job_assigned_to_filter' ), 10, 4 );
		add_action('wpml_cache_clear', array($this, 'reload'));
	}

	/**
	 * This filters the check whether or not a job is assigned to a specific translator for local string jobs.
	 * It is to be used after assigning a job, as it will update the assignment for local string jobs itself.
	 *
	 * @param bool       $assigned_correctly
	 * @param string|int $job_id
	 * @param int        $translator_id
	 * @param string|int $service
	 *
	 * @return bool
	 */
	public function job_assigned_to_filter( $assigned_correctly, $job_id, $translator_id, $service ) {
		if ( ( ! $service || $service === 'local' ) && strpos( $job_id, 'string|' ) !== false ) {
			global $wpdb;
			$job_id = preg_replace( '/[^0-9]/', '', $job_id );
			$wpdb->update(
				$wpdb->prefix . 'icl_string_translations',
				array( 'translator_id' => $translator_id ),
				array( 'id' => $job_id )
			);
			$assigned_correctly = true;
		}

		return $assigned_correctly;
	}

	private function is_in_basket( $element_id, $lang, $element_type_prefix ) {
		return TranslationProxy_Basket::anywhere_in_basket(
			$element_id,
			$element_type_prefix,
			array( $lang => 1 )
		);
	}

	/**
	 * @param array  $allowed_langs
	 * @param int    $element_id
	 * @param string $element_type_prefix
	 *
	 * @return array
	 */
	public function filter_target_langs( $allowed_langs, $element_id, $element_type_prefix ) {
		if ( TranslationProxy_Basket::anywhere_in_basket( $element_id, $element_type_prefix ) ) {
			$allowed_langs = array();
		} else {
			$src_lang = SitePress::get_source_language_by_trid( $this->get_element_trid( $element_id,
																						 $element_type_prefix ) );
			foreach ( $allowed_langs as $key => $lang_code ) {
				if ( $lang_code === $src_lang || $this->is_in_active_job( $element_id,
																		  $lang_code,
																		  $element_type_prefix )
				) {
					unset( $allowed_langs[ $key ] );
				}
			}
		}

		return $allowed_langs;
	}

	public function filter_translation_status( $status, $trid, $target_lang_code ) {
		/** @var WPML_TM_Element_Translations $wpml_tm_element_translations */
		global $wpml_tm_element_translations;

		$element_ids         = $this->get_element_ids( $trid );
		$element_type_prefix = $wpml_tm_element_translations->get_element_type_prefix( $trid, $target_lang_code );
		foreach ( $element_ids as $id ) {
			if ( $this->is_in_basket( $id, $target_lang_code, $element_type_prefix ) ) {
				$status = ICL_TM_IN_BASKET;
				break;
			} elseif ( (bool) ( $job_status = $this->is_in_active_job(
					$id,
					$target_lang_code,
					$element_type_prefix,
					true
				) ) !== false
			) {
				$status = $job_status;
			}
		}
		$status = $status != ICL_TM_IN_BASKET && $wpml_tm_element_translations->is_update_needed( $trid,
																								  $target_lang_code )
			? ICL_TM_NEEDS_UPDATE
			: $status;

		return $status;
	}

	public function reload(){
		$this->element_id_cache = array();
	}

	protected function get_element_trid( $element_id, $element_type_prefix ) {
		/**
		 * @var WPML_Post_Translation $wpml_post_translations
		 */
		global $wpml_post_translations;
		global $wpdb;

		if ( $element_type_prefix === 'post' ) {
			$trid = $wpml_post_translations->get_element_trid( $element_id );
		} elseif ( (bool) $element_type_prefix === true && (bool) $element_id === true ) {
			$trid = $wpdb->get_var(
				$wpdb->prepare(
					"SELECT trid FROM {$wpdb->prefix}icl_translations WHERE element_id = %d AND element_type LIKE %s",
					$element_id,
					$element_type_prefix . '%'
				)
			);
		} else {
			$trid = false;
		}

		return $trid;
	}

	protected function is_in_active_job(
		$element_id,
		$target_lang_code,
		$element_type_prefix,
		$return_status = false
	) {
		/**
		 * @var TranslationManagement        $iclTranslationManagement
		 * @var WPML_TM_Element_Translations $wpml_tm_element_translations
		 */
		global $wpml_tm_element_translations;
		$trid = $this->get_element_trid( $element_id, $element_type_prefix );
		if ( $return_status && SitePress::get_source_language_by_trid( $trid ) === $target_lang_code ) {
			$res = ICL_TM_COMPLETE;
		} else {
			$job_id = $wpml_tm_element_translations->get_job_id( $trid, $target_lang_code );
			$res    = false;
			if ( $job_id > 0 ) {
				$res = $wpml_tm_element_translations->get_translation_status( $trid, $target_lang_code );
				$res = $return_status
					? $res
					: in_array( $res, array( ICL_TM_IN_PROGRESS, ICL_TM_WAITING_FOR_TRANSLATOR ), true );
			} elseif ( $return_status && (bool) $wpml_tm_element_translations->get_element_id( $trid,
																							   $target_lang_code ) === true
			) {
				$res = ICL_TM_COMPLETE;
			}
		}

		return $res;
	}

	private function get_element_ids( $trid ) {
		global $wpdb;

		if ( (bool) $trid === true ) {
			if ( isset( $this->element_id_cache[ $trid ] ) ) {
				$res = $this->element_id_cache[ $trid ];
			} else {
				$res                             = $wpdb->get_col( $wpdb->prepare( "SELECT element_id
																					FROM {$wpdb->prefix}icl_translations
																					WHERE trid = %d",
																				   $trid ) );
				$this->element_id_cache[ $trid ] = $res;

			}
		} else {
			$res = false;
		}

		return $res;
	}

}