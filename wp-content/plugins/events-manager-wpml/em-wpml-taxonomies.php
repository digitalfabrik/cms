<?php
/**
 * EM does some extreme stuff to the main WP_Query object (global $wp_query) in order to show taxonomy pages as a normal WP page with EM formatting. 
 * WPML messes around with the main WP_Query object too, resetting it whenever generating a language switcher for example.
 * This means that when showing a event category or tag page, WPML resets all the work we did to tweak the WP_Query object.
 * The below code 'sets things right' so WPML can't reset these specific page instances.
 * If you don't like this, create taxonomy-event-tag.php and taxonomy-event-category.php files in your theme and load your taxonomy info that way, these hooks will be ignored.
 */
class EM_WPML_TAXONOMIES {
    
    public static function init(){
        add_action('em_category_taxonomy_template', 'EM_WPML_TAXONOMIES::category_taxonomy_workaround');
        add_action('em_tag_taxonomy_template', 'EM_WPML_TAXONOMIES::tag_taxonomy_workaround');
    }
    
    /**
     * Hooks in after EM_Category_Taxonomy::template() and makes sure WPML doesn't reset $wp_query and wipe the work EM just did.  
     */        
    public static function category_taxonomy_workaround(){
        global $EM_Category; /* @var $EM_Category EM_Category */
        if( defined('EM_WPML_TAXONOMIES_TWEAKED') && EM_WPML_TAXONOMIES_TWEAKED ) return; //prevent endless loop
        
        if( EM_Events::count(array('category'=>$EM_Category->term_id)) == 0 ){
            self::preset_query( $EM_Category->output(get_option('dbem_category_page_title_format')) );
        }
        
        define('EM_WPML_TAXONOMIES_TWEAKED', true);
        wp_reset_query();
        add_filter('the_post', 'EM_Category_Taxonomy::template',1);
    }
    
    /**
     * Hooks in after EM_Tag_Taxonomy::template() and makes sure WPML doesn't reset $wp_query and wipe the work EM just did.  
     */
    public static function tag_taxonomy_workaround(){
        global $EM_Tag;
        if( defined('EM_WPML_TAXONOMIES_TWEAKED') && EM_WPML_TAXONOMIES_TWEAKED ) return; //prevent endless loop
        
        if( EM_Events::count(array('tag'=>$EM_Tag->term_id)) == 0 ){
            self::preset_query( $EM_Tag->output(get_option('dbem_tag_page_title_format')) );
        }
        
        define('EM_WPML_TAXONOMIES_TWEAKED', true);
        wp_reset_query();
        add_filter('the_post', 'EM_Tag_Taxonomy::template',1);
    }
    
    /**
     * Prep the $GLOBALS['wp_the_query'] with one found post, so that WPML doesn't tell WP to show a "nothing found" page when resetting WP_Query
     * @param string $page_title title for the page, which would be used in instances where no tag/category page is defined and there are non results for this specific taxonomy.
     */
    public static function preset_query( $page_title = '' ){
        $wp_query = $GLOBALS['wp_the_query']; /* @var $wp_query WP_Query */
        $wp_query->found_posts = 1;
        $wp_query->posts = array();
        $wp_query->posts[0] = new stdClass();
        $wp_query->posts[0]->post_title = $wp_query->queried_object->post_title = $page_title;
        $post_array = array('ID', 'post_author', 'post_date','post_date_gmt','post_content','post_excerpt','post_status','comment_status','ping_status','post_password','post_name','to_ping','pinged','post_modified','post_modified_gmt','post_content_filtered','post_parent','guid','menu_order','post_type','post_mime_type','comment_count','filter');
        foreach($post_array as $post_array_item) $wp_query->posts[0]->$post_array_item = '';
        $wp_query->post = $wp_query->posts[0];
        $GLOBALS['wp_the_query'] = $wp_query;
    }
    
}
EM_WPML_TAXONOMIES::init();