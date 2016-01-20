<?php

// plugins option page
require_once __DIR__ .  '/options/ClickGuideOptionsPage.php';
require_once __DIR__ .  '/optionsInstance/ClickGuideOptionsPageInstance.php';
require_once __DIR__ .  '/presentation/ClickGuidePresentation.php';

class ClickGuide {

	private $clickGuideOptionsPage;
	private $ClickGuideOptionsPageInstance;
	private $ClickGuidePresentation;

	public function __construct() {
		$this->clickGuideOptionsPage = new ClickGuideOptionsPage();
		$this->ClickGuideOptionsPageInstance = new ClickGuideOptionsPageInstance();

		$this->showPresentation();
	}

	public function showPresentation() {
		if( !is_network_admin() ) {
			$option_table_name = CLICKGUIDE_INSTANCE_OPTIONS_TABLE;
			$tours_option_name = CLICKGUIDE_INSTANCE_OPTION_NAME;	

			$this->ClickGuideOptionsPageInstance = new ClickGuidePresentation();
		}
	}

	public function createDatabaseTable() {
		global $wpdb;
		$table_name = CLICKGUIDE_TABLE;

		if( $wpdb->get_var( "SHOW TABLES LIKE '{$table_name}'") != $table_name ) {

			// get default charset of wordpress database
			if (!empty ($wpdb->charset)) {
				$charset_collate = "DEFAULT CHARACTER SET {$wpdb->charset}";
			}

			// get collate of wordpress database
			if (!empty ($wpdb->collate)) {
				$charset_collate .= " COLLATE {$wpdb->collate}";
			}

			/* 
			 * create table
			 * cg_type (0 = tour, 1 = waypoint)
			 *
			 */
			$table_name = CLICKGUIDE_TABLE;
			$sql = "CREATE TABLE IF NOT EXISTS {$table_name} (
					cg_id bigint(20) NOT NULL AUTO_INCREMENT,
					cg_type tinyint(1) NOT NULL default 0, 
					cg_name VARCHAR(255) DEFAULT NULL,
					cg_desc longtext DEFAULT NULL,
					cg_order bigint(20) NOT NULL default 0,
					cg_waypoints longtext DEFAULT NULL,
					cg_site longtext DEFAULT NULL,
					cg_position longtext DEFAULT NULL,
					UNIQUE KEY cg_id (cg_id)
				) {$charset_collate};";

			require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
			dbDelta($sql);

		}

	}

}

?>