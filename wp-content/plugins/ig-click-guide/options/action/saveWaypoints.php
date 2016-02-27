<?php

	if( $_POST ) {

		$failures = '';
		$table_name = CLICKGUIDE_TABLE;

		// save new waypoints
		if( $_POST['newRow'] == 'yes' ) {
			$newWaypoints = $_POST['newWaypoint'];
			$tour = $_GET['tour'];

			// write waypoint to db
			foreach($newWaypoints as &$newWaypoint) {
				$name = $newWaypoint['name'];
				$desc = $newWaypoint['desc'];
				$order = $newWaypoint['order'];
				$site = $newWaypoint['site'];
				$position = $newWaypoint['position'];

				if( !empty($name) and !empty($position) and !empty($site) ) { 	

					$newWaypoint = new ClickGuideWaypoint( $name, $desc, $order, $site, $position );
					$idOfNewRow = $newWaypoint->writeInDB();

					$currentTourWaypoints = $wpdb->get_row( "SELECT cg_waypoints FROM $table_name WHERE cg_id = $tour" );
					if( !empty($currentTourWaypoints->cg_waypoints) ) {
						$setWithNewWaypoints = $currentTourWaypoints->cg_waypoints . ',' . $idOfNewRow;
					} else {
						$setWithNewWaypoints = $idOfNewRow;
					}					
					$wpdb->update( $table_name, array('cg_waypoints' => $setWithNewWaypoints), array('cg_id' => $tour) ); 

				} else {

					$failures .= '<div class="optionsPageFailure">';
					$missingFields = array();
					if( empty($name) ) { $missingFields[] = 'ein <i>Name</i>'; }
					if( empty($site) ) { $missingFields[] = 'eine <i>Seite</i>'; }
					if( empty($position) ) { $missingFields[] = 'eine <i>Position</i>'; }
					$failures .= '<span>Fehlermeldung für den neuen Wegpunkt <i>' . (empty($name) ? 'kein Name' : $name) . '</i>: Es muss ';
					$missingFieldCount = count($missingFields);
					$missingFieldCounter = 0;
					foreach($missingFields as &$missingField) {
						$missingFieldCounter++;
						if($missingFieldCounter == $missingFieldCount and $missingFieldCount > 1) { $failures .= ' und '; }
						$failures .= $missingField;
						if($missingFieldCounter != $missingFieldCount and $missingFieldCounter != $missingFieldCount - 1) { $failures .= ', '; }
					}
					$failures .= ' angegeben werden. Die Wegpunkte mit den fehlenden Daten konnten nicht in die Datenbank eingetragen werden und m&uuml;ssen neu angelegt werden.</span>';
					$failures .= '</div>';

				}				
			}

		}

		// save existing waypoints
		if( isset($_POST['waypoint']) ) {
			$exisitingWaypoints = $_POST['waypoint'];

			foreach($exisitingWaypoints as &$exisitingWaypoint) {
				$id = $exisitingWaypoint['id'];
				$order = $exisitingWaypoint['order'];
				$name = $exisitingWaypoint['name'];
				$desc = $exisitingWaypoint['desc'];
				$site = $exisitingWaypoint['site'];
				$position = $exisitingWaypoint['position'];

				if( !empty($name) and !empty($position) and !empty($site) ) { 

					$data = array(
							'cg_order' => $order,
							'cg_name' => $name,
							'cg_desc' => $desc,
							'cg_site' => $site,
							'cg_position' => $position
						);
					$wpdb->update( $table_name, $data, array('cg_id' => $id) ); 

				} else {

					$failures .= '<div class="optionsPageFailure">';
					$missingFields = array();
					if( empty($name) ) { $missingFields[] = 'ein <i>Name</i>'; }
					if( empty($site) ) { $missingFields[] = 'eine <i>Seite</i>'; }
					if( empty($position) ) { $missingFields[] = 'eine <i>Position</i>'; }
					$failures .= '<span>Fehlermeldung für bestehenden Wegpunkt <i>' . (empty($name) ? 'kein Name' : $name) . '</i>: Es muss ';
					$missingFieldCount = count($missingFields);
					$missingFieldCounter = 0;
					foreach($missingFields as &$missingField) {
						$missingFieldCounter++;
						if($missingFieldCounter == $missingFieldCount and $missingFieldCount > 1) { $failures .= ' und '; }
						$failures .= $missingField;
						if($missingFieldCounter != $missingFieldCount and $missingFieldCounter != $missingFieldCount - 1) { $failures .= ', '; }
					}
					$failures .= ' angegeben werden. Die Wegpunkte mit den fehlenden Daten konnten nicht in die Datenbank eingetragen werden und m&uuml;ssen neu angelegt werden.</span>';
					$failures .= '</div>';

				}
			}
		}


	}

?>