<link href="<?php echo CLICKGUIDE_BASEURL; ?>/assets/css/presentation.css" rel="stylesheet" type="text/css" />


<div id="welcomeBox" class="presentationBox">
	<div class="closeBox"></div>
	<div class="welcomeMessage">
		<?php
			global $wpdb;
			$optionsTableName = CLICKGUIDE_NETWORK_OPTIONS_TABLE;
			$welcomeMessage = $wpdb->get_row( "SELECT * FROM $optionsTableName WHERE option_name = 'welcome_popup_message'" );

			if( $welcomeMessage->option_value != null ) {
				echo $welcomeMessage->option_value; 
			}
		?>
	</div>
	<div class="tours">
		<h3>Vorhandene Touren</h3>
		<?php 
			$option_table_name = CLICKGUIDE_INSTANCE_OPTIONS_TABLE;
			$tours_option_name = CLICKGUIDE_INSTANCE_OPTION_NAME;
			$cg_table_name = CLICKGUIDE_TABLE;

			$out = '<ul>';

			$chosenTours = $wpdb->get_row( "SELECT * FROM $option_table_name WHERE option_name = '$tours_option_name'" );

			if( $chosenTours != null ) {
				$chosenTours = explode(',', $chosenTours->option_value);

				$toursArr = array();
				foreach ($chosenTours as &$value) {
					$tour = $wpdb->get_row( "SELECT * FROM $cg_table_name WHERE cg_id = $value" );
					$toursArr[$tour->cg_id] = $tour->cg_order;
				}

				asort($toursArr);

				foreach($toursArr as $key => $value) {
					$tour = $wpdb->get_row( "SELECT * FROM $cg_table_name WHERE cg_id = $key" );

					require __DIR__ . '/printLowestWaypoint.php';
				}
			} else {
				$tours = $wpdb->get_results( "SELECT * FROM $cg_table_name WHERE cg_type = 0" );

				foreach( $tours as &$tour ) {
					require __DIR__ . '/printLowestWaypoint.php';
				}
			}

			$out .= '</ul>';

			echo $out;
		?>
	</div>
	<?php
		// current url without param 'cg'
		$doNotShowAgainLink = parse_url($_SERVER['REQUEST_URI']);
		parse_str( $doNotShowAgainLink['query'], $doNotShowAgainLinkQuery );
		unset( $doNotShowAgainLinkQuery['cg'] );
		$doNotShowAgainLink = $doNotShowAgainLink['path'] . ( !empty( $doNotShowAgainLinkQuery ) ? '?cg=dnsa&' : '?cg=dnsa' ) . http_build_query( $doNotShowAgainLinkQuery );
	?>
	<a href="<?php echo $doNotShowAgainLink; ?>" id="doNotShowAgain" class="bottomButton">Dieses Fenster nicht mehr anzeigen</a>
</div>