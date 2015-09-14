<?php
/*
 * Load quote code
 */

/**
 * Include thickbox content
 *
 * @param string $action
 * @param array $data $_REQUEST
 */
function icl_quote_ajax($action, $data = array()) {
    if ($action == 'quote-get' || isset($data['next']) || isset($data['back'])) {
        require_once ICL_PLUGIN_PATH . '/inc/quote/quote-get.php';
    } else if ($action == 'quote-get-submit') {
        require_once ICL_PLUGIN_PATH . '/inc/quote/quote-get-submit.php';
    }
}

add_action('icl_ajx_custom_call', 'icl_quote_ajax', 10, 2);

/**
 * Init JS on admin dashboard
 * @global string $pagenow
 */
function icl_quote_admin_init() {    
    global $pagenow;
    if ($pagenow == 'index.php'
            || (isset($_GET['page'])
                    && $_GET['page'] == WPML_TM_FOLDER . '/menu/main.php')
            ){
        wp_enqueue_script('jquery');
        wp_enqueue_script('jquery-form');
        wp_enqueue_script('thickbox');
    }    
}

add_action('admin_init', 'icl_quote_admin_init');
