<?php
/*
 * Intergration of 'Edit restrictions' module with "FooGallery" (https://wordpress.org/plugins/foogallery/) plugin
 * Project: User Role Editor Pro WordPress plugin
 * Author: Vladimir Garagulya
 * email: support@role-editor.com
 * 
 */

class URE_FooGallery {
    
    private $user = null;
    
    public function __construct( $pea_user ) {
        
        $this->user = $pea_user;
        
        add_filter( 'foogallery_album_exlcuded_galleries', array( $this, 'exclude_galleries'), 10, 1 );
        
    }
    // end of __construct()
    
    
    private function is_restricted( $restriction_type, $posts_list, $gallery_id ) {
        
        $restrict_it = false;
        if ( $restriction_type==1 ) { // Allow: not edit others
            if ( !in_array( $gallery_id, $posts_list ) ) {    // not edit others
                $restrict_it = true;
            }
        } else {    // Prohibit: Not edit these
            if ( in_array( $gallery_id, $posts_list ) ) {    // not edit these
                $restrict_it = true;
            }
        }
                
        return $restrict_it; 
    }
    // end of restrict_gallery()

    
    public function exclude_galleries( $excluded ) {
        
        // do not limit user with Administrator role
        if ( !$this->user->is_restriction_applicable() ) {
            return $excluded;
        }
        
        $posts_list = $this->user->get_posts_list();
        if ( count( $posts_list )==0 ) {
            return $excluded;
        }
        
        $all_galleries = foogallery_get_all_galleries( $excluded );
        if ( $excluded===false ) {
            $excluded = array();
        }
        $restriction_type = $this->user->get_restriction_type();
        foreach( $all_galleries as $gallery ) {
            if ( in_array( $gallery->ID, $excluded ) ) {
                continue;
            } 
            if ( $this->is_restricted( $restriction_type, $posts_list, $gallery->ID ) ) {
                $excluded[] = $gallery->ID;
            }
        }
        
        return $excluded;
    }
    // end of exclude_galleries()
    
}
// end of class URE_FooGallery
