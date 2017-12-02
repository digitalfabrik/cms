<?php
class EM_Categories_Frontend extends EM_Taxonomy_Frontend {
	
	public static $taxonomy_name = 'event-category'; //converted into a constant value during init()
	public static $this_class = 'EM_Categories_Frontend'; //needed until 5.3 minimum is enforced for late static binding
	public static $tax_class = 'EM_Category';
	public static $option_name = 'category';
	public static $option_name_plural = 'categories';
	
	public static function init(){
		self::$taxonomy_name = EM_TAXONOMY_CATEGORY; //awaiting LST in PHP 5.3
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
class EM_Category_Taxonomy extends EM_Categories_Frontend {} //backwards compatibility

EM_Categories_Frontend::init();

//Walker classes allowing for hierarchical display of categories

/**
 * Create an array of Categories. Copied from Walker_CategoryDropdown, but makes it possible for the selected argument to be an array.
 *
 * @package WordPress
 * @since 2.1.0
 * @uses Walker
 */
class EM_Walker_Category extends Walker {
	/**
	 * @see Walker::$tree_type
	 * @since 2.1.0
	 * @var string
	 */
	var $tree_type = 'event-category';

	/**
	 * @see Walker::$db_fields
	 * @since 2.1.0
	 * @todo Decouple this
	 * @var array
	 */
	var $db_fields = array ('parent' => 'parent', 'id' => 'term_id');

	function __construct(){ 
		$tree_type = EM_TAXONOMY_CATEGORY;
	}
	
	/**
	 * @see Walker::start_el()
	 */
	function start_el( &$output, $object, $depth = 0, $args = array(), $current_object_id = 0 ) {
		$pad = str_repeat('&nbsp;', $depth * 3);
		$cat_name = $object->name;
		$name = !empty($args['name']) ? $args['name']:'event_categories[]';
		$output .= !empty($args['before']) ? $args['after']:'';
		$output .= $pad."<input type=\"checkbox\" name=\"$name\" class=\"level-$depth\" value=\"".$object->term_id."\"";
		if ( (is_array($args['selected']) && in_array($object->term_id, $args['selected'])) || ($object->term_id == $args['selected']) )
			$output .= ' checked="checked"';
		$output .= ' /> ';
		$output .= $cat_name;
		$output .= !empty($args['after']) ? $args['after']:'<br />';
	}
}

/**
 * Create an array of Categories. Copied from Walker_CategoryDropdown, but makes it possible for the selected argument to be an array.
 *
 * @package WordPress
 * @since 2.1.0
 * @uses Walker
 */
class EM_Walker_CategoryMultiselect extends EM_Walker_Category {
	/**
	 * @see Walker::start_el()
	 */
	function start_el( &$output, $object, $depth = 0, $args = array(), $current_object_id = 0 ) {
		$pad = str_repeat('&nbsp;', $depth * 3);
		$cat_name = $object->name;
		$output .= "\t<option class=\"level-$depth\" value=\"".$object->term_id."\"";
		if ( (is_array($args['selected']) && in_array($object->term_id, $args['selected'])) || ($object->term_id == $args['selected']) )
			$output .= ' selected="selected"';
		$output .= '>';
		$output .= $pad.$cat_name;
		$output .= "</option>\n";
	}
}