<?php

class WPML_Set_Language extends WPML_Full_Translation_API {

	/**
	 * @param int           $el_id
	 * @param string        $el_type
	 * @param int|bool|null $trid Trid the element is to be assigned to. Input that is == false will cause the term to
	 *                            be assigned a new trid and potential translation relations to/from it to disappear.
	 * @param string        $language_code
	 * @param null|string   $src_language_code
	 * @param bool          $check_duplicates
	 *
	 * @return bool|int|null|string
	 */
	public function set_element_language_details(
		$el_id,
		$el_type = 'post_post',
		$trid,
		$language_code,
		$src_language_code = null,
		$check_duplicates = true
	) {
		$this->clear_cache();
		if ( $check_duplicates && $el_id && $this->check_duplicate( $el_type, $el_id ) === true ) {
			throw new InvalidArgumentException( 'element_id and type do not match' );
		}

		$src_language_code = $src_language_code === $language_code ? null : $src_language_code;

		if ( $trid ) { // it's a translation of an existing element
			$this->maybe_delete_orphan( $trid, $language_code, $el_id );

			if ( (bool) ( $translation_id = $this->is_language_change( $el_id, $el_type, $trid ) ) === true
			     && (bool) $this->trid_lang_trans_id( $trid, $language_code ) === false
			) {
				$this->wpdb->update(
					$this->wpdb->prefix . 'icl_translations',
					array( 'language_code' => $language_code ),
					array( 'translation_id' => $translation_id )
				);
			} elseif ( (bool) ( $translation_id = $this->is_source_element_change( $el_id, $el_type ) ) === true ) {
				$this->change_translation_of( $trid, $el_id, $el_type, $language_code, $src_language_code );
			} elseif ( (bool) ( $translation_id = $this->is_placeholder_update( $trid, $language_code ) ) === true ) {
				$this->wpdb->update(
					$this->wpdb->prefix . 'icl_translations',
					array( 'element_id' => $el_id ),
					array( 'translation_id' => $translation_id )
				);
			} elseif ( (bool) ( $translation_id = $this->trid_lang_trans_id( $trid, $language_code ) ) === false ) {
				$translation_id = $this->insert_new_row( $el_id, $trid, $el_type, $language_code, $src_language_code );
			}
		} else { // it's a new element or we are removing it from a trid
			$this->delete_existing_row( $el_type, $el_id );
			$translation_id = $this->insert_new_row( $el_id, false, $el_type, $language_code, $src_language_code );
		}

		$this->clear_cache();
		if ( $translation_id && substr( $el_type, 0, 4 ) === 'tax_' ) {
			$taxonomy = substr( $el_type, 4 );
			do_action( 'created_term_translation', $taxonomy, $el_id, $language_code );
		}
		do_action( 'icl_set_element_language', $translation_id, $el_id, $language_code, $trid );

		return $translation_id;
	}

	/**
	 * Runs various database repair and cleanup actions on icl_translations
	 *
	 * @return int Number of rows in icl_translations that were fixed
	 */
	public function repair_broken_assignments() {
		$rows_fixed = $this->fix_missing_original();
		$rows_fixed += $this->fix_wrong_source_language();
		$rows_fixed += $this->fix_broken_type_assignments();
		$this->clear_cache();
		wp_cache_init();

		return $rows_fixed;
	}

	/**
	 * Returns the translation id belonging to a specific trid, language_code combination
	 *
	 * @param int    $trid
	 * @param string $lang
	 *
	 * @return null|int
	 */
	private function trid_lang_trans_id( $trid, $lang ) {

		return $this->wpdb->get_var( $this->wpdb->prepare( "SELECT translation_id
															FROM {$this->wpdb->prefix}icl_translations
															WHERE trid = %d
																AND language_code = %s
															LIMIT 1",
		                                                   $trid,
		                                                   $lang ) );
	}

	/**
	 * Changes the source_language_code of an element
	 *
	 * @param int    $trid
	 * @param int    $el_id
	 * @param string $el_type
	 * @param string $language_code
	 * @param string $src_language_code
	 */
	private function change_translation_of( $trid, $el_id, $el_type, $language_code, $src_language_code ) {
		$src_language_code = empty( $src_language_code )
			? $this->sitepress->get_source_language_by_trid( $trid ) : $src_language_code;
		if ( $src_language_code !== $language_code ) {
			$this->wpdb->update(
				$this->wpdb->prefix . 'icl_translations',
				array(
					'trid'                 => $trid,
					'language_code'        => $language_code,
					'source_language_code' => $src_language_code
				),
				array( 'element_type' => $el_type, 'element_id' => $el_id )
			);
		}
	}

	/**
	 * @param string $el_type
	 * @param int    $el_id
	 */
	private function delete_existing_row( $el_type, $el_id ) {
		$this->wpdb->query(
			$this->wpdb->prepare(
				"DELETE FROM {$this->wpdb->prefix}icl_translations
					 WHERE element_type = %s
				      AND element_id = %d",
				$el_type,
				$el_id ) );
	}

	/**
	 * Inserts a new row into icl_translations
	 *
	 * @param int    $el_id
	 * @param int    $trid
	 * @param string $el_type
	 * @param string $language_code
	 * @param string $src_language_code
	 *
	 * @return int Translation ID of the new row
	 */
	private function insert_new_row( $el_id, $trid, $el_type, $language_code, $src_language_code ) {
		$new = array(
			'element_type'  => $el_type,
			'language_code' => $language_code,
		);

		if ( $trid === false ) {
			$trid = 1 + $this->wpdb->get_var( "SELECT MAX(trid) FROM {$this->wpdb->prefix}icl_translations" );
		} else {
			$src_language_code           = empty( $src_language_code )
				? $this->sitepress->get_source_language_by_trid( $trid ) : $src_language_code;
			$new['source_language_code'] = $src_language_code;
		}

		$new['trid'] = $trid;
		if ( $el_id ) {
			$new['element_id'] = $el_id;
		}
		$this->wpdb->insert( $this->wpdb->prefix . 'icl_translations', $new );
		$translation_id = $this->wpdb->insert_id;

		return $translation_id;
	}

	private function is_language_change( $el_id, $el_type, $trid ) {
		$translation_id = null;
		if ( ! is_null( $el_id ) ) {
			$translation_id = $this->wpdb->get_var(
				$this->wpdb->prepare(
					"SELECT translation_id
					 FROM {$this->wpdb->prefix}icl_translations
					 WHERE element_type = %s
					  AND element_id = %d
					  AND trid = %d",
					$el_type,
					$el_id,
					$trid
				)
			);
		}

		return $translation_id;
	}

	/**
	 * Checks if a given trid, language_code combination contains a placeholder with NULL element_id
	 * and if so returns the translation id of this row.
	 *
	 * @param int    $trid
	 * @param string $language_code
	 *
	 * @return null|string translation id
	 */
	private function is_placeholder_update( $trid, $language_code ) {

		return $this->wpdb->get_var(
			$this->wpdb->prepare( "	SELECT translation_id
									FROM {$this->wpdb->prefix}icl_translations
									WHERE trid=%d
										AND language_code = %s
										AND element_id IS NULL",
				$trid,
				$language_code
			) );
	}

	private function is_source_element_change( $el_id, $el_type ) {
		$translation_id = null;
		if ( ! is_null( $el_id ) ) {
			$translation_id = $this->wpdb->get_var(
				$this->wpdb->prepare( "SELECT translation_id
					                   FROM {$this->wpdb->prefix}icl_translations
					                   WHERE element_type= %s
					                    AND element_id= %d",
					$el_type,
					$el_id
				)
			);
		}

		return $translation_id;
	}

	/**
	 * Checks if a trid contains an existing translation other than a specific element id and deletes that row if it
	 * exists.
	 *
	 * @param int    $trid
	 * @param string $language_code
	 * @param int    $correct_element_id
	 */
	private function maybe_delete_orphan( $trid, $language_code, $correct_element_id ) {
		$translation_id = $this->wpdb->get_var(
			$this->wpdb->prepare(
				"SELECT translation_id
				 FROM {$this->wpdb->prefix}icl_translations
				 WHERE   trid = %d
					AND language_code = %s
					AND element_id <> %d
					AND source_language_code IS NOT NULL
					",
				$trid,
				$language_code,
				$correct_element_id
			)
		);

		if ( $translation_id ) {
			$this->wpdb->query(
				$this->wpdb->prepare(
					"DELETE FROM {$this->wpdb->prefix}icl_translations WHERE translation_id=%d",
					$translation_id
				)
			);
		}
	}

	/**
	 * Checks if a duplicate element_id already exists with a different than the input type.
	 * This only applies to posts and taxonomy terms.
	 *
	 * @param string $el_type
	 * @param int    $el_id
	 *
	 * @return bool
	 */
	private function check_duplicate( $el_type, $el_id ) {
		$res   = false;
		$exp   = explode( '_', $el_type );
		$_type = $exp[0];
		if ( in_array( $_type, array( 'post', 'tax' ) ) ) {
			$_el_exists = $this->wpdb->get_var(
				$this->wpdb->prepare(
					"SELECT translation_id
                        FROM {$this->wpdb->prefix}icl_translations
                        WHERE element_id = %d
                          AND element_type <> %s
                          AND element_type LIKE %s",
					$el_id,
					$el_type,
					$_type . '%'
				)
			);
			if ( $_el_exists ) {
				$res = true;
			}
		}

		return $res;
	}

	private function fix_broken_type_assignments() {

		return $this->wpdb->query( "UPDATE {$this->wpdb->prefix}icl_translations t
									JOIN {$this->wpdb->prefix}icl_translations c
										ON c.trid = t.trid
											AND c.language_code != t.language_code
									SET t.element_type = c.element_type
									WHERE c.source_language_code IS NULL
										AND t.source_language_code IS NOT NULL" );
	}

	private function fix_wrong_source_language() {

		return $this->wpdb->query( "UPDATE {$this->wpdb->prefix}icl_translations
									SET source_language_code = NULL
									WHERE source_language_code = ''
										OR source_language_code = language_code" );
	}

	private function fix_missing_original() {
		$broken_elements = $this->wpdb->get_results(
			"	SELECT MIN(iclt.element_id) AS element_id, iclt.trid
				FROM {$this->wpdb->prefix}icl_translations iclt
				LEFT JOIN {$this->wpdb->prefix}icl_translations iclo
					ON iclt.trid = iclo.trid
					AND iclo.source_language_code IS NULL
				WHERE iclo.translation_id IS NULL
				GROUP BY iclt.trid" );
		$rows_affected   = 0;
		foreach ( $broken_elements as $element ) {
			$rows_affected += $this->wpdb->query(
				$this->wpdb->prepare(
					"UPDATE {$this->wpdb->prefix}icl_translations
					 SET source_language_code = NULL
					 WHERE trid = %d AND element_id = %d",
					$element->trid,
					$element->element_id
				)
			);
		}

		return $rows_affected;
	}

	private function clear_cache() {
		$this->term_translations->reload();
		$this->post_translations->reload();
		$this->sitepress->icl_translations_cache->clear();
	}
}
