<?php

class WPML_Color_Picker {
	private $color_selector_item;

	function __construct( $color_selector_item ) {
		$this->color_selector_item = $color_selector_item;
		add_action( 'admin_print_scripts', array( $this, 'admin_print_scripts' ) );
	}

	function admin_print_scripts() {
		wp_enqueue_script( 'wp-color-picker' );
		wp_register_style( 'wpml-color-picker', ICL_PLUGIN_URL . '/res/css/colorpicker.css', array('wp-color-picker'), ICL_SITEPRESS_VERSION);
		wp_enqueue_style ( 'wpml-color-picker' );
	}

	function current_language_color_selector_control() {
		echo $this->get_current_language_color_selector_control();
	}

	function get_current_language_color_selector_control() {
		$args          = $this->color_selector_item;
		$label         = isset( $args[ 'label' ] ) ? $args[ 'label' ] : '';
		$color_default = $args[ 'default' ];
		$color_value   = isset( $args[ 'value' ] ) ? $args[ 'value' ] : $color_default;
		$input_size    = isset( $args[ 'size' ] ) ? $args[ 'size' ] : 7;

		$input_name = $args[ 'input_name_group' ] . '[' . $args[ 'input_name_id' ] . ']';

		$input_id = str_replace( ']', '', str_replace( '[', '-', str_replace( '_', '-', $input_name ) ) );

		$result = '';
		if ( $label ) {
			$result .= '<label for="' . $input_id . '">' . $label . '</label><br />';
		} else {
			$result .= '<label for="' . $input_id . '" style="display: none;"></label>';
		}
		$result .= '<input class="wpml-colorpicker wp-color-picker-field" type="text"';
		$result .= 'size="' . $input_size . '"';
		$result .= 'id="' . $input_id . '"';
		$result .= 'name="' . $input_name . '"';
		$result .= 'value="' . $color_value . '"';
		$result .= 'data-default-color="' . $color_default . '"';
		$result .= '/>';

		return $result;
	}
}