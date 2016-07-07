<?php

class ICL_Language_Switcher extends WP_Widget {
	/**
	 * Sets up the widgets name etc
	 */
	public function __construct() {
		parent::__construct( 'icl_lang_sel_widget', // Base ID
		                     __( 'Language Selector', 'sitepress' ), // Name
		                     array( 'description' => __( 'Language Selector', 'sitepress' ), ) // Args
		);
	}

	/**
	 * Outputs the content of the widget
	 *
	 * @param array $args
	 * @param array $instance
	 */
	public function widget( $args, $instance ) {
					
		if ( function_exists( 'wpml_home_url_ls_hide_check' ) && wpml_home_url_ls_hide_check() ) {
			return;
		}

		language_selector_widget( $args );
	}

}