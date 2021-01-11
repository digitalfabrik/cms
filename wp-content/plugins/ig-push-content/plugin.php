<?php
/**
 * Plugin Name: Integreat - Push Content
 * Description: Manage tokens to allow pushing content to pages
 * Version: 1.0
 * Author: Sven Seeberg <seeberg@integreat-app.de>
 * Author URI: https://github.com/Integreat
 * License: MIT
 * Text Domain: ig-attach-content
 */

/**
 * Load plugin text domain for translations in backend
 */
add_action( 'plugins_loaded', function() {
	load_plugin_textdomain('ig-push-content', false, basename( dirname( __FILE__ )));
});

/**
 * Add meta box for generating an auth token including an "require review" checkbox.
 */
function ig_pc_add_metabox() {
	add_meta_box( 'ig_pc_metabox', __( 'Push Content', 'ig-push-content' ), 'ig_pc_create_metabox', 'page', 'side' );
}
add_action( 'add_meta_boxes_page', 'ig_pc_add_metabox' );


/**
 * Generate meta box.
 *
 * @param WP_Post $post Current post object.
 */
function ig_pc_create_metabox( $post ) {

    ig_pc_html(
        get_post_meta( $_GET['post'], 'ig_push_content_token', true ),
        get_post_meta( $_GET['post'], 'ig_push_content_review', true )
    );
}

/**
 * Generate HTML for input box.
 */
function ig_pc_html( $token, $review ) {
    wp_nonce_field( basename( __FILE__ ), 'pc_nonce' );
?>
	<script type="text/javascript" >
        function pc_generate_token() {
            var token = Math.random().toString(36).substr(2) + Math.random().toString(36).substr(2);
            jQuery('#pc_token').html('<input id="ig_push_content_review" name="ig_push_content_review" type="checkbox" />' +
'<label for="ig_push_content_review"><?php echo __( 'Require review', 'ig-push-content' ); ?></label><br>' +
'<label for="ig_push_content_token"><?php echo __( 'Token', 'ig-push-content' ); ?>:</label><br>' +
'<input id="ig_push_content_token" name="ig_push_content_token" type="text" value="'+token+'" readonly /><br>' +
'<input type="button" class="button-secondary" value="<?php echo __( 'Revoke token', 'ig-push-content' ); ?>" onclick="pc_revoke_token();" />');
        }
        function pc_revoke_token() {
            jQuery('#pc_token').html('<input type="button" class="button-secondary" value="<?php echo __( 'Generate token', 'ig-push-content' ); ?>" onclick="pc_generate_token()">');
        }
	</script>
    <div id="pc_token">
        <?php
        if ( $token ) {
            echo '<input id="ig_push_content_review" name="ig_push_content_review" type="checkbox"'.( $review ? ' checked': '').' />
<label for="ig_push_content_review">' . __( 'Require review', 'ig-push-content' ) . '</label><br>
<label for="ig_push_content_token">' . __( 'Token', 'ig-push-content' ) . ':</label><br>
<input id="ig_push_content_token" name="ig_push_content_token" type="text" value="' . $token . '" readonly /><br>
<input type="button" class="button-secondary" value="'. __( 'Revoke token', 'ig-push-content' ) .'" onclick="pc_revoke_token();" />
';
        } else {
            echo '<input type="button" class="button-secondary" value="'. __( 'Generate token', 'ig-push-content' ) .'" onclick="pc_generate_token();" />
';
        }?>
    </div>
<?php
}

/**
* Save Meta Box contents
*
* @param int $post_id Post ID
*/
function ig_pc_save_meta_box( $post_id ) {
    $key_token = 'ig_push_content_token';
    $key_require_review = 'ig_push_content_review';
    $key_denied = 'ig_push_content_denied';
    if ( current_user_can( 'publish_pages' ) ) {
        if ( Null == $_POST[$key_token] ) {
            delete_post_meta( $post_id, $key_token);
            delete_post_meta( $post_id, $key_require_review);
            delete_post_meta( $post_id, $key_denied );
        } else {
            update_post_meta( $post_id, $key_token, $_POST[$key_token] );
            update_post_meta( $post_id, $key_require_review, $_POST[$key_require_review] );
            update_post_meta( $post_id, $key_denied, 0 );
        }
    }
}
add_action('save_post', 'ig_pc_save_meta_box');
add_action('edit_post', 'ig_pc_save_meta_box');

/**
* Update page via API
*
* @param str $data a JSON string containing the new page content
*/
function ig_pc_save_page( WP_REST_Request $request ) {
    foreach ($request->get_params() as $key => $value) {
        $params = json_decode($key);
        $content = $params->content;
        $token = esc_sql($params->token);
    }
    global $wpdb;
    $query =  "SELECT post_id FROM " . $wpdb->prefix . "postmeta WHERE meta_value='" . $token . "' AND meta_key='ig_push_content_token'";
    $results = $wpdb->get_results( $query );
    if ( count($results) != 1 ) {
        return array( "status" => "denied" );
    }
    $post_id = $results[0]->post_id;

    // allow filtering the data
    $data = apply_filters( 'ig_pc_update_page', $data );

    // check if review flag is required
    if ( $page_meta_review !== "on" ) {
        add_filter( 'ig_allow_publishing', function ( ) { return True; } );
    }

    // update the post
    $post = get_post( $post_id, ARRAY_A );
    $post['post_content'] = $content;
    $new_id = wp_insert_post( $post );

    if ( $new_id > 0 )
        return array( "status" => "success" );
    else
        return array( "status" => "error" );
}
