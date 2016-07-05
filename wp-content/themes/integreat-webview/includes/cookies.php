<?php

    // set lang cookie
    function set_lang_cookie() {
        if( $_GET['sc'] == 1 or !isset($_COOKIE['integreat_lang']) ) {
            if( !is_admin() ) {
                // set language cookie
                setcookie( 'integreat_lang', ICL_LANGUAGE_CODE, time()+3600*24*100, '/', $_SERVER['SERVER_NAME'], false);

                // redirect to same site and remove param sc to ensure that cookie is set
                $uri = $_SERVER['REQUEST_URI'];
                $uri = parse_url($uri);
                $path = $uri['path'];
                parse_str($uri['query'],$params);
                unset($params['sc']);
                $fullNewURL = "http" . (isset($_SERVER['HTTPS']) ? "s" : "") . "://" . $_SERVER['HTTP_HOST'] . $path . (!empty(!empty($params)) ? "?" : "") . http_build_query($params);
                wp_redirect($fullNewURL);
                exit;
            }
        }
    }
    if(strpos($_SERVER['REQUEST_URI'],"wp-json") === false) {
        add_action('init','set_lang_cookie');
    }

    // forward to lang defined in cookie
    function redirect_to_defined_lang() {
        // forward to lang defined in cookie
        if (isset($_COOKIE['integreat_lang'])) {
            if ($_COOKIE['integreat_lang'] != ICL_LANGUAGE_CODE) {
                $languages = icl_get_languages('skip_missing=1&orderby=id&order=desc');

                if(isset($languages[$_COOKIE['integreat_lang']])) {
                    $redirectLink = $languages[$_COOKIE['integreat_lang']]['url'];
                } else {
                    $redirectLink = get_bloginfo('wpurl') . '/' . $_COOKIE['integreat_lang'];
                }
                wp_redirect($redirectLink);
                exit;
            }
        }
    }
    if(strpos($_SERVER['REQUEST_URI'],"wp-json") === false) {
        add_action('wp_enqueue_scripts','redirect_to_defined_lang');
    }
?>
