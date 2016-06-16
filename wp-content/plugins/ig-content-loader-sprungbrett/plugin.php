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
 * Get Sprungbrett JSON-DATA, transform it to html code (cl_sb_json_to_html()) and send it to base-plugin (cl_save_content) with Parameters $parent_id , $html and $blog_id
 *
 */
function cl_sb_update_content($parent_id, $meta_value, $blog_id) {

    // sprungbrett praktika -> ig-content-loader-sprungbrett
    if($meta_value == "Sprungbrett Praktika") {
        
        $json = file_get_contents('http://localhost/json.txt');
        $json = json_decode($json, TRUE);
        $html = cl_sb_json_to_html($json);

        cl_save_content( $parent_id, $html, $blog_id);

        return;  
        
    }

}
add_action('cl_update_content','cl_sb_update_content', 10, 3);


// get json data and transform it to html table
function cl_sb_json_to_html($json) {

    $html_table_prefix = '<table>';
    $html_table_suffix = '</table>';
    
    foreach($json as $jobitem) {
        $htmlstring .= '<tr><td><b>'.$jobitem['title'].'</b></td>'.
                       '<td>'.$jobitem['description'].'</td>'.
                       '<td><span class="dashicons dashicons-yes"></span></td></tr>';
    }
    
    $htmlstring = $html_table_prefix.$htmlstring.$html_table_suffix;

    return $htmlstring;
}

// registriert plugin in base and return meta infos
function cl_sb_metabox_item($array) {
    $array[] = json_decode('{"id": "ig-content-loader-sprungbrett", "name": "Sprungbrett Praktika"}');
    return $array;
}
add_filter('cl_metabox_item', 'cl_sb_metabox_item');



// inline-styles for frontend output
function myStyleSheet() {

echo '
       <style type="text/css">
            /* table styles*/
            td {
                display:block;
                margin-left:10px;
                }
            /* keep the last child without brake */
            tr td:last-child {
                display:table-cell;   
                }
            tr, td {
                font-family: "Noto Sans";
                padding: 0;
                }
            table {
                border-collapse: collapse;    
                }
            table, td, tr {
                }
            tr {
                }
            /* display count of available inters */
            #praktika_count_text {
                color: rgb(74, 74, 74);
                font-family: "Noto Sans";
                }
            #count_text_wrapper {
                text-align: center;
                background-color: rgb(239, 239, 239);
                }
        </style>
    ';
}
add_action( 'wp_print_styles', 'myStyleSheet' );

?>