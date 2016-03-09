<?php

    // switch to language that is defined in cookie if defined language exists in wpml
    function switch_to_defined_language() {
        if( isset($_COOKIE['integreat_lang']) and !isset($_GET['redirectlang']) ) {
            $uri = $_SERVER['REQUEST_URI'];
            $uri = parse_url($uri);
            $path = $uri['path'];
            parse_str($uri['query'],$params);
            $params['lang'] = $_COOKIE['integreat_lang'];
            $params['redirectlang'] = 1;
            $fullNewURL = "http" . (isset($_SERVER['HTTPS']) ? "s" : "") . "://" . $_SERVER['HTTP_HOST'] . $path . (!empty(!empty($params)) ? "?" : "") . http_build_query($params);
            wp_redirect($fullNewURL);
            exit;
        }
    }
    add_action('init','switch_to_defined_language');

?>