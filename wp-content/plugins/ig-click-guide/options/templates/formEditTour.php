<?php 
	require_once dirname( dirname( __FILE__ ) ) . '/action/saveTour.php';
	require_once dirname( dirname( __FILE__ ) ) . '/action/saveWaypoints.php';
	require_once dirname( dirname( __FILE__ ) ) . '/action/deleteWaypoint.php';

	if( isset( $_GET[ 'tour' ] ) ) {
        $tour = $_GET[ 'tour' ];

        $table_name = CLICKGUIDE_TABLE;
		global $wpdb;
    	$tourObj = $wpdb->get_row( "SELECT * FROM $table_name WHERE cg_id = $tour" );
    } else {
        $tour = false;
    }
?>

<div class="edittour">
<?php if(!isset($tour) or !$tour): ?>
	<h3 class="failure">Es ist ein Fehler aufgetreten. Anscheinend wurde keine Tour ausgew&auml;hlt. Bitte kehren Sie zur &Uuml;bersicht der Touren zur&uuml;ck und versuchen Sie es erneut.</h3>
<?php else: ?>
	
	<h3>Tour <i><?php echo $tourObj->cg_name; ?></i> bearbeiten</h3>

	<?php 
		if( isset($success) ) {
			echo $success;
		}
		if( isset($failures) ) {
			echo $failures;
		}
	?>

	<form action="<?php echo $_SERVER['PHP_SELF']; ?>?page=clickguide-optionspage&tab=edittour&tour=<?php echo $tour; ?>" method="post">
		<table class="form-table">
			<tbody>
				<tr>
					<th scope="row">Name</th>
					<td>
						<input type="text" name="tourName" value="<?php echo $tourObj->cg_name; ?>" />
					</td>
				</tr>
				<tr>
					<th scope="row">Beschreibung</th>
					<td>
						<textarea name="tourDesc" placeholder="Diese Bechreibung dient nur internen Zewck und wird dem Nutzer nicht angezeigt."><?php if( !empty($tourObj->cg_desc) ) { echo $tourObj->cg_desc; } ?></textarea>
					</td>
				</tr>
			</tbody>
		</table>
		<p><input class="button button-primary" type="submit" value="Tour speichern" /></p>

		<h4 class="waypointsTitle">Wegpunkte</h4>
		<?php require_once __DIR__ . '/waypoints.php'; ?>
		
		<p><input class="button button-primary" type="submit" value="Tour speichern" /></p>
	</form>

<?php endif; ?>