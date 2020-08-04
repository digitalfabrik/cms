<?php
/**
 * Plugin Name: Support SVG upload
 * Description: 
 * Version: 1.0
 * Author: Integreat project / Sven Seeberg
 * Author URI: https://github.com/Integreat
 * License: MIT
 */


function kb_svg ( $svg_mime ){
        $svg_mime['svg'] = 'image/svg+xml';
        return $svg_mime;
}

add_filter( 'upload_mimes', 'kb_svg' );
