<?php
/**
 * WPML_Element_Translation Class
 *
 * @package wpml-core
 * @abstract
 *
 */

abstract class WPML_Element_Translation extends WPML_WPDB_User {
	/** @var string[] $element_langs */
	protected $element_langs = array();
	/** @var int[] $element_trids */
	protected $element_trids = array();
	/** @var string[] $element_source_langs */
	protected $element_source_langs = array();
	/** @var array[] $translations */
	protected $translations = array();
	/** @var array[] $trid_groups */
	protected $trid_groups = array();

	/**
	 * @param wpdb $wpdb
	 */
	public function __construct( &$wpdb ) {
		parent::__construct( $wpdb );
	}

	protected abstract function get_element_join();

	/**
	 * Clears the cached translations.
	 */
	public function reload() {
		$this->element_trids        = array();
		$this->element_langs        = array();
		$this->element_source_langs = array();
		$this->translations         = array();
		$this->trid_groups          = array();
	}

	public function get_element_trid( $element_id ) {

		return $this->maybe_populate_cache ( $element_id )
			? $this->element_trids[ $element_id ] : null;
	}

	/**
	 * @param int        $element_id
	 * @param string     $lang
	 * @param bool|false $original_fallback if true will return input $element_id if no translation is found
	 *
	 * @return null|int
	 */
	public function element_id_in( $element_id, $lang, $original_fallback = false ) {
		$result = ( $original_fallback ? (int) $element_id : null );
		if ( $this->maybe_populate_cache( $element_id ) && isset( $this->translations[ $element_id ][ $lang ] ) ) {
			$result = (int) $this->translations[ $element_id ][ $lang ];
		}

		return $result;
	}

	/**
	 * @param int  $element_id
	 * @param bool $root if true gets the root element of the trid which itself
	 * has no original. Otherwise returns the direct original of the given
	 * element_id.
	 *
	 * @return int|null null if the element has no original
	 */
	public function get_original_element( $element_id, $root = false ) {
		$element_id  = (int) $element_id;
		$source_lang = $this->maybe_populate_cache( $element_id )
			? $this->element_source_langs[ $element_id ] : null;
		$res         = $source_lang === null ? $element_id : null;
		$res         = $res === null && ! $root ? $this->translations[ $element_id ][ $source_lang ] : $res;
		if ( $res === null && $root ) {
			foreach ( $this->translations[ $element_id ] as $trans_id ) {
				if ( ! $this->element_source_langs[ $trans_id ] ) {
					$res = $trans_id;
					break;
				}
			}
		}
		$res = $res ? (int) $res : null;

		return $res !== $element_id ? $res : null;
	}

	public function get_element_id( $lang, $trid ) {
		$this->maybe_populate_cache ( false, $trid );

		return isset( $this->trid_groups [ $trid ][ $lang ] ) ? $this->trid_groups [ $trid ][ $lang ] : null;
	}

	/**
	 * @param $element_id
	 *
	 * @return null|string
	 */
	public function get_element_lang_code( $element_id ) {
		$result = null;

		if ( $this->maybe_populate_cache( $element_id ) ) {
			$result = $this->element_langs[ $element_id ];
		}

		return $result;
	}

	/**
	 * @param int $element_id
	 * @param string $output
	 *
	 * @return array|null|stdClass
	 */
	public function get_element_language_details( $element_id, $output = OBJECT ) {
		$result = null;
		if ( $element_id && $this->maybe_populate_cache( $element_id ) ) {
			$result                       = new stdClass();
			$result->element_id           = $element_id;
			$result->trid                 = $this->element_trids[ $element_id ];
			$result->language_code        = $this->element_langs[ $element_id ];
			$result->source_language_code = $this->element_source_langs[ $element_id ];
		}

		if ( $output == ARRAY_A ) {
			return $result ? get_object_vars( $result ) : null;
		} elseif ( $output == ARRAY_N ) {
			return $result ? array_values( get_object_vars( $result ) ) : null;
		} else {
			return $result;
		}
	}

	public function get_source_lang_code( $element_id ) {

		return $this->maybe_populate_cache ( $element_id )
			? $this->element_source_langs[ $element_id ] : null;
	}

	public function get_element_translations( $element_id, $trid = false, $actual_translations_only = false ) {
		$valid_element = $this->maybe_populate_cache ( $element_id, $trid );

		if ( $element_id ) {
			$res = $valid_element
				? ( $actual_translations_only
					? $this->filter_for_actual_trans ( $element_id ) : $this->translations[ $element_id ] ) : array();
		} elseif ( $trid ) {
			$res = isset( $this->trid_groups[ $trid ] ) ? $this->trid_groups[ $trid ] : array();
		}

		return isset( $res ) ? $res : array();
	}

	public function prefetch_ids( $element_ids ) {
		$element_ids = (array) $element_ids;
		$element_ids = array_diff( $element_ids, array_keys( $this->element_trids ) );
		if ( (bool) $element_ids === false ) {
			return;
		}

		$trid_snippet = " tridt.element_id IN (" . wpml_prepare_in( $element_ids, '%d' ) . ")";
		$sql          = $this->build_sql( $trid_snippet );
		$elements     = $this->wpdb->get_results( $sql, ARRAY_A );

		$this->group_and_populate_cache( $elements );
	}

	/**
	 * @param string $trid_snippet
	 *
	 * @return string
	 */
	private function build_sql( $trid_snippet ) {

		return "SELECT t.element_id, t.language_code, t.source_language_code, t.trid
				    " . $this->get_element_join() . "
				    JOIN {$this->wpdb->prefix}icl_translations tridt
				      ON tridt.element_type = t.element_type
				      AND tridt.trid = t.trid
				    WHERE {$trid_snippet}";
	}

	private function maybe_populate_cache( $element_id, $trid = false ) {
		if ( ! $element_id && ! $trid ) {
			return false;
		}
		if ( ! $element_id && isset( $this->trid_groups [ $trid ] ) ) {
			return true;
		}
		if ( ! $element_id || ! isset( $this->translations[ $element_id ] ) ) {
			if ( ! $element_id ) {
				$trid_snippet = $this->wpdb->prepare( " tridt.trid = %d ", $trid );
			} else {
				$trid_snippet = $this->wpdb->prepare( " tridt.trid = (SELECT trid " . $this->get_element_join() . " WHERE element_id = %d LIMIT 1)",
				                                      $element_id );
			}
			$sql      = $this->build_sql( $trid_snippet );
			$elements = $this->wpdb->get_results( $sql, ARRAY_A );
			$this->populate_cache( $elements );
			if ( $element_id && ! isset( $this->translations[ $element_id ] ) ) {
				$this->translations[ $element_id ] = array();
			}
		}

		return ! empty( $this->translations[ $element_id ] );
	}

	private function group_and_populate_cache( $elements ) {
		$trids = array();
		foreach($elements as $element){
			$trid = $element['trid'];
			if ( ! isset( $trids[$trid] ) ) {
				$trids[$trid] = array();
			}
			$trids[$trid][] = $element;
		}
		foreach($trids as $trid_group){
			$this->populate_cache($trid_group);
		}
	}

	private function populate_cache( $elements ) {
		$element_ids = array();
		foreach ( $elements as $element ) {
			$trans_id                                = $element[ 'element_id' ];
			$trans_lang_code                         = $element[ 'language_code' ];
			$element_ids[ $trans_lang_code ]         = $trans_id;
			$this->element_trids[ $trans_id ]        = $element[ 'trid' ];
			$this->element_langs[ $trans_id ]        = $trans_lang_code;
			$this->element_source_langs[ $trans_id ] = $element[ 'source_language_code' ];
		}
		foreach ( $element_ids as $translation_id ) {
			$trid                                  = $this->element_trids[ $translation_id ];
			$this->trid_groups[ $trid ]            = $element_ids;
			$this->translations[ $translation_id ] = &$this->trid_groups[ $trid ];
		}
	}

	private function filter_for_actual_trans( $element_id ) {
		$res = $this->translations[ $element_id ];
		foreach ( $res as $lang => $element ) {
			if ( $this->element_source_langs[ $element ] !== $this->element_langs[ $element_id ] ) {
				unset( $res[ $lang ] );
			}
		}

		return $res;
	}
}