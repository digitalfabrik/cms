/**
*	PDF Creator Lite for WordPress
*	---
*/
function JSadminBuildPDF () {
	
	//get all form values before emptying div!
	var checkedPageIDs = getCheckedPagesArray( 'checkerPage' );
	var font = jQuery('#fontFamily').val(); 
	var fontColour = jQuery('#font_cpicker').val();
	var bgColour = jQuery('#bg_cpicker').val();
	var linkColour = jQuery('#link_cpicker').val();
	var useCSS = jQuery('input[name=useCSS]:checked').val();
	var addToC = jQuery('#addToC').is(':checked');
	var addFrontPage = jQuery('#addFrontPage').is(':checked');
	
	//empty div and show 'building' dialog
	jQuery('#interfaceWrap').empty().append('<br /><div class="waitingDiv"> Building...</div><span class="description">For large documents this may take a couple of minutes!</span>');
	
	var data = { 
		'action': 		'SSAPDFadminBuildPDF', 
		'useCSS':		useCSS,
		'fontFamily':	font,
		'font_cpicker':	fontColour,
		'bg_cpicker':	bgColour,
		'link_cpicker':	linkColour,
		'addToC':		addToC,
		'addFrontPage':	addFrontPage,
		'checkerPage': 	checkedPageIDs
	};
	
	jQuery.ajax({
		type:     "POST",
		data:     data,
		url:      ajaxurl,
		success:  function( response ) {
			jQuery("#interfaceWrap").html( response ); //the download/preview dialog
		}
	});
}


function getCheckedPagesArray ( selector ) {

	var checkedPages = [];
	
	jQuery('.' + selector).each( function (index) {
		if ( jQuery(this).is(':checked') ) {
			checkedPages.push( jQuery(this).val() );
		}
	});
	
	return checkedPages;
}

