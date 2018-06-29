<?php

class OTGS_Installer_WP_Components_Setting_Templates {

	const COMMERCIAL_TAB_TEMPLATE = 'commercial-tab';
	const NOTICE_TEMPLATE = 'notice';

	/**
	 * @var IOTGS_Installer_Template_Service
	 */
	private $template_service;

	public function __construct( IOTGS_Installer_Template_Service $template_service ) {
		$this->template_service = $template_service;
	}

	/**
	 * @param string $site_url
	 * @param string $site_name
	 */
	public function render_commercial( $site_url, $site_name, $repo_id ) {
		echo $this->template_service->show( $this->get_commercial_tab_model( $site_url, $site_name, $repo_id ), self::COMMERCIAL_TAB_TEMPLATE );
	}

	/**
	 * @param array $sites
	 */
	public function render_notice( $sites ) {
		echo $this->template_service->show( $this->get_notice_model( $sites ), self::NOTICE_TEMPLATE );
	}

	/**
	 * @param string $site_url
	 * @param string $site_name
	 * @param string $repo_id
	 *
	 * @return array
	 */
	private function get_commercial_tab_model( $site_url, $site_name, $repo_id ) {
		return array(
			'strings' => array(
				'message' => sprintf( __( 'Keep %s up-to-date about which theme and plugins I use', 'installer' ), $site_url ),
				'stop_sending' => sprintf(
					__( 'If you ever want to stop sending the information about active plugins and theme to %1$s, please visit the Plugins admin screen. You will see a checkbox to control this in %2$s plugin section.', 'installer' ),
					$site_url,
					$site_name
				),
				'tooltip' => sprintf(
					__( 'Almost always, %s support team can help you resolve issues faster and better when we know which theme and plugin you use. When this option is selected, we will include this information in your %s account profile (which only you and %s support can access) and update it as needed.', 'installer' ),
					$site_name,
					$site_url,
					$site_name
				),
			),
			'repo_id' => $repo_id,
		);
	}

	/**
	 * @param array $site_names
	 *
	 * @return array
	 */
	private function get_notice_model( array $site_names ) {
		$model = array(
			'strings' => array(
				'title'   => sprintf( __( 'Want faster support for %s?', 'installer' ), implode( $site_names, '' ) ),
				'message' => sprintf( __( '%s plugin can report to your wpml.org account which theme and plugins you are using. This information allows us to give you more accurate and faster support.', 'installer' ), implode( $site_names, '' ) ),
			),
		);

		if ( 2 === count( $site_names ) ) {
			$model['strings']['title']   = __( 'Want faster support for WPML and Toolset?', 'installer' );
			$model['strings']['message'] = __( 'WPML and Toolset plugins can report to your wpml.org and wp-types.com accounts which theme and plugins you are using. This information allows us to give you more accurate and faster support.', 'installer' );
		}

		$model['strings']['agree']        = __( "I'm in", 'installer' );
		$model['strings']['disagree']     = __( 'No thanks', 'installer' );
		$model['strings']['tell_me_more'] = __( 'Tell me more', 'installer' );

		return $model;
	}
}