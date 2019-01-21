<?php
/**
 * Plugin Name: Menu Link to hilfe.integreat-app.de
 * Description: Template-base to include any foreign content into Integreat
 * Version: 1.0
 * Author: Julian Orth
 * Author URI: https://github.com/Integreat
 * License: MIT
 */

add_action('admin_menu', 'example_admin_menu');

/**
* add external link to Tools area
*/
function example_admin_menu() {
    load_plugin_textdomain( 'integreat-help', false, dirname( plugin_basename( __FILE__ ) ) . '/lang/' );
    global $submenu;
    $url = 'https://wiki.integreat-app.de/';
    $submenu['tools.php'][] = array('<div id="wikiblank">'.__('Integreat Wiki').'</div>', 'edit_pages', $url);
}

add_action( 'admin_footer', 'make_wiki_blank' );
function make_wiki_blank()
{
    ?>
    <script type="text/javascript">
    jQuery(document).ready(function($) {
        $('#wikiblank').parent().attr('target','_blank');
    });
    </script>
    <?php
}
