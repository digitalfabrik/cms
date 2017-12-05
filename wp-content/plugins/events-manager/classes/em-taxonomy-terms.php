<?php
class EM_Taxonomy_Terms extends EM_Object implements Iterator{

	protected $is_ms_global = false;
	protected $meta_key = 'event-taxonomy';
	protected $taxonomy = 'event-taxonomy';
	protected $terms_name = 'taxonomies';
	protected $term_class = 'EM_Taxonomy';
	
	/**
	 * Blank instance of this class used in the static functions until PHP 5.3 brings us LSB
	 * @var EM_Taxonomy_Terms
	 */
	protected static $instance;
		
	/**
	 * Array of EM_Taxonomy_Term child objects for a specific event
	 * @var array
	 */
	var $terms = array();
	/**
	 * Event ID of this set of taxonomy terms
	 * @var int
	 */
	var $event_id;
	/**
	 * Post ID of this set of taxonomy terms
	 * @var int
	 */
	var $post_id;
	
	/**
	 * Creates an EM_Taxonomy_Terms instance, currently accepts an EM_Event object (gets all Taxonomy Terms for that event) or array of any EM_Taxonomy_Term objects, which can be manipulated in bulk with helper functions.
	 * @param mixed $data
	 * @return null
	 */
	public function __construct( $data = false ){
		global $wpdb;
		if( $this->is_ms_global ) self::ms_global_switch();
		if( is_object($data) && get_class($data) == "EM_Event" && !empty($data->post_id) ){ //Creates a blank taxonomies object if needed
			$this->event_id = $data->event_id;
			$this->post_id = $data->post_id;
			if( EM_MS_GLOBAL && $this->is_ms_global && !is_main_site($data->blog_id) ){
				$term_ids = $wpdb->get_col('SELECT meta_value FROM '.EM_META_TABLE." WHERE object_id='{$this->event_id}' AND meta_key='{$this->meta_key}'");
				foreach($term_ids as $term_id){
					$this->terms[$term_id] = new $this->term_class($term_id);
				}
			}else{
				if( EM_MS_GLOBAL && (get_current_blog_id() !== $data->blog_id || (!$data->blog_id && !is_main_site()) )  ){
					if( !$this->blog_id ) $this->blog_id = get_current_site()->blog_id;
			        switch_to_blog($this->blog_id);
					$results = get_the_terms( $data->post_id, $this->taxonomy );
					restore_current_blog();
			    }else{
					$results = get_the_terms( $data->post_id, $this->taxonomy );
			    }
				if( is_array($results) ){
					foreach($results as $result){
						$this->terms[$result->term_id] = new $this->term_class($result);
					}
				}
			}
		}elseif( is_array($data) && self::array_is_numeric($data) ){
			foreach($data as $term_id){
				$this->terms[$term_id] =  new $this->term_class($term_id);
			}
		}elseif( is_array($data) ){
			foreach( $data as $EM_Taxonomy_Term ){
				if( get_class($EM_Taxonomy_Term) == $this->term_class){
					$this->terms[] = $EM_Taxonomy_Term;
				}
			}
		}
		if( $this->is_ms_global ) self::ms_global_switch_back();
		do_action('em_'.$this->terms_name, $this);
	}
	
	public function get_post(){
		self::ms_global_switch();
		$this->terms = array();
		if(!empty($_POST['event_'.$this->terms_name]) && self::array_is_numeric($_POST['event_'.$this->terms_name])){
			foreach( $_POST['event_'.$this->terms_name] as $term ){
				$this->terms[$term] = new $this->term_class($term);
			}
		}
		self::ms_global_switch_back();
		do_action('em_'. $this->terms_name .'_get_post', $this);
	}
	
	public function save(){
		$term_slugs = array();
		foreach($this->terms as $EM_Taxonomy_Term){
			/* @var $EM_Taxonomy_Term EM_Taxonomy_Term */
			if( !empty($EM_Taxonomy_Term->slug) ) $term_slugs[] = $EM_Taxonomy_Term->slug; //save of taxonomy will soft-fail if slug is empty
		}
		if( count($term_slugs) == 0 && get_option('dbem_default_'.$this->singular) > 0 ){
			$default_term = get_term_by('id',get_option('dbem_default_'.$this->singular), $this->taxonomy);
			if($default_term) $term_slugs[] = $default_term->slug;
		}
		if( is_multisite() && $this->is_ms_global ){
			//In MS Global mode, we also save taxonomy meta information for global lookups
			if( EM_MS_GLOBAL && !empty($this->event_id) ){
				//delete terms from event
				$this->save_index();
			}
			if( !EM_MS_GLOBAL || is_main_site() ){
				wp_set_object_terms($this->post_id, $term_slugs, $this->taxonomy);
			}
		}else{
			wp_set_object_terms($this->post_id, $term_slugs, $this->taxonomy);
		}
		do_action('em_'. $this->terms_name .'_save', $this);
	}
	
	public function save_index(){
		global $wpdb;
		$wpdb->query('DELETE FROM '.EM_META_TABLE." WHERE object_id='{$this->event_id}' AND meta_key='{$this->meta_key}'");
		foreach($this->terms as $EM_Taxonomy_Term){
			$wpdb->insert(EM_META_TABLE, array('meta_value'=>$EM_Taxonomy_Term->term_id,'object_id'=>$this->event_id,'meta_key'=>$this->meta_key));
		}
	}
	
	public function has( $search ){
		if( is_numeric($search) ){
			foreach($this->terms as $EM_Taxonomy_Term){
				if($EM_Taxonomy_Term->term_id == $search) return apply_filters('em_'. $this->terms_name .'_has', true, $search, $this);
			}
		}else{
			foreach($this->terms as $EM_Taxonomy_Term){
				if($EM_Taxonomy_Term->slug == $search || $EM_Taxonomy_Term->name == $search ) return apply_filters('em_'. $this->terms_name .'_has', true, $search, $this);
			}			
		}
		return apply_filters('em_'. $this->terms_name .'_has', false, $search, $this);
	}
	
	public function get_first(){
		foreach($this->terms as $EM_Taxonomy_Term){
			return $EM_Taxonomy_Term;
		}
		return false;
	}
	
	public function get_ids(){
		$ids = array();
		foreach($this->terms as $EM_Taxonomy_Term){
			if( !empty($EM_Taxonomy_Term->term_id) ){
				$ids[] = $EM_Taxonomy_Term->term_id;
			}
		}
		return $ids;
	}
	
	/**
	 * Gets the event for this object, or a blank event if none exists
	 * @return EM_Event
	 */
	public function get_event(){
		if( is_numeric($this->event_id) ){
			return em_get_event($this->event_id);
		}else{
			return new EM_Event();
		}
	}
		
	public static function get( $args = array() ) {		
		//Quick version, we can accept an array of IDs, which is easy to retrieve
		self::ms_global_switch();
		if( self::array_is_numeric($args) ){ //Array of numbers, assume they are taxonomy IDs to retreive
			$term_args = self::get_default_search( array('include' => $args ));
			$results = get_terms( $term_args );
		}else{
			//We assume it's either an empty array or array of search arguments to merge with defaults
			$term_args = self::get_default_search($args);		
			$results = get_terms( $term_args );		
		
			//If we want results directly in an array, why not have a shortcut here? We don't use this in code, so if you're using it and filter the em_{taxonomy}_get hook, you may want to do this one too.
			if( !empty($args['array']) ){
				return apply_filters('em_'. self::$instance->terms_name .'_get_array', $results, $args);
			}
		}
		//Make returned results EM_Taxonomy_Term child objects
		$results = (is_array($results)) ? $results:array();
		$terms = array();
		foreach ( $results as $term ){
			$terms[$term->term_id] = new self::$instance->term_class($term);
		}
		self::ms_global_switch_back();	
		return apply_filters('em_'. self::$instance->terms_name .'_get', $terms, $args);
	}

	public static function output( $args = array() ){
		$EM_Taxonomy_Term_old = !empty($GLOBALS[self::$instance->term_class]) ? $GLOBALS[self::$instance->term_class] : false; //When looping, we can replace EM_Taxonomy_Term global with the current event in the loop
		//get page number if passed on by request (still needs pagination enabled to have effect)
		$page_queryvar = !empty($args['page_queryvar']) ? $args['page_queryvar'] : 'pno';
		if( !array_key_exists('page',$args) && !empty($args['pagination']) && !empty($_REQUEST[$page_queryvar]) && is_numeric($_REQUEST[$page_queryvar]) ){
			$page = $args['page'] = $_REQUEST[$page_queryvar];
		}
		//Can be either an array for the get search or an array of EM_Taxonomy_Term objects
		if( is_object(current($args)) && get_class((current($args))) == self::$instance->term_class ){
			$func_args = func_get_args();
			$terms = $func_args[0];
			$args = (!empty($func_args[1])) ? $func_args[1] : array();
			$args = apply_filters('em_'. self::$instance->terms_name .'_output_args', self::get_default_search($args), $terms);
			$limit = ( !empty($args['limit']) && is_numeric($args['limit']) ) ? $args['limit']:false;
			$offset = ( !empty($args['offset']) && is_numeric($args['offset']) ) ? $args['offset']:0;
			$page = ( !empty($args['page']) && is_numeric($args['page']) ) ? $args['page']:1;
		}else{
			$args = apply_filters('em_'. self::$instance->terms_name .'_output_args', self::get_default_search($args) );
			$limit = ( !empty($args['limit']) && is_numeric($args['limit']) ) ? $args['limit']:false;
			$offset = ( !empty($args['offset']) && is_numeric($args['offset']) ) ? $args['offset']:0;
			$page = ( !empty($args['page']) && is_numeric($args['page']) ) ? $args['page']:1;
			$args['limit'] = $args['offset'] = $args['page'] = false; //we count overall terms here
			$terms = self::get( $args );
			$args['limit'] = $limit;
			$args['offset'] = $offset;
			$args['page'] = $page;
		}
		//What format shall we output this to, or use default
		$format = ( $args['format'] == '' ) ? get_option( 'dbem_'. self::$instance->terms_name .'_list_item_format' ) : $args['format'] ;
		
		$output = "";
		$terms_count = count($terms);
		$terms = apply_filters('em_'. self::$instance->terms_name .'_output_'. self::$instance->terms_name, $terms);
		if ( count($terms) > 0 ) {
			$term_count = 0;
			$terms_shown = 0;
			foreach ( $terms as $EM_Taxonomy_Term ) {
				$GLOBALS[self::$instance->term_class] = $EM_Taxonomy_Term;
				if( ($terms_shown < $limit || empty($limit)) && ($term_count >= $offset || $offset === 0) ){
					$output .= $EM_Taxonomy_Term->output($format);
					$terms_shown++;
				}
				$term_count++;
			}
			//Add headers and footers to output
			if( $format == get_option( 'dbem_'. self::$instance->terms_name .'_list_item_format' ) ){
			    //we're using the default format, so if a custom format header or footer is supplied, we can override it, if not use the default
			    $format_header = empty($args['format_header']) ? get_option('dbem_'. self::$instance->terms_name .'_list_item_format_header') : $args['format_header'];
			    $format_footer = empty($args['format_footer']) ? get_option('dbem_'. self::$instance->terms_name .'_list_item_format_footer') : $args['format_footer'];
			}else{
			    //we're using a custom format, so if a header or footer isn't specifically supplied we assume it's blank
			    $format_header = !empty($args['format_header']) ? $args['format_header'] : '' ;
			    $format_footer = !empty($args['format_footer']) ? $args['format_footer'] : '' ;
			}
			$output =  $format_header .  $output . $format_footer;
			//Pagination (if needed/requested)
			if( !empty($args['pagination']) && !empty($limit) && $terms_count >= $limit ){
				//Show the pagination links (unless there's less than 10 events, or the custom limit)
				$output .= self::get_pagination_links($args, $terms_count);
			}
		} else {
			$output = get_option ( 'dbem_no_'. self::$instance->terms_name .'_message' );
		}
		//FIXME check if reference is ok when restoring object, due to changes in php5 v 4
		if( !empty($EM_Taxonomy_Term_old ) ) $GLOBALS[self::$instance->term_class] = $EM_Taxonomy_Term_old;
		return apply_filters('em_'. self::$instance->terms_name .'_output', $output, $terms, $args);		
	}
	
	public static function get_pagination_links($args, $count, $search_action = 'search_cats', $default_args = array()){
		//get default args if we're in a search, supply to parent since we can't depend on late static binding until WP requires PHP 5.3 or later
		if( empty($default_args) && (!empty($args['ajax']) || !empty($_REQUEST['action']) && $_REQUEST['action'] == $search_action) ){
			$default_args = self::get_default_search();
			$default_args['limit'] = get_option('dbem_'. self::$instance->terms_name .'_default_limit');
		}
		return parent::get_pagination_links($args, $count, $search_action, $default_args);
	}

	public static function get_post_search($args = array(), $filter = false, $request = array(), $accepted_args = array()){
		//supply $accepted_args to parent argument since we can't depend on late static binding until WP requires PHP 5.3 or later
		$accepted_args = !empty($accepted_args) ? $accepted_args : array_keys(self::get_default_search());
		return apply_filters('em_'. self::$instance->terms_name .'_get_post_search', parent::get_post_search($args, $filter, $request, $accepted_args));
	}
	
	/* 
	 * Adds custom calendar search defaults
	 * @param array $array_or_defaults may be the array to override defaults
	 * @param array $array
	 * @return array
	 * @uses EM_Object#get_default_search()
	 */
	public static function get_default_search( $array_or_defaults = array(), $array = array() ){
		$defaults = array(
			//added from get_terms, so they don't get filtered out
			'orderby' => get_option('dbem_'. self::$instance->terms_name .'_default_orderby'), 'order' => get_option('dbem_'. self::$instance->terms_name .'_default_order'),
			'hide_empty' => false, 'exclude' => array(), 'exclude_tree' => array(), 'include' => array(),
			'number' => '', 'fields' => 'all', 'slug' => '', 'parent' => '',
			'hierarchical' => true, 'child_of' => 0, 'get' => '', 'name__like' => '',
			'pad_counts' => false, 'offset' => '', 'search' => '', 'cache_domain' => 'core'		
		);
		//sort out whether defaults were supplied or just the array of search values
		if( empty($array) ){
			$array = $array_or_defaults;
		}else{
			$defaults = array_merge($defaults, $array_or_defaults);
		}
		$return = parent::get_default_search($defaults,$array);
		$return['taxonomy'] = self::$instance->taxonomy; //this shouldn't change regardless
		if( is_array($return['orderby']) ) $return['orderby'] = implode(',', $return['orderby']); //clean up for WP functions
		return apply_filters('em_'. self::$instance->terms_name .'_get_default_search', $return, $array, $defaults);
	}
	
	//Iterator Implementation
    public function rewind(){
        reset($this->terms);
    }  
    public function current(){
        $var = current($this->terms);
        return $var;
    }  
    public function key(){
        $var = key($this->terms);
        return $var;
    }  
    public function next(){
        $var = next($this->terms);
        return $var;
    }  
    public function valid(){
        $key = key($this->terms);
        $var = ($key !== NULL && $key !== FALSE);
        return $var;
    }
}