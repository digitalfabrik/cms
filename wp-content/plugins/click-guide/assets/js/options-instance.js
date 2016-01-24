jQuery(document).ready(function() {

	loadTours();

	changeTours();

});

function loadTours() {
	jQuery('.chooseTours table tr').each(function() {
		var checkbox = jQuery(this).find('.checkboxTour');
		if( checkbox.is(':checked') ) {
			var thisTourID = checkbox.val();
			
			var currentChosenTours = jQuery('#chosenTours').val();
			if( currentChosenTours != '' ) {
				var newChosenTours = currentChosenTours + ',' + thisTourID;
			} else {
				var newChosenTours = thisTourID;
			}

			jQuery('#chosenTours').val(newChosenTours);
		}
	});
}

function changeTours() {
	jQuery('.checkboxTour').click(function() {
		jQuery('#chosenTours').val(''); 
		loadTours();
	});
}