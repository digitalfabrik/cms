<?php
class EM_Categories extends EM_Taxonomy_Terms {
	
	//Overridable functions
	protected $is_ms_global = true;
	protected $taxonomy = 'event-categories';
	protected $meta_key = 'event-category';
	protected $terms_name = 'categories';
	protected $term_class = 'EM_Category';
	protected $ajax_search_action = 'search_cats';
	
	/**
	 * Creates an EM_Categories instance, currently accepts an EM_Event object (gets all Categories for that event) or array of any EM_Category objects, which can be manipulated in bulk with helper functions.
	 * @param mixed $data
	 * @return null
	 */
	function __construct( $data = false ){
		$this->taxonomy = EM_TAXONOMY_CATEGORY;
		parent::__construct($data);
	}
		
	/**
	 * Legacy get overload for any use of $EM_Categories->tags
	 * @param string $var_name
	 * @return array|NULL
	 */
	function __get( $var_name ){
		if( $var_name == 'categories' ){
			return $this->terms;
		}
		return null;
	}
	
	/**
	 * Legacy overload for use of empty($this->categories)
	 * @param string $prop
	 * @return boolean
	 */
	function __isset( $prop ){
		if( $prop == 'categories' ){
			return !empty($this->terms);
		}
		return parent::__isset( $prop );
	}
	
	//Functions we won't need when PHP 5.3 minimum allows for use of LSB
	
	public static function get( $args = array() ){
		self::$instance = new EM_Categories();
		return parent::get($args);
	}

	public static function output( $args = array() ){
		self::$instance = new EM_Categories();
		return parent::output($args);
	}
	
	public static function get_pagination_links($args, $count, $search_action = 'search_cats', $default_args = array()){
		self::$instance = new EM_Categories();
		return parent::get_pagination_links($args, $count, $search_action, $default_args);
	}

	public static function get_post_search($args = array(), $filter = false, $request = array(), $accepted_args = array()){
		self::$instance = new EM_Categories();
		return parent::get_post_search($args, $filter, $request, $accepted_args);
	}
	
	public static function get_default_search( $array_or_defaults = array(), $array = array() ){
		self::$instance = new EM_Categories();
		return parent::get_default_search($defaults,$array);
	}
}