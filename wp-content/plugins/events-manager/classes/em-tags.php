<?php
class EM_Tags extends EM_Taxonomy_Terms {	
	//Overridable functions
	protected $taxonomy = 'event-tags';
	protected $meta_key = 'event-tags';
	protected $terms_name = 'tags';
	protected $term_class = 'EM_Tag';
	protected $ajax_search_action = 'search_tags';
	
	/**
	 * Creates an EM_Tags instance, currently accepts an EM_Event object (gets all Categories for that event) or array of any EM_Category objects, which can be manipulated in bulk with helper functions.
	 * @param mixed $data
	 * @return null
	 */
	public function __construct( $data = false ){
		$this->taxonomy = EM_TAXONOMY_TAG;
		parent::__construct($data);
	}
	
	/**
	 * Legacy get overload for any use of $EM_Tags->tags
	 * @param string $var_name
	 * @return array|NULL
	 */
	public function __get( $var_name ){
		if( $var_name == 'tags' ){
			return $this->terms;
		}
		return null;
	}
	
	/**
	 * Legacy overload for use of empty($this->tags)
	 * @param string $prop
	 * @return boolean
	 */
	function __isset( $prop ){
		if( $prop == 'tags' ){
			return !empty($this->terms);
		}
		return parent::__isset( $prop );
	}
	
	//Functions we won't need when PHP 5.3 minimum allows for use of LSB
	
	public static function get( $args = array() ){
		self::$instance = new EM_Tags();
		return parent::get($args);
	}

	public static function output( $args = array() ){
		self::$instance = new EM_Tags();
		return parent::output($args);
	}
	
	public static function get_pagination_links($args, $count, $search_action = 'search_tags', $default_args = array()){
		self::$instance = new EM_Tags();
		return parent::get_pagination_links($args, $count, $search_action, $default_args);
	}

	public static function get_post_search($args = array(), $filter = false, $request = array(), $accepted_args = array()){
		self::$instance = new EM_Tags();
		return parent::get_post_search($args, $filter, $request, $accepted_args);
	}
	
	public static function get_default_search( $array_or_defaults = array(), $array = array() ){
		self::$instance = new EM_Tags();
		return parent::get_default_search($defaults,$array);
	}
}