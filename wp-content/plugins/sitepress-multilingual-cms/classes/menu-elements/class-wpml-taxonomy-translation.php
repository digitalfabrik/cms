<?php
class WPML_Taxonomy_Translation {

	private $taxonomy = '';
	private $tax_selector = true;
	private $taxonomy_obj = false;

	/**
	 * WPML_Taxonomy_Translation constructor.
	 *
	 * @param string $taxonomy if given renders a specific taxonomy,
	 *                         otherwise renders a placeholder
	 * @param bool[] $args array with possible indices:
	 *                     'taxonomy_selector' => bool .. whether or not to show the taxonomy selector
	 */
	public function __construct( $taxonomy = '', $args = array() ) {
		$this->tax_selector = isset( $args['taxonomy_selector'] ) ? $args['taxonomy_selector'] : true;
		$this->taxonomy     = $taxonomy ? $taxonomy : false;
		if ( $taxonomy ) {
			$this->taxonomy_obj = get_taxonomy( $taxonomy );
		}
	}

	/**
	 * Echos the HTML that serves as an entry point for the taxonomy translation
	 * screen and enqueues necessary js.
	 */
	public function render() {
		WPML_Taxonomy_Translation_Table_Display::enqueue_taxonomy_table_js();
		$output = '<div class="wrap">';
		if ( $this->taxonomy ) {
			$output .= '<input type="hidden" id="tax-preselected" value="' . $this->taxonomy . '">';
		}
		if ( ! $this->tax_selector ) {
			$output .= '<input type="hidden" id="tax-selector-hidden" value="1"/>';
		}
		if ( $this->tax_selector ) {
			$output .= '<h1>' . __( 'Taxonomy Translation',
					'sitepress' ) . '</h1>';
			$output .= '<br/>';
		}
		$output .= '<div id="wpml_tt_taxonomy_translation_wrap">';
		$output .= '<div class="loading-content"><span class="spinner" style="visibility: visible"></span></div>';
		$output .= '</div>';
		do_action( 'icl_menu_footer' );
		$output .= apply_filters( 'wpml_taxonomy_translation_bottom',
			$html = '', $this->taxonomy, $this->taxonomy_obj );
		echo $output . '</div>';
	}
}
