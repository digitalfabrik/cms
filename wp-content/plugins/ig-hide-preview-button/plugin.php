<?php
/**
 * Plugin Name: Hide Preview Button
 * Description: Hides the preview button when creating or editing posts
 * Version: 1.0
 * Author: Maximilian Ammann
 * Author URI: https://github.com/Integreat
 * License: MIT
 * Text Domain: ig-hide-preview-button
 */


global $pagenow;

if ('post.php' == $pagenow || 'post-new.php' == $pagenow) {
    function wpse_125800_custom_publish_box() {
        if (!is_admin()) {
            return;
        }

        $style = '';
        $style .= '<style type="text/css">';
        $style .= '#edit-slug-box, #minor-publishing-actions, #visibility, .num-revisions, .curtime';
        $style .= '{display: none; }';
        $style .= '</style>';

        echo $style;
    }

    add_action('admin_head', 'wpse_125800_custom_publish_box');
}
