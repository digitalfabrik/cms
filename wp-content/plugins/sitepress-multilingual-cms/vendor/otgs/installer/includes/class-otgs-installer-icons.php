<?php

class OTGS_Installer_Icons {

	/**
	 * @var WP_Installer
	 */
	private $installer;

	/**
	 * @var array
	 */
	private $products_map;

	public function __construct( WP_Installer $installer ) {
		$this->installer = $installer;
	}

	public function add_hooks() {
		add_filter( 'otgs_installer_upgrade_check_response', array( $this, 'add_icons_on_response' ), 10, 3 );
	}

	/**
	 * @param stdClass $response
	 * @param string $plugin_id
	 *
	 * @return stdClass
	 */
	public function add_icons_on_response( $response, $plugin_id, $repository ) {
		$product = isset( $this->installer->settings['repositories'][ $repository ]['data']['products-map'][ $plugin_id ] )
			? $this->installer->settings['repositories'][ $repository ]['data']['products-map'][ $plugin_id ]
			: '';

		if ( $product ) {
			$base            = $this->installer->plugin_url() . '/vendor/otgs/icons/plugin-icons/' . $repository . '/' . $product . '/icon';
			$response->icons = array(
				'svg' => $base . '.svg',
				'1x'  => $base . '.png',
				'2x'  => $base . '.png',
			);
		}

		return $response;
	}
}