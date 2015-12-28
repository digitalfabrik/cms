<?php

class WPML_TM_ICL_Translations extends WPML_WPDB_User {

	private $table = 'icl_translations';

	/** @var  WPML_TM_Records */
	private $tm_records;

	private $translation_id = 0;

	/**
	 * WPML_TM_ICL_Translations constructor.
	 *
	 * @param wpdb            $wpdb
	 * @param WPML_TM_Records $tm_records
	 * @param int|array       $id
	 * @param string          $type translation id, trid_lang or id_prefix for now
	 */
	public function __construct( &$wpdb, &$tm_records, $id, $type = 'translation_id' ) {
		parent::__construct( $wpdb );
		$this->tm_records = &$tm_records;
		if ( $id > 0 && $type === 'translation_id'
		) {
			$this->{$type} = $id;
		} elseif ( $type === 'id_type_prefix' && isset( $id['element_id'] ) && isset( $id['type_prefix'] ) ) {
			$this->translation_id = $this->wpdb->get_var(
				$this->wpdb->prepare( " SELECT translation_id
										FROM {$this->wpdb->prefix}{$this->table}
										WHERE element_id = %d
											AND element_type LIKE %s
										LIMIT 1",
					$id['element_id'], $id['type_prefix'] . '%' ) );
		} elseif ( $type === 'trid_lang' && isset( $id['trid'] ) && isset( $id['language_code'] ) ) {
			$this->translation_id = $this->wpdb->get_var(
				$this->wpdb->prepare( " SELECT translation_id
										FROM {$this->wpdb->prefix}{$this->table}
										WHERE trid = %d
											AND language_code = %s
										LIMIT 1",
					$id['trid'], $id['language_code'] ) );
		} else {
			throw new InvalidArgumentException( 'Unknown column: ' . $type . ' or invalid id: ' . serialize( $id ) );
		}
	}

	/**
	 * @return int
	 */
	public function trid() {

		return $this->wpdb->get_var(
			$this->wpdb->prepare( " SELECT trid
										FROM {$this->wpdb->prefix}{$this->table}
										WHERE translation_id = %d
										LIMIT 1", $this->translation_id ) );
	}

	/**
	 * @return int
	 */
	public function translation_id() {

		return $this->translation_id;
	}

	/**
	 * @return int
	 */
	public function element_id() {

		return $this->wpdb->get_var(
			$this->wpdb->prepare( " SELECT element_id
										FROM {$this->wpdb->prefix}{$this->table}
										WHERE translation_id = %d
										LIMIT 1", $this->translation_id ) );
	}

	/**
	 * @return int
	 */
	public function language_code() {

		return $this->wpdb->get_var(
			$this->wpdb->prepare( " SELECT language_code
										FROM {$this->wpdb->prefix}{$this->table}
										WHERE translation_id = %d
										LIMIT 1", $this->translation_id ) );
	}

	/**
	 * @return int
	 */
	public function source_language_code() {

		return $this->wpdb->get_var(
			$this->wpdb->prepare( " SELECT source_language_code
										FROM {$this->wpdb->prefix}{$this->table}
										WHERE translation_id = %d
										LIMIT 1", $this->translation_id ) );
	}

	/**
	 *
	 * @return $this
	 */
	public function delete() {
		$this->tm_records
			->icl_translation_status_by_translation_id( $this->translation_id )
			->delete();
		$this->wpdb->delete(
			$this->wpdb->prefix . $this->table, $this->get_args() );

		return $this;
	}

	private function get_args() {

		return array( 'translation_id' => $this->translation_id );
	}
}