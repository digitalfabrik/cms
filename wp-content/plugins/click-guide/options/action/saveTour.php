<?php 

	if( $_POST and !empty($_POST['tourName']) ) {

		// update tour (name and desc)
		$table_name = CLICKGUIDE_TABLE;
		$cgid = $_GET['tour'];

		global $wpdb;
		if( isset($_POST['tourDesc']) and !empty($_POST['tourDesc']) ) {
			$name = $_POST['tourName'];
			$desc = $_POST['tourDesc'];
			$wpdb->update( $table_name, array('cg_name' => $name, 'cg_desc' => $desc), array( 'cg_id' => $cgid ) );
		} else {
			$name = $_POST['tourName'];
			$wpdb->update( $table_name, array('cg_name' => $name), array( 'cg_id' => $cgid ) ); 
		}

		// success message
		$success = '<div class="optionsPageSuccess">';
		$success .= '<span>Die Tour wurde aktualisiert.</span>';
		$success .= '</div>';
	}

?>