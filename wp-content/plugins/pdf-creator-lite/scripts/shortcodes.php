<?php

/**
*	Handler for shortcode [pdf-lite].
*/
function drawFrontEndPDF_download( $atts, $passedcontent = null )
{	
	extract( shortcode_atts( 
		array(
			'allpages'  	=> '',
			'allposts'  	=> '',			
			'cat'  			=> '',
			'page'   		=> '',
			'customposts'	=> '',
			'icon'			=> '1',
			'linktext'   	=> 'Download PDF',
			'toc'  			=> '',
			'titlepage'  	=> '',
			'filename'		=> '',
			'iconsize'		=> '',
			'font'			=> '',
			'fontcolor'		=> '',
			'fontcolour'	=> '',
			'linkcolor'		=> '',
			'linkcolour'	=> '',
			'bgcolor'		=> '',
			'bgcolour'		=> '',
		), 
		$atts
	));
	
	//alias params
	$fontcolor 	= ( '' !== $fontcolour ) 	? $fontcolour : $fontcolor;
	$linkcolor 	= ( '' !== $linkcolour ) 	? $linkcolour : $linkcolor;
	$bgcolor 	= ( '' !== $bgcolour ) 		? $bgcolour : $bgcolor;
	
	$requestType = '';
	$content = '';
	
	
	// Get the contents of the image dir
	$pdfIconDir = SSAPDF_PLUGIN_URL.'/images/pdf_icons/';	
	$pdfIconArray = getPDFIconArray();
	$iconCount = count($pdfIconArray); // Get a count of all the available download icons
	if($icon>$iconCount || !is_numeric($icon)){$icon=1;} // If the number is great than the ttoal, make it 1 - or if its not numeric
	
	if( $cat ){ 
		$requestType = "category"; 
		$content = $cat;
		$filenameAppend = $cat;
	}
	if( $page ){ 
		$requestType = "page"; 
		$content = $page;
		$filenameAppend = $page;
	}
	if( $customposts ){
		$requestType="customPost"; 
		$content = $customposts;
		$filenameAppend = $customposts;
	}
	if( $allpages==true ){
		$requestType="allpages"; 
		$content = "";
		$filenameAppend = '';
	}
	if( $allposts==true ){
		$requestType="allposts"; 
		$content = "";
		$filenameAppend = '';
	}	
		
	if($requestType=="") // nothing exists so just download current page / post
	{
		//	Get the Page ID
		$content = get_the_ID();
		$requestType = "page";
		$filenameAppend = get_the_title( $content );
	}
	
	if ( $filename === '' )
	{
		$filename = $requestType . '_' . $filenameAppend;
	}
	else
	{
		if ( strpos( $filename, '.pdf' ) == false )
		{
			$filename = $filename.'.pdf';
		}	
	}
	
	$pdfOptions = 	$toc . ',' . $titlepage . ',' . $filename . ',' . get_the_ID();
	
	
	$htmlString = '';
	$htmlString .= '<div id="pdfLiteDownload"><a href="javascript:pdfCreateRequest(\''.$requestType.'\', \''.$content.'\', \''.$pdfOptions.'\');">';
	
	if($icon<>"false")
	{
		$icon = sprintf("%02d", $icon);	
		$myIconSize = 64; // Default dimensions		
		if ( $iconsize ) { 
			$myIconSize = $iconsize; //change the size to thosedimensions
		} 
		$htmlString .= '<img src="'.$pdfIconDir.'pdf_icon'.$icon.'.png" width="'.$iconsize.'" height="'.$iconsize.'" style="vertical-align:middle; padding-right:5px">';
	}
	
	$htmlString .= $linktext.'</a></div>';
	$htmlString .= '<style> .waitingDiv { width:20px; height:20px; background:url('.SSAPDF_PLUGIN_URL.'/images/loader.gif) no-repeat; } </style>';
	$htmlString .= '<div id="PDF_downloadFeedback"></div>';
	
	//style settings
	$htmlString .= '<input type="hidden" value="' . $font . '" id="pcl_font" />';
	$htmlString .= '<input type="hidden" value="' . $fontcolor . '" id="pcl_fontcolor" />';
	$htmlString .= '<input type="hidden" value="' . $linkcolor . '" id="pcl_linkcolor" />';
	$htmlString .= '<input type="hidden" value="' . $bgcolor . '" id="pcl_bgcolor" />';
	
	return $htmlString;
}

?>