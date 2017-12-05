<?php
class EM_Tags_Frontend extends EM_Taxonomy_Frontend {
	
	public static $taxonomy_name = 'event-tag'; //converted into a constant value during init()
	public static $this_class = 'EM_Tags_Frontend'; //needed until 5.3 minimum is enforced for late static binding
	public static $tax_class = 'EM_Tag';
	public static $option_name = 'tag';
	public static $option_name_plural = 'tags';
	
	public static function init(){
		self::$taxonomy_name = EM_TAXONOMY_TAG; //awaiting LSB in PHP 5.3
		self::static_binding();
		parent::init();
	}
	
	//These following functions can be removed when PHP 5.3 is minimum and LSB is available
	
	public static function template($template = ''){
		self::static_binding();
		return parent::template($template);
	}
	
	public static function the_content($content){
		self::static_binding();
		return parent::the_content($content);
	}
	
	public static function parse_query( $wp_query ){
		//we do some double-checking here to prevent running self::static_binding() during the self::template() function when WP_Query is called.
	    if( !$wp_query->is_main_query() ) return;
		if( $wp_query->is_tax(self::$taxonomy_name) || !empty($wp_query->{'em_'.self::$option_name.'_id'}) ){
			self::static_binding();
			return parent::parse_query( $wp_query );
		}
	}
	
	public static function wpseo_breadcrumb_links( $links ){
		self::static_binding();
		return parent::wpseo_breadcrumb_links( $links );
	}
	
	/**
	 * Temporary function until WP requires PHP 5.3, so that we can make use of late static binding. 
	 * Until then, all functions needing LST should run this function before calling the parent. If all extending classes do this we shouldn't have a problem.  
	 */
	public static function static_binding(){
		EM_Taxonomy_Frontend::$taxonomy_name = self::$taxonomy_name; 
		EM_Taxonomy_Frontend::$this_class = self::$this_class;
		EM_Taxonomy_Frontend::$tax_class = self::$tax_class;
		EM_Taxonomy_Frontend::$option_name = self::$option_name;
		EM_Taxonomy_Frontend::$option_name_plural = self::$option_name_plural;
	}
}
class EM_Tag_Taxonomy extends EM_Tags_Frontend {} //backwards compatibility

EM_Tags_Frontend::init();