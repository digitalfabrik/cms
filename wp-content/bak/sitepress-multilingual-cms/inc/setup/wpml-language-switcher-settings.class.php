<?php

class WPML_Setup_Language_Switcher_Settings {

	protected $ls_options;
	protected $ls_sidebars;

	function __construct($ls_sidebars, $ls_options) {
		$this->ls_sidebars = $ls_sidebars;
		$this->ls_options = $ls_options;
	}

	public function set_ls_sidebar( ) {
		$counter                  = $this->get_widget_index ();
		$language_switcher_prefix = 'icl_lang_sel_widget-';
		$active_widgets           = get_option ( 'sidebars_widgets' );
		foreach ( $this->ls_sidebars as $target_sidebar_id => $add_widget ) {
			$widget_exists = false;
			if ( isset( $active_widgets[ $target_sidebar_id ] ) ) {
				$active_sidebar_widgets = $active_widgets[ $target_sidebar_id ];
				$widget_exists          = $this->widget_exists( $language_switcher_prefix, $active_sidebar_widgets );
			}
			if ( $add_widget && !$widget_exists ) {
				$active_widgets = $this->add_to_sidebar ( $active_widgets,
				                                          $target_sidebar_id,
				                                          $language_switcher_prefix,
				                                          $counter );
				$counter = $this->update_widget_options ( $counter );
			} elseif ( !$add_widget && $widget_exists && isset($active_sidebar_widgets) ) {
				$active_widgets = $this->remove_widget ( $active_sidebar_widgets,
				                                         $language_switcher_prefix,
				                                         $active_widgets,
				                                         $target_sidebar_id );
			}
		}

		wp_set_sidebars_widgets ( $active_widgets );
	}

	public function set_ls_options() {

		$ls_opt_keys = array( 'icl_lso_link_empty', 'icl_lso_flags', 'icl_lso_native_lang', 'icl_lso_display_lang' );

		foreach ( $ls_opt_keys as $option_key ) {
			if ( !isset( $this->ls_options[ $option_key ] ) || false === (bool) $this->ls_options[ $option_key ] ) {
				$this->ls_options[ $option_key ] = 0;
			}

			icl_set_setting ( $option_key, $this->ls_options[ $option_key ], true );
		}
	}

	/**
	 * @return int
	 */
	private function get_widget_index(){
		$widget_icl_lang_sel_widget = get_option ( 'widget_icl_lang_sel_widget' );
		$counter                    = is_array ( $widget_icl_lang_sel_widget )
			? max ( array_keys ( $widget_icl_lang_sel_widget ) ) : 0;
		if ( !is_numeric ( $counter ) || $counter <= 0 ) {
			$counter = 1;
		}

		return $counter;
	}

	/**
	 * @param int $counter
	 * @return int
	 */
	private function update_widget_options( $counter ) {
		$language_switcher_content             = get_option ( 'widget_icl_lang_sel_widget' );
		$language_switcher_content[ $counter ] = array( 'title_show' => 0 );
		if ( !array_key_exists ( '_multiwidget', $language_switcher_content ) ) {
			$language_switcher_content[ '_multiwidget' ] = 1;
		}
		update_option ( 'widget_icl_lang_sel_widget', $language_switcher_content );

		return $counter + 1;
	}

	/**
	 * @param $prefix
	 * @param $active_sidebar_widgets
	 *
	 * @return bool
	 */
	private function widget_exists( $prefix, $active_sidebar_widgets ) {
		$widget_exists = false;
		if ( $active_sidebar_widgets ) {
			foreach ( $active_sidebar_widgets as $index => $active_sidebar_widget ) {
				if ( strpos( $active_sidebar_widget, $prefix ) !== false ) {
					$widget_exists = true;
					break;
				}
			}
		}

		return $widget_exists;
	}

	/**
	 * @param $active_sidebar_widgets
	 * @param $language_switcher_prefix
	 * @param $active_widgets
	 * @param $target_sidebar_id
	 * @return bool
	 */
	private function remove_widget( $active_sidebar_widgets, $language_switcher_prefix, $active_widgets, $target_sidebar_id ) {
		foreach ( $active_sidebar_widgets as $index => $active_sidebar_widget ) {
			if ( strpos ( $active_sidebar_widget, $language_switcher_prefix ) !== false ) {
				unset( $active_widgets[ $target_sidebar_id ][ $index ] );
			}
		}

		return $active_widgets;
	}

	/**
	 * @param $active_widgets
	 * @param $target_sidebar_id
	 * @param $language_switcher_prefix
	 * @param $counter
	 * @return array
	 */
	private function add_to_sidebar( $active_widgets, $target_sidebar_id, $language_switcher_prefix, $counter ) {
		if ( isset( $active_widgets[ $target_sidebar_id ] ) ) {
			$active_sidebar_widgets = $active_widgets[ $target_sidebar_id ];
			array_unshift ( $active_sidebar_widgets, $language_switcher_prefix . $counter );
		} else {
			$active_sidebar_widgets    = array();
			$active_sidebar_widgets[ ] = $language_switcher_prefix . $counter;
		}

		$active_widgets[ $target_sidebar_id ] = $active_sidebar_widgets;
		return $active_widgets;
	}
}