<?php

class WPML_TM_Translation_Status extends WPML_TM_Record_User {

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
		add_action('wpml_cache_clear', array($this, 'reload'));
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
		} elseif ( (bool) ( $trid = $this
				->tm_records
				->icl_translations_by_element_id_and_type_prefix(
					$element_id, $element_type_prefix )
				->trid() ) === true
		) {
			foreach ( $allowed_langs as $key => $lang_code ) {
				$element = $this->tm_records->icl_translations_by_trid_and_lang( $trid, $lang_code );
				if ( ( $element->element_id() && ! $element->source_language_code() )
				     || $this->is_in_active_job( $element_id,
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

		if ( $trid ) {
			$element_ids         = array_filter( $this->get_element_ids( $trid ) );
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
					break;
				}
			}
			$status = $status != ICL_TM_IN_BASKET && $wpml_tm_element_translations->is_update_needed( $trid,
				$target_lang_code )
				? ICL_TM_NEEDS_UPDATE
				: $status;
		}

		return $status;
	}

	public function reload(){
		$this->element_id_cache = array();
	}

	private function is_in_active_job(
		$element_id,
		$target_lang_code,
		$element_type_prefix,
		$return_status = false
	) {
		$trid = $this->tm_records->icl_translations_by_element_id_and_type_prefix( $element_id, $element_type_prefix )->trid();
		$element_translated = $this->tm_records->icl_translations_by_trid_and_lang( $trid, $target_lang_code );
		if ( ! $element_translated->source_language_code()
		     && $element_translated->element_id() == $element_id
		) {
			$res = $return_status ? ICL_TM_COMPLETE : false;
		} else {
			$res            = false;
			$translation_id = $element_translated->translation_id();
			if ( $translation_id ) {
				$res = $this->tm_records->icl_translation_status_by_translation_id( $translation_id )->status();
				$res = $return_status
					? $res
					: in_array( $res, array(
						ICL_TM_IN_PROGRESS,
						ICL_TM_WAITING_FOR_TRANSLATOR
					), true );
			}
		}

		return $res;
	}

	private function get_element_ids( $trid ) {
		if ( ! isset( $this->element_id_cache[ $trid ] ) ) {
			$wpdb = $this->tm_records->wpdb();
			$this->element_id_cache[ $trid ] = $wpdb->get_col(
				$wpdb->prepare( "SELECT element_id
								 FROM {$wpdb->prefix}icl_translations
								 WHERE trid = %d",
					$trid ) );
		}

		return $this->element_id_cache[ $trid ];
	}
}