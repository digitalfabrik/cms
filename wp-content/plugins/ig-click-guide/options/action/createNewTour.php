<?php 
	if( $_POST and isset( $_POST['createNewTour'] ) ) { // form submitted?
 		
		if( !empty( $_POST['tourName'] ) ) { // required name for tour given
			
			$tourName = $_POST['tourName'];

			if( !empty( $_POST['tourDesc'] ) ) {
				$tourDesc = $_POST['tourDesc'];
			} else {
				$tourDesc = null;
			}
				
			$newTour = new ClickGuideTour( $tourName, $tourDesc, 1 );

			$newTour->writeInDB();

			$success = '<div class="optionsPageSuccess">';
			$success .= '<span>Die Tour <i>' . $tourName . '</i> wurde angelegt.</span>';
			$success .= '</div>';
			echo $success;

		} else { // missing required name for tour
			$failures = '<div class="optionsPageFailure">';
			$failures .= '<span>Es muss ein Name für die Tour angegeben werden.</span>';
			$failures .= '</div>';
			echo $failures;
		}

	}
?>