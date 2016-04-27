<div class="waypoints">
	<?php 
		$table_name = CLICKGUIDE_TABLE;
		$tour = $_GET['tour'];
		$existingWaypoints = $wpdb->get_row( "SELECT cg_waypoints FROM $table_name WHERE cg_id = $tour" );
		if( isset($existingWaypoints->cg_waypoints) and !empty($existingWaypoints->cg_waypoints) ) {
			$idsConcerningWaypoints = explode(',',$existingWaypoints->cg_waypoints);
		}
	?>
	<input id="checkNewRow" type="hidden" name="newRow" value="no" />
	<input id="ifDeletedRows" type="hidden" name="ifDeletedRows" value="no" />
	<table width="100%" cellspacing="0">
		<thead>
			<th width="30px"></th>
			<th width="20%">Name</th>
			<th>Beschreibung</th>
			<th width="18%">
				Seite<br />
				<small>Alles nach dem letzten Slash. Für <i>Seiten</i> z.B. <code>admin.php?page=cms-tpv-page-page</code></small>
			</th>
			<th width="18%">
				Position<br />
				<small>Mit <code>.</code> für eine Klasse oder <code>#</code> für eine ID. Selbige können durch Inspektion über <code>F12</code> im Brwoser festgestellt werden.</small>
			</th>
			<th width="20px"></th>
		</thead>
		<tbody class="waypoints-sortable">
			<?php if( isset($existingWaypoints->cg_waypoints) and !empty($existingWaypoints->cg_waypoints) ): ?>
				<?php 
					// sort array
					$orderWaypoints = array();
					foreach($idsConcerningWaypoints as &$idWaypoint) {
						// id as value
						// order as key
						$orderwp = $wpdb->get_row( "SELECT cg_order FROM $table_name WHERE cg_id = $idWaypoint" );
						$orderWaypoints[$idWaypoint] = $orderwp->cg_order;
					}
					asort($orderWaypoints);
				?>
				<?php foreach($orderWaypoints as $key => $value): ?>
					<?php $waypoint = $wpdb->get_row( "SELECT * FROM $table_name WHERE cg_id = $key" ); ?>
					<tr class="existingwp">
						<td>
							<input type="hidden" class="existingwpID" name="waypoint[wp<?php echo (!empty($waypoint->cg_id) ? $waypoint->cg_id : ''); ?>][id]" value="<?php echo (!empty($waypoint->cg_id) ? $waypoint->cg_id : ''); ?>" />
							<span><?php echo (!empty($waypoint->cg_order) ? $waypoint->cg_order : ''); ?></span>
							<input type="hidden" name="waypoint[wp<?php echo (!empty($waypoint->cg_id) ? $waypoint->cg_id : ''); ?>][order]" value="<?php echo (!empty($waypoint->cg_order) ? $waypoint->cg_order : ''); ?>" class="waypointOrder" />
						</td>
						<td>
							<input type="text" name="waypoint[wp<?php echo (!empty($waypoint->cg_id) ? $waypoint->cg_id : ''); ?>][name]" value="<?php echo (!empty($waypoint->cg_name) ? $waypoint->cg_name : ''); ?>" />
						</td>
						<td>
							<?php 
								$textareaID = 'existingWaypoint' . (!empty($waypoint->cg_id) ? $waypoint->cg_id : '') . 'Desc';
								$textareaName = 'waypoint[wp' . (!empty($waypoint->cg_id) ? $waypoint->cg_id : '') . '][desc]';
								$textareaContent = (!empty($waypoint->cg_desc) ? $waypoint->cg_desc : '');
								echo wp_editor( $textareaContent, $textareaID, array( 'textarea_name' => $textareaName, 'editor_height' => 150, 'wpautop' => false ) );
							?>
						</td>
						<td>
							<input type="text" name="waypoint[wp<?php echo (!empty($waypoint->cg_id) ? $waypoint->cg_id : ''); ?>][site]" value="<?php echo (!empty($waypoint->cg_site) ? $waypoint->cg_site : ''); ?>" />
						</td>
						<td>
							<input type="text" name="waypoint[wp<?php echo (!empty($waypoint->cg_id) ? $waypoint->cg_id : ''); ?>][position]" value="<?php echo (!empty($waypoint->cg_position) ? $waypoint->cg_position : ''); ?>" />
						</td>
						<td class="deleteWaypointTd"><span class="deleteWaypoint"></span></td>
					</tr>
				<?php endforeach; ?>
			<?php endif; ?>
		</tbody>
	</table>
	<div class="addWaypointRowWrap">
		<span class="addWaypointButton button button-primary">Neuen Wegpunkt hinzuf&uuml;gen</span>
	</div>
</div>