<?php

/**
 * Class WPML_TM_CMS_ID
 */
class WPML_TM_CMS_ID {

	private $cms_id_parts_glue = '_';
	private $cms_id_parts_fallback_glue = '|||';

	/**
	 * @param int    $post_id
	 * @param string $post_type
	 * @param string $source_language
	 * @param string $target_language
	 *
	 * @return string
	 */
	public function build_cms_id( $post_id, $post_type, $source_language, $target_language ) {
		$cms_id_parts = array( $post_type, $post_id, $source_language, $target_language );

		return implode( $this->cms_id_parts_glue, $cms_id_parts );
	}

	/**
	 * Returns the cms_id for a given job
	 *
	 * @param int $job_id
	 *
	 * @return false|string
	 */
	function cms_id_from_job_id( $job_id ) {
		global $wpdb;

		$original_element_row = $wpdb->get_row(
			$wpdb->prepare( "SELECT o.element_id,
									o.element_type,
									o.language_code as source_lang,
									i.language_code as target_lang
							FROM {$wpdb->prefix}icl_translations o
							JOIN {$wpdb->prefix}icl_translations i
								ON i.trid = o.trid
									AND i.source_language_code = o.language_code
							JOIN {$wpdb->prefix}icl_translation_status s
								ON s.translation_id = i.translation_id
							JOIN {$wpdb->prefix}icl_translate_job j
								ON j.rid = s.rid
							WHERE j.job_id = %d
							LIMIT 1", $job_id ) );

		$type_parts = (bool) $original_element_row === true ? explode( '_', $original_element_row->element_type, 2 ) : false;

		return count( $type_parts ) === 2
			? $this->build_cms_id( $original_element_row->element_id, end( $type_parts ), $original_element_row->source_lang, $original_element_row->target_lang )
			: false;
	}

	/**
	 * @param string $cms_id
	 *
	 * @return array;
	 */
	public function parse_cms_id( $cms_id ) {
		if ( $this->is_standard_format( $cms_id ) ) {
			$parts = array_filter( explode( $this->cms_id_parts_glue, $cms_id ) );
			while ( count( $parts ) > 4 ) {
				$parts_copy = $parts;
				$parts[0]   = $parts_copy[0] . $this->cms_id_parts_glue . $parts_copy[1];
				unset( $parts_copy[0] );
				unset( $parts_copy[1] );
				$parts = array_merge( array( $parts[0] ), array_values( array_filter( $parts_copy ) ) );
			}
		} else {
			$parts = explode( $this->cms_id_parts_fallback_glue, $cms_id );
		}

		return array_pad( array_slice( $parts, 0, 4 ), false, 4 );
	}

	/**
	 * @param string   $cms_id
	 * @param bool|TranslationProxy_Service $translation_service
	 *
	 * @return int|null translation id for the given cms_id's target
	 */
	public function get_translation_id( $cms_id, $translation_service = false ) {
		global $wpdb, $wpml_translation_job_factory;

		list( $post_type, $element_id, , $target_lang ) = $this->parse_cms_id( $cms_id );
		$translation_id = $wpdb->get_var( $wpdb->prepare( "	SELECT t.translation_id
													FROM {$wpdb->prefix}icl_translations t
													JOIN {$wpdb->prefix}icl_translations o
														ON o.trid = t.trid
															AND o.element_type = t.element_type
													WHERE o.element_id=%d
														AND t.language_code=%s
														AND o.element_type LIKE %s
													LIMIT 1",
				$element_id, $target_lang, '%_' . $post_type ) );
		if ( ! $translation_id && $translation_service ) {
			$job_id         = $wpml_translation_job_factory->create_local_post_job( $element_id, $target_lang );
			$job            = $wpml_translation_job_factory->get_translation_job( $job_id, false, false, true );
			$translation_id = $job ? $job->get_translation_id() : 0;
			if ( $translation_id ) {
				$wpdb->update( $wpdb->prefix . 'icl_translation_status',
					array( 'status' => ICL_TM_IN_PROGRESS, 'translation_service' => $translation_service->id ),
						array( 'translation_id' => $translation_id ) );
			}
		}

		return $translation_id;
	}

	/**
	 * @param $cms_id
	 *
	 * @return bool
	 */
	private function is_standard_format( $cms_id ) {

		return count( array_filter( explode( $this->cms_id_parts_fallback_glue, $cms_id ) ) ) < 3;
	}
}