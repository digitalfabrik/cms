<?php
/**
 * Plugin Name: Content Loader Sprungbrett
 * Description: Template for plugin to include external data into integreat
 * Version: 0.1
 * Author: Julian Orth, Sven Seeberg
 * Author URI: https://github.com/Integreat
 * License: MIT
 */


/**
 * Get Sprungbrett JSON-data, transform it to html table and call save content function in base plugin
 *
 * @param int $parent_id, string $meta_value, int $blog_id, string $blog_name
 */
function cl_sb_update_content($parent_id, $meta_value, $blog_id, $blog_name) {

    if($meta_value == "Sprungbrett Praktika") {
        
        $json = file_get_contents('http://localhost/json.txt');
        $json = json_decode($json, TRUE);
        $html = cl_sb_json_to_html($json, $blog_name);
        
        $json2 = file_get_contents('http://localhost/json-testergebnis.txt');
        $json2 = json_decode($json2, TRUE);
        
        $testcomp = $json2['results'][2]['company'];
        echo htmlspecialchars_decode($testcomp);
//        var_dump("ok ".$testcomp2);
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
  
    $html_job_count_text = '<div id="count_text_wrapper"><p id="praktika_count_text">Zeige <strong>'.count($json).'</strong>'.
                           ' Praktika in <strong>'.$blog_name.'</strong></p></div>';
    $html_table_header = '<tr id="table_header"><th id="table_header_col1" >Bezeichnung / <span id="table_header_comp">Unternehmen</span></th><th id="table_header_trade">Ausbildung</th></tr>';
    $html_table_prefix = '<table id="job_table">';
    $html_table_suffix = '</table>';

    
    // generate job list and pick icon depending on json value
    foreach($json as $jobitem) {
        $htmlstring .= '<tr class="job_item">
                        <td class="job_title"><b>'.$jobitem['title'].'</b><br><span class="job_company">'.$jobitem['description'].'</span></td>
                        <td class="job_trade"><span class="job_trade_tick dashicons '.($jobitem['zip'] ? "dashicons-yes" :  "dashicons-no").'"></span></td></tr>';
    }
    
    $htmlstring = $html_job_count_text.$html_table_prefix.$html_table_header.$htmlstring.$html_table_suffix;

    return $htmlstring;
}

/**
 * Register Plugin in Base-Plugin and return meta infos
 *
 * @param array $array
 */
function cl_sb_metabox_item($array) {
    $array[] = json_decode('{"id": "ig-content-loader-sprungbrett", "name": "Sprungbrett Praktika"}');
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
                margin-bottom: 1px; /* border fix */
                border-bottom: 1px solid lightgray;
                border-right: none;
                }
            .job_company {
                font-size: 18px;
                }
            .job_trade_tick {
                font-size:30px !important;
                }
        </style>
    ';
}
add_action( 'wp_print_styles', 'table_styles' );

?>