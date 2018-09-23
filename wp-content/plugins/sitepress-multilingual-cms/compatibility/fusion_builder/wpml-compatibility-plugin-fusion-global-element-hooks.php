<?php

class WPML_Compatibility_Plugin_Fusion_Global_Element_Hooks implements IWPML_Action {

	const BEFORE_ADD_GLOBAL_ELEMENTS_PRIORITY = 5;
	const GLOBAL_SHORTCODE_START = '[fusion_global id="';

	/** @var IWPML_Current_Language $current_language */
	private $current_language;

	/** @var WPML_Translation_Element_Factory $element_factory */
	private $element_factory;

	public function __construct(
		IWPML_Current_Language $current_language,
		WPML_Translation_Element_Factory $element_factory
	) {
		$this->current_language = $current_language;
		$this->element_factory  = $element_factory;
	}

	public function add_hooks() {
		add_filter(
			'content_edit_pre',
			array( $this, 'translate_global_element_ids' ),
			self::BEFORE_ADD_GLOBAL_ELEMENTS_PRIORITY
		);
	}

	public function translate_global_element_ids( $content ) {
		$pattern = '/' . preg_quote( self::GLOBAL_SHORTCODE_START, '[' ) . '([\d]+)"\]/';
		return preg_replace_callback( $pattern, array( $this, 'replace_global_id' ), $content );
	}

	private function replace_global_id( array $matches ) {
		$global_id       = (int) $matches[1];
		$element         = $this->element_factory->create( $global_id, 'post' );
		$translation     = $element->get_translation( $this->current_language->get_current_language() );

		if ( $translation ) {
			$global_id = $translation->get_element_id();
		}

		return self::GLOBAL_SHORTCODE_START . $global_id . '"]';
	}
}
