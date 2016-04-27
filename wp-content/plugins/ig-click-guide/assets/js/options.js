jQuery(document).ready(function() {

	sortableWaypointOrder();

	addWaypoint();

	deleteWaypoint();

});

function sortableWaypointOrder() {
	jQuery('.waypoints-sortable').sortable({
		update: function( event, ui ) {
			jQuery(this).find('tr').each(function() {
				var trIndex = jQuery(this).index() + 1;
				jQuery(this).find('td:first-child span').html(trIndex);
				jQuery(this).find('td:first-child input.waypointOrder').val(trIndex);
			});
		}
	});
}

function addWaypoint() {
	jQuery('.addWaypointButton').click(function() {
		var waypointOrder = jQuery('.waypoints-sortable tr').length + 1;

		var waypointRow = '<tr class="newrow">';
		waypointRow += '<td>';
		waypointRow += '<span>' + waypointOrder + '</span>';
		waypointRow += '<input type="hidden" name="newWaypoint[wp' + waypointOrder + '][order]" value="' + waypointOrder + '" class="waypointOrder" />';
		waypointRow += '</td>';
		waypointRow += '<td>';
		waypointRow += '<input type="text" name="newWaypoint[wp' + waypointOrder + '][name]" value="" />';
		waypointRow += '</td>';
		waypointRow += '<td>';
		waypointRow += '<textarea id="waypointDesc" name="newWaypoint[wp' + waypointOrder + '][desc]"></textarea>';
		waypointRow += '</td>';
		waypointRow += '<td>';
		waypointRow += '<input type="text" name="newWaypoint[wp' + waypointOrder + '][site]" value="" />';
		waypointRow += '</td>';
		waypointRow += '<td>';
		waypointRow += '<input type="text" name="newWaypoint[wp' + waypointOrder + '][position]" value="" />';
		waypointRow += '</td>';
		waypointRow += '<td class="deleteWaypointTd"><span class="deleteWaypoint"></span></td>';
		waypointRow += '</tr>';

		jQuery('.waypoints-sortable').append(waypointRow);

		// TODO wp_editor

		// set value of hidden field newrow to yes
		jQuery('#checkNewRow').val('yes');
	});
}

function deleteWaypoint() {
	jQuery(document.body).on('click', '.deleteWaypoint' ,function() {
	// jQuery('span.deleteWaypoint').click(function() {
		var rowElem = jQuery(this).parent().parent();

		// set hidden field for deletion of database and add ids of deleted waypoints to it
		if( rowElem.hasClass('existingwp') ) {
			var idOfWp = rowElem.find('.existingwpID').val();

			jQuery('#ifDeletedRows').val('yes');

			if( jQuery('#deletedWaypoints').length == 0 ) {
				jQuery('.waypoints').prepend('<input id="deletedWaypoints" type="hidden" name="deletedWaypoints" />');
			}

			var currentDeletedWp = jQuery('#deletedWaypoints').val();
			if( currentDeletedWp != '' ) {
				var deletedWpIds = currentDeletedWp + ',' + idOfWp;
			} else {
				var deletedWpIds = idOfWp;
			}
			jQuery('#deletedWaypoints').val(deletedWpIds);
		}

		// remove row
		rowElem.remove();

		// set value of hidden field #checkNewRow to 'no' if there is no tr with class "newrow"
		if( jQuery('.newrow').length == 0 ) {
			jQuery('#checkNewRow').val('no');
		}

		// set new order for all rows
		jQuery('.waypoints-sortable').find('tr').each(function() {
			var trIndex = jQuery(this).index() + 1;
			jQuery(this).find('td:first-child span').html(trIndex);
			jQuery(this).find('td:first-child input.waypointOrder').val(trIndex);
		});
	});	
}