<?php 
	
	if($_POST) {

		if($_POST['chosenTours'] != null and $_POST['chosenTours'] != '') {
			$thisChosenTours = $_POST['chosenTours'];
		} else {
			$thisChosenTours = 'none';
		}

		$savedChosenTours = $wpdb->get_row( "SELECT * FROM $option_table_name WHERE option_name = '$tours_option_name'" );

		if( $savedChosenTours == null ) { // create option

			$wpdb->insert( $option_table_name, array( 'option_name' => $tours_option_name, 'option_value' => $thisChosenTours ) );
		
		} else { // update option

			$wpdb->update( $option_table_name, array( 'option_value' => $thisChosenTours ), array( 'option_name' => $tours_option_name ) );

		}

		// success message
		$success = '<div class="optionsPageSuccess">';
		$success .= '<span>Die Auswahl der Touren für diese Instanz wurde aktualisiert.</span>';
		$success .= '</div>';
		echo $success; 

	}

?>