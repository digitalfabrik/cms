<?php

class WPML_TM_ICL_Translations extends WPML_TM_Record_User {

	private $table = 'icl_translations';

	private $fields = array();

	private $related = array();

	/** @var wpdb $wpdb */
	private $wpdb;

	private $translation_id = 0;

	/**
	 * WPML_TM_ICL_Translations constructor.
	 *
	 * @throws InvalidArgumentException if given data does not correspond to a
	 * record in icl_translations
	 *
	 * @param WPML_TM_Records $tm_records
	 * @param int|array       $id
	 * @param string          $type translation id, trid_lang or id_prefix for now
	 */
	public function __construct( &$tm_records, $id, $type = 'translation_id' ) {
		$this->wpdb = $tm_records->wpdb();
		parent::__construct( $tm_records );
		if ( $id > 0 && $type === 'translation_id'
		) {
			$this->{$type} = $id;
		} elseif ( $type === 'id_type_prefix' && isset( $id['element_id'] ) && isset( $id['type_prefix'] ) ) {
			$this->select_translation_id(
				" element_id = %d AND element_type LIKE %s ",
				array( $id['element_id'], $id['type_prefix'] . '%' ) );
		} elseif ( $type === 'trid_lang' && isset( $id['trid'] ) && isset( $id['language_code'] ) ) {
			$this->select_translation_id(
				" trid = %d AND language_code = %s ",
				array( $id['trid'], $id['language_code'] ) );
		} else {
			throw new InvalidArgumentException( 'Unknown column: ' . $type . ' or invalid id: ' . serialize( $id ) );
		}
	}

	/**
	 * @return WPML_TM_ICL_Translations[]
	 */
	public function translations() {
		if ( (bool) $this->related === false ) {
			$translation_ids = $this->wpdb->get_results(
				"SELECT translation_id, language_code
			 FROM {$this->wpdb->prefix}{$this->table}
			 WHERE trid = " . $this->trid() );
			foreach ( $translation_ids as $row ) {
				$this->related[ $row->language_code ] = $this->tm_records
					->icl_translations_by_translation_id( $row->translation_id );
			}
		}

		return $this->related;
	}

	/**
	 * @return null|int
	 */
	public function trid() {

		return $this->select_field( 'trid' );
	}

	/**
	 * @return int
	 */
	public function translation_id() {

		return $this->translation_id;
	}

	/**
	 * @return null|int
	 */
	public function element_id() {

		return $this->select_field( 'element_id' );
	}

	/**
	 * @return string|null
	 */
	public function language_code() {

		return $this->select_field( 'language_code' );
	}

	/**
	 * @return string|null
	 */
	public function source_language_code() {

		return $this->select_field( 'source_language_code' );
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

	private function select_field( $field ) {

		$this->fields[ $field ] = isset( $this->fields[ $field ] ) ? $this->fields[ $field ] : $this->wpdb->get_var(
			$this->wpdb->prepare( " SELECT {$field}
										FROM {$this->wpdb->prefix}{$this->table}
										WHERE translation_id = %d
										LIMIT 1", $this->translation_id ) );

		return $this->fields[ $field ];
	}

	private function get_args() {

		return array( 'translation_id' => $this->translation_id );
	}

	private function select_translation_id( $where, $prepare_args ) {
		$this->translation_id = $this->wpdb->get_var(
			"SELECT translation_id FROM {$this->wpdb->prefix}{$this->table}
			 WHERE" . $this->wpdb->prepare( $where, $prepare_args )
			. " LIMIT 1" );
		if ( ! $this->translation_id ) {
			throw new InvalidArgumentException( 'No translation entry found for query: ' . serialize( $where ) . serialize( $prepare_args ) );
		}
	}
}