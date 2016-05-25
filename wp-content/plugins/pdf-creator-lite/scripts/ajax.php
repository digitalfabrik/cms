<?php

function frontEndDownloadPDF()
{

	$requestType = $_POST['requestType'];
	$myContent = $_POST['myContent'];
	$pdfOptions = $_POST['pdfOptions'];
	
	// get the options	
	$optionsArray= explode(",", $pdfOptions);
	$toc = $optionsArray[0];
	$titlepage = $optionsArray[1];
	$filename = $optionsArray[2];
	$currentPostID = $optionsArray[3];
	
	// Create an array from the data	
	$contentArray= explode(",", $myContent);
	$newIDarray = array();
	
	
//	echo '<h3>PDF content is : "'.$requestType.'" ('.$myContent.')</h3>';
	switch ($requestType) {
		case "allpages":
			//echo 'Create an array of all pages / subpages IDs called "newIDarray"';
			$WPargs = array(
				'sort_order' => 'ASC',
				'sort_column' => 'menu_order',
				'hierarchical' => 1,
				'exclude' => '',
				'include' => '',
				'post_type' => 'page',
				'post_status' => 'publish'
			);
			$WPpages = get_pages( $WPargs );
			foreach ($WPpages as $page_data)
			{
				$newIDarray[] = $page_data->ID;
			}
			
		break;
		
		case "allposts":
			//echo 'Create an array of all post IDs called "newIDarray"';
			$WPargs = array(
				'sort_order' => 'ASC',
				//'sort_column' => 'menu_order',
				//'hierarchical' => 1,
				'exclude' => '',
				'include' => '',
				'post_type' => 'post',
				'post_status' => 'publish'
			);
			$WPposts = get_pages( $WPargs );
			foreach ($WPposts as $page_data)
			{
				$newIDarray[] = $page_data->ID;
			}
		
		break;		
		
		
		case "page":
		
			// Go through the array and convert slugs into IDs
			foreach ($contentArray as $myID)
			{
				
				if(is_numeric($myID))
				{
					//echo $myID.' (From Integer)<br/>';
					$newIDarray[]=$myID;
				}
				else
				{
					$myID = SSAPDF_getPageIDFromSlug(ltrim($myID)); // left trim, any blank space
					//echo $myID.' (From Slug Conversion)<br/>';
					$newIDarray[]=$myID;
				}
			}		
		
		break;
		
		case "category":
		
			// Go through the array and convert slugs into IDs
			foreach ($contentArray as $myCat)
			{		
				$tempCatArray = array(); // Craete temp cat ID array
				if(is_numeric($myCat))
				{
					$tempCatArray[]=$myCat;
					
				}
				else
				{
					$catID = get_cat_ID( ltrim($myCat) ); // left trim any space as it doesn't like space
					$tempCatArray[]=$catID;
				}	
				
				foreach($tempCatArray as $catID) // GO through the array of cat IDs
				{
					// Now go through the cat ID array and get the post content IDs
					$contentIDArray = SSAPDF_getPostIDsFromCategory($catID, $taxonomy='category');
					
					foreach($contentIDArray as $postID)
					{
						$newIDarray[]=$postID;
					}
				}
			}
				
		
		break;
	}
	
	// Add Custom Hook for ADd ons
	$newIDarray = getCaseIDs( $newIDarray );
	
	echo '<div style="border:solid 3px #000; padding:20px; background:#f1f1f1; width:540px;">';
	
	$pdfFilename = SSAPDF_frontendBuildPDF ( $newIDarray, $titlepage, $toc, $filename );
	SSAPDF_drawFrontendFeedback( $pdfFilename );
	
	echo '</div>';
	die();
}





?>