<?php

/**
 * @package wpml-core
 */
class WPML_UI_Screen_Options_Factory {

	/**
	 * @param string $option_name
	 * @param int    $default_per_page
	 *
	 * @return WPML_UI_Screen_Options_Pagination
	 */
	public function create_pagination( $option_name, $default_per_page ) {
		$pagination = new WPML_UI_Screen_Options_Pagination( $option_name,
			$default_per_page );
		$pagination->init_hooks();

		return $pagination;
	}
}
