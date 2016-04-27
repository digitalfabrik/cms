<?php require_once dirname( dirname( __FILE__ ) ) . '/action/setOrder.php'; // form action ?>

<div class="listingTours">
	<?php 
		if( isset( $_GET['deleted'] ) ) {
			$success = '<div class="optionsPageSuccess">';
			$success .= '<span>Die Tour <i>' . $_GET['deleted'] . '</i> wurde gel&ouml;scht.</span>';
			$success .= '</div>';
			echo $success;
		} 
		if( isset( $_GET['order'] ) ) {
			$success = '<div class="optionsPageSuccess">';
			$success .= '<span>Die Reihenfolge der Touren wurde gespeichert.</span>';
			$success .= '</div>';
			echo $success;
		} 
	?>
	

	<?php 
		$table_name = CLICKGUIDE_TABLE;

		global $wpdb;
		$tours = $wpdb->get_results( "SELECT * FROM $table_name WHERE cg_type = 0 ORDER BY cg_order", OBJECT );
	?>
	<?php if( isset($tours) and !empty($tours) ): ?>
		<h3>Touren</h3>
		Der Klick Guide besteht aus <strong>Touren</strong>, denen einzelne <strong>Wegpunkte</strong> zugeordnet werden. Eine Tour deckt einen Funktionsbereich (z.B. <i>Seiten einrichten</i>) ab. Die einzelnen Schritt innerhalb eines Funktionsbereichs werden durch die Wegpunkte dargestellt. Ein Wegpunkt entspricht einem Overlay mit einer Anweisung (z.B. <i>Klicken Sie hier, um eine neue Seite zu erstellen</i>).

		<br /><br />

		<form action="?page=clickguide-optionspage&tab=tours" method="post">
			<div class="orderSubmit">
				<input class="button button-primary" type="submit" value="Reihenfolge speichern" />
			</div>
			<table cellspacing="0" class="listingToursTable">
				<tr>
					<thead>
						<tr>
							<th width="20%">Name</th>
							<th width="50%">Beschreibung</th>
							<th width="15%">Reihenfolge</th>
							<th width="15%">Funktion</th>
						</tr>
					</thead>
					<tbody>
						<?php foreach($tours as $clickGuideTour): ?>
							<tr>
								<td><?php echo $clickGuideTour->cg_name; ?></td>
								<td><?php echo $clickGuideTour->cg_desc; ?></td>
								<td class="order">
									<input class="orderField" name="cgid<?php echo $clickGuideTour->cg_id; ?>" type="text" value="<?php echo $clickGuideTour->cg_order; ?>" />								
								</td>
								<td>
									<a href="?page=clickguide-optionspage&tab=edittour&tour=<?php echo $clickGuideTour->cg_id; ?>">&raquo; bearbeiten</a><br /><br />
									<a href="?page=clickguide-optionspage&tab=deletetour&tour=<?php echo $clickGuideTour->cg_id; ?>">&raquo; l&ouml;schen</a> 
								</td>
							</tr>
						<?php endforeach; ?>
					</tbody>
				</tr>
			</table>
			<div class="orderSubmit">
				<input class="button button-primary" type="submit" value="Reihenfolge speichern" />
			</div>
		</form>
	<?php endif; ?>
</div>