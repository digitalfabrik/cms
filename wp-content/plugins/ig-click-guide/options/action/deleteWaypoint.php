<?php

	if( $_POST ) {

		if( $_POST['ifDeletedRows'] == 'yes' ) {
			$tour = $_GET['tour'];
			$table_name = CLICKGUIDE_TABLE;
			
			$deletedWaypointsIds = explode(',',$_POST['deletedWaypoints']);

			// delete waypoints from database
			foreach($deletedWaypointsIds as &$deletedWaypointId) {
				$wpdb->delete( $table_name, array('cg_id' => $deletedWaypointId) );
			}

			// delete waypoints id from cg_waypoints of tour
			$currentWaypointsOfTour = $wpdb->get_row( "SELECT cg_waypoints FROM $table_name WHERE cg_id = $tour" );
			$currentWaypointsOfTour = explode(',',$currentWaypointsOfTour->cg_waypoints);
			
			foreach($currentWaypointsOfTour as $key => $wpid) {
				if( in_array($wpid, $deletedWaypointsIds) ) {
					unset($currentWaypointsOfTour[$key]);
				}
			}

			$newCgWaypoints = '';
			$wpsOfTourCount = count($currentWaypointsOfTour);
			$wpsOfTourCounter = 0;
			foreach ($currentWaypointsOfTour as &$wpid) {
				$wpsOfTourCounter++;
				if($wpsOfTourCounter != $wpsOfTourCount) {
					$newCgWaypoints .= $wpid . ',';
				} else {
					$newCgWaypoints .= $wpid;
				}
			}
			$wpdb->update( $table_name, array('cg_waypoints' => $newCgWaypoints), array('cg_id' => $tour));

			// set cg_waypoints of tour to NULL if empty
			$currentTourObj = $wpdb->get_row( "SELECT cg_waypoints FROM $table_name WHERE cg_id = $tour" );
			if( empty( $currentTourObj->cg_waypoints ) ) {
				$wpdb->query("UPDATE $table_name SET cg_waypoints = NULL WHERE cg_id = $tour");
			}
		}

	}

?>