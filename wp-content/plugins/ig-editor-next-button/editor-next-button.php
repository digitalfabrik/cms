<?php
/*
Plugin Name: Editor Next Button
*/

function get_next_button() {
    global $wpdb;
    $url = explode('/', $_SERVER['REQUEST_URI']);
    $last_part = $url[count($url)-1];
    $post_id = substr($last_part, 14, strpos($last_part, '&') - 14);
    $query = "SELECT DISTINCT ID, post_parent FROM $wpdb->posts WHERE post_type = 'page' AND post_parent = ".$post_id;
    $results = $wpdb->get_results($query);
    if (count($results) > 0) {
        $text = $results[0]->ID;
    } else {
        $blog_id = get_current_blog_id();
        $found_next_post = false;
        while (!$found_next_post) {
            $query = "SELECT post_parent from $wpdb->posts WHERE ID = " . $post_id;
            $results = $wpdb->get_results($query);
            if (count($results) > 0) {
                $post_parent = $results[0]->post_parent;
                $query = "SELECT DISTINCT posts.ID FROM $wpdb->posts posts, wp_" . $blog_id . "_icl_translations icl WHERE posts.ID = icl.element_id AND posts.post_type IN ('page', 'revision') AND posts.post_parent IN (SELECT post_parent from $wpdb->posts WHERE ID = " . $post_id . ") AND icl.language_code = (SELECT language_code FROM wp_". $blog_id . "_icl_translations icl WHERE icl.element_id = " . $post_id . " AND element_type = 'post_page') ORDER BY posts.menu_order";
                $results = $wpdb->get_results($query);
                $found_current_post = false;
                foreach ($results as $result) {
                    if ($result->ID == $post_id) {
                      $found_current_post = true;
                    } else if ($found_current_post) {
                      $text = $result->ID;
                      $found_next_post = true;
                      break;
                    }
                }
                if (!$found_next_post) {
		     $post_id = $post_parent;
                }
            } else {
                break;
            }
        }
    }
    if ($text != '') {
        wp_register_script(
            'get_next_button',
            plugins_url('next-button.js', __FILE__),
            array( 'jquery' ), false, true
        );
        wp_enqueue_script( 'get_next_button' );
        array_pop($url);
        $text = 'http://' . $_SERVER['HTTP_HOST'] . implode('/', $url) . '/' . substr($last_part, 0, 14) . $text . substr($last_part, strpos($last_part, '&'));
        wp_localize_script('get_next_button', 'params', array('text' => $text));
    }
}

add_action('admin_enqueue_scripts', 'get_next_button');
