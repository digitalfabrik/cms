<?php
/**
 * This class extends the EM_Taxonomy_Admin and adds category images and colors to the admin area.
 * 
 * Currently, all functions here serve the purpose of getting around lack of late static binding in PHP < 5.3.
 * Eventually when PHP 5.3 is enforced only certain class properties need to be defined for use in the parent class via static:: 
 *
 */
class EM_Categories_Admin extends EM_Taxonomy_Admin{
	
	public static $taxonomy_name = 'EM_TAXONOMY_CATEGORY'; //converted into a constant value during init()
	public static $this_class = 'EM_Categories_Admin'; //needed until 5.3 minimum is enforced for late static binding
	public static $tax_class = 'EM_Category';
	public static $option_name = 'category';
	public static $name_singular = 'category';
	public static $name_plural = 'categories';
	public static $placeholder_image = '#_CATEGORYIMAGE';
	public static $placeholder_color = '#_CATEGORYCOLOR';
	
	public static function init(){
		self::$taxonomy_name = EM_TAXONOMY_CATEGORY;
		self::static_binding();
		parent::init();
	}
	
	public static function form_add(){
		self::static_binding();
		parent::form_add();
	}
	
	public static function form_edit($tag){
		self::static_binding();
		parent::form_edit($tag);
	}
	
	public static function save( $term_id, $tt_id ){
		self::static_binding();
		parent::save( $term_id, $tt_id );
	}
	
	public static function delete( $term_id ){
		self::static_binding();
		parent::delete( $term_id );
	}
	
	/**
	 * Temporary function until WP requires PHP 5.3, so that we can make use of late static binding. 
	 * Until then, all functions needing LST should run this function before calling the parent. If all extending classes do this we shouldn't have a problem.  
	 */
	public static function static_binding(){
		EM_Taxonomy_Admin::$taxonomy_name = self::$taxonomy_name; 
		EM_Taxonomy_Admin::$this_class = self::$this_class;
		EM_Taxonomy_Admin::$tax_class = self::$tax_class;
		EM_Taxonomy_Admin::$option_name = self::$option_name;
		EM_Taxonomy_Admin::$name_singular = self::$name_singular;
		EM_Taxonomy_Admin::$name_plural = self::$name_plural;
		EM_Taxonomy_Admin::$placeholder_image = self::$placeholder_image;
		EM_Taxonomy_Admin::$placeholder_color = self::$placeholder_color;
	}
}
add_action('admin_init',array('EM_Categories_Admin','init'));