<link href="<?php echo CLICKGUIDE_BASEURL; ?>/assets/css/options-instance.css" rel="stylesheet" type="text/css" />
<script src="<?php echo CLICKGUIDE_BASEURL; ?>/assets/js/options-instance.js" type="text/javascript"></script>

<?php 
	global $wpdb;
	$option_table_name = CLICKGUIDE_INSTANCE_OPTIONS_TABLE;
	$tours_option_name = CLICKGUIDE_INSTANCE_OPTION_NAME;	
?>

<?php require_once __DIR__ . '/saveChosenTours.php'; ?>

<?php 
	$table_name = CLICKGUIDE_TABLE;	
	$tours = $wpdb->get_results( "SELECT * FROM $table_name WHERE cg_type = 0 ORDER BY cg_order ASC" );

	$savedChosenTours = $wpdb->get_row( "SELECT * FROM $option_table_name WHERE option_name = '$tours_option_name'" );

	$savedChosenToursArr = array();
	if($savedChosenTours != null and $savedChosenTours->option_value != null and $savedChosenTours->option_value != '') { 
		$savedChosenTours = explode(',',$savedChosenTours->option_value);
		foreach($savedChosenTours as &$value) {
			$savedChosenToursArr[] = $value;
		}
	}
?>

<div class="chooseTours">
	<table width="100%">
	<?php
		foreach($tours as &$tour) {
			echo '<tr>';
			echo '<td width="25px">';
			if($savedChosenToursArr != null) {
				if(in_array($tour->cg_id, $savedChosenToursArr)) {
					echo '<input type="checkbox" class="checkboxTour" name="tours" value="' . $tour->cg_id . '" checked />';
				} else {
					echo '<input type="checkbox" class="checkboxTour" name="tours" value="' . $tour->cg_id . '" />';
				}
			} else {
				echo '<input type="checkbox" class="checkboxTour" name="tours" value="' . $tour->cg_id . '" checked />';
			}			
			echo '</td>';
			echo '<td>';
			echo $tour->cg_name;
			echo '</td>';
			echo '</tr>';
		}
	?>
	</table>

	<form method="post" action="<?php echo "http://$_SERVER[HTTP_HOST]$_SERVER[REQUEST_URI]"; ?>">
		<input type="hidden" name="chosenTours" value="" id="chosenTours" />
		<?php submit_button(); ?>
	</form>
</div>