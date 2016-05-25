<?php

/**
*	Registers a new admin 
*	sub-menu page under 'Tools'
*/
function SSAPDF_addAdminPage ()
{
	$page_title = 'PDF Creator';
	$menu_title = 'PDF Creator';
	$capability = 'edit_pages';
	$menu_slug = 'exportAsPDF';
	$function = 'SSAPDF_drawAdminPage';
	
	$adminPage = add_management_page( $page_title, $menu_title, $capability, $menu_slug, $function );
	add_action( 'admin_head-'. $adminPage, 'SSAPDF_adminHeadActions' );
}


/**
*	Admin-side head callback, only fires 
*	on the ssapdf admin screen.
*/
function SSAPDF_adminHeadActions ()
{
	//enqueue jQuery and plugin's ajax script.
	wp_enqueue_script( 
		'ssapdf-ajax', 
		plugins_url( '/js/admin.js', __FILE__ ), 
		array('jquery') 
	);
	
	//enqueue colour picker css
	wp_enqueue_style( 'spectrumcp', SSAPDF_PLUGIN_URL . '/colourpicker/spectrum.css' );
}


/**
*	Frontend WP head 
*	callback.
*/
function SSAPDF_frontendHeadActions ()
{
	//enqueue jQuery and plugin's ajax script.
	wp_enqueue_script( 
		'ssapdf-ajax', 
		plugins_url( '/js/frontend.js', __FILE__ ), 
		array('jquery') 
	);
	
	//write JS WP ajax url, and any other vars.
	wp_localize_script( 
		'ssapdf-ajax', 
		'ssapdfAjax', 
		array( 
			'ajaxurl' => admin_url( 'admin-ajax.php' ) 
		) 
	);
}


/**
*	Front-end WP footer 
*	callback.
*/
function SSAPDF_frontendFooterActions ()
{

}


/**
*	Coverts a hex-colour string 
*	into rgb format string.
*/
function SSAPDF_hex2RGB ( $hexStr, $returnAsString = false, $seperator = ',' )
{
	$hexStr = preg_replace( "/[^0-9A-Fa-f]/", '', $hexStr ); // Gets a proper hex string
	$rgbArray = array();
	
	if (strlen($hexStr) == 6) { //If a proper hex code, convert using bitwise operation. No overhead... faster
		$colorVal = hexdec($hexStr);
		$rgbArray['red'] = 0xFF & ($colorVal >> 0x10);
		$rgbArray['green'] = 0xFF & ($colorVal >> 0x8);
		$rgbArray['blue'] = 0xFF & $colorVal;
	} elseif (strlen($hexStr) == 3) { //if shorthand notation, need some string manipulations
		$rgbArray['red'] = hexdec(str_repeat(substr($hexStr, 0, 1), 2));
		$rgbArray['green'] = hexdec(str_repeat(substr($hexStr, 1, 1), 2));
		$rgbArray['blue'] = hexdec(str_repeat(substr($hexStr, 2, 1), 2));
	} else {
		return false; //Invalid hex color code
	}
	return $returnAsString ? implode($seperator, $rgbArray) : $rgbArray; // returns the rgb string or the associative array
}


/**
*	Returns integer representing page's depth in 
*	the site's structure (0 = top level parent).
*/
function SSAPDF_getPageDepth ( $id )
{
	return count( get_post_ancestors( $id ) );
}


/**
*
*/
function SSAPDF_drawAdminFeedback ()
{
	$blogID = get_current_blog_id();
	
	$siteTitle = get_bloginfo( 'name', 'raw' );
	$pdfFileName = preg_replace( "/&#?[a-z0-9]{2,8};/i", "", $siteTitle );
	$pdfFileName = str_replace( " ", "-",  $pdfFileName );
	$pdfFileName = str_replace( "/", "_",  $pdfFileName );
	$pdfFileName = $pdfFileName . ".pdf"; 
	
	$protocol = ( isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] != 'off' ) ? 'https' : 'http';
	$rootURL = $protocol . '://' . $_SERVER['HTTP_HOST'];
	
	//$fileLocal = '/ssapdf_tmp/blog' . $blogID . '/' . $pdfFileName;
	//$fileURL = $rootURL . $fileLocal;
	
	$WPuploads = wp_upload_dir();
	$basePath = $WPuploads['basedir'];
	$baseURL = $WPuploads['baseurl'];
	
	
	
	
	$fileLocal = $basePath . '/' . $pdfFileName;
	$fileURL = $baseURL . '/' . $pdfFileName;
	
	//fix WP - it gets the protocol wrong!
	$secureProtocol = ( isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] != 'off' ) ? true : false;
	if ( $secureProtocol ) {
		$fileURL = preg_replace('!^http://!i', 'https://', $fileURL );
	}
	
	
	$pluginFolder = plugins_url('', __FILE__);
	
	echo '<p>Your PDF is ready!</p>';
	echo '<a href="' . $fileURL . '" id="forceDownloadLink" class="button-primary" style="padding-left:25px; padding-right:25px;">Download PDF</a>';
	
	echo '&nbsp;<a id="previewLink" class="button-primary" style="padding-left:25px; padding-right:25px;" target="_blank" href="' . $fileURL . '">Preview</a>';
	echo '<br /><p>Please note that preview is only available in some browsers.</p>';
	
	echo '<div id="previewDownload"></div>';
	
	echo '<p><a href="?page=exportAsPDF">&laquo; Back to Export Settings</a></p>';
	
	echo '<br />';
	echo '<p style="color:#777;"><em>Problems downloading? Here is a direct link to the PDF file, to save it you can right-click it<br />and choose \'save target\': </em>
			&nbsp;<a href="' . $fileURL . '">' . $pdfFileName . '</a></p>';
	
	echo '<div id="forceDownload"></div>';
	?>
	
		<script type="text/javascript">
			var SSAPDF = {};
					
			SSAPDF.addForceFrame = function ( file ) {
				jQuery('#forceDownload').empty().append('<iframe id="forceDownloadFrame" name="forceDownloadFrame" src="<?php echo $pluginFolder; ?>/download.php?pdf=loc' + file + '" style="display:none;"></iframe>');
			};
			
			SSAPDF.addPreviewFrame = function ( url ) {
				jQuery('#previewDownload').empty().append('<iframe style="width:422px; height:580px; border:1px solid #aaa;" id="previewFrame" name="previewFrame" src="' + url + '"></iframe>');
			};
			
			jQuery(document).ready( function () {				
				jQuery('#forceDownloadLink').click( function ( e ) {
					SSAPDF.addForceFrame( '<?php echo $fileLocal; ?>' );
					e.preventDefault();
				});
				
				jQuery('#previewLink').click( function ( e ) {
					SSAPDF.addPreviewFrame( '<?php echo $fileURL; ?>' );
					jQuery(this).text('Refresh Preview');
					e.preventDefault();
				});
			});
		</script>	

	<?php
}




/**
*
*/
function SSAPDF_drawFrontendFeedback ( $pdfFileName )
{
	$blogID = get_current_blog_id();
	
	$siteTitle = get_bloginfo( 'name', 'raw' );
	//$pdfFileName = preg_replace( "/&#?[a-z0-9]{2,8};/i", "", $siteTitle );
	//$pdfFileName = str_replace( " ", "-",  $pdfFileName );
	//$pdfFileName = str_replace( "/", "_",  $pdfFileName );
	//$pdfFileName = $pdfFileName . ".pdf"; 
	
	$protocol = ( isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] != 'off' ) ? 'https' : 'http';
	$rootURL = $protocol . '://' . $_SERVER['HTTP_HOST'];
	
	//$fileLocal = '/ssapdf_tmp/blog' . $blogID . '/' . $pdfFileName;
	//$fileURL = $rootURL . $fileLocal;
	$WPuploads = wp_upload_dir();
	$basePath = $WPuploads['basedir'];
	$baseURL = $WPuploads['baseurl'];
	
	$fileLocal = $basePath . '/' . $pdfFileName;
	$fileURL = $baseURL . '/' . $pdfFileName;
	
	//fix WP - it gets the protocol wrong!
	$secureProtocol = ( isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] != 'off' ) ? true : false;
	if ( $secureProtocol ) {
		$fileURL = preg_replace('!^http://!i', 'https://', $fileURL );
	}
	
	$pluginFolder = plugins_url('', __FILE__);
	
	echo '<p>Your PDF is ready!</p>';
	echo '<a href="' . $fileURL . '" id="forceDownloadLink" class="button-primary" style="padding-right:25px;">Download PDF</a>';
	
	echo '&nbsp;<a id="previewLink" class="button-primary" style="padding-left:25px; padding-right:5px;" target="_blank" href="' . $fileURL . '">Preview</a> ';
	echo '<span style="font-size:10px; color:#777">( Preview is only available in some browsers. )</span>';
	
	echo '<div id="previewDownload"></div>';
		
	echo '<hr />';
	echo '<p style="color:#777; font-size:10px; line-height:120%;"><em>Problems downloading? Here is a direct link to the PDF file.<br/>';
	echo 'Right-click it and choose \'save target\': </em> <a href="' . $fileURL . '" target="blank">' . $pdfFileName . '</a></p>';
	
	echo '<div id="forceDownload"></div>';
	?>
	
		<script type="text/javascript">
			var SSAPDF = {};
					
			SSAPDF.addForceFrame = function ( file ) {
				jQuery('#forceDownload').empty().append('<iframe id="forceDownloadFrame" name="forceDownloadFrame" src="<?php echo $pluginFolder; ?>/download.php?pdf=loc' + file + '" style="display:none;"></iframe>');
			};
			
			SSAPDF.addPreviewFrame = function ( url ) {
				console.log(url);
				jQuery('#previewDownload').empty().append('<iframe style="width:422px; height:580px; border:1px solid #aaa;" id="previewFrame" name="previewFrame" src="' + url + '"></iframe>');
			};
			
			jQuery(document).ready( function () {				
				jQuery('#forceDownloadLink').click( function ( e ) {
					SSAPDF.addForceFrame( '<?php echo $fileLocal; ?>' );
					e.preventDefault();
				});
				
				jQuery('#previewLink').click( function ( e ) {
					SSAPDF.addPreviewFrame( '<?php echo $fileURL; ?>' );
					jQuery(this).text('Refresh Preview');
					e.preventDefault();
				});
			});
		</script>	

	<?php
}






/**
*
*/
function SSAPDF_getPageContent($page_id) {
	$page_data = get_page($page_id); 
	$content = apply_filters('the_content', $page_data->post_content);
	
	// Remove the pdf lite shortcopde TO DO
	
	
	return $content; 
}


/**
*
*/
function SSAPDF_getPageIDFromSlug($page_slug)
{
    $page = get_page_by_path($page_slug);
    if(!empty($page)) {
        return $page->ID;
    }
}


/**
*
*/
function SSAPDF_getPostIDsFromCategory($cat, $taxonomy='category')
{
    return get_posts(array(
        'numberposts'   => -1, // get all posts.
        'tax_query'     => array(
            array(
                'taxonomy'  => $taxonomy,
                'field'     => 'id',
                'terms'     => is_array($cat) ? $cat : array($cat),
            ),
        ),
        'fields'        => 'ids', // only get post IDs.
    ));
}


/**
*
*/
function getPDFIconArray()
{

	// Get the contents of the image dir
	$pdfIconDir = EXPORT_AS_PDF_PATH.'/images/pdf_icons/';
	
	$imageArray = array();
	//path to directory to scan
	$myIcons = scandir($pdfIconDir);
	foreach($myIcons as $imageRef)
	{
		if($imageRef != "." && $imageRef != "..") 
		{
			$imageArray[]= $imageRef;
		}
	}	
	//$imageArray = asort($imageArray);
	return $imageArray;
}





?>