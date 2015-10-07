<?php
/**
*	Generates a pdf and saves it to server,
*	used admin-side from plugin screen to generate 
*	site pages-only pdf with cover and index.
*/
function SSAPDFadminBuildPDF ()
{
	
	$hasFrontPage = ( isset($_POST['addFrontPage']) && $_POST['addFrontPage'] === 'true' ) ? true : false;
	$hasToC = ( isset($_POST['addToC']) && $_POST['addToC'] === 'true' ) ? true : false;
	
	//set up some default style options
	$bg_rgb = array(
		'red' 	=> 255,
		'green' => 255,
		'blue' 	=> 255 
	);

	$text_font = 'helvetica';
	$text_hex = '#363636';
	$link_hex = '#3333ff';

	//swap in custom options if needed
	if ( $_POST['useCSS'] == 'custom' )
	{
		$bg_rgb = SSAPDF_hex2RGB( $_POST['bg_cpicker'] );
		$text_font = $_POST['fontFamily'];
		$text_hex = $_POST['font_cpicker'];
		$link_hex = $_POST['link_cpicker'];
	}

	$text_rgb = SSAPDF_hex2RGB( $text_hex );
	$link_rgb = SSAPDF_hex2RGB( $link_hex );
	
	//make a display creation date
	$utsNow = strtotime('now');
	$displayDate = date( 'jS F Y', $utsNow );
	
	//grab the site info
	$siteURL = get_bloginfo('url');
	$siteTitle = get_bloginfo( 'name', 'raw' ); 
	
	//clean up site title to use as filename
	$pdfFileName = preg_replace( "/&#?[a-z0-9]{2,8};/i", "", $siteTitle );
	$pdfFileName = str_replace( " ", "-",  $pdfFileName );
	$pdfFileName = str_replace( "/", "_",  $pdfFileName );
	$pdfFileName = $pdfFileName . ".pdf";
	
	
	//--- init TCpdf ---------------------------------------
	$pdf = new SSA_PDF(PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);

	//pass style settings into the class vars
	$pdf->bg_rgb = $bg_rgb;
	
	$pdf->text_font = $text_font;
	$pdf->text_hex = $text_hex;
	$pdf->link_hex = $link_hex;

	$pdf->text_rgb = $text_rgb;
	$pdf->link_rgb = $link_rgb;
	
	$pdf->displayDate = $displayDate;
	$pdf->siteURL = $siteURL;
	$pdf->siteTitle = $siteTitle;
	
	// set document information
	$pdf->SetCreator('SSA-PDF(TC)');
	$pdf->SetAuthor('');
	$pdf->SetTitle( $pdfFileName );
	$pdf->SetSubject('');
	$pdf->SetKeywords('');

	// set header data
	$pdf->SetHeaderMargin( PDF_MARGIN_HEADER );
	// set footer data
	$pdf->SetFooterMargin( PDF_MARGIN_FOOTER );
	// set default monospaced font
	$pdf->SetDefaultMonospacedFont(PDF_FONT_MONOSPACED);
	// set margins
	$pdf->SetMargins(PDF_MARGIN_LEFT, PDF_MARGIN_TOP, PDF_MARGIN_RIGHT);
	// set auto page breaks
	$pdf->SetAutoPageBreak(TRUE, PDF_MARGIN_BOTTOM);
	// set image scale factor
	$pdf->setImageScale(PDF_IMAGE_SCALE_RATIO);
	
	// set default font subsetting mode
	//$pdf->setFontSubsetting( true );

	// Set font
	// dejavusans is a UTF-8 Unicode font, if you only need to
	// print standard ASCII chars, you can use core fonts like
	// helvetica or times to reduce file size.
	//$pdf->SetFont( 'dejavusans', '', 11, '', true, true );
	
	$pdf->SetTextColorArray	(
		array( $text_rgb['red'], $text_rgb['green'], $text_rgb['blue'] ),
		false
	);
		
	
	//--- build the contents ---------------------------------------
	$blogID = get_current_blog_id();
	
	if ( function_exists('switch_to_blog') ) //check multisite
	{
		switch_to_blog( $blogID );
	}
	
	if ( current_user_can( 'manage_options' ) ) //Only let them download the file if they are admin
	{ 
		//get the WP pages
		$args = array(
			'sort_order' => 'ASC',
			'sort_column' => 'menu_order',
			'hierarchical' => 1,
			'exclude' => '',
			'include' => '',
			'post_type' => 'page',
			'post_status' => 'publish'
		);
		$pages = get_pages( $args );
		
		//set some extra document css 
		$cssStr = '<style type="text/css"> ';
		$cssStr .= '.pageBreak { page-break-after: always; } ';
		$cssStr .= '* { font-family:' . $text_font . ';	} ';
		$cssStr .= 'a { color:' . $link_hex . '; } ';
		$cssStr .= 'a:visited { color:' . $link_hex . '; } ';
		$cssStr .= ' </style>';
				

		//make a front page
		if ( $hasFrontPage )
		{
			$displayTitle = get_bloginfo( 'name', 'display' );
			//$htmlStr .= '<div class="pageBreak">';
			$htmlStr = '&nbsp;<br />&nbsp;<br />&nbsp;<br />&nbsp;<br />&nbsp;<br />&nbsp;<br />&nbsp;<br /><h1 style="font-size:40px; text-align:center; text-decoration:underline;">' . $displayTitle . '</h1>';
			$htmlStr .= '<p style="text-align:center;">&nbsp;<br />&nbsp;<br />PDF Created on ' . $displayDate . '<br /><a href="' . $siteURL . '">' . $siteURL . '</a></p>';
			//$htmlStr .= '</div>';
			
			$pdf->AddPage();
			$pdf->writeHTML	(
				$cssStr . $htmlStr,
				true,
				false,
				false,
				false,
				'' 
			);
		}
		

		//build the WP pages
		$pageno = 1;
		$checkedPages = $_POST['checkerPage'];
		foreach ($pages as $page_data)
		{
			//if ( isset($checkedPages[$page_data->ID]) )
			if ( in_array( $page_data->ID, $checkedPages, false ) )
			{
				$title = $page_data->post_title; 
				$content = $page_data->post_content;
				
				//remove any sitemap shortcodes
				$content = preg_replace( '/\[sitemap_pages[^\]]*]/i', '', $content );
				$content = preg_replace( '/\[pdf-lite[^\]]*]/i', '', $content );
				
				$depth = SSAPDF_getPageDepth( $page_data->ID );
				
				//only add the page if it's not contents page
				if ( $title != 'Contents' )
				{
					//prepend the root url to any local absolute img paths
					$PRcontent = preg_replace_callback(
						"/<img [^>]*src=['\"](\/[^\"']*)['\"][^>]*>/iU",
						function ( $matches ) {				
							$protocol = ( isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] != 'off' ) ? 'https' : 'http';
							$rootURL = $protocol . '://' . $_SERVER['HTTP_HOST'];
							return '<img src="' . $rootURL . $matches[1] . '" />';
						},
						$content
					);
					
					//run wp formatting filters and process shortcodes
					$PRcontent = apply_filters( 'the_content', $PRcontent ); 
					
					//Remove QTL feedback AFTER processing!
					if ( shortcode_exists('QTL-Question') )	{
						$pattern = '#<!--QTLfeedbackStart-->(?>\r\n|\n|\r|\f|\x0b|\x85)*((?!QTLfeedbackStart).)*<!--QTLfeedbackEnd-->#is';
						$PRcontent = preg_replace( $pattern, "", $PRcontent, -1);
					}

					
					$topPage = ( $hasFrontPage ) ? '#2' : '#1';
					
					//build the page content
					$htmlStr = '<h1>' . $title . '</h1>';
					$htmlStr .= $PRcontent;
					
					//tcpdf issue linking to toc. 
					//only adding a top link when there's a cover page or no TOC,
					//jumps you to cover page regardless.
					if ( $hasFrontPage || ! $hasToC )
					{
						$htmlStr .= '<br /><a href="#1" style="font-size:10px;">Top</a><br /><br />';
					}
					
					$pdf->AddPage();				
					$pdf->Bookmark( $title, $depth, 0, '', 'B', array($link_rgb['red'], $link_rgb['green'], $link_rgb['blue']), 0, '#TOC' );
					
					$pdf->writeHTML	(
						$cssStr . $htmlStr,
						true,
						false,
						false,
						false,
						'' 
					);
									
					$pageno++;
				}
			}
		}
		
		
		//add table of contents
		if ( $hasToC )
		{
			$pdf->addTOCPage();

			// write the TOC title
			$pdf->SetFont( $text_font, 'B', 16);
			$pdf->MultiCell(0, 16, 'Table of Contents', 0, 'L', 0, 1, '', '', true, 0);

			$pdf->SetFont( $text_font, '', 11);
			$insertAt = ( $hasFrontPage ) ? 2 : 1;
			$pdf->addTOC( $insertAt, $text_font, '.', 'TOC', 'B', array( $link_rgb['red'], $link_rgb['green'], $link_rgb['blue'] ));

			$pdf->endTOCPage();
		}
		
		
		//--- output the PDF ---------------------------------------	
		//$basePath = $_SERVER['DOCUMENT_ROOT'] . 'ssapdf_tmp/blog' . $blogID;
		$WPuploads = wp_upload_dir();
		$basePath = $WPuploads['basedir'];
		if ( ! file_exists( $basePath ) ) {
			mkdir( $basePath, 0777, true );
		}
		
		//temp set server limit and timeout
		ini_set("memory_limit", "512M");
		ini_set("max_execution_time", "600");
		ini_set("allow_url_fopen", "1");
		
		//output PDF document.
		$pdf->Output( $basePath . '/' . $pdfFileName, 'F');
		
		SSAPDF_drawAdminFeedback();
		
		//echo '<br /><br />##dbug:<br />';
		//echo '<pre>';
		//print_r($_POST);
		//echo '</pre>';
		
		die();
		
	}
	else
	{
		echo '<html><head></head><body><p>You do not have permission to perform this action.<br />Please contact an administrator.</p></body></html>';
	}

} 


?>