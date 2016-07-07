<?php
/**
 * Plugin Name: Content Loader Sprungbrett
 * Description: Template for plugin to include external data into integreat
 * Version: 1.0
 * Author: Julian Orth
 * Author URI: https://github.com/Integreat
 * License: MIT
 */


/**
 * Get Sprungbrett JSON-data, transform it to html table and call save content function in base plugin
 *
 * @param int $parent_id, string $meta_value, int $blog_id, string $blog_name
 */
function cl_sb_update_content($parent_id, $meta_value, $blog_id, $blog_name) {

	if($meta_value == "ig-content-loader-sprungbrett") {
		
		$json = file_get_contents('https://www.sprungbrett-intowork.de/ajax/app-search-internships?location=augsburg');
		$json = json_decode($json, TRUE);
		$html = cl_sb_json_to_html($json, $blog_name);
		
		cl_save_content( $parent_id, $html, $blog_id);

		return;  
	}

}
add_action('cl_update_content','cl_sb_update_content', 10, 4);

/** 
 * get json data, transform it to html table and return it as string
 *
 * @param object $json, string $blog_name
 */
function cl_sb_json_to_html($json, $blog_name) {
  

	$html_job_count_text = '<div id="count_text_wrapper"><p id="praktika_count_text">Zeige <strong>'.$json['total'].'</strong>'.
						   ' Praktika in <strong>'.$blog_name.'</strong></p></div>';
	$html_table_header = '<tr id="table_header"><th id="table_header_col1" >Bezeichnung / <span id="table_header_comp">Unternehmen</span></th><th id="table_header_trade">Ausbildung</th></tr>';
	$html_table_prefix = '<table id="job_table">';
	$html_table_suffix = '</table>';

	// generate job list and pick icon for apprenticeship depending on json value
	foreach($json['results'] as $jobitem) {
		$htmlstring .= '<tr class="job_item">
						<td class="job_title"><a class="joblink" href="'.$jobitem['url'].'"><b>'.$jobitem['title'].'</b><br><span class="job_company">'.$jobitem['company'].'</span></td></a>
						<td class="job_trade"><span class="job_trade_tick dashicons '.($jobitem['apprenticeship'] ? "dashicons-yes" :  "dashicons-no").'"></span></td></tr>';
	}
	
	// concatinate strings to create html table
	$htmlstring = $html_job_count_text.$html_table_prefix.$html_table_header.$htmlstring.$html_table_suffix;

	return $htmlstring;
}

/**
 * Register Plugin in Base-Plugin and return meta infos
 *
 * @param array $array
 */
function cl_sb_metabox_item($array) {
	$array[] = array('id'=>'ig-content-loader-sprungbrett', 'name'=>'Sprungbrett Praktika');
	return $array;
}
add_filter('cl_metabox_item', 'cl_sb_metabox_item');


/**
 * Inline CSS-Styles for plugin
 */
function table_styles() {

echo '
	   <style type="text/css">
			/* display count of available interns */
			#praktika_count_text {
				color: rgb(74, 74, 74);
				font-family: "Noto Sans";
				}
			#count_text_wrapper {
				text-align: center;
				background-color: rgb(239, 239, 239);
				}
				
			/* table styles*/
			#table_header_col1 {
				width: 70%;
				border-bottom:2px solid black;
				}
			#table_header_comp {
				font-weight:300;
				font-size: 18px;
				}
			#table_header_trade {
				border-bottom: 2px solid black;
				text-align: center;
				}
			td {
				display:block;
				margin-left:10px;
				}
			tr, td {
				font-family: "Noto Sans";
				padding: 0;
				}
			th {
				border-right: none !important;
				border-bottom: 1px solid rgb(51, 51, 51);
				}
			#job_table {
				border:none;
				font-family: "Noto Sans", sans-serif;
				}

			/* table items */
			.job_trade {
				line-height:0px;
				width:20%;
				display:table-cell; 
				border-bottom: 1px solid lightgray;
				border-right: none;
				text-align:center;
				padding-left: 0px; /* fix */
				}
			.job_title {
				width:100%;
				margin-bottom: 0px; /* border fix */
				border-bottom: 1px solid lightgray;
				border-right: none;
				}
			.job_company {
				font-size: 18px;
				}
			.job_trade_tick {
				font-size:30px !important;
				}
			tr td a.joblink {
				border-bottom: none;
			}
		</style>
	';
}
add_action( 'wp_print_styles', 'table_styles' );

?>
