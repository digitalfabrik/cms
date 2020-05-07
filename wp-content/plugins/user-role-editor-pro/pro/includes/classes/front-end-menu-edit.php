<?php
/**
 * User Role Editor WordPress plugin
 * Front End Menu Access add-on
 * Class URE_Front_End_Menu_Edit - extends WordPress core Walker_Nav_Menu_Edit from  wp-admin/includes/class-walker-nav-menu-edit.php
 *
 * Author: Vladimir Garagulia
 * Author email: support@role-editor.com
 * Author URI: https://www.role-editor.com
 * License: GPL v2+
 */
class URE_Front_End_Menu_Edit extends Walker_Nav_Menu_Edit {

    /**
     * Start the element output.
     *
     * @see Walker_Nav_Menu::start_el()
     *
     * @param string $output Passed by reference. Used to append additional content.
     * @param object $item   Menu item data object.
     * @param int    $depth  Depth of menu item. Used for padding.
     * @param array  $args   Not used.
     * @param int    $id     Not used.
     */
    public function start_el(&$output, $item, $depth = 0, $args = array(), $id = 0) {
        $item_output = '';
        $output .= parent::start_el( $item_output, $item, $depth, $args, $id );
        $regexp = $this->get_regexp();
        $output .= preg_replace(
            // NOTE: Check this regex on major WP version updates!
            $regexp,
            $this->get_custom_fields( $item, $depth, $args ),
            $item_output
        );        
    }
    // end of start_el()
    
    
    /**
     * Get regular expression before which to insert a menu item access by role(s) HTML output 
     * Regular expression is selected according to the current version of WordPress
     * @ToDo: Check this regular expression on major WP version updates!
     * 
     * @global string $wp_version
     * @return string
     */
    private function get_regexp() {
        global $wp_version;
        
        if (version_compare($wp_version, '4.7', '>=')) {
            $reg_exp =  '/(?=<fieldset[^>]+class="[^"]*field-move)/';
        } else {
            $reg_exp =  '/(?=<p[^>]+class="[^"]*field-move)/';
        }
        
        return $reg_exp;        
    }
    // end of get_regexp()
    
    
    /**
     * Get custom fields from the theme and plugins
     * Monitor related discussion and major WordPress core updates: https://core.trac.wordpress.org/ticket/18584
     * @param object $item  Menu item data object.
     * @param int    $depth  Depth of menu item. Used for padding.
     * @param array  $args  Menu item args.
     * @param int    $id    Nav menu ID.
     *
     * @return string Form fields
     */
    private function get_custom_fields( $item, $depth, $args = array(), $id = 0 ) {
        ob_start();

        /**
         * Get menu item custom fields from plugins/themes
         *
         * @param int    $item_id post ID of menu
         * @param object $item  Menu item data object.
         * @param int    $depth  Depth of menu item. Used for padding.
         * @param array  $args  Menu item args.
         *
         * @return string Custom fields
         */
        do_action( 'wp_nav_menu_item_custom_fields', $id, $item, $depth, $args );
        $output = ob_get_clean();
        
        return $output;
    }    
    
}
// end of URE_Front_End_Menu_Edit class
