<?php
/**
 * Plugin Name: Integreat - Page Stamp
 * Description: Add stamp to pages with meta information
 * Version: 1.0
 * Author: Sven Seeberg <seeberg@integreat-app.de>
 * Author URI: https://github.com/Integreat
 * License: MIT
 * Text Domain: ig-page-stamp
 */


/**
 * Load plugin text domain for translations in backend
 */
add_action( 'plugins_loaded', function() {
    load_plugin_textdomain('ig-page-stamp', false, basename( dirname( __FILE__ )));
});

/**
 * Add meta box for generating an auth token including an "require review" checkbox.
 */
function ig_ps_add_metabox() {
    add_meta_box( 'ig_ps_metabox', __( 'Page stamp', 'ig-page-stamp' ), 'ig_ps_create_metabox', 'page', 'side' );
}
add_action( 'add_meta_boxes_page', 'ig_ps_add_metabox' );


/**
 * Generate meta box.
 *
 * @param WP_Post $post Current post object.
 */
function ig_ps_create_metabox( $post ) {
    ig_ps_html( $post->ID );
}

/**
 * Generate HTML for input box.
 */
function ig_ps_html( $post_id ) {
    $key_activate = 'ig_ps_activate';
    $key_votes = 'ig_ps_votes';
    $key_orgs = 'ig_ps_organisation';
    $active = get_post_meta( $post_id, $key_activate, true );
    $votes = get_post_meta( $post_id, $key_votes, true );
    $orgs = get_post_meta( $post_id, $key_orgs, true );
    wp_nonce_field( basename( __FILE__ ), 'ps_nonce' );
?>
    <div id="ig_ps_metabox">
        <input id="ig_ps_activate" name="ig_ps_activate" type="checkbox" <?php echo ($active ? ' checked': ''); ?>>
        <label for="ig_ps_activate"><?php echo __( 'Activate' ); ?></label><br>
        <input id="ig_ps_votes" name="ig_ps_votes" type="checkbox" <?php echo ($votes ? ' checked': ''); ?>>
        <label for="ig_ps_votes"><?php echo __( 'Show positive feedback', 'ig-page-stamp' ); ?></label><br>
        <label for="ig_ps_organisation"></label><br>
        <input id="ig_ps_organisation" name="ig_ps_organisation" type="text" value="<?php echo ($orgs); ?>" /><br>
    </div>
<?php
}

/**
* Save Meta Box contents
*
* @param int $post_id Post ID
*/
function ig_ps_save_meta_box( $post_id ) {
    $key_activate = 'ig_ps_activate';
    $key_votes = 'ig_ps_votes';
    $key_orgs = 'ig_ps_organisation';
    if ( current_user_can( 'publish_pages' ) ) {
        if ( in_array( $key_activate, $_POST ) ) {
            delete_post_meta( $post_id, $key_activate);
            delete_post_meta( $post_id, $key_votes);
            delete_post_meta( $post_id, $key_orgs );
        } else {
            update_post_meta( $post_id, $key_activate, $_POST[$key_activate] );
            update_post_meta( $post_id, $key_votes, $_POST[$key_votes] );
            update_post_meta( $post_id, $key_orgs, $_POST[$key_orgs] );
        }
    }
}
add_action('save_post', 'ig_ps_save_meta_box');
add_action('edit_post', 'ig_ps_save_meta_box');

/**
 * Attach stamp to pages when requested via API
 *
 * @param WP_Post $post
 *
 * @return WP_Post
 */
function ig_ps_modify_post( WP_Post $post ) {
    $wpdb;

    $key_activate = 'ig_ps_activate';
    $key_votes = 'ig_ps_votes';
    $key_orgs = 'ig_ps_organisation';

    /**
     * In some cases it seems that the API is working through some posts more than
     * once. In such cases we don't want to attach the stamp multiple times.
     * Therefore we store if we already manipulated a page and return if that is
     * the case.
     */
    global $ig_ps_already_manipulated;
    if ( !$ig_ps_already_manipulated ) {
        $ig_ps_already_manipulated = array();
    }
    if ( in_array( $post->ID, $ig_ps_already_manipulated) ) {
        return $post;
    }
    $ig_ps_already_manipulated[] = $post->ID;

    /**
     * Generate and attach stamp
     */
    $active = get_post_meta( $post->ID, $key_activate, true );
    if( "on" === $active && ! in_array( 'nostamp', $_GET ) ) {
        $votes = get_post_meta( $post->ID, $key_votes, true );
        $orgs = get_post_meta( $post->ID, $key_orgs, true );

        $stamp = ig_generate_stamp( $post->ID, $votes, $orgs );

        $post->post_content = $post->post_content . $stamp;
    }
    return $post;
}
/**
 * The page should be modified if it is loaded by normal themes with the the_post
 * function or via the API.
 */
add_filter('wp_api_extensions_pre_post', 'ig_ps_modify_post', 999, 2);
add_action('the_post', 'ig_ps_modify_post');

function ig_generate_stamp( $post_id, $votes, $orgs ) {
    $html = "<style>
.igstamp {
    display: table;
    width: 100%;
    align-content: center;
    padding-top: 5px;
    padding-bottom: 5px;
    color: rgb(88, 88, 88);
}
.igstampcell {
    display: table-cell;
    width: 33%;
}
</style>
<div class='igstamp'>
<div class='igstampcell' style='text-align:left;'>" . __('Verified by', 'ig-page-stamp') . " $orgs</div>\n";
    if ( "on" === $votes ) {
        $upvotes = get_upvotes( $post_id );
        $html .= "<div class='igstampcell' style='text-align: center;'>$upvotes&#215;&#9786;</div>\n";
    }
    $html .= "<div class='igstampcell' style='text-align: right;'>". get_the_modified_time( 'Y-m-d', $post_id ). "</div>
</div>\n";
    return str_replace('\n', '', $html);
}

function get_upvotes( $post_id ) {
    return 5;
}