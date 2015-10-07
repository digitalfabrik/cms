/**
*	PDF Creator Lite for WordPress
*	---
*/
function pdfCreateRequest( requestType, myContent, pdfOptions )	{
	
	jQuery('#PDF_downloadFeedback').empty().append('<div class="waitingDiv"></div>');
	
	var font 		= jQuery('#pcl_font').val(); 
	var fontcolor 	= jQuery('#pcl_fontcolor').val();
	var linkcolor 	= jQuery('#pcl_linkcolor').val();
	var bgcolor 	= jQuery('#pcl_bgcolor').val();
	
	var data = { 
		'action': 		'frontEndDownloadPDF', 
		'requestType':	requestType,
		'myContent':	myContent,
		'pdfOptions':	pdfOptions,
		'ajaxVars':		ssapdfAjax,
		'font':			font,
		'fontcolor':	fontcolor,
		'bgcolor':		bgcolor,
		'linkcolor':	linkcolor,
	};
	
	jQuery.ajax({
		type: 		"POST",
		data: 		data,
		url: 		ssapdfAjax.ajaxurl,
		success: 	function( response ) {
			jQuery("#PDF_downloadFeedback").html( response );
		}
	});
}

