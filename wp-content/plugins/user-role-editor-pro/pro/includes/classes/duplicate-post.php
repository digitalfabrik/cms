<?php
/*
 * Intergration of 'Edit restrictions' module with "Duplicate Post" plugin:
 * User restricted to post to the certain categories only, can clone existing posts from other categories 
 * with auto assigning the 1st allowed category to a new created post
 * Project: User Role Editor Pro WordPress plugin
 * Author: Vladimir Garagulia
 * email: support@role-editor.com
 * 
 */


class URE_Duplicate_Post {        
    
    
    public static function restore_dp_post_copy_taxonomies() {
        
        add_action('dp_duplicate_post', 'duplicate_post_copy_post_taxonomies', 50, 2);
        add_action('dp_duplicate_page', 'duplicate_post_copy_post_taxonomies', 50, 2);
        if (has_action('duplicate_post_post_copy', array(__CLASS__, 'restore_dp_post_copy_taxonomies'))) {
            remove_action('duplicate_post_post_copy', array(__CLASS__, 'restore_dp_post_copy_taxonomies'));
        }        
    }
    // end of restore_db_post_copy_taxonomies()
    
    
    /*
     * Duplicate Post (DP) plugin removes all terms from a cloned post, including an allowed term, assigned to a new post by auto_assign_term() to make it editable for its author
     * This function removes 'DP' actions to prevent this behaviour.
     */
    public static function prevent_term_remove($new_id, $post) {
        
        $current_user_id = get_current_user_id();        
        if ($post->post_author==$current_user_id) {    // In assumption that "Duplicate Post" is allowed to copy terms from the source post
            return;
        }
        
        remove_action('dp_duplicate_post', 'duplicate_post_copy_post_taxonomies', 50, 2);
        remove_action('dp_duplicate_page', 'duplicate_post_copy_post_taxonomies', 50, 2);
        if (!has_action('duplicate_post_post_copy', array(__CLASS__, 'restore_dp_post_copy_taxonomies'))) {
            add_action('duplicate_post_post_copy', array(__CLASS__, 'restore_dp_post_copy_taxonomies'));
        }
    }
    // end of prevent_term_remove()        
}
// end of URE_Duplicate_Post class