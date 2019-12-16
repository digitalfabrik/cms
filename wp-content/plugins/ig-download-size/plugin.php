<?php
/**
 * Plugin Name: Calculate Download Size
 * Description: Estimates download size of Integreat content
 * Version: 1.0
 * Author: Integreat Team
 * Author URI: https://github.com/Integreat
 * License: MIT
 */

add_action('admin_menu', 'ig_download_size_menu');

/**
* add external link to Tools area
*/
function ig_download_size_menu() {
    load_plugin_textdomain( 'ig-download-size', false, dirname( plugin_basename( __FILE__ ) ) . '/lang/' );
    add_submenu_page('index.php', __( 'Integreat Mobile App Download Size', 'ig-download-size' ), __( 'Download Size', 'ig-download-size' ), 'edit_pages', 'ig_download_size','ig_download_size');
}

function ig_download_size() {
    echo "<div class=\"wrap\"><h1>Download Size</h1><ul>";

    $main_lang = $sitepress->get_default_language();

    $languages = apply_filters( 'wpml_active_languages', NULL, 'orderby=id&order=desc' );
    $language_codes = array();
    foreach ( $languages as $language ) {
        if( $language["code"] == $main_lang ) {
          echo "<li>" . $language["translated_name"] . ": <span id=\"size-".$language["code"]."\">" . __( "Loading", 'ig-download-size' ) . "</span></li>";
          $language_codes[] = "'" . $language["code"] . "'";
        }
    }
    $language_codes = join(", ", $language_codes);
    ?>
    </ul></div>
    <script>
        jQuery( document ).ready(function() {
            var languages = [<?php echo $language_codes; ?>];
            languages.forEach(function(lang_code) {
                var data = {
                    'action': 'ig_ds_ajax_calc',
                    'size-language': lang_code
                };
                jQuery.post(ajaxurl, data, function(response) {
                    jQuery("#size-"+lang_code).html(response);
                });
            });
        });
    </script>
    <?php
}

function ig_ds_ajax_calc() {
    $size = ig_ds_parse_pages( $_POST['size-language'] );
    if ( $size < 15000000 ) {
        $trafficlight = "<font color='#0f0'>&#x2B24;</font><font color='#aaa'>&#x2B24;</font><font color='#aaa'>&#x2B24;</font>";
    } elseif ( $size < 30000000 ) {
        $trafficlight = "<font color='#aaa'>&#x2B24;</font><font color='#ff0'>&#x2B24;</font><font color='#aaa'>&#x2B24;</font>";
    } else {
        $trafficlight = "<font color='#aaa'>&#x2B24;</font><font color='#aaa'>&#x2B24;</font><font color='#f00'>&#x2B24;</font>";
    }
    echo human_filesize( $size, 2 )." ".$trafficlight;
    wp_die();
}
add_action( 'wp_ajax_ig_ds_ajax_calc', 'ig_ds_ajax_calc' );

function ig_ds_get_pages( $url ) {
    $opts = [
        "http" => [
            "method" => "GET",
            "header" => "X-Integreat-Development: 1\r\n"
        ]
    ];
    $context = stream_context_create($opts);
    $content = file_get_contents( $url, false, $context );
    $content = json_decode( $content );
    return $content;
}

function ig_ds_parse_pages( $language ) {
    $url = site_url() . "/" . $language . "/wp-json/extensions/v3/pages";
    $resources[ $url ] = 0;
    $content = ig_ds_get_pages( $url );

    foreach ( $content as $page ) {
        $resources[ $page->thumbnail ] = 0;
        $doc = new DOMDocument();
        $doc->loadHTML( $page->content );
        $xpath = new DOMXpath($doc);

        $expressions[ "//a/@href" ] = array(".pdf");
        $expressions[ "//img/@src" ] = array(".png", ".jpg", ".jpeg");

        foreach ( $expressions as $expression => $filters) {
            $attributes = $xpath->query( $expression );
            foreach ($attributes as $attribute) {
                if ( ig_ds_filter_resources( $attribute->value, $filters ) ) {
                    $resources[ $attribute->value ] = 0;
                }
            }
        }
    }
    $resources = ig_ds_get_resource_size($resources);
    return ig_ds_sum ( $resources );
}

function ig_ds_filter_resources ( $resource, $filters ) {
    foreach ( $filters as $filter ) {
        if ( substr( $resource, -(strlen($filter))) == $filter )
            return true;
    }
    return false;
}

function ig_ds_get_resource_size ( $resources ) {
    foreach ( $resources as $url => $size ) {
        $headers = get_headers( $url, 1 );
        $resources[ $url ] = $headers['Content-Length'];
    }
    return $resources;
}

function ig_ds_sum ( $resources ) {
    $total_size = 0;
    foreach ( $resources as $url => $size ) {
        $total_size = $total_size + (int)$size;
    }
    return $total_size;
}

function human_filesize($bytes, $dec = 2)
{
    $size   = array('B', 'kB', 'MB', 'GB', 'TB', 'PB', 'EB', 'ZB', 'YB');
    $factor = floor((strlen($bytes) - 1) / 3);
    return sprintf("%.{$dec}f", $bytes / pow(1024, $factor)) . @$size[$factor];
}
