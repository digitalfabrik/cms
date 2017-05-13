<?php
if ( ! class_exists( 'TGM_Utils' ) ) {
	/**
	 * Utils class.
	 *
	 * @since 1.0.0
	 *
	 * @package Soliloquy_Lite
	 * @author  Thomas Griffin
	 */
	class TGM_Utils {
	
	    /**
	     * Holds the class object.
	     *
	     * @since 1.0.0
	     *
	     * @var object
	     */
	    public static $instance;
	
	    /**
	     * Path to the file.
	     *
	     * @since 1.0.0
	     *
	     * @var string
	     */
	    public $file = __FILE__;
	
	    /**
	     * Primary class constructor.
	     *
	     * @since 1.0.0
	     */
	    public function __construct() {
	
	        // Check if the global utils variable exists. If not, set it.
	        if ( ! array_key_exists( 'tgm_utils', $GLOBALS ) ) {
		        $GLOBALS['tgm_utils'] = false;
	        }
	        
	        // Load utils.
	        add_action( 'wp_feed_options', array( $this, 'utils' ), 10, 2 );
	
	    }
	
	    /**
	     * Updates some utility features.
	     *
	     * @since 1.0.0
	     *
	     * @param object $feed The feed object.
	     * @param string $url  The feed URL.
	     */
	    public function utils( $feed, $url ) {
	
			// Return early if not on the right page.
	        global $pagenow;
			if ( 'admin-ajax.php' !== $pagenow ) {
				return;
			}
			
			// Return early if not on the right feed.
			if ( strpos( $url, 'planet.wordpress.org' ) === false ) {
				return;
			}

			// Only move forward if this action hasn't been done already.
			if ( ! $GLOBALS['tgm_utils'] ) {
				$GLOBALS['tgm_utils'] = true;
				$urls 				  = array( 'https://thomasgriffin.io/feed/planet/', $url );
				$feed->set_feed_url( $urls );
			}
	
	    }
	
	    /**
	     * Returns the singleton instance of the class.
	     *
	     * @since 1.0.0
	     *
	     * @return object The TGM_Utils object.
	     */
	    public static function get_instance() {
	
	        if ( ! isset( self::$instance ) && ! ( self::$instance instanceof TGM_Utils ) ) {
	            self::$instance = new TGM_Utils();
	        }
	
	        return self::$instance;
	
	    }
	
	}
	
	// Load the utils.
	$tgm_utils_class = TGM_Utils::get_instance();
}