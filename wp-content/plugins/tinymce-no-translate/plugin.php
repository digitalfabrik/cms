<?php
/**
 * Plugin Name: No translate for TinyMCE
 * Description: Adds a button in TinyMCE for the translate="no" attribute.
 * Version: 1.0
 * Author: Integreat Team / Sven Seeberg
 * Author URI: https://github.com/Integreat
 * License: MIT
 * Text Domain: tinymce-no-translate
 * Domain Path: /
 */

if ( is_admin() ) {
    add_action( 'init', function() {
        if ( ! current_user_can( 'edit_posts' ) && ! current_user_can( 'edit_pages' ) ) {
            return;
        }
        if ( get_user_option( 'rich_editing' ) !== 'true' ) {
            return;
        }
        add_editor_style( plugin_dir_url( __FILE__ ) . 'no-translate.css' );
        add_filter( 'mce_external_plugins', 'add_tinymce_no_translate_plugin' );
        add_filter( 'mce_buttons', 'add_tinymce_no_translate_button' );
    } );
}

function add_tinymce_no_translate_plugin( $plugin_array ) {
    $plugin_array['no_translate_attribute'] = plugin_dir_url( __FILE__ ) . 'no-translate.js';
    return $plugin_array;
}

function add_tinymce_no_translate_button( $buttons ) {
    array_push( $buttons, 'notranslatebtn' );
    return $buttons;
}
