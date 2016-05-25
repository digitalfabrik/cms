addLoadEvent(function() {
	maybe_hide_some_quickedit('rs_hide_quickedit_ids');
});

function maybe_hide_some_quickedit(div_id) {
	var div_content = document.getElementById(div_id);
	
	if ( div_content ) {
		var hide_ids = div_content.innerHTML.split(",");

		for ( var i in hide_ids ) {
			jQuery(document).ready( function($) {
				$( '#page-' + hide_ids[i] + ' span.inline' ).hide();
			});
		}
	} 
}