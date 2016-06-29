<?php

abstract class WPML_Custom_Field_Setting extends WPML_TM_User {

	/** @var  string $index */
	private $index;

	/**
	 * WPML_Custom_Field_Setting constructor.
	 *
	 * @param TranslationManagement $tm_instance
	 * @param string                $index
	 */
	public function __construct( &$tm_instance, $index ) {
		parent::__construct( $tm_instance );
		$this->index = $index;
	}

	/**
	 * @return bool true if the custom field setting is given by a setting in
	 *              a wpml-config.xml
	 */
	public function is_read_only() {

		return in_array(
			$this->index,
			$this->tm_instance->settings[ $this->get_read_only_array_setting_index() ],
			true );
	}

	/**
	 * @return bool
	 */
	public function excluded() {

		return in_array( $this->index, $this->get_excluded_keys() ) || ( $this->is_read_only() && $this->status() === WPML_IGNORE_CUSTOM_FIELD );
	}

	public function status() {
		$state_index = $this->get_state_array_setting_index();
		if ( ! isset( $this->tm_instance->settings[ $state_index ][ $this->index ] ) ) {
			$this->tm_instance->settings[ $state_index ][ $this->index ] = WPML_IGNORE_CUSTOM_FIELD;
		}

		return (int) $this->tm_instance->settings[ $state_index ][ $this->index ];
	}

	public function make_read_only() {
		$ro_index                                   = $this->get_read_only_array_setting_index();
		$this->tm_instance->settings[ $ro_index ][] = $this->index;
		$this->tm_instance->settings[ $ro_index ]   = array_unique( $this->tm_instance->settings[ $ro_index ] );
	}

	public function set_to_copy() {
		$this->set_state( WPML_COPY_CUSTOM_FIELD );
	}

	public function set_to_translatable() {
		$this->set_state( WPML_TRANSLATE_CUSTOM_FIELD );
	}

	public function set_to_nothing() {
		$this->set_state( WPML_IGNORE_CUSTOM_FIELD );
	}
	
	public function set_editor_style( $style ) {
		$this->tm_instance->settings[ $this->get_editor_style_array_setting_index() ][ $this->index ] = $style;
	}
	
	public function get_editor_style() {
		$setting = $this->get_editor_style_array_setting_index();
		return isset( $this->tm_instance->settings[ $setting ][ $this->index ] ) ? $this->tm_instance->settings[ $setting ][ $this->index ] : '';
	}

	public function set_editor_label( $label ) {
		$this->tm_instance->settings[ $this->get_editor_label_array_setting_index() ][ $this->index ] = $label;
	}

	public function get_editor_label() {
		$setting = $this->get_editor_label_array_setting_index();
		return isset( $this->tm_instance->settings[ $setting ][ $this->index ] ) ? $this->tm_instance->settings[ $setting ][ $this->index ] : '';
	}

	public function set_editor_group( $group ) {
		$this->tm_instance->settings[ $this->get_editor_group_array_setting_index() ][ $this->index ] = $group;
	}

	public function get_editor_group() {
		$setting = $this->get_editor_group_array_setting_index();
		return isset( $this->tm_instance->settings[ $setting ][ $this->index ] ) ? $this->tm_instance->settings[ $setting ][ $this->index ] : '';
	}

	private function set_state( $state ) {
		$this->tm_instance->settings[ $this->get_state_array_setting_index() ][ $this->index ] = $state;
	}

	/**
	 * @return string
	 */
	protected abstract function get_state_array_setting_index();

	/**
	 * @return string
	 */
	protected abstract function get_read_only_array_setting_index();

	protected abstract function get_editor_style_array_setting_index();
	protected abstract function get_editor_label_array_setting_index();
	protected abstract function get_editor_group_array_setting_index();
	
	/**
	 * @return  string[]
	 */
	protected abstract function get_excluded_keys();
}