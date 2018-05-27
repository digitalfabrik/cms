<?php

class OTGS_Installer_WP_Components_Hooks {

	const EVENT_HOOK = 'otgs_send_components_data';

	/**
	 * @var OTGS_Installer_WP_Components_Storage
	 */
	private $storage;

	/**
	 * @var OTGS_Installer_WP_Components_Sender
	 */
	private $sender;

	/**
	 * @var OTGS_Installer_WP_Share_Local_Components_Setting
	 */
	private $setting;

	public function __construct(
		OTGS_Installer_WP_Components_Storage $storage,
		OTGS_Installer_WP_Components_Sender $sender,
		OTGS_Installer_WP_Share_Local_Components_Setting $setting
	) {
		$this->storage = $storage;
		$this->sender  = $sender;
		$this->setting = $setting;
	}

	public function add_hooks() {
		add_action( self::EVENT_HOOK, array( $this, 'send_components_data' ) );
		add_action( 'init', array( $this, 'schedule_components_report' ) );
		add_filter( 'installer_fetch_subscription_data_request', array( $this, 'add_components_into_subscription_request' ) );
	}

	public function schedule_components_report() {
		if ( ! wp_next_scheduled( self::EVENT_HOOK ) ) {
			wp_schedule_single_event( strtotime( '+1 week' ), self::EVENT_HOOK );
		}
	}

	public function send_components_data() {
		if ( $this->storage->is_outdated() ) {
			$this->storage->refresh_cache();
			$this->sender->send( $this->storage->get() );
		}
	}

	/**
	 * @param array $args
	 *
	 * @return array
	 */
	public function add_components_into_subscription_request( $args ) {
		if ( isset( $args['body']['repository_id'] ) && $this->setting->is_repo_allowed( $args['body']['repository_id'] ) ) {
			$this->storage->refresh_cache();
			$args['body']['components'] = $this->storage->get();
			$args['body']['phpversion'] = phpversion();
		}

		return $args;
	}
}