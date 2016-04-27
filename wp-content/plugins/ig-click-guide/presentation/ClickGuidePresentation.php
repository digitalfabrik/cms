<?php

	class ClickGuidePresentation {

		private $keyUsermetaWelcomeBox;
		public $namingClickGuide;

		public function __construct() {
			$this->keyUsermetaWelcomeBox = 'suppress_welcome_box';

			// set Variable with naming for click guide
			$this->namingClickGuide = 'Klick Guide';

			global $wpdb;
			$optionsTableName = CLICKGUIDE_NETWORK_OPTIONS_TABLE;
			$optionNamingClickGuide = $wpdb->get_row( "SELECT * FROM $optionsTableName WHERE option_name = 'clickguide_naming'" );

			if( $optionNamingClickGuide->option_value != null ) {
				$this->namingClickGuide = $optionNamingClickGuide->option_value;
			}

			// enque javascript
			add_action( 'admin_enqueue_scripts', array( $this, 'enque_clickguide_presentation_script' ) );

			// save user meta for hiding welcome box
			add_action( 'admin_init', array( $this, 'suppressWelcomeBox' ) );

			// add tab for click guide in wordpress help menu
			add_action( 'current_screen', array( $this, 'enableWelcomeBoxWPHelpMenu' ) );

			// save user meta for enabling welcome box
			add_action( 'admin_init', array( $this, 'enableWelcomeBox' ) );

			// show welcome box
			add_action( 'admin_init', array( $this, 'showWelcomeBox' ) );

			// display waypoint
			add_action( 'admin_init', array( $this, 'displayWaypoint' ) );
		}

		public function enque_clickguide_presentation_script() {
			wp_enqueue_script(
				'clickguide-presentation',
				CLICKGUIDE_BASEURL . '/assets/js/presentation.js',
				array( 'jquery' )
			);
		}
		
		// show welcome box on every backend page 
		public function showWelcomeBox() {
			// check if current user has suppressed the welcome box
			$usermetaWelcomeBox = get_user_meta( get_current_user_id(), $this->keyUsermetaWelcomeBox, true );

			if( $usermetaWelcomeBox == '' or $usermetaWelcomeBox == 'no' ) {
				if( !isset( $_GET['cgwp'] ) ) {
					require_once __DIR__ . '/welcomeBox.php';
				}
			}
		}

		// suppress welcome box for current user
		public function suppressWelcomeBox() {
			if( isset( $_GET['cg'] ) ) {
				if ( $_GET['cg'] == 'dnsa' ) {
					$user_ID = get_current_user_id();
					update_user_meta( $user_ID, $this->keyUsermetaWelcomeBox, 'yes' );
				}
			}
		}

		// enable welcome box via WP Help menu
		public function enableWelcomeBoxWPHelpMenu() {
			// $current_link = $_SERVER['REQUEST_URI'] . ( $_GET ? '&cg=sa' : '?cg=sa' );

			// current url without param 'cg'
			$current_link = parse_url($_SERVER['REQUEST_URI']);
			parse_str( $current_link['query'], $current_link_query );
			unset( $current_link_query['cg'] );
			$current_link = $current_link['path'] . ( !empty( $current_link_query ) ? '?cg=sa&' : '?cg=sa' ) . http_build_query( $current_link_query );

			$content = '<h3><a href="' . $current_link . '">&raquo; ' . $this->namingClickGuide . ' aufrufen</a></h3>';

			$screen = get_current_screen();
			$screen->add_help_tab(array(
				'id' => 'clickguide-help-tab',            //unique id for the tab
				'title' => $this->namingClickGuide,      //unique visible title for the tab
				'content' => $content
			));
		}

		// save user meta for enabling welcome box
		public function enableWelcomeBox() {
			if( isset( $_GET['cg'] ) ) {
				if ($_GET['cg'] == 'sa') {
					$user_ID = get_current_user_id();
					update_user_meta( $user_ID, $this->keyUsermetaWelcomeBox, 'no' );
				}
			}
		}

		// display waypoint that is adressed by param cgwp
		public function displayWaypoint() {
			// check if current user has suppressed the welcome box
			$usermetaWelcomeBox = get_user_meta( get_current_user_id(), $this->keyUsermetaWelcomeBox, true );

			if( $usermetaWelcomeBox == '' or $usermetaWelcomeBox == 'no' ) {
				if( isset( $_GET['cgwp'] ) ) {
					require_once __DIR__ . '/waypoint.php';
				}
			}
		}

		// get next waypoint
		function getNextWaypopint() {
			$currentWaypoint = $_GET['cgwp'];
			$waypointResults = $this->getAllConcerningWaypoints( $currentWaypoint );

			asort( $waypointResults );

			$getNext = false;
			$nextWaypoint = false;
			foreach( $waypointResults as $key => $value ) {
				if( $getNext ) {
					$nextWaypoint = $key;
					break;
				}
				if( $currentWaypoint == $key ) {
					$getNext = true;
				}
			}

			return $nextWaypoint;
		}

		// get previous waypoint
		function getPreviousWaypopint() {
			$currentWaypoint = $_GET['cgwp'];
			$waypointResults = $this->getAllConcerningWaypoints( $currentWaypoint );

			arsort( $waypointResults );

			$getPrevious = false;
			$previousWaypoint = false;
			foreach( $waypointResults as $key => $value ) {
				if( $getPrevious ) {
					$previousWaypoint = $key;
					break;
				}
				if( $currentWaypoint == $key ) {
					$getPrevious = true;
				}
			}

			return $previousWaypoint;
		}

		/* helper function
		 * get all waypoints of the tour of the current waypoint.
		 * @return waypoints as an array with cg_id as index and cg_order as value
		 * @param current waypoint
		 */
		function getAllConcerningWaypoints( $currentWaypoint ) {
			$cgTableName = CLICKGUIDE_TABLE;
			global $wpdb;
			$tours = $wpdb->get_results("SELECT cg_id, cg_waypoints FROM $cgTableName WHERE cg_type = 0");

			foreach($tours as &$tour) {
				$waypoints = explode( ',', $tour->cg_waypoints );
				if( in_array( $currentWaypoint, $waypoints ) ) {
					$tourOfCurrentWaypoint = $tour;
				}
			}

			$waypoints = explode( ',', $tourOfCurrentWaypoint->cg_waypoints );
			$waypointResults = array();
			foreach( $waypoints as &$waypoint ) {
				$waypointResult = $wpdb->get_row("SELECT cg_id, cg_order FROM $cgTableName WHERE cg_id = $waypoint");
				$waypointResults[$waypointResult->cg_id] = $waypointResult->cg_order;
			}

			return $waypointResults;
		}

		/* helper function
		 * @return full link for a waypoint
		 * @param id of waypoint
		 */
		function getLinkOfWaypoint( $waypointID ) {
			global $wpdb;
			$cgTableName = CLICKGUIDE_TABLE;

			$waypoint = $wpdb->get_row("SELECT cg_id, cg_site FROM $cgTableName WHERE cg_id = $waypointID");
			$waypointLink = $waypoint->cg_site;
			if( strpos( $waypoint->cg_site, '?' ) ) {
				$waypointLink .= '&cgwp=';
			} else {
				$waypointLink .= '?cgwp=';
			}
			$waypointLink .= $waypoint->cg_id;

			return $waypointLink;
		}
	}

?>