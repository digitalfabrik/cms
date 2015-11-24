<?php
interface WPML_Translatable_Element_Interface {
	public function get_words_count();

	public function get_translations();

	public function get_type_name( $label = null );
}