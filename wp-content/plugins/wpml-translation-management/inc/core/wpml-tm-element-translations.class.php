<?php

class WPML_TM_Element_Translations {

	/** @var  int[] $trid_cache */
	private $trid_cache;
	/** @var  int[] $job_id_cache */
	private $job_id_cache;
	/** @var  int[] $job_id_cache */
	private $translation_status_cache;
	/** @var  bool[] $update_status_cache */
	private $update_status_cache;
	/** @var  string[] $element_type_prefix_cache */
	private $element_type_prefix_cache = array();

	public function __construct() {
		add_action( 'wpml_cache_clear', array( $this, 'reload' ) );
		add_filter( 'wpml_tm_get_element_id', array( $this, 'get_element_id_filter' ), 10, 2 );
		add_filter( 'wpml_tm_translation_status', array( $this, 'get_translation_status_filter' ), 10, 2 );
	}

	public function reload() {
		$this->trid_cache                = array();
		$this->job_id_cache              = array();
		$this->translation_status_cache  = array();
		$this->update_status_cache       = array();
		$this->element_type_prefix_cache = array();
	}

	public function get_element_id_filter( $empty, $arg ) {
		$trid = $arg['trid'];
		$language_code = $arg['language_code'];
		return $this->get_element_id($trid, $language_code);
	}
	/**
	 * @param int    $trid
	 * @param string $language_code
	 *
	 * @return bool|int element_id for trid/lang combination or false if not found
	 */
	public function get_element_id( $trid, $language_code ) {
		if ( (bool) $trid === false || (bool) $language_code === false ) {

			return false;
		}

		$element_id = isset( $this->trid_cache[ $trid ][ $language_code ] )
			? $this->trid_cache[ $trid ][ $language_code ]
			: $this->retrieve_el_id( $trid, $language_code );

		$element_id = (int) $element_id;

		return $element_id > 0 ? $element_id : false;
	}

	public function is_update_needed( $trid, $language_code ) {
		if ( isset( $this->update_status_cache[ $trid ][ $language_code ] ) ) {
			$needs_update = $this->update_status_cache[ $trid ][ $language_code ];
		} else {
			$this->get_job_id( $trid, $language_code );
			$needs_update = isset( $this->update_status_cache[ $trid ][ $language_code ] )
				? $this->update_status_cache[ $trid ][ $language_code ] : 0;
		}

		return (bool) $needs_update;
	}

	/**
	 * @param int    $trid
	 * @param string $language_code
	 *
	 * @return string
	 */
	public function get_element_type_prefix( $trid, $language_code ) {
		if ( isset( $this->element_type_prefix_cache[ $trid ] ) ) {
			$prefix = $this->element_type_prefix_cache[ $trid ];
		} else {
			$this->get_job_id( $trid, $language_code );
			$prefix = isset( $this->element_type_prefix_cache[ $trid ] )
				? $this->element_type_prefix_cache[ $trid ] : "";
		}

		return $prefix;
	}

	public function get_translation_status_filter( $empty, $args ) {
		$trid = $args['trid'];
		$language_code = $args['language_code'];

		return $this->get_translation_status($trid, $language_code);
	}
	/**
	 * @param int    $trid
	 * @param string $language_code
	 *
	 * @return int
	 */
	public function get_translation_status( $trid, $language_code ) {
		if ( isset( $this->translation_status_cache[ $trid ][ $language_code ] ) ) {
			$status = $this->translation_status_cache[ $trid ][ $language_code ];
		} else {
			$this->get_job_id( $trid, $language_code );
			$status = isset( $this->translation_status_cache[ $trid ][ $language_code ] )
				? $this->translation_status_cache[ $trid ][ $language_code ] : 0;
		}

		return (int) $status;
	}

	public function get_job_id( $trid, $target_lang_code ) {
		global $wpdb, $wpml_language_resolution;

		if ( (bool) $trid === false || (bool) $target_lang_code === false ) {
			return false;
		}

		if ( ! isset( $this->job_id_cache[ $trid ][ $target_lang_code ] ) ) {
			$jobs         = $wpdb->get_results( $wpdb->prepare( "
														SELECT
															tj.job_id,
															ts.status,
															ts.needs_update,
															t.language_code,
															SUBSTRING_INDEX(t.element_type, '_', 1)
																AS element_type_prefix
														FROM {$wpdb->prefix}icl_translate_job tj
														JOIN {$wpdb->prefix}icl_translation_status ts
															ON tj.rid = ts.rid
														JOIN {$wpdb->prefix}icl_translations t
															ON ts.translation_id = t.translation_id
														WHERE t.trid = %d
												",
																$trid ) );
			$active_langs = $wpml_language_resolution->get_active_language_codes();

			foreach ( $active_langs as $lang_code ) {
				$this->cache_job_in_lang( $jobs, $lang_code, $trid );
			}
		}

		return $job_id = $this->job_id_cache[ $trid ][ $target_lang_code ];
	}

	/**
	 * @param object[]   $jobs
	 * @param string     $lang
	 * @param     string $trid
	 *
	 * @return false|object
	 */
	private function cache_job_in_lang( $jobs, $lang, $trid ) {
		$res = false;
		foreach ( $jobs as $job ) {
			if ( $job->language_code === $lang ) {
				$res = $job;
				break;
			}
		}

		if ( (bool) $res === true ) {
			$job_id              = $res->job_id;
			$status              = $res->status;
			$needs_update        = (bool) $res->needs_update;
			$element_type_prefix = $res->element_type_prefix;
		} else {
			$job_id              = - 1;
			$status              = 0;
			$needs_update        = false;
			$element_type_prefix = $this->fallback_type_prefix($trid);
		}

		$this->cache_job( $trid, $lang, $job_id, $status, $needs_update, $element_type_prefix );

		return $res;
	}

	private function fallback_type_prefix( $trid ) {
		global $wpdb;

		return isset( $this->element_type_prefix_cache[ $trid ] ) && (bool) $this->element_type_prefix_cache[ $trid ] === true
			? $this->element_type_prefix_cache[ $trid ]
			: $wpdb->get_var( $wpdb->prepare( "SELECT SUBSTRING_INDEX(element_type, '_', 1)
                                                FROM {$wpdb->prefix}icl_translations
                                                WHERE trid = %d
                                                LIMIT 1",
											  $trid ) );
	}

	private function retrieve_el_id( $trid, $language_code ) {
		/**
		 * @var WPML_Post_Translation $wpml_post_translations
		 * @var WPML_Term_Translation $wpml_term_translations
		 */
		global $wpml_post_translations, $wpml_term_translations;

		foreach ( array( $wpml_post_translations, $wpml_term_translations ) as $trans_obj ) {
			/** @var WPML_Element_Translation $trans_obj */
			$translations = $trans_obj->get_element_translations( false, $trid );
			if ( isset( $translations[ $language_code ] ) ) {
				$element_id = $translations[ $language_code ];
				break;
			}
		}
		if ( ! isset( $element_id ) ) {
			$element_id = $this->get_el_id_from_db( $trid, $language_code );
		}

		$element_id = (bool) $element_id === true ? $element_id : - 1;
		$this->cache_el_id( $trid, $language_code, $element_id );

		return $element_id;
	}

	private function cache_el_id( $trid, $language_code, $element_id ) {
		$this->maybe_init_trid_cache( $trid );
		if ( (bool) $element_id === true && (bool) $trid === true && (bool) $language_code === true ) {
			$this->trid_cache[ $trid ]                   = isset( $this->trid_cache[ $trid ] )
				? $this->trid_cache[ $trid ] : array();
			$this->trid_cache[ $trid ][ $language_code ] = $element_id;
		}
	}

	/**
	 * @param int    $trid
	 * @param string $language_code
	 * @param int    $job_id
	 * @param int    $status
	 * @param bool   $needs_update
	 * @param string $element_type_prefix
	 */
	private function cache_job( $trid, $language_code, $job_id, $status, $needs_update, $element_type_prefix ) {
		if ( (bool) $job_id === true && (bool) $trid === true && (bool) $language_code === true ) {
			$this->maybe_init_trid_cache( $trid );
			$this->job_id_cache[ $trid ][ $language_code ]             = $job_id;
			$this->translation_status_cache[ $trid ][ $language_code ] = $status;
			$this->update_status_cache[ $trid ][ $language_code ]      = $needs_update;
			$this->element_type_prefix_cache[ $trid ]                  = isset( $this->element_type_prefix_cache[ $trid ] )
																		 && (bool) $this->element_type_prefix_cache[ $trid ] === true
				? $this->element_type_prefix_cache[ $trid ] : $element_type_prefix;
		}
	}

	private function get_el_id_from_db( $trid, $language_code ) {
		global $wpdb;

		return $wpdb->get_var(
			$wpdb->prepare( "	SELECT element_id
								FROM {$wpdb->prefix}icl_translations
								WHERE trid = %d
									AND language_code = %s",
							$trid,
							$language_code )
		);
	}

	private function maybe_init_trid_cache( $trid ) {
		foreach (
			array(
				&$this->job_id_cache,
				&$this->trid_cache,
				&$this->translation_status_cache,
				&$this->update_status_cache
			) as $cache
		) {
			$cache[ $trid ] = isset( $cache[ $trid ] ) ? $cache[ $trid ] : array();
		}
	}
}